<?php

namespace App\Services\Payments\Gateways;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Exceptions\PaymentGatewayException;
use App\Models\Payment;
use App\Services\Payments\RazorpaySettings;
use App\Support\Money;
use App\Support\Payments\PaymentInitiation;
use App\Support\Payments\PaymentStatusUpdate;
use App\Support\Payments\RefundResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Razorpay Standard Checkout.
 *
 * initiate() creates a Razorpay order and sends the customer to our own pay
 * page, which opens Razorpay's checkout modal. Settlement arrives through the
 * signed webhook; nothing here marks an order paid.
 *
 * The mode and key id a payment was created with are kept in request_payload,
 * so refunds keep using the right account after staff switch test/live.
 */
class RazorpayGateway implements PaymentGateway
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly RazorpaySettings $settings,
        private readonly array $config,
    ) {}

    public function initiate(Payment $payment): PaymentInitiation
    {
        $mode = $this->settings->mode();
        $order = $payment->order;

        $body = [
            'amount' => $payment->money()->amount,
            'currency' => $payment->currency,
            'receipt' => Str::limit($order->order_number, 40, ''),
            'notes' => [
                'order_number' => $order->order_number,
                'payment_id' => (string) $payment->getKey(),
            ],
        ];

        $response = $this->request($mode, 'post', '/orders', $body);
        $reference = $response['id'] ?? null;

        if (! is_string($reference) || $reference === '') {
            throw PaymentGatewayException::unexpectedResponse();
        }

        return new PaymentInitiation(
            reference: $reference,
            status: PaymentStatus::Pending,
            redirectUrl: route('checkout.pay', $order),
            payload: $response,
            requestPayload: [
                'mode' => $mode,
                'key_id' => $this->settings->keyId($mode),
                'body' => $body,
            ],
        );
    }

    public function fetch(Payment $payment): PaymentStatusUpdate
    {
        $response = $this->request($this->modeFor($payment), 'get', '/orders/'.$payment->gateway_reference);

        return new PaymentStatusUpdate(
            status: ($response['status'] ?? null) === 'paid' ? PaymentStatus::Paid : PaymentStatus::Pending,
            reference: $payment->gateway_reference,
            payload: $response,
        );
    }

    public function refund(Payment $payment, Money $amount, ?string $reason = null): RefundResult
    {
        $mode = $this->modeFor($payment);
        $capturedPaymentId = $this->capturedPaymentId($mode, (string) $payment->gateway_reference);

        $response = $this->request($mode, 'post', "/payments/{$capturedPaymentId}/refund", [
            'amount' => $amount->amount,
            'notes' => array_filter(['reason' => $reason]),
        ]);

        return new RefundResult(
            status: ($response['status'] ?? null) === 'failed' ? RefundStatus::Failed : RefundStatus::Succeeded,
            reference: isset($response['id']) ? (string) $response['id'] : null,
            payload: $response,
        );
    }

    /**
     * The key id the customer's browser must use to open checkout for this
     * payment's Razorpay order.
     */
    public function checkoutKeyId(Payment $payment): string
    {
        return (string) ($payment->request_payload['key_id'] ?? $this->settings->keyId($this->modeFor($payment)));
    }

    public function modeFor(Payment $payment): string
    {
        $mode = $payment->request_payload['mode'] ?? null;

        return in_array($mode, RazorpaySettings::Modes, true) ? $mode : $this->settings->mode();
    }

    /**
     * A Razorpay order can collect several attempts; refunds target the one
     * that was captured.
     */
    private function capturedPaymentId(string $mode, string $orderId): string
    {
        $response = $this->request($mode, 'get', "/orders/{$orderId}/payments");

        foreach ((array) ($response['items'] ?? []) as $attempt) {
            if (is_array($attempt) && ($attempt['status'] ?? null) === 'captured' && isset($attempt['id'])) {
                return (string) $attempt['id'];
            }
        }

        throw PaymentGatewayException::requestFailed("No captured Razorpay payment found for order {$orderId}.");
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function request(string $mode, string $method, string $path, array $body = []): array
    {
        $keyId = $this->settings->keyId($mode);
        $keySecret = $this->settings->keySecret($mode);

        if ($keyId === '' || $keySecret === null) {
            throw PaymentGatewayException::configuration("Razorpay {$mode} keys are not configured.");
        }

        $url = rtrim((string) ($this->config['base_url'] ?? 'https://api.razorpay.com/v1'), '/').$path;

        try {
            $pending = Http::withBasicAuth($keyId, $keySecret)
                ->timeout((int) ($this->config['timeout'] ?? 30))
                ->retry(
                    (int) ($this->config['retries'] ?? 2),
                    200,
                    fn (\Throwable $exception): bool => $exception instanceof ConnectionException,
                    throw: false,
                )
                ->acceptJson()
                ->asJson();

            $response = $method === 'get' ? $pending->get($url) : $pending->post($url, $body);

            if ($response->failed()) {
                $message = (string) ($response->json('error.description') ?? 'HTTP '.$response->status());

                throw PaymentGatewayException::requestFailed($message);
            }
        } catch (HttpClientException $exception) {
            throw PaymentGatewayException::requestFailed($exception->getMessage());
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw PaymentGatewayException::unexpectedResponse();
        }

        return $json;
    }
}
