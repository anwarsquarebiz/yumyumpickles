import { StatusBadge } from '@/components/admin/status-badge';
import { Pagination } from '@/components/pagination';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem, type OrderDetail, type Paginated, type SelectOption } from '@/types';
import { Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { type FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin' },
    { title: 'Orders', href: '/admin/orders' },
];

interface AdminOrdersIndexProps {
    orders: Paginated<OrderDetail>;
    filters: { search: string | null; status: string | null };
    statuses: SelectOption[];
}

export default function AdminOrdersIndex({ orders, filters, statuses }: AdminOrdersIndexProps) {
    const [search, setSearch] = useState(filters.search ?? '');

    const apply = (overrides: Record<string, unknown>) => {
        const next = { ...filters, search, ...overrides };
        router.get('/admin/orders', Object.fromEntries(Object.entries(next).filter(([, value]) => value !== null && value !== '')), {
            preserveState: true,
            replace: true,
        });
    };

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        apply({});
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs} title="Orders" description="Manage customer orders and fulfilment.">
            <div className="flex flex-col gap-3 sm:flex-row">
                <form onSubmit={submit} className="relative w-full sm:max-w-xs">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-400" />
                    <Input className="pl-9" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search number or email" />
                </form>
                <select
                    value={filters.status ?? ''}
                    onChange={(event) => apply({ status: event.target.value || null })}
                    className="h-9 rounded-md border border-neutral-300 bg-transparent px-3 text-sm dark:border-neutral-700"
                    aria-label="Filter by status"
                >
                    <option value="">All statuses</option>
                    {statuses.map((status) => (
                        <option key={status.value} value={status.value}>
                            {status.label}
                        </option>
                    ))}
                </select>
            </div>

            <div className="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-800">
                <table className="w-full text-sm">
                    <thead className="bg-neutral-50 text-left dark:bg-neutral-900">
                        <tr>
                            <th className="px-4 py-3 font-medium">Order</th>
                            <th className="px-4 py-3 font-medium">Customer</th>
                            <th className="px-4 py-3 font-medium">Status</th>
                            <th className="px-4 py-3 font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                        {orders.data.map((order) => (
                            <tr key={order.id}>
                                <td className="px-4 py-3">
                                    <Link href={`/admin/orders/${order.id}`} className="font-medium hover:underline">
                                        {order.order_number}
                                    </Link>
                                </td>
                                <td className="px-4 py-3">
                                    <p>{order.customer_name ?? order.email}</p>
                                    {order.customer_name && <p className="text-muted-foreground text-xs">{order.email}</p>}
                                </td>
                                <td className="px-4 py-3">
                                    <StatusBadge label={order.status_label} tone={order.status_tone} />
                                </td>
                                <td className="px-4 py-3">{order.grand_total.formatted}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <Pagination paginator={orders} />
        </AdminLayout>
    );
}
