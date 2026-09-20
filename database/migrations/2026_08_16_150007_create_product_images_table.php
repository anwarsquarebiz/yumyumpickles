<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            /*
             | An image may be pinned to a single variant; when null it belongs
             | to the product gallery as a whole.
             */
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();

            $table->string('disk', 32)->default('public');
            $table->string('path');
            $table->string('alt')->nullable();
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();

            $table->index(['product_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
