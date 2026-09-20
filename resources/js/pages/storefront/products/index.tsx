import { Breadcrumbs, PageIntro, ProductCard } from '@/components/yumyum';
import { Button } from '@/components/ui/button';
import StorefrontLayout from '@/layouts/storefront-layout';
import { toPickle, unwrapData } from '@/lib/pickle';
import { type CatalogFilters, type Paginated, type ProductSummary, type SeoMeta, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Grid2X2, List, SlidersHorizontal, X } from 'lucide-react';
import { useEffect, useMemo, useState, type ReactNode } from 'react';
import { createPortal } from 'react-dom';

interface ShopProps {
    products: Paginated<ProductSummary> | { data: ProductSummary[] };
    filters: CatalogFilters;
    seo: SeoMeta;
}

export default function ShopPage({ products, seo }: ShopProps) {
    const { content } = usePage<SharedData>().props;
    const categories = content?.categories ?? [];
    const list = unwrapData(products);
    const items = Array.isArray(list) ? list : [];
    const pickled = items.map(toPickle);
    const params = new URLSearchParams(typeof window === 'undefined' ? '' : window.location.search);
    const [category, setCategory] = useState(params.get('category') ?? 'All');
    const [spice, setSpice] = useState('All');
    const [max, setMax] = useState(1300);
    const [sort, setSort] = useState('popular');
    const [isList, setIsList] = useState(false);
    const [inStockOnly, setInStockOnly] = useState(false);
    const [filtersOpen, setFiltersOpen] = useState(false);

    useEffect(() => {
        const next = new URLSearchParams(window.location.search).get('category');
        if (next) {
            setCategory(next);
        }
    }, []);

    const visible = useMemo(
        () =>
            pickled
                .filter(
                    (product) =>
                        (category === 'All' || product.category === category) &&
                        (spice === 'All' || product.spice === spice) &&
                        product.price <= max &&
                        (!inStockOnly || product.inStock),
                )
                .sort((a, b) => {
                    if (sort === 'low') return a.price - b.price;
                    if (sort === 'high') return b.price - a.price;
                    if (sort === 'new') return Number(b.isNew) - Number(a.isNew);
                    return b.reviews - a.reviews;
                }),
        [pickled, category, spice, max, sort, inStockOnly],
    );

    const activeFilters = Number(category !== 'All') + Number(spice !== 'All') + Number(max < 1300) + Number(inStockOnly);
    const categoryOptions = ['All', ...categories.map((item) => item.name)];
    const filterPanel = (
        <FilterPanel
            category={category}
            setCategory={setCategory}
            spice={spice}
            setSpice={setSpice}
            max={max}
            setMax={setMax}
            inStockOnly={inStockOnly}
            setInStockOnly={setInStockOnly}
            categories={categoryOptions}
        />
    );

    return (
        <StorefrontLayout>
            <Head title={seo.title}>
                {seo.description && <meta name="description" content={seo.description} />}
            </Head>
            <PageIntro eyebrow="Small batch · Big flavour" title="Find your perfect achaar" copy="Filter by flavour, heat, price and availability, then bring home a jar made the traditional way." />
            <Breadcrumbs items={[{ label: 'Shop' }]} />
            <div className="section-shell grid gap-8 pb-20 lg:grid-cols-[240px_1fr]">
                <aside className="border-border bg-card hidden h-fit rounded-lg border p-5 lg:block">
                    <h2 className="flex items-center gap-2 text-lg font-extrabold">
                        <SlidersHorizontal className="size-5" /> Filters
                    </h2>
                    {filterPanel}
                </aside>
                <div>
                    <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                        <p className="text-muted-foreground min-w-0 text-sm">{visible.length} handcrafted pickles</p>
                        <div className="flex shrink-0 items-center gap-2">
                            <Button variant="outline" className="lg:hidden" aria-label="Open filters" onClick={() => setFiltersOpen(true)}>
                                <SlidersHorizontal />
                                Filters
                                {activeFilters > 0 && (
                                    <span className="bg-primary text-primary-foreground grid size-5 place-items-center rounded-full text-[11px] font-bold">
                                        {activeFilters}
                                    </span>
                                )}
                            </Button>
                            <FiltersModal open={filtersOpen} onClose={() => setFiltersOpen(false)} resultCount={visible.length}>
                                <FilterPanel
                                    category={category}
                                    setCategory={setCategory}
                                    spice={spice}
                                    setSpice={setSpice}
                                    max={max}
                                    setMax={setMax}
                                    inStockOnly={inStockOnly}
                                    setInStockOnly={setInStockOnly}
                                    categories={categoryOptions}
                                    namePrefix="mobile"
                                />
                            </FiltersModal>
                            <select value={sort} onChange={(event) => setSort(event.target.value)} className="border-input bg-background h-10 rounded-md border px-3 text-sm" aria-label="Sort products">
                                <option value="popular">Most popular</option>
                                <option value="new">Newest</option>
                                <option value="low">Price: low to high</option>
                                <option value="high">Price: high to low</option>
                            </select>
                            <Button size="icon" variant={isList ? 'outline' : 'default'} aria-label="Grid view" onClick={() => setIsList(false)}>
                                <Grid2X2 />
                            </Button>
                            <Button size="icon" variant={isList ? 'default' : 'outline'} aria-label="List view" onClick={() => setIsList(true)}>
                                <List />
                            </Button>
                        </div>
                    </div>
                    <div className={isList ? 'grid gap-4' : 'grid gap-5 sm:grid-cols-2 xl:grid-cols-3'}>
                        {visible.map((product) => (
                            <ProductCard key={product.id} product={product} compact={isList} />
                        ))}
                    </div>
                </div>
            </div>
        </StorefrontLayout>
    );
}

