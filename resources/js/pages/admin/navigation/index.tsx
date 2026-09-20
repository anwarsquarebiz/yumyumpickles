import { FormCard, FormField } from '@/components/admin/form-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type IdOption, type SelectOption } from '@/types';
import { router, useForm } from '@inertiajs/react';
import { GripVertical } from 'lucide-react';
import { useEffect, useRef, useState, type DragEvent, type FormEventHandler, type KeyboardEvent } from 'react';

interface NavigationItemRow {
    id: number;
    title: string;
    type: string;
    type_label: string;
    resource_id: number | null;
    url: string | null;
    position: number;
    destination: string;
}

interface AdminNavigationIndexProps {
    items: { data: NavigationItemRow[] } | NavigationItemRow[];
    link_types: SelectOption[];
    collections: IdOption[];
    pages: IdOption[];
    blogs: IdOption[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin' },
    { title: 'Navigation', href: '/admin/navigation' },
];

const selectClassName = 'h-10 w-full rounded-md border border-neutral-300 bg-transparent px-3 text-sm dark:border-neutral-700';

export default function AdminNavigationIndex({ items, link_types, collections, pages, blogs }: AdminNavigationIndexProps) {
    const rows = Array.isArray(items) ? items : items.data;

    return (
        <AdminLayout
            breadcrumbs={breadcrumbs}
            title="Navigation"
            description="Choose the links shown in the storefront header. Drag the handle to reorder them."
        >
            <div className="space-y-6">
                {rows.length === 0 ? (
                    <div className="rounded-xl border border-neutral-200 p-12 text-center dark:border-neutral-800">
                        <p className="font-medium">No header links yet</p>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Until you add items, the header shows All products, published collections, and the journal.
                        </p>
                    </div>
                ) : (
                    <SortableNavigationList
                        items={rows}
                        linkTypes={link_types}
                        collections={collections}
                        pages={pages}
                        blogs={blogs}
                    />
                )}

                <AddNavigationItemForm linkTypes={link_types} collections={collections} pages={pages} blogs={blogs} />
            </div>
        </AdminLayout>
    );
}

function SortableNavigationList({
    items,
    linkTypes,
    collections,
    pages,
    blogs,
}: {
    items: NavigationItemRow[];
    linkTypes: SelectOption[];
    collections: IdOption[];
    pages: IdOption[];
    blogs: IdOption[];
}) {
    const [ordered, setOrdered] = useState(items);
    const [draggingId, setDraggingId] = useState<number | null>(null);
    const orderedRef = useRef(ordered);
    const draggingIdRef = useRef<number | null>(null);
    const savingRef = useRef(false);

    orderedRef.current = ordered;

    useEffect(() => {
        setOrdered(items);
    }, [items]);

    const persist = (next: NavigationItemRow[]): void => {
        const ids = next.map((item) => item.id);
        const persisted = items.map((item) => item.id).join(',');

        if (ids.length < 2 || ids.join(',') === persisted || savingRef.current) {
            return;
        }

        savingRef.current = true;
        router.put(
            '/admin/navigation/order',
            { ids },
            {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => {
                    savingRef.current = false;
                },
            },
        );
    };

    const applyOrder = (next: NavigationItemRow[], save: boolean): void => {
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

    const onDragOver = (event: DragEvent<HTMLLIElement>, overId: number): void => {
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
        <ul className="divide-y divide-neutral-200 rounded-xl border border-neutral-200 dark:divide-neutral-800 dark:border-neutral-800">
            {ordered.map((item) => (
                <li
                    key={item.id}
                    onDragOver={(event) => onDragOver(event, item.id)}
                    onDrop={(event) => event.preventDefault()}
                    className={cn('flex items-start gap-3 p-4', draggingId === item.id && 'opacity-50')}
                >
                    <button
                        type="button"
                        draggable
                        onDragStart={(event) => onDragStart(event, item.id)}
                        onDragEnd={onDragEnd}
                        onKeyDown={(event) => onKeyDown(event, item.id)}
                        aria-label={`Reorder ${item.title}`}
                        title="Drag to reorder, or use the up and down arrow keys"
                        className="text-muted-foreground hover:text-foreground mt-8 inline-flex size-8 shrink-0 cursor-grab items-center justify-center rounded-md active:cursor-grabbing"
                    >
                        <GripVertical className="size-4" />
                    </button>
                    <div className="min-w-0 flex-1">
                        <NavigationItemEditor
                            item={item}
                            linkTypes={linkTypes}
                            collections={collections}
                            pages={pages}
                            blogs={blogs}
                        />
                    </div>
                </li>
            ))}
        </ul>
    );
}

function AddNavigationItemForm({
    linkTypes,
    collections,
    pages,
    blogs,
}: {
    linkTypes: SelectOption[];
    collections: IdOption[];
    pages: IdOption[];
    blogs: IdOption[];
}) {
    const form = useForm({
        title: '',
        type: 'catalog',
        resource_id: null as number | null,
        url: '',
        position: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        form.post('/admin/navigation', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <form onSubmit={submit}>
            <FormCard title="Add menu item" description="Name the link, then choose what it should open.">
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <FormField label="Name" htmlFor="new-title" error={form.errors.title} required>
                        <Input
                            id="new-title"
                            value={form.data.title}
                            onChange={(event) => form.setData('title', event.target.value)}
                            placeholder="All products"
                        />
                    </FormField>
                    <FormField label="Link to" htmlFor="new-type" error={form.errors.type} required>
                        <select
                            id="new-type"
                            value={form.data.type}
                            onChange={(event) => {
                                form.setData('type', event.target.value);
                                form.setData('resource_id', null);
                                form.setData('url', '');
                            }}
                            className={selectClassName}
                        >
                            {linkTypes.map((type) => (
                                <option key={type.value} value={type.value}>
                                    {type.label}
                                </option>
                            ))}
                        </select>
                    </FormField>
                    <ResourceFields
                        type={form.data.type}
                        resourceId={form.data.resource_id}
                        url={form.data.url}
                        errors={form.errors}
                        collections={collections}
                        pages={pages}
                        blogs={blogs}
                        idPrefix="new"
                        onResourceId={(value) => form.setData('resource_id', value)}
                        onUrl={(value) => form.setData('url', value)}
                    />
                </div>
                <Button type="submit" disabled={form.processing}>
                    Add menu item
                </Button>
            </FormCard>
        </form>
    );
}

function NavigationItemEditor({
    item,
    linkTypes,
    collections,
    pages,
    blogs,
}: {
    item: NavigationItemRow;
    linkTypes: SelectOption[];
    collections: IdOption[];
    pages: IdOption[];
    blogs: IdOption[];
}) {
    const form = useForm({
        title: item.title,
        type: item.type,
        resource_id: item.resource_id,
        url: item.url ?? '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        form.put(`/admin/navigation/${item.id}`, { preserveScroll: true });
    };

    return (
        <div>
            <form onSubmit={submit} className="grid gap-3 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-end">
                <FormField label="Name" htmlFor={`title-${item.id}`} error={form.errors.title} required>
                    <Input
                        id={`title-${item.id}`}
                        value={form.data.title}
                        onChange={(event) => form.setData('title', event.target.value)}
                    />
                </FormField>
                <FormField label="Link to" htmlFor={`type-${item.id}`} error={form.errors.type} required>
                    <select
                        id={`type-${item.id}`}
                        value={form.data.type}
                        onChange={(event) => {
                            form.setData('type', event.target.value);
                            form.setData('resource_id', null);
                            form.setData('url', '');
                        }}
                        className={selectClassName}
                    >
                        {linkTypes.map((type) => (
                            <option key={type.value} value={type.value}>
                                {type.label}
                            </option>
                        ))}
                    </select>
                </FormField>
                <ResourceFields
                    type={form.data.type}
                    resourceId={form.data.resource_id}
                    url={form.data.url}
                    errors={form.errors}
                    collections={collections}
                    pages={pages}
                    blogs={blogs}
                    idPrefix={`item-${item.id}`}
                    onResourceId={(value) => form.setData('resource_id', value)}
                    onUrl={(value) => form.setData('url', value)}
                />
                <div className="flex gap-2">
                    <Button type="submit" size="sm" disabled={form.processing}>
                        Save
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => router.delete(`/admin/navigation/${item.id}`, { preserveScroll: true })}
                    >
                        Remove
                    </Button>
                </div>
            </form>
            <p className="text-muted-foreground mt-2 text-xs">Currently opens: {item.destination}</p>
        </div>
    );
}

function ResourceFields({
    type,
    resourceId,
    url,
    errors,
    collections,
    pages,
    blogs,
    idPrefix,
    onResourceId,
    onUrl,
}: {
    type: string;
    resourceId: number | null;
    url: string;
    errors: Partial<Record<string, string>>;
    collections: IdOption[];
    pages: IdOption[];
    blogs: IdOption[];
    idPrefix: string;
    onResourceId: (value: number | null) => void;
    onUrl: (value: string) => void;
}) {
    if (type === 'collection' || type === 'page' || type === 'blog') {
        const options = type === 'collection' ? collections : type === 'page' ? pages : blogs;
        const label = type === 'collection' ? 'Collection' : type === 'page' ? 'Page' : 'Blog';
        const empty = type === 'collection' ? 'No collections yet' : type === 'page' ? 'No pages yet' : 'No blogs yet';

        return (
            <FormField label={label} htmlFor={`${idPrefix}-resource`} error={errors.resource_id} required>
                <select
                    id={`${idPrefix}-resource`}
                    value={resourceId ?? ''}
                    onChange={(event) => onResourceId(event.target.value === '' ? null : Number(event.target.value))}
                    className={selectClassName}
                >
                    <option value="">{options.length === 0 ? empty : `Choose ${label.toLowerCase()}`}</option>
                    {options.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
            </FormField>
        );
    }

    if (type === 'url') {
        return (
            <FormField label="URL" htmlFor={`${idPrefix}-url`} error={errors.url} hint="A path like /products or a full https:// URL." required>
                <Input
                    id={`${idPrefix}-url`}
                    value={url}
                    onChange={(event) => onUrl(event.target.value)}
                    placeholder="/products"
                />
            </FormField>
        );
    }

    return <p className="text-muted-foreground self-end text-sm">{type === 'home' ? 'Opens the home page.' : 'Opens the product catalogue.'}</p>;
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
