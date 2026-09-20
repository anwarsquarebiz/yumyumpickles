import { StatusBadge } from '@/components/admin/status-badge';
import AdminLayout from '@/layouts/admin-layout';
import { type AddressRecord, type BreadcrumbItem, type OrderDetail } from '@/types';
import { Link } from '@inertiajs/react';

interface AdminCustomerShowProps {
    customer: { id: number; name: string; email: string; phone: string | null; created_at: string | null; orders_count: number };
    addresses: { data: AddressRecord[] } | AddressRecord[];
    orders: { data: OrderDetail[] } | OrderDetail[];
}

export default function AdminCustomerShow({ customer, addresses, orders }: AdminCustomerShowProps) {
    const addressList = Array.isArray(addresses) ? addresses : addresses.data;
    const orderList = Array.isArray(orders) ? orders : orders.data;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Customers', href: '/admin/customers' },
        { title: customer.name, href: `/admin/customers/${customer.id}` },
    ];

    return (
        <AdminLayout breadcrumbs={breadcrumbs} title={customer.name} description={customer.email}>
            <div className="grid gap-6 lg:grid-cols-2">
                <section className="rounded-xl border border-neutral-200 p-6 dark:border-neutral-800">
                    <h2 className="font-medium">Addresses</h2>
                    <ul className="mt-3 space-y-2 text-sm">
                        {addressList.map((address) => (
                            <li key={address.id}>{address.one_line}</li>
                        ))}
                    </ul>
                </section>
                <section className="rounded-xl border border-neutral-200 p-6 dark:border-neutral-800">
                    <h2 className="font-medium">Orders</h2>
                    <ul className="mt-3 space-y-2 text-sm">
                        {orderList.map((order) => (
                            <li key={order.id} className="flex items-center justify-between">
                                <Link href={`/admin/orders/${order.id}`} className="hover:underline">
                                    {order.order_number}
                                </Link>
                                <StatusBadge label={order.status_label} tone={order.status_tone} />
                            </li>
                        ))}
                    </ul>
                </section>
            </div>
        </AdminLayout>
    );
}