function FiltersModal({
    open,
    onClose,
    resultCount,
    children,
}: {
    open: boolean;
    onClose: () => void;
    resultCount: number;
    children: ReactNode;
}) {
    useEffect(() => {
        if (!open) {
            return;
        }

        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        document.body.style.overflow = 'hidden';
        window.addEventListener('keydown', onKey);

        return () => {
            document.body.style.overflow = '';
            window.removeEventListener('keydown', onKey);
        };
    }, [open, onClose]);

    if (!open || typeof document === 'undefined') {
        return null;
    }

    return createPortal(
        <div className="fixed inset-0 z-50 lg:hidden">
            <button type="button" className="absolute inset-0 bg-black/80" aria-label="Close filters" onClick={onClose} />
            <div role="dialog" aria-modal="true" aria-labelledby="shop-filters-title" className="bg-background relative flex h-full w-[min(88%,24rem)] flex-col overflow-y-auto p-6 shadow-xl">
                <div className="mb-2 flex items-center justify-between gap-3">
                    <h2 id="shop-filters-title" className="flex items-center gap-2 text-lg font-extrabold">
                        <SlidersHorizontal className="size-5" /> Filters
                    </h2>
                    <Button type="button" variant="ghost" size="icon" onClick={onClose} aria-label="Close filter dialog">
                        <X />
                    </Button>
                </div>
                {children}
                <Button type="button" className="mt-8 w-full" onClick={onClose}>
                    Show {resultCount} pickles
                </Button>
            </div>
        </div>,
        document.body,
    );
}

function FilterPanel({
    category,
    setCategory,
    spice,
    setSpice,
    max,
    setMax,
    inStockOnly,
    setInStockOnly,
    categories,
    namePrefix = 'desktop',
}: {
    category: string;
    setCategory: (value: string) => void;
    spice: string;
    setSpice: (value: string) => void;
    max: number;
    setMax: (value: number) => void;
    inStockOnly: boolean;
    setInStockOnly: (value: boolean) => void;
    categories: string[];
    namePrefix?: string;
}) {
    return (
        <>
            <Filter namePrefix={namePrefix} label="Category" value={category} setValue={setCategory} options={categories} />
            <Filter namePrefix={namePrefix} label="Spice level" value={spice} setValue={setSpice} options={['All', 'Mild', 'Medium', 'Hot']} />
            <label className="mt-6 block text-sm font-bold">
                Up to ₹{max}
                <input type="range" min="200" max="1300" step="10" value={max} onChange={(event) => setMax(Number(event.target.value))} className="accent-primary mt-3 w-full" />
            </label>
            <label className="mt-6 flex items-center gap-2 text-sm">
                <input type="checkbox" checked={inStockOnly} onChange={(event) => setInStockOnly(event.target.checked)} className="accent-primary size-4" />
                In stock only
            </label>
        </>
    );
}

function Filter({
    label,
    value,
    setValue,
    options,
    namePrefix,
}: {
    label: string;
    value: string;
    setValue: (value: string) => void;
    options: string[];
    namePrefix: string;
}) {
    return (
        <fieldset className="mt-6">
            <legend className="mb-3 text-sm font-bold">{label}</legend>
            <div className="space-y-2">
                {options.map((option) => (
                    <label key={option} className="flex items-center gap-2 text-sm">
                        <input type="radio" name={`${namePrefix}-${label}`} checked={value === option} onChange={() => setValue(option)} className="accent-primary" />
                        {option}
                    </label>
                ))}
            </div>
        </fieldset>
    );
}
