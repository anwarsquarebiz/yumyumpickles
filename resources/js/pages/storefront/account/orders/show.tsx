import { StatusBadge } from '@/components/admin/status-badge';
import StorefrontLayout from '@/layouts/storefront-layout';
import { type OrderDetail, type SeoMeta } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface AccountOrderShowProps {
    order: { data: OrderDetail };
    seo: SeoMeta;
}

export default function AccountOrderShow({ order, seo }: AccountOrderShowProps) {
    const item = order.data;

    return (
        <StorefrontLayout>
            <Head title={seo.title} />

            <div className="mx-auto w-full max-w-3xl px-4 py-12 sm:px-6">
                <Link href="/account/orders" className="text-sm text-neutral-600 hover:underline dark:text-neutral-400">
                    Back to orders
                </Link>
                <div className="mt-4 flex items-center justify-between gap-4">
                    <h1 className="text-3xl font-semibold tracking-tight">{item.order_number}</h1>
                    <StatusBadge label={item.status_label} tone={item.status_tone} />
                </div>

                <ul className="mt-8 space-y-2 text-sm">
                    {(item.items ?? []).map((line) => (
                        <li key={line.id} className="flex justify-between">
                            <span>
                                {line.product_title} × {line.quantity}
                            </span>
                            <span>{line.total.formatted}</span>
                        </li>
                    ))}
                </ul>

                <dl className="mt-6 space-y-1 text-sm">
                    <div className="flex justify-between">
                        <dt>Subtotal</dt>
                        <dd>{item.subtotal.formatted}</dd>
                    </div>
                    <div className="flex justify-between">
                        <dt>Shipping</dt>
                        <dd>{item.shipping.formatted}</dd>
                    </div>
                    <div className="flex justify-between font-semibold">
                        <dt>Total</dt>
                        <dd>{item.grand_total.formatted}</dd>
                    </div>
                </dl>

                <h2 className="mt-10 font-medium">Timeline</h2>
                <ol className="mt-3 space-y-2 text-sm">
                    {(item.timeline ?? []).map((event) => (
                        <li key={event.id}>
                            <span className="font-medium">{event.to_status}</span>
                            {event.note && <span className="text-muted-foreground"> — {event.note}</span>}
                            {event.created_at && <span className="block text-xs text-neutral-500">{event.created_at}</span>}
                        </li>
                    ))}
                </ol>
            </div>
        </StorefrontLayout>
    );
}
