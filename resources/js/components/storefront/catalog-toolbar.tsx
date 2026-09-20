import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { catalogSortOptions, defaultCatalogSort } from '@/lib/catalog-sort';
import { type CatalogFilters } from '@/types';
import { router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { type FormEventHandler, useState } from 'react';

interface CatalogToolbarProps {
    filters: CatalogFilters;
    /** The URL filters are applied against, so this works on any listing page. */
    baseUrl: string;
    resultCount: number;
}

export function CatalogToolbar({ filters, baseUrl, resultCount }: CatalogToolbarProps) {
    const [search, setSearch] = useState(filters.search ?? '');

    const apply = (overrides: Partial<CatalogFilters>) => {
        const next = { ...filters, search, ...overrides };

        router.get(
            baseUrl,
            Object.fromEntries(Object.entries(next).filter(([, value]) => value !== null && value !== '' && value !== false)),
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        apply({});
    };

    const hasFilters = Boolean(filters.search || filters.in_stock);

    return (
        <div className="flex flex-col gap-4 border-b border-neutral-200 pb-6 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800">
            <form onSubmit={submit} className="relative w-full sm:max-w-xs">
                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-400" />
                <Input
                    type="search"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder="Search products"
                    aria-label="Search products"
                    className="pl-9"
                />
            </form>

            <div className="flex flex-wrap items-center gap-3">
                <p className="text-sm text-neutral-500 dark:text-neutral-400">
                    {resultCount} {resultCount === 1 ? 'product' : 'products'}
                </p>

                <label className="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        checked={Boolean(filters.in_stock)}
                        onChange={(event) => apply({ in_stock: event.target.checked })}
                        className="size-4 rounded border-neutral-300 dark:border-neutral-700"
                    />
                    In stock only
                </label>

                <select
                    value={filters.sort ?? defaultCatalogSort}
                    onChange={(event) => apply({ sort: event.target.value })}
                    aria-label="Sort products"
                    className="rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                >
                    {catalogSortOptions.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>

                {hasFilters && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => {
                            setSearch('');
                            router.get(baseUrl, {}, { preserveScroll: true, replace: true });
                        }}
                    >
                        <X className="mr-1 size-4" />
                        Clear
                    </Button>
                )}
            </div>
        </div>
    );
}
