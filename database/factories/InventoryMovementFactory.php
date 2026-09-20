<?php

namespace Database\Factories;

use App\Enums\InventoryMovementReason;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $delta = fake()->numberBetween(1, 50);

        return [
            'product_variant_id' => ProductVariant::factory(),
            'quantity_delta' => $delta,
            'quantity_after' => $delta,
            'reason' => InventoryMovementReason::Restock,
            'reference_type' => null,
            'reference_id' => null,
            'user_id' => null,
            'note' => null,
        ];
    }
}
