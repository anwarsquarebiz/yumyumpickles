import { FormCard, FormField } from '@/components/admin/form-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin-layout';
import { type BlogPostRecord, type BlogRecord, type Paginated, type SelectOption } from '@/types';
import { Link, router, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

interface AdminBlogsIndexProps {
    blogs: { data: BlogRecord[] } | BlogRecord[];
}

export default function AdminBlogsIndex({ blogs }: AdminBlogsIndexProps) {
    const list = Array.isArray(blogs) ? blogs : blogs.data;
    const form = useForm({ title: '', slug: '', description: '', seo_title: '', seo_description: '' });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        form.post('/admin/blogs');
    };

    return (
        <AdminLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/admin' },
                { title: 'Blogs', href: '/admin/blogs' },
            ]}
            title="Blogs"
            description="Named journals that hold posts, matching Shopify's /blogs/{blog}/{post} URLs."
        >
            <ul className="space-y-2">
                {list.map((blog) => (
                    <li key={blog.id} className="flex items-center justify-between rounded-xl border border-neutral-200 px-4 py-3 dark:border-neutral-800">
                        <Link href={`/admin/blogs/${blog.id}/edit`} className="font-medium hover:underline">
                            {blog.title}
                        </Link>
                        <span className="text-muted-foreground text-sm">{blog.posts_count ?? 0} posts</span>
                    </li>
                ))}
            </ul>

            <form onSubmit={submit} className="mt-6 max-w-lg space-y-3 rounded-xl border border-neutral-200 p-6 dark:border-neutral-800">
                <h2 className="font-medium">New blog</h2>
                <Input placeholder="Title" value={form.data.title} onChange={(event) => form.setData('title', event.target.value)} />
                <Button type="submit" disabled={form.processing}>
                    Create blog
                </Button>
            </form>
        </AdminLayout>
    );
}

export function BlogEditor({
    blog,
    posts,
    categories,
    statuses,
}: {
    blog: BlogRecord;
    posts: Paginated<BlogPostRecord>;
    categories: { id: number; name: string }[];
    statuses: SelectOption[];
}) {
    const blogForm = useForm({
        title: blog.title,
        slug: blog.slug,
        description: blog.description ?? '',
        seo_title: blog.seo_title ?? '',
        seo_description: blog.seo_description ?? '',
    });
    const postForm = useForm({
        blog_id: blog.id,
        blog_category_id: '',
        title: '',
        slug: '',
        excerpt: '',
        content: '',
        status: 'published',
        seo_title: '',
        seo_description: '',
    });

    return (
        <AdminLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/admin' },
                { title: 'Blogs', href: '/admin/blogs' },
                { title: blog.title, href: `/admin/blogs/${blog.id}/edit` },
            ]}
            title={blog.title}
            actions={
                <Button variant="outline" onClick={() => confirm('Delete this blog and its posts?') && router.delete(`/admin/blogs/${blog.id}`)}>
                    Delete
                </Button>
            }
        >
            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    blogForm.put(`/admin/blogs/${blog.id}`);
                }}
                className="space-y-3 rounded-xl border border-neutral-200 p-6 dark:border-neutral-800"
            >
                <FormField label="Title" htmlFor="title" error={blogForm.errors.title}>
                    <Input id="title" value={blogForm.data.title} onChange={(event) => blogForm.setData('title', event.target.value)} />
                </FormField>
                <Button type="submit" disabled={blogForm.processing}>
                    Save blog
                </Button>
            </form>

            <section className="space-y-3">
                <h2 className="font-medium">Posts</h2>
                <ul className="divide-y divide-neutral-200 rounded-xl border border-neutral-200 dark:divide-neutral-800 dark:border-neutral-800">
                    {posts.data.map((post) => (
                        <li key={post.id} className="flex items-center justify-between px-4 py-3 text-sm">
                            <span>{post.title}</span>
                            <Button variant="ghost" size="sm" onClick={() => router.delete(`/admin/blog-posts/${post.id}`)}>
                                Delete
                            </Button>
                        </li>
                    ))}
                </ul>
            </section>

            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    postForm.post('/admin/blog-posts');
                }}
                className="space-y-3 rounded-xl border border-neutral-200 p-6 dark:border-neutral-800"
            >
                <h2 className="font-medium">New post</h2>
                <Input placeholder="Title" value={postForm.data.title} onChange={(event) => postForm.setData('title', event.target.value)} />
                <textarea
                    placeholder="Content"
                    rows={6}
                    value={postForm.data.content}
                    onChange={(event) => postForm.setData('content', event.target.value)}
                    className="w-full rounded-md border border-neutral-300 bg-transparent px-3 py-2 text-sm dark:border-neutral-700"
                />
                <select
                    value={postForm.data.status}
                    onChange={(event) => postForm.setData('status', event.target.value)}
                    className="h-9 rounded-md border border-neutral-300 bg-transparent px-3 text-sm dark:border-neutral-700"
                >
                    {statuses.map((status) => (
                        <option key={status.value} value={status.value}>
                            {status.label}
                        </option>
                    ))}
                </select>
                <Button type="submit" disabled={postForm.processing}>
                    Publish post
                </Button>
            </form>
        </AdminLayout>
    );
}
