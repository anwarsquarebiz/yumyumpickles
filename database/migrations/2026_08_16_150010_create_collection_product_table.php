<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            /*
             | Manual ordering of products inside a collection.
             */
            $table->unsignedInteger('position')->default(0);

            $table->unique(['collection_id', 'product_id']);
            $table->index(['collection_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_product');
    }
};
