<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('seo_description');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('loyalty_points')->default(0)->after('accepts_marketing');
            $table->string('referral_code', 32)->nullable()->unique()->after('loyalty_points');
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('seo_description');
        });

        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name');
            $table->string('author_city')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->text('body');
            $table->string('status', 16)->default('approved');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'status']);
        });

        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
        });

        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('product_reviews');

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['loyalty_points', 'referral_code']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
