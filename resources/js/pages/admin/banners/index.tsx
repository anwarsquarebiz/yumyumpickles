import { StatusBadge } from '@/components/admin/status-badge';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem, type HomeBanner, type Paginated } from '@/types';
import { Link, router } from '@inertiajs/react';
import { ImageOff, Plus, Search } from 'lucide-react';
import { type FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin' },
    { title: 'Banners', href: '/admin/banners' },
];

interface AdminBannersIndexProps {
    banners: Paginated<HomeBanner>;
    filters: { search: string | null };
}

export default function AdminBannersIndex({ banners, filters }: AdminBannersIndexProps) {
    const [search, setSearch] = useState(filters.search ?? '');

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        router.get('/admin/banners', search ? { search } : {}, { preserveState: true, preserveScroll: true, replace: true });
    };

    return (
        <AdminLayout
            breadcrumbs={breadcrumbs}
            title="Banners"
            description="Full-width home page slides. Drag order with the position field."
            actions={
                <Button asChild>
                    <Link href="/admin/banners/create">
                        <Plus className="mr-1 size-4" />
                        New banner
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
                    placeholder="Search banners"
                    aria-label="Search banners"
                    className="pl-9"
                />
            </form>

            {banners.data.length === 0 ? (
                <div className="rounded-xl border border-neutral-200 p-12 text-center dark:border-neutral-800">
                    <p className="font-medium">No banners yet</p>
                    <p className="text-muted-foreground mt-1 text-sm">Published banners appear as a full-width slider on the home page.</p>
                </div>
            ) : (
                <div className="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-800">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-neutral-50 text-left dark:bg-neutral-900">
                                <tr>
                                    <th scope="col" className="px-4 py-3 font-medium">
                                        Banner
                                    </th>
                                    <th scope="col" className="px-4 py-3 font-medium">
                                        Status
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
                                {banners.data.map((banner) => (
                                    <tr key={banner.id}>
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-3">
                                                {banner.image_url ? (
                                                    <img
                                                        src={banner.image_url}
                                                        alt=""
                                                        className="size-14 rounded-md object-cover"
                                                    />
                                                ) : (
                                                    <span className="flex size-14 items-center justify-center rounded-md bg-neutral-100 text-neutral-400 dark:bg-neutral-800">
                                                        <ImageOff className="size-5" />
                                                    </span>
                                                )}
                                                <div>
                                                    <Link href={`/admin/banners/${banner.id}/edit`} className="font-medium hover:underline">
                                                        {banner.title}
                                                    </Link>
                                                    {banner.subtitle && (
                                                        <p className="text-muted-foreground line-clamp-1 text-xs">{banner.subtitle}</p>
                                                    )}
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <StatusBadge label={banner.status} status={banner.status} />
                                        </td>
                                        <td className="px-4 py-3">{banner.position}</td>
                                        <td className="px-4 py-3 text-right">
                                            <Button asChild variant="ghost" size="sm">
                                                <Link href={`/admin/banners/${banner.id}/edit`}>Edit</Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            <Pagination paginator={banners} />
        </AdminLayout>
    );
}
