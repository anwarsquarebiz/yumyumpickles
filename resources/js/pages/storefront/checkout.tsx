import { Breadcrumbs, PageIntro } from '@/components/yumyum';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import StorefrontLayout from '@/layouts/storefront-layout';
import { googleItem, trackGoogleEvent } from '@/lib/google-analytics';
import { moneyValue, newMetaEventId, trackMetaEvent } from '@/lib/meta-pixel';
import { unwrapList } from '@/lib/pickle';
import { type AddressRecord, type CartDetail, type SeoMeta, type SharedData, type ShippingQuote } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { type FormEventHandler, useEffect, useMemo } from 'react';

interface CheckoutPageProps {
    cartDetail: { data: CartDetail };
    shipping_methods: ShippingQuote[];
    addresses: { data: AddressRecord[] } | AddressRecord[];
    customer: { email: string; phone: string | null; name: string } | null;
    tax_rate_basis_points: number;
    guest_checkout_enabled: boolean;
    seo: SeoMeta;
}

interface AddressFields {
    first_name: string;
    last_name: string;
    company: string;
    address_line1: string;
    address_line2: string;
    city: string;
    province: string;
    postal_code: string;
    country_code: string;
    phone: string;
}

const emptyAddress = (): AddressFields => ({
    first_name: '',
    last_name: '',
    company: '',
    address_line1: '',
    address_line2: '',
    city: '',
    province: '',
    postal_code: '',
    country_code: 'IN',
    phone: '',
});

const unwrapAddresses = (value: CheckoutPageProps['addresses']): AddressRecord[] =>
    Array.isArray(value) ? value : (value.data ?? []);

