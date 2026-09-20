import { Breadcrumbs, PageIntro, ProductCard } from '@/components/yumyum';
import { Button } from '@/components/ui/button';
import StorefrontLayout from '@/layouts/storefront-layout';
import { readWishlist, toPickle } from '@/lib/pickle';
import { type SeoMeta, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { useMemo } from 'react';

interface WishlistProps {
    seo: SeoMeta;
}

export default function WishlistPage({ seo }: WishlistProps) {
    const catalog = usePage<SharedData>().props.catalog ?? [];
    const items = useMemo(() => {
        const saved = readWishlist();
        return catalog.map(toPickle).filter((product) => saved.includes(product.id));
    }, [catalog]);

    return (
        <StorefrontLayout>
            <Head title={seo.title}>{seo.description && <meta name="description" content={seo.description} />}</Head>
            <PageIntro eyebrow="Saved for later" title="Your wishlist" copy="The jars you are not ready to let go of just yet." />
            <Breadcrumbs items={[{ label: 'Wishlist' }]} />
            <div className="section-shell py-16">
                {items.length === 0 ? (
                    <div className="border-border rounded-2xl border border-dashed p-12 text-center">
                        <p>Nothing saved yet.</p>
                        <Button asChild className="mt-4">
                            <Link href="/shop">Explore pickles</Link>
                        </Button>
                    </div>
                ) : (
                    <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        {items.map((product) => (
                            <ProductCard key={product.id} product={product} />
                        ))}
                    </div>
                )}
            </div>
        </StorefrontLayout>
    );
}
