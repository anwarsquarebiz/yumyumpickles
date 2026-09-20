import StorefrontLayout from '@/layouts/storefront-layout';
import { type BlogPostRecord, type SeoMeta } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface BlogPostShowProps {
    post: { data: BlogPostRecord };
    seo: SeoMeta;
}

export default function BlogPostShow({ post, seo }: BlogPostShowProps) {
    const item = post.data;

    return (
        <StorefrontLayout>
            <Head title={seo.title}>{seo.description && <meta name="description" content={seo.description} />}</Head>

            <article className="mx-auto w-full max-w-3xl px-4 py-12 sm:px-6">
                <p className="text-sm text-neutral-500">
                    <Link href={`/blogs/${item.url?.split('/')[2] ?? 'news'}`} className="hover:underline">
                        Journal
                    </Link>
                </p>
                <h1 className="mt-2 text-3xl font-semibold tracking-tight">{item.title}</h1>
                {item.author && <p className="text-muted-foreground mt-2 text-sm">By {item.author.name}</p>}
                {item.featured_image_url && (
                    <img src={item.featured_image_url} alt="" className="mt-6 w-full rounded-xl object-cover" />
                )}
                {item.content && (
                    <div className="prose dark:prose-invert mt-8 max-w-none" dangerouslySetInnerHTML={{ __html: item.content }} />
                )}
            </article>
        </StorefrontLayout>
    );
}
