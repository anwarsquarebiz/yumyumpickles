<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');

            /*
             | Options map onto product_variants.option1..option3 by position,
             | so a product supports at most three options.
             */
            $table->unsignedTinyInteger('position')->default(1);
            $table->json('values')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'name']);
            $table->index(['product_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_options');
    }
};
