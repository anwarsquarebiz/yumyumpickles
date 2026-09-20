<?php

namespace App\Http\Requests\Admin\Product;

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Shared validation for creating and updating a product with its nested
 * options and variants.
 */
abstract class ProductFormRequest extends FormRequest
{
    /**
     * The product being edited, or null when creating.
     */
    abstract protected function product(): ?Product;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'body_html' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'vendor' => ['nullable', 'string', 'max:255'],
            'product_type' => ['nullable', 'string', 'max:255'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'published_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
            'metadata.spice' => ['nullable', 'string', 'max:32'],
            'metadata.story' => ['nullable', 'string'],
            'metadata.ingredients' => ['nullable', 'array'],
            'metadata.ingredients.*' => ['nullable', 'string', 'max:255'],
            'metadata.nutrition' => ['nullable', 'array'],
            'metadata.pairs_with' => ['nullable', 'array'],
            'metadata.pairs_with.*' => ['nullable', 'string', 'max:255'],
            'metadata.badge' => ['nullable', 'string', 'max:64'],
            'metadata.is_new' => ['nullable', 'boolean'],

            'collection_ids' => ['array'],
            'collection_ids.*' => ['integer', 'exists:collections,id'],

            'options' => ['array', 'max:'.config('shop.catalog.max_options_per_product', 3)],
            'options.*.name' => ['required', 'string', 'max:255'],
            'options.*.position' => ['nullable', 'integer', 'min:1', 'max:3'],
            'options.*.values' => ['array'],
            'options.*.values.*' => ['string', 'max:255'],

            'variants' => ['required', 'array', 'min:1'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.title' => ['nullable', 'string', 'max:255'],
            'variants.*.sku' => ['nullable', 'string', 'max:64'],
            'variants.*.barcode' => ['nullable', 'string', 'max:64'],
            'variants.*.price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'variants.*.compare_at_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'variants.*.cost' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'variants.*.option1' => ['nullable', 'string', 'max:255'],
            'variants.*.option2' => ['nullable', 'string', 'max:255'],
            'variants.*.option3' => ['nullable', 'string', 'max:255'],
            'variants.*.inventory_quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'variants.*.track_inventory' => ['boolean'],
            'variants.*.inventory_policy' => ['required', Rule::enum(InventoryPolicy::class)],
            'variants.*.weight' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'variants.*.weight_unit' => ['nullable', 'string', 'in:kg,g,lb,oz'],
            'variants.*.position' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'variants.required' => 'A product needs at least one variant.',
            'variants.min' => 'A product needs at least one variant.',
            'variants.*.price.required' => 'Every variant needs a price.',
            'variants.*.inventory_quantity.required' => 'Every variant needs a stock quantity.',
        ];
    }

    protected function prepareForValidation(): void
    {
        /*
         | An active product with no publish date is published immediately;
         | anything not active is unpublished. This keeps the storefront's
         | "published" scope meaningful without a second toggle in the form.
         */
        $status = $this->input('status');
        $publishedAt = $this->input('published_at');

        if ($status === ProductStatus::Active->value && blank($publishedAt)) {
            $this->merge(['published_at' => now()->toDateTimeString()]);
        }

        if ($status !== ProductStatus::Active->value && $status !== null) {
            $this->merge(['published_at' => null]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateVariantIdsBelongToProduct($validator);
            $this->validateSkusAreUnique($validator);
        });
    }

    /**
     * Reject a variant id that belongs to a different product, which would
     * otherwise let one product's form edit another product's variant.
     */
    private function validateVariantIdsBelongToProduct(Validator $validator): void
    {
        $product = $this->product();

        /** @var array<int, array<string, mixed>> $variants */
        $variants = $this->input('variants', []);

        foreach ($variants as $index => $variant) {
            $id = $variant['id'] ?? null;

            if (blank($id)) {
                continue;
            }

            $belongs = $product !== null && ProductVariant::query()
                ->whereKey($id)
                ->where('product_id', $product->getKey())
                ->exists();

            if (! $belongs) {
                $validator->errors()->add("variants.{$index}.id", 'This variant does not belong to the product.');
            }
        }
    }

    /**
     * SKUs are unique store-wide, and must also not repeat within the payload.
     */
    private function validateSkusAreUnique(Validator $validator): void
    {
        /** @var array<int, array<string, mixed>> $variants */
        $variants = $this->input('variants', []);

        $seen = [];

        foreach ($variants as $index => $variant) {
            $sku = $variant['sku'] ?? null;

            if (blank($sku)) {
                continue;
            }

            $normalized = mb_strtolower(trim((string) $sku));

            if (isset($seen[$normalized])) {
                $validator->errors()->add("variants.{$index}.sku", 'This SKU is repeated on another variant.');

                continue;
            }

            $seen[$normalized] = true;

            $taken = ProductVariant::query()
                ->where('sku', $sku)
                ->when(
                    filled($variant['id'] ?? null),
                    fn ($query) => $query->whereKeyNot($variant['id']),
                )
                ->exists();

            if ($taken) {
                $validator->errors()->add("variants.{$index}.sku", 'This SKU is already used by another product.');
            }
        }
    }
}
