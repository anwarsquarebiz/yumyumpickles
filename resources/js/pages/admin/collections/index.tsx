import { StatusBadge } from '@/components/admin/status-badge';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem, type CollectionDetail, type Paginated } from '@/types';
import { Link, router } from '@inertiajs/react';
import { ImageOff, Plus, Search } from 'lucide-react';
import { type FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin' },
    { title: 'Collections', href: '/admin/collections' },
];

interface AdminCollectionsIndexProps {
    collections: Paginated<CollectionDetail>;
    filters: { search: string | null };
}

export default function AdminCollectionsIndex({ collections, filters }: AdminCollectionsIndexProps) {
    const [search, setSearch] = useState(filters.search ?? '');

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        router.get('/admin/collections', search ? { search } : {}, { preserveState: true, preserveScroll: true, replace: true });
    };

    return (
        <AdminLayout
            breadcrumbs={breadcrumbs}
            title="Collections"
            description="Group products into browsable collections."
            actions={
                <Button asChild>
                    <Link href="/admin/collections/create">
                        <Plus className="mr-1 size-4" />
                        New collection
                    </Link>
                </Button>
            }
        >
            <form onSubmit={submit} className="relative w-full sm:max-w-xs">
                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-400" />
                <Input
                    type="search"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder="Search collections"
                    aria-label="Search collections"
                    className="pl-9"
                />
            </form>

            {collections.data.length === 0 ? (
                <div className="rounded-xl border border-neutral-200 p-12 text-center dark:border-neutral-800">
                    <p className="font-medium">No collections yet</p>
                    <p className="text-muted-foreground mt-1 text-sm">Collections let customers browse related products together.</p>
                </div>
            ) : (
                <div className="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-800">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-neutral-50 text-left dark:bg-neutral-900">
                                <tr>
                                    <th scope="col" className="px-4 py-3 font-medium">
                                        Collection
                                    </th>
                                    <th scope="col" className="px-4 py-3 font-medium">
                                        Status
                                    </th>
                                    <th scope="col" className="px-4 py-3 font-medium">
                                        Products
                                    </th>
                                    <th scope="col" className="px-4 py-3 font-medium">
                                        Position
                                    </th>
                                    <th scope="col" className="px-4 py-3 text-right font-medium">
                                        <span className="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                {collections.data.map((collection) => (
                                    <tr key={collection.id} className="hover:bg-neutral-50 dark:hover:bg-neutral-900">
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-3">
                                                <div className="size-10 shrink-0 overflow-hidden rounded-md bg-neutral-100 dark:bg-neutral-800">
                                                    {collection.image_url ? (
                                                        <img src={collection.image_url} alt={collection.title} className="size-full object-cover" />
                                                    ) : (
                                                        <div className="flex size-full items-center justify-center text-neutral-400">
                                                            <ImageOff className="size-4" />
                                                        </div>
                                                    )}
                                                </div>
                                                <Link href={`/admin/collections/${collection.id}/edit`} className="font-medium hover:underline">
                                                    {collection.title}
                                                </Link>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <StatusBadge label={collection.status} status={collection.status} />
                                        </td>
                                        <td className="px-4 py-3">{collection.products_count ?? 0}</td>
                                        <td className="px-4 py-3">{collection.position}</td>
                                        <td className="px-4 py-3 text-right">
                                            <Button asChild variant="ghost" size="sm">
                                                <Link href={`/admin/collections/${collection.id}/edit`}>Edit</Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            <Pagination paginator={collections} />
        </AdminLayout>
    );
}
