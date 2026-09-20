<?php

namespace App\Http\Requests\Storefront;

use App\Services\Currency\CurrencyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SwitchCurrencyRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'currency' => ['required', 'string', 'size:3', Rule::in(array_keys(app(CurrencyService::class)->supported()))],
        ];
    }

    public function currency(): string
    {
        return strtoupper((string) $this->validated('currency'));
    }
}
