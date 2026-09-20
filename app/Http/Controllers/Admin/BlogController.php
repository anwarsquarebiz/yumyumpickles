<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublishStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Blog\BlogFormRequest;
use App\Http\Requests\Admin\Blog\BlogPostFormRequest;
use App\Http\Resources\BlogPostResource;
use App\Http\Resources\BlogResource;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Services\Content\BlogService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function __construct(private readonly BlogService $blogs) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Blog::class);

        $blogs = Blog::query()->withCount('posts')->latest('id')->get();

        return Inertia::render('admin/blogs/index', [
            'blogs' => BlogResource::collection($blogs),
        ]);
    }

    public function store(BlogFormRequest $request): RedirectResponse
    {
        $blog = $this->blogs->createBlog($request->validated());

        return to_route('admin.blogs.edit', $blog)
            ->with('success', "Blog \"{$blog->title}\" was created.");
    }

    public function edit(Blog $blog): Response
    {
        $this->authorize('update', $blog);

        $posts = BlogPost::query()
            ->where('blog_id', $blog->getKey())
            ->with(['category', 'author'])
            ->latest('id')
            ->paginate((int) config('shop.catalog.admin_per_page', 20))
            ->withQueryString();

        return Inertia::render('admin/blogs/edit', [
            'blog' => new BlogResource($blog),
            'posts' => BlogPostResource::collection($posts),
            'categories' => BlogCategory::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => PublishStatus::options(),
        ]);
    }

    public function update(BlogFormRequest $request, Blog $blog): RedirectResponse
    {
        $this->blogs->updateBlog($blog, $request->validated());

        return back()->with('success', 'Blog saved.');
    }

    public function destroy(Blog $blog): RedirectResponse
    {
        $this->authorize('delete', $blog);

        $title = $blog->title;
        $this->blogs->deleteBlog($blog);

        return to_route('admin.blogs.index')->with('success', "Blog \"{$title}\" was deleted.");
    }

    public function storePost(BlogPostFormRequest $request): RedirectResponse
    {
        $post = $this->blogs->createPost(
            array_merge($request->validated(), ['featured_image' => $request->file('featured_image')]),
            $request->user()?->getKey(),
        );

        return back()->with('success', "Post \"{$post->title}\" was created.");
    }

    public function updatePost(BlogPostFormRequest $request, BlogPost $post): RedirectResponse
    {
        $this->blogs->updatePost(
            $post,
            array_merge($request->validated(), ['featured_image' => $request->file('featured_image')]),
        );

        return back()->with('success', 'Post saved.');
    }

    public function destroyPost(BlogPost $post): RedirectResponse
    {
        $this->authorize('delete', $post);

        $this->blogs->deletePost($post);

        return back()->with('success', 'Post deleted.');
    }
}
