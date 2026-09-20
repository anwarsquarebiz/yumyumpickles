<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle', 500)->nullable();
            $table->string('button_label', 80)->nullable();
            $table->string('button_url', 2048)->nullable();
            $table->string('image_disk', 32)->nullable();
            $table->string('image_path')->nullable();
            $table->string('alt')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->string('status', 32)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
