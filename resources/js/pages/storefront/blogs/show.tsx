import { Pagination } from '@/components/pagination';
import StorefrontLayout from '@/layouts/storefront-layout';
import { type BlogPostRecord, type BlogRecord, type Paginated, type SeoMeta } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface BlogShowProps {
    blog: { data: BlogRecord };
    posts: Paginated<BlogPostRecord>;
    seo: SeoMeta;
}

export default function BlogShow({ blog, posts, seo }: BlogShowProps) {
    return (
        <StorefrontLayout>
            <Head title={seo.title}>{seo.description && <meta name="description" content={seo.description} />}</Head>

            <div className="mx-auto w-full max-w-3xl px-4 py-12 sm:px-6">
                <h1 className="text-3xl font-semibold tracking-tight">{blog.data.title}</h1>
                {blog.data.description && <p className="text-muted-foreground mt-3">{blog.data.description}</p>}

                <ul className="mt-10 space-y-8">
                    {posts.data.map((post) => (
                        <li key={post.id}>
                            <Link href={post.url ?? '#'} className="block hover:underline">
                                <h2 className="text-xl font-medium">{post.title}</h2>
                            </Link>
                            {post.excerpt && <p className="text-muted-foreground mt-1 text-sm">{post.excerpt}</p>}
                            {post.published_at && <p className="mt-1 text-xs text-neutral-500">{post.published_at}</p>}
                        </li>
                    ))}
                </ul>

                <Pagination paginator={posts} />
            </div>
        </StorefrontLayout>
    );
}
