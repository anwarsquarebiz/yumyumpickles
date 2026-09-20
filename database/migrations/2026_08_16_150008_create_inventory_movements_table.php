<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         | An append-only ledger of every stock change. product_variants
         | .inventory_quantity is the running balance; this table explains how
         | it got there and is never mutated after insert.
         */
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity_delta');
            $table->integer('quantity_after');
            $table->string('reason', 32);
            $table->nullableMorphs('reference');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['product_variant_id', 'created_at']);
            $table->index('reason');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
