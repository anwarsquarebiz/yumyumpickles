import { Pagination } from '@/components/pagination';
import { AccountNav } from '@/components/account-nav';
import { Breadcrumbs, PageIntro } from '@/components/yumyum';
import StorefrontLayout from '@/layouts/storefront-layout';
import { type OrderDetail, type Paginated, type SeoMeta } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface AccountOrdersProps {
    orders: Paginated<OrderDetail>;
    seo: SeoMeta;
}

export default function AccountOrders({ orders, seo }: AccountOrdersProps) {
    return (
        <StorefrontLayout>
            <Head title={seo.title} />
            <PageIntro eyebrow="Your jars on the way" title="Order history" copy="Track every batch we packed for your kitchen." />
            <Breadcrumbs items={[{ label: 'Account', href: '/account' }, { label: 'Orders' }]} />
            <div className="section-shell pb-20">
                <AccountNav current="orders" />

                {orders.data.length === 0 ? (
                    <p className="text-muted-foreground mt-8 text-sm">You have not placed any orders yet.</p>
                ) : (
                    <ul className="mt-8 divide-y divide-neutral-200 dark:divide-neutral-800">
                        {orders.data.map((order) => (
                            <li key={order.id} className="flex items-center justify-between py-4">
                                <div>
                                    <Link href={`/account/orders/${order.id}`} className="font-medium hover:underline">
                                        {order.order_number}
                                    </Link>
                                    <p className="text-muted-foreground text-sm">
                                        {order.status_label} · {order.grand_total.formatted}
                                    </p>
                                </div>
                                <Link href={`/account/orders/${order.id}`} className="text-sm underline">
                                    View
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination paginator={orders} />
            </div>
        </StorefrontLayout>
    );
}
