<?php

use App\Enums\PublishStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blog_category_id')->nullable()->constrained()->nullOnDelete();

            /*
             | The author. Kept nullable so removing a staff account does not
             | delete their posts.
             */
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title');
            $table->string('slug');
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('featured_image_disk', 32)->nullable();
            $table->string('featured_image_path')->nullable();
            $table->string('status', 16)->default(PublishStatus::Draft->value);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            /*
             | Slugs only need to be unique within their blog, which is what the
             | /blogs/{blog_slug}/{post_slug} route resolves against.
             */
            $table->unique(['blog_id', 'slug']);
            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
