import { StatusBadge } from '@/components/admin/status-badge';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin-layout';
import { type AdminCoupon, type BreadcrumbItem, type Paginated } from '@/types';
import { Link, router } from '@inertiajs/react';
import { Plus, Search } from 'lucide-react';
import { type FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin' },
    { title: 'Coupons', href: '/admin/coupons' },
];

interface AdminCouponsIndexProps {
    coupons: Paginated<AdminCoupon>;
    filters: { search: string | null; status: string | null };
}

const toneFor = (label: string): string => {
    if (label === 'Active') {
        return 'emerald';
    }

    if (label === 'Scheduled') {
        return 'blue';
    }

    if (label === 'Expired' || label === 'Limit reached') {
        return 'rose';
    }

    return 'zinc';
};

export default function AdminCouponsIndex({ coupons, filters }: AdminCouponsIndexProps) {
    const [search, setSearch] = useState(filters.search ?? '');

    const apply = (overrides: Record<string, unknown>) => {
        const next = { ...filters, search, ...overrides };

        router.get('/admin/coupons', Object.fromEntries(Object.entries(next).filter(([, value]) => value !== null && value !== '')), {
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
            title="Coupons"
            description="Fixed and percentage discounts with usage limits."
            actions={
                <Button asChild>
                    <Link href="/admin/coupons/create">
                        <Plus className="mr-1 size-4" />
                        New discount
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
                        placeholder="Search codes"
                        aria-label="Search discount codes"
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
                    <option value="active">Active</option>
                    <option value="inactive">Disabled</option>
                </select>
            </div>

            {coupons.data.length === 0 ? (
                <div className="rounded-xl border border-neutral-200 p-12 text-center dark:border-neutral-800">
                    <p className="font-medium">No discount codes yet</p>
                    <p className="text-muted-foreground mt-1 text-sm">Create a code to offer a fixed or percentage discount at checkout.</p>
                </div>
            ) : (
                <div className="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-800">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-neutral-50 text-left dark:bg-neutral-900">
                                <tr>
                                    <th scope="col" className="px-4 py-3 font-medium">
                                        Code
                                    </th>
                                    <th scope="col" className="px-4 py-3 font-medium">
                                        Value
                                    </th>
                                    <th scope="col" className="px-4 py-3 font-medium">
                                        Status
                                    </th>
                                    <th scope="col" className="px-4 py-3 font-medium">
                                        Uses
                                    </th>
                                    <th scope="col" className="px-4 py-3 text-right font-medium">
                                        <span className="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                {coupons.data.map((coupon) => (
                                    <tr key={coupon.id} className="hover:bg-neutral-50 dark:hover:bg-neutral-900">
                                        <td className="px-4 py-3">
                                            <Link href={`/admin/coupons/${coupon.id}/edit`} className="font-medium hover:underline">
                                                {coupon.code}
                                            </Link>
                                            {coupon.description && (
                                                <p className="text-muted-foreground truncate text-xs">{coupon.description}</p>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">{coupon.display_value}</td>
                                        <td className="px-4 py-3">
                                            <StatusBadge label={coupon.status_label} tone={toneFor(coupon.status_label)} />
                                        </td>
                                        <td className="px-4 py-3">
                                            {coupon.used_count}
                                            {coupon.usage_limit !== null ? ` / ${coupon.usage_limit}` : ''}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <Button asChild variant="ghost" size="sm">
                                                <Link href={`/admin/coupons/${coupon.id}/edit`}>Edit</Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            <Pagination paginator={coupons} />
        </AdminLayout>
    );
}
