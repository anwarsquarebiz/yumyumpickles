import { Breadcrumbs, PageIntro, ProductCard } from '@/components/yumyum';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import StorefrontLayout from '@/layouts/storefront-layout';
import { toPickle, unwrapData, unwrapList } from '@/lib/pickle';
import { type CartDetail, type SeoMeta, type SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Minus, Plus, X } from 'lucide-react';
import { type FormEventHandler, useState } from 'react';

interface CartPageProps {
    cartDetail: { data: CartDetail } | null;
    seo: SeoMeta;
}

export default function CartPage({ cartDetail, seo }: CartPageProps) {
    const catalog = usePage<SharedData>().props.catalog ?? [];
    const detail = unwrapData(cartDetail);
    const items = unwrapList(detail?.items);
    const recs = catalog.map(toPickle).filter((product) => product.inStock && !items.some((line) => line.product.slug === product.id)).slice(0, 4);
    const [pin, setPin] = useState('');
    const [shipNote, setShipNote] = useState('');

    return (
        <StorefrontLayout>
            <Head title={seo.title}>{seo.description && <meta name="description" content={seo.description} />}</Head>
            <PageIntro eyebrow="Almost at the table" title="Your cart" copy="Add a coupon, check delivery, or complete the trio — Buy 2 Get 1 applies automatically on 3 jars." />
            <Breadcrumbs items={[{ label: 'Cart' }]} />
            <div className="section-shell grid gap-10 pb-20 lg:grid-cols-[minmax(0,1fr)_340px]">
                <div className="space-y-5">
                    {items.length === 0 ? (
                        <div className="border-border rounded-2xl border border-dashed p-12 text-center">
                            <p className="text-lg font-extrabold">Your cart is empty</p>
                            <Button asChild className="mt-4">
                                <Link href="/shop">Shop pickles</Link>
                            </Button>
                        </div>
                    ) : (
                        items.map((line) => (
                            <div key={line.id} className="border-border grid grid-cols-[96px_1fr_auto] gap-4 rounded-2xl border p-4">
                                {line.image ? <img src={line.image.url} alt="" className="size-24 rounded-lg object-cover" /> : <div className="bg-muted size-24 rounded-lg" />}
                                <div>
                                    <Link href={line.product.url} className="hover:text-primary font-extrabold">
                                        {line.product.title}
                                    </Link>
                                    <p className="text-muted-foreground text-sm">{line.variant.options.join(' / ')}</p>
                                    <div className="mt-3 flex items-center gap-2">
                                        <Button variant="outline" size="icon" className="size-8" onClick={() => router.patch(`/cart/items/${line.id}`, { quantity: line.quantity - 1 }, { preserveScroll: true, preserveState: true })}>
                                            <Minus />
                                        </Button>
                                        <span className="w-6 text-center">{line.quantity}</span>
                                        <Button variant="outline" size="icon" className="size-8" onClick={() => router.patch(`/cart/items/${line.id}`, { quantity: line.quantity + 1 }, { preserveScroll: true, preserveState: true })}>
                                            <Plus />
                                        </Button>
                                    </div>
                                </div>
                                <div className="text-right">
                                    <b>{line.line_total.formatted}</b>
                                    <Button variant="ghost" size="icon" className="mt-2" onClick={() => router.delete(`/cart/items/${line.id}`, { preserveScroll: true })}>
                                        <X />
                                    </Button>
                                </div>
                            </div>
                        ))
                    )}
                </div>
                <aside className="border-border bg-card h-fit space-y-4 rounded-2xl border p-5">
                    <h2 className="text-xl font-extrabold">Order summary</h2>
                    <Row label="Subtotal" value={detail?.totals.subtotal.formatted ?? '₹0'} />
                    {detail && detail.totals.discount.amount > 0 && <Row label={`Discount ${detail.totals.coupon_code ?? ''}`} value={`−${detail.totals.discount.formatted}`} />}
                    <p className="text-muted-foreground text-xs">Shipping is calculated at checkout. Free above ₹499.</p>
                    <div className="flex justify-between text-lg font-extrabold">
                        <span>Total</span>
                        <span>{detail?.totals.total.formatted ?? '₹0'}</span>
                    </div>
                    <CouponForm coupon={detail?.coupon ?? null} discounted={(detail?.totals.discount.amount ?? 0) > 0} />
                    <p className="text-muted-foreground text-xs">Try YUMYUM10, FIRST50, YUMFAM or FREESHIP.</p>
                    <form
                        className="grid gap-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            setShipNote(pin.length === 6 ? 'Most metros: 3–5 working days. COD available.' : 'Enter a 6-digit pincode.');
                        }}
                    >
                        <Input value={pin} onChange={(event) => setPin(event.target.value.replace(/\D/g, ''))} maxLength={6} placeholder="Shipping pincode" aria-label="Shipping pincode" />
                        <Button type="submit" variant="outline">
                            Calculate shipping
                        </Button>
                    </form>
                    {shipNote && <p className="text-brand-leaf text-sm">{shipNote}</p>}
                    <Button asChild size="lg" className="w-full" disabled={items.length === 0}>
                        <Link href="/checkout">Checkout</Link>
                    </Button>
                </aside>
            </div>
            {recs.length > 0 && (
                <section className="bg-muted py-16">
                    <div className="section-shell">
                        <h2 className="text-3xl font-extrabold">Complete the table</h2>
                        <p className="text-muted-foreground mt-2">Jars that pair beautifully with what you already chose.</p>
                        <div className="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                            {recs.map((product) => (
                                <ProductCard key={product.id} product={product} />
                            ))}
                        </div>
                    </div>
                </section>
            )}
        </StorefrontLayout>
    );
}

function Row({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex justify-between text-sm">
            <span>{label}</span>
            <span>{value}</span>
        </div>
    );
}

function CouponForm({ coupon, discounted }: { coupon: CartDetail['coupon']; discounted: boolean }) {
    const form = useForm<{ code: string }>({ code: '' });
    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        form.post('/cart/coupon', { preserveScroll: true, onSuccess: () => form.reset('code') });
    };

    if (coupon && discounted) {
        return (
            <div className="flex items-center justify-between gap-2 text-sm">
                <span className="text-brand-leaf font-bold">
                    {coupon.code} · {coupon.value}
                </span>
                <Button variant="ghost" onClick={() => router.delete('/cart/coupon', { preserveScroll: true })}>
                    Remove
                </Button>
            </div>
        );
    }

    return (
        <form className="flex gap-2" onSubmit={submit}>
            <Input value={form.data.code} onChange={(event) => form.setData('code', event.target.value.toUpperCase())} placeholder="Coupon code" aria-label="Coupon code" />
            <Button type="submit" variant="outline" disabled={form.processing}>
                Apply
            </Button>
        </form>
    );
}
