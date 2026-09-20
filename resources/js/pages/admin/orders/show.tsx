import { StatusBadge } from '@/components/admin/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem, type OrderDetail } from '@/types';
import { Link, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

interface AdminOrderShowProps {
    order: { data: OrderDetail };
}

function addressesMatch(left: Record<string, string | null> | null, right: Record<string, string | null> | null): boolean {
    return JSON.stringify(left ?? null) === JSON.stringify(right ?? null);
}

export default function AdminOrderShow({ order }: AdminOrderShowProps) {
    const item = order.data;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Orders', href: '/admin/orders' },
        { title: item.order_number, href: `/admin/orders/${item.id}` },
    ];
    const billingSameAsShipping = addressesMatch(item.shipping_address, item.billing_address);

    const statusForm = useForm({ status: item.allowed_transitions[0]?.value ?? item.status, note: '' });
    const refundForm = useForm({ amount: item.refundable.decimal, reason: '', restock: false });

    const updateStatus: FormEventHandler = (event) => {
        event.preventDefault();
        statusForm.patch(`/admin/orders/${item.id}/status`);
    };

    const refund: FormEventHandler = (event) => {
        event.preventDefault();
        refundForm.post(`/admin/orders/${item.id}/refunds`);
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs} title={item.order_number} description={item.email}>
            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <section className="rounded-xl border border-neutral-200 p-6 dark:border-neutral-800">
                        <div className="mb-4 flex items-center justify-between">
                            <h2 className="font-medium">Items</h2>
                            <StatusBadge label={item.status_label} tone={item.status_tone} />
                        </div>
                        <ul className="space-y-2 text-sm">
                            {(item.items ?? []).map((line) => (
                                <li key={line.id} className="flex justify-between">
                                    <span>
                                        {line.product_title}
                                        {line.variant_title ? ` · ${line.variant_title}` : ''} × {line.quantity}
                                    </span>
                                    <span>{line.total.formatted}</span>
                                </li>
                            ))}
                        </ul>
                        <dl className="mt-4 space-y-1 border-t border-neutral-200 pt-4 text-sm dark:border-neutral-800">
                            <div className="flex justify-between">
                                <dt>Subtotal</dt>
                                <dd>{item.subtotal.formatted}</dd>
                            </div>
                            {item.discount.amount > 0 && (
                                <div className="flex justify-between text-emerald-600">
                                    <dt>{item.coupon_code ? `Discount (${item.coupon_code})` : 'Discount'}</dt>
                                    <dd>-{item.discount.formatted}</dd>
                                </div>
                            )}
                            <div className="flex justify-between">
                                <dt>Shipping{item.shipping_method_name ? ` · ${item.shipping_method_name}` : ''}</dt>
                                <dd>{item.shipping.formatted}</dd>
                            </div>
                            {item.tax.amount > 0 && (
                                <div className="flex justify-between">
                                    <dt>Tax</dt>
                                    <dd>{item.tax.formatted}</dd>
                                </div>
                            )}
                            <div className="flex justify-between font-semibold">
                                <dt>Total</dt>
                                <dd>{item.grand_total.formatted}</dd>
                            </div>
                            {item.refunded.amount > 0 && (
                                <div className="flex justify-between text-neutral-500">
                                    <dt>Refunded</dt>
                                    <dd>-{item.refunded.formatted}</dd>
                                </div>
                            )}
                        </dl>
                    </section>

                    {(item.payments ?? []).length > 0 && (
                        <section className="rounded-xl border border-neutral-200 p-6 dark:border-neutral-800">
                            <h2 className="font-medium">Payments</h2>
                            <ul className="mt-3 space-y-2 text-sm">
                                {(item.payments ?? []).map((payment) => (
                                    <li key={payment.id} className="flex justify-between gap-4">
                                        <span>
                                            {payment.status_label}
                                            {payment.gateway ? ` · ${payment.gateway}` : ''}
                                            {payment.paid_at ? ` · ${payment.paid_at}` : ''}
                                            {payment.failure_reason ? ` — ${payment.failure_reason}` : ''}
                                        </span>
                                        <span>{payment.amount.formatted}</span>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}

                    <section className="rounded-xl border border-neutral-200 p-6 dark:border-neutral-800">
                        <h2 className="font-medium">Timeline</h2>
                        <ol className="mt-3 space-y-2 text-sm">
                            {(item.timeline ?? []).map((event) => (
                                <li key={event.id}>
                                    <span className="font-medium">{event.to_status}</span>
                                    {event.note && <span className="text-muted-foreground"> — {event.note}</span>}
                                    <span className="block text-xs text-neutral-500">{event.created_at}</span>
                                </li>
                            ))}
                        </ol>
                    </section>
                </div>

                <div className="space-y-6">
                    <section className="space-y-4 rounded-xl border border-neutral-200 p-6 dark:border-neutral-800">
                        <h2 className="font-medium">Customer</h2>
                        <dl className="space-y-3 text-sm">
                            <div>
                                <dt className="text-muted-foreground text-xs font-medium tracking-wide uppercase">Contact</dt>
                                <dd className="mt-1 space-y-0.5">
                                    {item.customer ? (
                                        <Link href={`/admin/customers/${item.customer.id}`} className="font-medium hover:underline">
                                            {item.customer_name ?? item.customer.name}
                                        </Link>
                                    ) : (
                                        item.customer_name && <p className="font-medium">{item.customer_name}</p>
                                    )}
                                    <p>{item.email}</p>
                                    {item.phone && <p>{item.phone}</p>}
                                    {!item.customer && <p className="text-muted-foreground text-xs">Guest checkout</p>}
                                </dd>
                            </div>

                            {item.shipping_address_lines.length > 0 && (
                                <div>
                                    <dt className="text-muted-foreground text-xs font-medium tracking-wide uppercase">Shipping address</dt>
                                    <dd className="mt-1 whitespace-pre-line">
                                        {item.shipping_address_lines.join('\n')}
                                        {item.shipping_address?.phone && item.shipping_address.phone !== item.phone && (
                                            <>
                                                {'\n'}
                                                {item.shipping_address.phone}
                                            </>
                                        )}
                                    </dd>
                                </div>
                            )}

                            {(item.billing_address_lines.length > 0 || billingSameAsShipping) && (
                                <div>
                                    <dt className="text-muted-foreground text-xs font-medium tracking-wide uppercase">Billing address</dt>
                                    <dd className="mt-1 whitespace-pre-line">
                                        {billingSameAsShipping ? 'Same as shipping address' : item.billing_address_lines.join('\n')}
                                    </dd>
                                </div>
                            )}

                            {item.shipping_method_name && (
                                <div>
                                    <dt className="text-muted-foreground text-xs font-medium tracking-wide uppercase">Shipping method</dt>
                                    <dd className="mt-1">{item.shipping_method_name}</dd>
                                </div>
                            )}

                            {item.customer_note && (
                                <div>
                                    <dt className="text-muted-foreground text-xs font-medium tracking-wide uppercase">Customer note</dt>
                                    <dd className="mt-1 whitespace-pre-line">{item.customer_note}</dd>
                                </div>
                            )}

                            {item.placed_at && (
                                <div>
                                    <dt className="text-muted-foreground text-xs font-medium tracking-wide uppercase">Placed</dt>
                                    <dd className="mt-1">{item.placed_at}</dd>
                                </div>
                            )}
                        </dl>
                    </section>

                    {item.allowed_transitions.length > 0 && (
                        <form onSubmit={updateStatus} className="space-y-3 rounded-xl border border-neutral-200 p-6 dark:border-neutral-800">
                            <h2 className="font-medium">Update status</h2>
                            <select
                                value={statusForm.data.status}
                                onChange={(event) => statusForm.setData('status', event.target.value)}
                                className="h-9 w-full rounded-md border border-neutral-300 bg-transparent px-3 text-sm dark:border-neutral-700"
                            >
                                {item.allowed_transitions.map((status) => (
                                    <option key={status.value} value={status.value}>
                                        {status.label}
                                    </option>
                                ))}
                            </select>
                            <Input placeholder="Note" value={statusForm.data.note} onChange={(event) => statusForm.setData('note', event.target.value)} />
                            <Button type="submit" disabled={statusForm.processing} className="w-full">
                                Update
                            </Button>
                        </form>
                    )}

                    {item.is_refundable && (
                        <form onSubmit={refund} className="space-y-3 rounded-xl border border-neutral-200 p-6 dark:border-neutral-800">
                            <h2 className="font-medium">Refund</h2>
                            <Input
                                type="number"
                                step="0.01"
                                min="0.01"
                                value={refundForm.data.amount}
                                onChange={(event) => refundForm.setData('amount', event.target.value)}
                            />
                            <Input placeholder="Reason" value={refundForm.data.reason} onChange={(event) => refundForm.setData('reason', event.target.value)} />
                            <label className="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    checked={refundForm.data.restock}
                                    onChange={(event) => refundForm.setData('restock', event.target.checked)}
                                />
                                Restock inventory
                            </label>
                            <Button type="submit" variant="outline" disabled={refundForm.processing} className="w-full">
                                Issue refund
                            </Button>
                        </form>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
