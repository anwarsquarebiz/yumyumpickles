import { Button } from '@/components/ui/button';
import { Breadcrumbs, PageIntro } from '@/components/yumyum';
import StorefrontLayout from '@/layouts/storefront-layout';
import { googleItem, trackGoogleEvent } from '@/lib/google-analytics';
import { moneyValue, trackMetaEvent } from '@/lib/meta-pixel';
import { type OrderDetail, type SeoMeta } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

interface CheckoutCompleteProps {
    order: { data: OrderDetail };
    seo: SeoMeta;
}

export default function CheckoutComplete({ order, seo }: CheckoutCompleteProps) {
    const item = order.data;
    const tracked = useRef(false);

    useEffect(() => {
        if (tracked.current || !item.is_paid) {
            return;
        }

        tracked.current = true;

        const lines = item.items ?? [];
        const contentIds = lines.map((line) => String(line.product_variant_id ?? line.product_id ?? line.id));
        const items = lines.map((line) =>
            googleItem(
                String(line.product_variant_id ?? line.product_id ?? line.id),
                line.unit_price.decimal,
                line.quantity,
                line.product_title,
            ),
        );

        if (item.meta_event_id) {
            trackMetaEvent(
                'Purchase',
                {
                    content_ids: contentIds,
                    content_type: 'product',
                    value: moneyValue(item.grand_total.decimal),
                    currency: item.currency,
                    num_items: lines.reduce((sum, line) => sum + line.quantity, 0),
                    contents: lines.map((line) => ({
                        id: String(line.product_variant_id ?? line.product_id ?? line.id),
                        quantity: line.quantity,
                        item_price: moneyValue(line.unit_price.decimal),
                    })),
                    order_id: item.order_number,
                },
                item.meta_event_id,
            );
        }

        trackGoogleEvent('purchase', {
            transaction_id: item.order_number,
            currency: item.currency,
            value: moneyValue(item.grand_total.decimal),
            items,
        });
    }, [item]);

    return (
        <StorefrontLayout>
            <Head title={seo.title} />
            <PageIntro eyebrow="The jar is on its way" title="Thank you" copy={`Order ${item.order_number} is ${item.status_label.toLowerCase()}. A confirmation will be sent to ${item.email}.`} />
            <Breadcrumbs items={[{ label: 'Account', href: '/account' }, { label: item.order_number }]} />

            <div className="section-shell max-w-2xl pb-20">

                <div className="mt-8 rounded-xl border border-neutral-200 p-6 text-left text-sm dark:border-neutral-800">
                    <ul className="space-y-2">
                        {(item.items ?? []).map((line) => (
                            <li key={line.id} className="flex justify-between">
                                <span>
                                    {line.product_title} × {line.quantity}
                                </span>
                                <span>{line.total.formatted}</span>
                            </li>
                        ))}
                    </ul>
                    <p className="mt-4 flex justify-between font-semibold">
                        <span>Total</span>
                        <span>{item.grand_total.formatted}</span>
                    </p>
                </div>

                <div className="mt-8 flex justify-center gap-3">
                    <Button asChild>
                        <Link href="/shop">Continue shopping</Link>
                    </Button>
                    <Button asChild variant="outline">
                        <Link href="/account/orders">Order history</Link>
                    </Button>
                </div>
            </div>
        </StorefrontLayout>
    );
}
