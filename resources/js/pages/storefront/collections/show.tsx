import { Pagination } from '@/components/pagination';
import { CatalogToolbar } from '@/components/storefront/catalog-toolbar';
import { ProductCard } from '@/components/storefront/product-card';
import StorefrontLayout from '@/layouts/storefront-layout';
import { type CatalogFilters, type CollectionSummary, type Paginated, type ProductSummary, type SeoMeta } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { PackageOpen } from 'lucide-react';

interface CollectionShowProps {
    collection: { data: CollectionSummary };
    description: string | null;
    products: Paginated<ProductSummary>;
    filters: CatalogFilters;
    seo: SeoMeta;
}

export default function CollectionShow({ collection, description, products, filters, seo }: CollectionShowProps) {
    const { data: current } = collection;

    return (
        <StorefrontLayout>
            <Head title={seo.title}>{seo.description && <meta name="description" content={seo.description} />}</Head>

            <div className="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6">
                <nav aria-label="Breadcrumb" className="mb-6 text-sm text-neutral-500 dark:text-neutral-400">
                    <Link href="/collections" className="hover:text-neutral-900 dark:hover:text-white">
                        Collections
                    </Link>
                    <span className="mx-2">/</span>
                    <span className="text-neutral-900 dark:text-white">{current.title}</span>
                </nav>

                <header className="mb-6 space-y-2">
                    <h1 className="text-3xl font-semibold tracking-tight">{current.title}</h1>
                    {description && <p className="max-w-2xl text-neutral-600 dark:text-neutral-400">{description}</p>}
                </header>

                <CatalogToolbar filters={filters} baseUrl={current.url} resultCount={products.meta.total} />

                {products.data.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 py-20 text-center">
                        <PackageOpen className="size-10 text-neutral-400" />
                        <p className="font-medium">Nothing here yet</p>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">This collection has no matching products.</p>
                    </div>
                ) : (
                    <>
                        <div className="mt-8 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                            {products.data.map((product) => (
                                <ProductCard key={product.id} product={product} />
                            ))}
                        </div>

                        <div className="mt-10">
                            <Pagination paginator={products} />
                        </div>
                    </>
                )}
            </div>
        </StorefrontLayout>
    );
}