export default function CheckoutPage({
    cartDetail: cart,
    shipping_methods,
    addresses,
    customer,
    tax_rate_basis_points,
    guest_checkout_enabled,
    seo,
}: CheckoutPageProps) {
    const { auth } = usePage<SharedData>().props;
    const saved = unwrapAddresses(addresses);
    const firstMethod = shipping_methods[0];
    const items = useMemo(() => unwrapList(cart.data.items), [cart.data.items]);

    const form = useForm({
        email: customer?.email ?? '',
        phone: customer?.phone ?? '',
        shipping_method_id: firstMethod?.id ?? 0,
        customer_note: '',
        save_address: Boolean(auth.user),
        billing_same_as_shipping: true,
        shipping: emptyAddress(),
        billing: emptyAddress(),
    });

    const selectedShipping = shipping_methods.find((method) => method.id === Number(form.data.shipping_method_id));
    const merchandise = cart.data.totals.total.amount;
    const shippingAmount = selectedShipping?.amount.amount ?? 0;
    const taxAmount = Math.round(((merchandise + shippingAmount) * tax_rate_basis_points) / 10000);
    const grandTotal = merchandise + shippingAmount + taxAmount;

    useEffect(() => {
        const contentIds = items.map((line) => String(line.variant.id));
        const value = cart.data.totals.total.decimal;

        trackMetaEvent(
            'InitiateCheckout',
            {
                content_ids: contentIds,
                content_type: 'product',
                value: moneyValue(value),
                currency: cart.data.currency,
                num_items: cart.data.totals.item_count,
                contents: items.map((line) => ({
                    id: String(line.variant.id),
                    quantity: line.quantity,
                    item_price: moneyValue(line.unit_price.decimal),
                })),
            },
            newMetaEventId(),
        );
        trackGoogleEvent('begin_checkout', {
            currency: cart.data.currency,
            value: moneyValue(value),
            items: items.map((line) =>
                googleItem(String(line.variant.id), line.unit_price.decimal, line.quantity, line.product.title),
            ),
        });
    }, [cart.data.id, cart.data.currency, items, cart.data.totals.item_count, cart.data.totals.total.decimal]);

    const format = (amount: number) =>
        new Intl.NumberFormat('en-US', { style: 'currency', currency: cart.data.currency }).format(amount / 100);

    const applySaved = (address: AddressRecord) => {
        form.setData('shipping', {
            first_name: address.first_name,
            last_name: address.last_name,
            company: address.company ?? '',
            address_line1: address.address_line1,
            address_line2: address.address_line2 ?? '',
            city: address.city,
            province: address.province ?? '',
            postal_code: address.postal_code,
            country_code: address.country_code,
            phone: address.phone ?? '',
        });
    };

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        form.post('/checkout');
    };

    const fieldError = (path: string) => (form.errors as Record<string, string>)[path];

    const needsLogin = !auth.user && !guest_checkout_enabled;

    return (
        <StorefrontLayout>
            <Head title={seo.title} />
            <PageIntro eyebrow="Last step" title="Checkout" copy="Guest checkout, saved addresses, and cash on delivery. We pack every jar as if it were travelling to family." />
            <Breadcrumbs items={[{ label: 'Cart', href: '/cart' }, { label: 'Checkout' }]} />

            <div className="section-shell pb-20">
                {needsLogin ? (
                    <div className="border-border rounded-2xl border p-8 text-center">
                        <p className="font-medium">Please sign in to complete your purchase.</p>
                        <Button asChild className="mt-4">
                            <Link href="/login">Sign in</Link>
                        </Button>
                    </div>
                ) : (
                    <form onSubmit={submit} className="grid gap-10 lg:grid-cols-[minmax(0,1fr)_320px]">
                        <div className="space-y-6">
                            <section className="border-border space-y-4 rounded-2xl border p-5">
                                <h2 className="text-lg font-extrabold">Contact</h2>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="email">Email</Label>
                                        <Input id="email" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} />
                                        {form.errors.email && <p className="text-sm text-red-600">{form.errors.email}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="phone">Phone</Label>
                                        <Input id="phone" value={form.data.phone} onChange={(event) => form.setData('phone', event.target.value)} />
                                    </div>
                                </div>
                            </section>

                            <section className="border-border space-y-4 rounded-2xl border p-5">
                                <h2 className="text-lg font-extrabold">Delivery address</h2>
                                {saved.length > 0 && (
                                    <div className="flex flex-wrap gap-2">
                                        {saved.map((address) => (
                                            <Button key={address.id} type="button" variant="outline" size="sm" onClick={() => applySaved(address)}>
                                                {address.one_line}
                                            </Button>
                                        ))}
                                    </div>
                                )}
                                <AddressForm prefix="shipping" values={form.data.shipping} onChange={(next) => form.setData('shipping', next)} error={fieldError} />
                            </section>

                            <section className="border-border space-y-4 rounded-2xl border p-5">
                                <h2 className="text-lg font-extrabold">Shipping method</h2>
                                {shipping_methods.length === 0 ? (
                                    <p className="text-muted-foreground text-sm">No shipping methods are configured.</p>
                                ) : (
                                    <div className="space-y-2">
                                        {shipping_methods.map((method) => (
                                            <label key={method.id} className="border-border flex items-center justify-between rounded-xl border px-4 py-3 text-sm">
                                                <span className="flex items-center gap-3">
                                                    <input
                                                        type="radio"
                                                        name="shipping_method_id"
                                                        checked={Number(form.data.shipping_method_id) === method.id}
                                                        onChange={() => form.setData('shipping_method_id', method.id)}
                                                    />
                                                    <span>
                                                        <span className="font-medium">{method.name}</span>
                                                        {method.description && <span className="text-muted-foreground block text-xs">{method.description}</span>}
                                                    </span>
                                                </span>
                                                <span>{method.amount.formatted}</span>
                                            </label>
                                        ))}
                                    </div>
                                )}
                                {form.errors.shipping_method_id && <p className="text-sm text-red-600">{form.errors.shipping_method_id}</p>}
                            </section>

                            {auth.user && (
                                <label className="flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        checked={form.data.save_address}
                                        onChange={(event) => form.setData('save_address', event.target.checked)}
                                    />
                                    Save this address to my account
                                </label>
                            )}

                            <Button type="submit" size="lg" className="w-full sm:w-auto" disabled={form.processing || shipping_methods.length === 0}>
                                Place COD order
                            </Button>
                        </div>

                        <aside className="border-border bg-card h-fit space-y-4 rounded-2xl border p-5">
                            <h2 className="text-xl font-extrabold">Your jars</h2>
                            <ul className="space-y-3 text-sm">
                                {items.map((line) => (
                                    <li key={line.id} className="flex justify-between gap-4">
                                        <span>
                                            {line.product.title} × {line.quantity}
                                        </span>
                                        <span>{line.line_total.formatted}</span>
                                    </li>
                                ))}
                            </ul>
                            <dl className="border-border space-y-2 border-t pt-4 text-sm">
                                <div className="flex justify-between">
                                    <dt>Subtotal</dt>
                                    <dd>{cart.data.totals.subtotal.formatted}</dd>
                                </div>
                                {cart.data.totals.discount.amount > 0 && (
                                    <div className="text-brand-leaf flex justify-between">
                                        <dt>Discount</dt>
                                        <dd>-{cart.data.totals.discount.formatted}</dd>
                                    </div>
                                )}
                                <div className="flex justify-between">
                                    <dt>Shipping</dt>
                                    <dd>{format(shippingAmount)}</dd>
                                </div>
                                {taxAmount > 0 && (
                                    <div className="flex justify-between">
                                        <dt>Tax</dt>
                                        <dd>{format(taxAmount)}</dd>
                                    </div>
                                )}
                                <div className="border-border flex justify-between border-t pt-3 text-base font-extrabold">
                                    <dt>To pay</dt>
                                    <dd>{format(grandTotal)}</dd>
                                </div>
                            </dl>
                            <Button asChild variant="ghost" className="w-full">
                                <Link href="/cart">Return to cart</Link>
                            </Button>
                        </aside>
                    </form>
                )}
            </div>
        </StorefrontLayout>
    );
}

function AddressForm({
    prefix,
    values,
    onChange,
    error,
}: {
    prefix: string;
    values: AddressFields;
    onChange: (next: AddressFields) => void;
    error: (path: string) => string | undefined;
}) {
    const set = (key: keyof AddressFields, value: string) => onChange({ ...values, [key]: value });

    const fields: Array<{ key: keyof AddressFields; label: string; className?: string }> = [
        { key: 'first_name', label: 'First name' },
        { key: 'last_name', label: 'Last name' },
        { key: 'address_line1', label: 'Address', className: 'sm:col-span-2' },
        { key: 'address_line2', label: 'Apartment, suite, etc.', className: 'sm:col-span-2' },
        { key: 'city', label: 'City' },
                        { key: 'province', label: 'State' },
                        { key: 'postal_code', label: 'Pincode' },
                        { key: 'country_code', label: 'Country' },
    ];

    return (
        <div className="grid gap-4 sm:grid-cols-2">
            {fields.map((field) => (
                <div key={field.key} className={`space-y-2 ${field.className ?? ''}`}>
                    <Label htmlFor={`${prefix}.${field.key}`}>{field.label}</Label>
                    <Input
                        id={`${prefix}.${field.key}`}
                        value={values[field.key]}
                        onChange={(event) => set(field.key, field.key === 'country_code' ? event.target.value.toUpperCase() : event.target.value)}
                    />
                    {error(`${prefix}.${field.key}`) && <p className="text-sm text-red-600">{error(`${prefix}.${field.key}`)}</p>}
                </div>
            ))}
        </div>
    );
}
