import { StatusBadge } from '@/components/admin/status-badge';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin-layout';
import { catalogSortOptions, defaultCatalogSort } from '@/lib/catalog-sort';
import { cn } from '@/lib/utils';
import { type AdminProductRow, type BreadcrumbItem, type IdOption, type Paginated, type SelectOption } from '@/types';
import { Link, router } from '@inertiajs/react';
import { GripVertical, ImageOff, Plus, Search } from 'lucide-react';
import { type DragEvent, type FormEventHandler, type KeyboardEvent, useEffect, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin' },
    { title: 'Products', href: '/admin/products' },
];

interface AdminProductsIndexProps {
    products: Paginated<AdminProductRow>;
    filters: { search: string | null; status: string | null; collection_id: number | null; sort: string | null };
    statuses: SelectOption[];
    collections: IdOption[];
}

/** Minor units to a display string; the admin list uses the raw aggregate. */
const formatMinor = (amount: number | null): string => (amount === null ? '—' : `$${(amount / 100).toFixed(2)}`);

export default function AdminProductsIndex({ products, filters, statuses, collections }: AdminProductsIndexProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const sort = filters.sort ?? defaultCatalogSort;
    const canReorder = sort === 'custom';

    const apply = (overrides: Record<string, unknown>) => {
        const next = { ...filters, search, ...overrides };

        router.get('/admin/products', Object.fromEntries(Object.entries(next).filter(([, value]) => value !== null && value !== '')), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        apply({});
    };

    return (
        <AdminLayout
            breadcrumbs={breadcrumbs}
            title="Products"
            description="Manage your catalog, pricing and stock. Drag to set the custom order used on the storefront."
            actions={
                <Button asChild>
                    <Link href="/admin/products/create">
                        <Plus className="mr-1 size-4" />
                        New product
                    </Link>
                </Button>
            }
        >
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                <form onSubmit={submit} className="relative w-full sm:max-w-xs">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-400" />
                    <Input
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search title, vendor or SKU"
                        aria-label="Search products"
                        className="pl-9"
                    />
                </form>

                <select
                    value={filters.status ?? ''}
                    onChange={(event) => apply({ status: event.target.value || null })}
                    aria-label="Filter by status"
                    className="h-9 rounded-md border border-neutral-300 bg-transparent px-3 text-sm dark:border-neutral-700"
                >
                    <option value="">All statuses</option>
                    {statuses.map((status) => (
                        <option key={status.value} value={status.value}>
                            {status.label}
                        </option>
                    ))}
                </select>

                <select
                    value={filters.collection_id ?? ''}
                    onChange={(event) => apply({ collection_id: event.target.value || null })}
                    aria-label="Filter by collection"
                    className="h-9 rounded-md border border-neutral-300 bg-transparent px-3 text-sm dark:border-neutral-700"
                >
                    <option value="">All collections</option>
                    {collections.map((collection) => (
                        <option key={collection.value} value={collection.value}>
                            {collection.label}
                        </option>
                    ))}
                </select>

                <select
                    value={sort}
                    onChange={(event) => apply({ sort: event.target.value })}
                    aria-label="Sort products"
                    className="h-9 rounded-md border border-neutral-300 bg-transparent px-3 text-sm dark:border-neutral-700"
                >
                    {catalogSortOptions.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
            </div>

            {products.data.length === 0 ? (
                <div className="rounded-xl border border-neutral-200 p-12 text-center dark:border-neutral-800">
                    <p className="font-medium">No products yet</p>
                    <p className="text-muted-foreground mt-1 text-sm">Create your first product to start selling.</p>
                </div>
            ) : (
                <SortableProductTable products={products} canReorder={canReorder} />
            )}

            <Pagination paginator={products} />
        </AdminLayout>
    );
}

function SortableProductTable({ products, canReorder }: { products: Paginated<AdminProductRow>; canReorder: boolean }) {
    const [ordered, setOrdered] = useState(products.data);
    const [draggingId, setDraggingId] = useState<number | null>(null);
    const orderedRef = useRef(ordered);
    const draggingIdRef = useRef<number | null>(null);
    const savingRef = useRef(false);

    orderedRef.current = ordered;

    useEffect(() => {
        setOrdered(products.data);
    }, [products.data]);

    const persist = (next: AdminProductRow[]): void => {
        const ids = next.map((item) => item.id);
        const persisted = products.data.map((item) => item.id).join(',');

        if (!canReorder || ids.length < 2 || ids.join(',') === persisted || savingRef.current) {
            return;
        }

        savingRef.current = true;
        router.put(
            '/admin/products/order',
            { ids },
            {
                preserveScroll: true,
                preserveState: true,
                onError: () => setOrdered(products.data),
                onFinish: () => {
                    savingRef.current = false;
                },
            },
        );
    };

    const applyOrder = (next: AdminProductRow[], save: boolean): void => {
        setOrdered(next);
        orderedRef.current = next;

        if (save) {
            persist(next);
        }
    };

    const onDragStart = (event: DragEvent<HTMLButtonElement>, id: number): void => {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', String(id));
        draggingIdRef.current = id;
        setDraggingId(id);
    };

    const onDragOver = (event: DragEvent<HTMLTableRowElement>, overId: number): void => {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';

        const fromId = draggingIdRef.current;

        if (fromId === null || fromId === overId) {
            return;
        }

        const current = orderedRef.current;
        const fromIndex = current.findIndex((item) => item.id === fromId);
        const overIndex = current.findIndex((item) => item.id === overId);

        if (fromIndex < 0 || overIndex < 0) {
            return;
        }

        const midpoint = event.currentTarget.getBoundingClientRect().top + event.currentTarget.offsetHeight / 2;

        if (fromIndex < overIndex && event.clientY < midpoint) {
            return;
        }

        if (fromIndex > overIndex && event.clientY > midpoint) {
            return;
        }

        applyOrder(moveItem(current, fromId, overId), false);
    };

    const onDragEnd = (): void => {
        persist(orderedRef.current);
        draggingIdRef.current = null;
        setDraggingId(null);
    };

    const onKeyDown = (event: KeyboardEvent<HTMLButtonElement>, id: number): void => {
        if (event.key !== 'ArrowUp' && event.key !== 'ArrowDown') {
            return;
        }

        event.preventDefault();
        applyOrder(moveItemByOffset(orderedRef.current, id, event.key === 'ArrowUp' ? -1 : 1), true);
    };

    return (
        <div className="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-800">
            {canReorder && (
                <p className="text-muted-foreground border-b border-neutral-200 px-4 py-2 text-sm dark:border-neutral-800">
                    {products.meta.total === 1
                        ? 'This product is listed so you can drag it into place.'
                        : `All ${products.meta.total} products are listed so you can drag any item to the top, the bottom, or anywhere in between.`}
                </p>
            )}
            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead className="bg-neutral-50 text-left dark:bg-neutral-900">
                        <tr>
                            {canReorder && (
                                <th scope="col" className="w-10 px-2 py-3">
                                    <span className="sr-only">Reorder</span>
                                </th>
                            )}
                            <th scope="col" className="px-4 py-3 font-medium">
                                Product
                            </th>
                            <th scope="col" className="px-4 py-3 font-medium">
                                Status
                            </th>
                            <th scope="col" className="px-4 py-3 font-medium">
                                Price
                            </th>
                            <th scope="col" className="px-4 py-3 font-medium">
                                Stock
                            </th>
                            <th scope="col" className="px-4 py-3 font-medium">
                                Variants
                            </th>
                            <th scope="col" className="px-4 py-3 text-right font-medium">
                                <span className="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                        {ordered.map((product) => (
                            <tr
                                key={product.id}
                                onDragOver={canReorder ? (event) => onDragOver(event, product.id) : undefined}
                                onDrop={canReorder ? (event) => event.preventDefault() : undefined}
                                className={cn(
                                    'hover:bg-neutral-50 dark:hover:bg-neutral-900',
                                    draggingId === product.id && 'opacity-50',
                                )}
                            >
                                {canReorder && (
                                    <td className="px-2 py-3">
                                        <button
                                            type="button"
                                            draggable
                                            onDragStart={(event) => onDragStart(event, product.id)}
                                            onDragEnd={onDragEnd}
                                            onKeyDown={(event) => onKeyDown(event, product.id)}
                                            aria-label={`Reorder ${product.title}`}
                                            title="Drag to reorder, or use the up and down arrow keys"
                                            className="text-muted-foreground hover:text-foreground inline-flex size-8 cursor-grab items-center justify-center rounded-md active:cursor-grabbing"
                                        >
                                            <GripVertical className="size-4" />
                                        </button>
                                    </td>
                                )}
                                <td className="px-4 py-3">
                                    <div className="flex items-center gap-3">
                                        <div className="size-10 shrink-0 overflow-hidden rounded-md bg-neutral-100 dark:bg-neutral-800">
                                            {product.images[0] ? (
                                                <img
                                                    src={product.images[0].url}
                                                    alt={product.images[0].alt ?? product.title}
                                                    className="size-full object-cover"
                                                />
                                            ) : (
                                                <div className="flex size-full items-center justify-center text-neutral-400">
                                                    <ImageOff className="size-4" />
                                                </div>
                                            )}
                                        </div>
                                        <div className="min-w-0">
                                            <Link href={`/admin/products/${product.id}/edit`} className="font-medium hover:underline">
                                                {product.title}
                                            </Link>
                                            {product.vendor && <p className="text-muted-foreground truncate text-xs">{product.vendor}</p>}
                                        </div>
                                    </div>
                                </td>
                                <td className="px-4 py-3">
                                    <StatusBadge label={product.status} status={product.status} />
                                </td>
                                <td className="px-4 py-3">{formatMinor(product.min_price_amount)}</td>
                                <td className="px-4 py-3">
                                    <span className={product.total_inventory === 0 ? 'text-rose-600 dark:text-rose-400' : ''}>
                                        {product.total_inventory ?? 0}
                                    </span>
                                </td>
                                <td className="px-4 py-3">{product.variants_count}</td>
                                <td className="px-4 py-3 text-right">
                                    <Button asChild variant="ghost" size="sm">
                                        <Link href={`/admin/products/${product.id}/edit`}>Edit</Link>
                                    </Button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

function moveItem<T extends { id: number }>(items: T[], fromId: number, toId: number): T[] {
    const fromIndex = items.findIndex((item) => item.id === fromId);
    const toIndex = items.findIndex((item) => item.id === toId);

    if (fromIndex < 0 || toIndex < 0 || fromIndex === toIndex) {
        return items;
    }

    const next = [...items];
    const [moved] = next.splice(fromIndex, 1);
    next.splice(toIndex, 0, moved);

    return next;
}

function moveItemByOffset<T extends { id: number }>(items: T[], id: number, offset: number): T[] {
    const index = items.findIndex((item) => item.id === id);
    const target = items[index + offset];

    if (index < 0 || target === undefined) {
        return items;
    }

    return moveItem(items, id, target.id);
}
