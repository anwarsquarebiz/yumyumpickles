import StorefrontLayout from '@/layouts/storefront-layout';
import { type CollectionDetail, type SeoMeta } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ImageOff } from 'lucide-react';

interface CollectionsIndexProps {
    collections: { data: CollectionDetail[] };
    seo: SeoMeta;
}

export default function CollectionsIndex({ collections, seo }: CollectionsIndexProps) {
    return (
        <StorefrontLayout>
            <Head title={seo.title}>{seo.description && <meta name="description" content={seo.description} />}</Head>

            <div className="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6">
                <header className="mb-8 space-y-2">
                    <h1 className="text-3xl font-semibold tracking-tight">Collections</h1>
                    <p className="text-neutral-600 dark:text-neutral-400">Browse our curated ranges.</p>
                </header>

                {collections.data.length === 0 ? (
                    <p className="py-20 text-center text-neutral-500 dark:text-neutral-400">No collections have been published yet.</p>
                ) : (
                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {collections.data.map((collection) => (
                            <Link
                                key={collection.id}
                                href={collection.url}
                                prefetch
                                className="group overflow-hidden rounded-xl border border-neutral-200 transition-shadow hover:shadow-md dark:border-neutral-800"
                            >
                                <div className="aspect-video bg-neutral-100 dark:bg-neutral-800">
                                    {collection.image_url ? (
                                        <img
                                            src={collection.image_url}
                                            alt={collection.title}
                                            loading="lazy"
                                            className="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                                        />
                                    ) : (
                                        <div className="flex size-full items-center justify-center text-neutral-400">
                                            <ImageOff className="size-8" />
                                        </div>
                                    )}
                                </div>

                                <div className="space-y-1 p-4">
                                    <p className="font-medium group-hover:underline">{collection.title}</p>
                                    {collection.description && (
                                        <p className="line-clamp-2 text-sm text-neutral-600 dark:text-neutral-400">{collection.description}</p>
                                    )}
                                    <p className="text-xs text-neutral-500 dark:text-neutral-500">
                                        {collection.products_count ?? 0} {collection.products_count === 1 ? 'product' : 'products'}
                                    </p>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </StorefrontLayout>
    );
}
