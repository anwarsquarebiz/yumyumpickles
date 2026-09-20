import { StatusBadge } from '@/components/admin/status-badge';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem, type DashboardMetrics, type OrderDetail } from '@/types';
import { Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/admin' }];

interface AdminDashboardProps {
    metrics: DashboardMetrics;
    recent_orders: { data: OrderDetail[] } | OrderDetail[];
}

export default function AdminDashboard({ metrics, recent_orders }: AdminDashboardProps) {
    const orders = Array.isArray(recent_orders) ? recent_orders : recent_orders.data;

    const cards = [
        { label: 'Revenue', value: metrics.revenue.formatted },
        { label: 'Today', value: metrics.today_revenue.formatted },
        { label: 'Orders', value: String(metrics.orders) },
        { label: 'Open orders', value: String(metrics.open_orders) },
        { label: 'Customers', value: String(metrics.customers) },
        { label: 'Products', value: String(metrics.products) },
    ];

    return (
        <AdminLayout breadcrumbs={breadcrumbs} title="Dashboard" description="An overview of your store's performance.">
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {cards.map((card) => (
                    <div key={card.label} className="rounded-xl border border-neutral-200 p-5 dark:border-neutral-800">
                        <p className="text-muted-foreground text-sm">{card.label}</p>
                        <p className="mt-1 text-2xl font-semibold">{card.value}</p>
                    </div>
                ))}
            </div>

            <section className="rounded-xl border border-neutral-200 dark:border-neutral-800">
                <div className="flex items-center justify-between px-4 py-3">
                    <h2 className="font-medium">Recent orders</h2>
                    <Link href="/admin/orders" className="text-sm underline">
                        View all
                    </Link>
                </div>
                <ul className="divide-y divide-neutral-200 dark:divide-neutral-800">
                    {orders.map((order) => (
                        <li key={order.id} className="flex items-center justify-between px-4 py-3 text-sm">
                            <Link href={`/admin/orders/${order.id}`} className="font-medium hover:underline">
                                {order.order_number}
                            </Link>
                            <StatusBadge label={order.status_label} tone={order.status_tone} />
                            <span>{order.grand_total.formatted}</span>
                        </li>
                    ))}
                </ul>
            </section>
        </AdminLayout>
    );
}
