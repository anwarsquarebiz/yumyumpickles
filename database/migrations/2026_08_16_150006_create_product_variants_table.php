<?php

use App\Enums\InventoryPolicy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('title')->default('Default');

            /*
             | Nullable so variants may exist before a SKU is assigned. Both
             | MySQL and SQLite permit repeated NULLs inside a unique index.
             */
            $table->string('sku', 64)->nullable()->unique();
            $table->string('barcode', 64)->nullable();

            $table->unsignedBigInteger('price_amount')->default(0);
            $table->unsignedBigInteger('compare_at_price_amount')->nullable();
            $table->unsignedBigInteger('cost_amount')->nullable();

            $table->string('option1')->nullable();
            $table->string('option2')->nullable();
            $table->string('option3')->nullable();

            $table->integer('inventory_quantity')->default(0);
            $table->boolean('track_inventory')->default(true);
            $table->string('inventory_policy', 16)->default(InventoryPolicy::Deny->value);

            $table->decimal('weight', 10, 3)->nullable();
            $table->string('weight_unit', 8)->default('kg');

            $table->unsignedInteger('position')->default(1);
            $table->timestamps();

            $table->index(['product_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
