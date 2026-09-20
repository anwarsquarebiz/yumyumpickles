<?php

use App\Enums\ShippingMethodType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('type', 32)->default(ShippingMethodType::FlatRate->value);
            $table->unsignedBigInteger('rate_amount')->default(0);

            /*
             | Subtotal at which free_over_threshold methods stop charging.
             */
            $table->unsignedBigInteger('free_over_amount')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
    }
};
