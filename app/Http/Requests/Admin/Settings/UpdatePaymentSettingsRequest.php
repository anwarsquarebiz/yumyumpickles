<?php

namespace App\Http\Requests\Admin\Settings;

use App\Services\Payments\RazorpaySettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePaymentSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cod_enabled' => ['boolean'],
            'razorpay.enabled' => ['boolean'],
            'razorpay.mode' => ['required', Rule::in(RazorpaySettings::Modes)],
            'razorpay.test.key_id' => ['nullable', 'string', 'max:64', 'regex:/^(rzp_test_[A-Za-z0-9]+)?$/'],
            'razorpay.test.key_secret' => ['nullable', 'string', 'max:255'],
            'razorpay.test.webhook_secret' => ['nullable', 'string', 'max:255'],
            'razorpay.live.key_id' => ['nullable', 'string', 'max:64', 'regex:/^(rzp_live_[A-Za-z0-9]+)?$/'],
            'razorpay.live.key_secret' => ['nullable', 'string', 'max:255'],
            'razorpay.live.webhook_secret' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'razorpay.test.key_id.regex' => 'Test key IDs start with rzp_test_.',
            'razorpay.live.key_id.regex' => 'Live key IDs start with rzp_live_.',
        ];
    }

    /**
     * Razorpay cannot be switched on for a mode that has no usable keys, and
     * checkout must keep at least one way to pay.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $enabled = $this->boolean('razorpay.enabled');
                $mode = (string) $this->input('razorpay.mode');
                $stored = app(RazorpaySettings::class);

                if ($enabled) {
                    if (blank($this->input("razorpay.{$mode}.key_id"))) {
                        $validator->errors()->add("razorpay.{$mode}.key_id", "Enter the {$mode} key ID to enable Razorpay in {$mode} mode.");
                    }

                    if (blank($this->input("razorpay.{$mode}.key_secret")) && $stored->keySecret($mode) === null) {
                        $validator->errors()->add("razorpay.{$mode}.key_secret", "Enter the {$mode} key secret to enable Razorpay in {$mode} mode.");
                    }
                }

                if (! $enabled && ! $this->boolean('cod_enabled')) {
                    $validator->errors()->add('cod_enabled', 'Keep cash on delivery on until Razorpay is enabled.');
                }
            },
        ];
    }
}
