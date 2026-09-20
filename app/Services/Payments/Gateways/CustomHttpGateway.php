<?php

namespace App\Services\Payments\Gateways;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Exceptions\PaymentGatewayException;
use App\Models\Payment;
use App\Support\Money;
use App\Support\Payments\PaymentInitiation;
use App\Support\Payments\PaymentStatusUpdate;
use App\Support\Payments\RefundResult;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

/**
 * Talks to the operator-supplied gateway over HTTP.
 *
 * Field names, paths and status strings live in config/payments.php so the
 * remote contract can change without touching this class.
 */
class CustomHttpGateway implements PaymentGateway
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config) {}

    public function initiate(Payment $payment): PaymentInitiation
    {
        $response = $this->request('post', $this->endpoint('create_payment'), [
            'order_number' => $payment->order->order_number,
            'amount' => $payment->money()->amount,
            'currency' => $payment->currency,
            'email' => $payment->order->email,
            'callback_url' => route('checkout.callback', $payment->order),
            'webhook_url' => url('/webhooks/payments/custom'),
        ]);

        return new PaymentInitiation(
            reference: $this->mapped($response, 'reference'),
            status: $this->mapStatus($this->mapped($response, 'status') ?? 'pending'),
            redirectUrl: $this->mapped($response, 'redirect_url'),
            payload: $response,
        );
    }

    public function fetch(Payment $payment): PaymentStatusUpdate
    {
        $path = str_replace('{reference}', (string) $payment->gateway_reference, $this->endpoint('fetch_payment'));
        $response = $this->request('get', $path);

        return new PaymentStatusUpdate(
            status: $this->mapStatus($this->mapped($response, 'status') ?? 'pending'),
            reference: $this->mapped($response, 'reference') ?? $payment->gateway_reference,
            payload: $response,
        );
    }

    public function refund(Payment $payment, Money $amount, ?string $reason = null): RefundResult
    {
        $path = str_replace('{reference}', (string) $payment->gateway_reference, $this->endpoint('refund_payment'));
        $response = $this->request('post', $path, [
            'amount' => $amount->amount,
            'reason' => $reason,
        ]);

        $remoteStatus = strtolower((string) ($this->mapped($response, 'status') ?? 'succeeded'));

        return new RefundResult(
            status: in_array($remoteStatus, ['failed', 'error', 'declined'], true)
                ? RefundStatus::Failed
                : RefundStatus::Succeeded,
            reference: $this->mapped($response, 'reference'),
            payload: $response,
        );
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $body = []): array
    {
        $base = rtrim((string) ($this->config['base_url'] ?? ''), '/');

        if ($base === '') {
            throw PaymentGatewayException::configuration('PAYMENT_GATEWAY_BASE_URL is not configured.');
        }

        $url = $base.$path;
        $timeout = (int) ($this->config['timeout'] ?? 30);
        $retries = (int) ($this->config['retries'] ?? 2);

        try {
            $pending = Http::timeout($timeout)
                ->retry($retries, 200)
                ->acceptJson();

            $pending = $this->authenticate($pending);

            $response = $method === 'get'
                ? $pending->get($url, $body)
                : $pending->post($url, $body);

            $response->throw();
        } catch (HttpClientException $exception) {
            throw PaymentGatewayException::requestFailed($exception->getMessage());
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw PaymentGatewayException::unexpectedResponse();
        }

        return $json;
    }

    /**
     * @param  PendingRequest  $pending
     */
    private function authenticate(mixed $pending): mixed
    {
        $key = (string) ($this->config['auth']['api_key'] ?? $this->config['api_key'] ?? '');

        if ($key === '') {
            throw PaymentGatewayException::configuration('PAYMENT_GATEWAY_API_KEY is not configured.');
        }

        $scheme = $this->config['auth']['scheme'] ?? 'header';
        $header = $this->config['auth']['header'] ?? 'X-Api-Key';

        if ($scheme === 'bearer') {
            return $pending->withToken($key);
        }

        return $pending->withHeaders([$header => $key]);
    }

    private function endpoint(string $name): string
    {
        $path = (string) ($this->config['endpoints'][$name] ?? '');

        if ($path === '' || $path === '/') {
            if ($name === 'create_payment') {
                return '';
            }

            throw PaymentGatewayException::configuration("Payment gateway endpoint [{$name}] is not configured.");
        }

        return str_starts_with($path, '/') ? $path : '/'.$path;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function mapped(array $payload, string $field): ?string
    {
        $key = (string) ($this->config['response_map'][$field] ?? $field);
        $value = Arr::get($payload, $key);

        return $value === null ? null : (string) $value;
    }

    private function mapStatus(string $remote): PaymentStatus
    {
        $map = $this->config['status_map'] ?? [];
        $canonical = $map[strtolower($remote)] ?? strtolower($remote);

        return PaymentStatus::tryFrom($canonical) ?? PaymentStatus::Pending;
    }
}
