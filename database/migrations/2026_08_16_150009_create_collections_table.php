<?php

use App\Enums\PublishStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_disk', 32)->nullable();
            $table->string('image_path')->nullable();
            $table->string('status', 16)->default(PublishStatus::Draft->value);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['status', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
