<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreSettingsRequest extends FormRequest
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
            'store.name' => ['required', 'string', 'max:255'],
            'store.email' => ['required', 'email', 'max:255'],
            'store.phone' => ['nullable', 'string', 'max:32'],
            'store.address' => ['nullable', 'string', 'max:500'],
            'store.tagline' => ['nullable', 'string', 'max:255'],
            'store.whatsapp' => ['nullable', 'string', 'max:32'],
            'storefront.announcements' => ['nullable', 'string'],
            'storefront.content_json' => ['nullable', 'string'],
            'checkout.tax_rate_basis_points' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'checkout.guest_checkout_enabled' => ['boolean'],
            'seo.default_title' => ['nullable', 'string', 'max:255'],
            'seo.default_description' => ['nullable', 'string', 'max:500'],
            'social.facebook' => ['nullable', 'string', 'max:255'],
            'social.instagram' => ['nullable', 'string', 'max:255'],
            'social.twitter' => ['nullable', 'string', 'max:255'],
            'ads.enabled' => ['boolean'],
            'ads.pixel_id' => ['nullable', 'string', 'max:32', 'regex:/^\d*$/'],
            'ads.access_token' => ['nullable', 'string', 'max:512'],
            'ads.test_event_code' => ['nullable', 'string', 'max:64'],
            'ads.advanced_matching' => ['boolean'],
            'google.enabled' => ['boolean'],
            'google.measurement_id' => ['nullable', 'string', 'max:24', 'regex:/^((G|GT)-[A-Z0-9]+)?$/i'],
            'gtm.enabled' => ['boolean'],
            'gtm.container_id' => ['nullable', 'string', 'max:24', 'regex:/^(GTM-[A-Z0-9]+)?$/i'],
        ];
    }
}
