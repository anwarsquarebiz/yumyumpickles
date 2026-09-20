<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('navigation_items', function (Blueprint $table) {
            $table->id();
            $table->string('menu', 32)->default('header')->index();
            $table->string('title');
            $table->string('type', 32)->index();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('url', 2048)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['menu', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_items');
    }
};
