import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Breadcrumbs, ProductCard, RecentlyViewed, Stars } from '@/components/yumyum';
import StorefrontLayout from '@/layouts/storefront-layout';
import { addManyToCart, addToCart, formatPrice, originalPriceForWeight, priceForWeight, rememberProduct, toPickle, unwrapData, unwrapList, variantIdForWeight, writeWishlist, readWishlist } from '@/lib/pickle';
import { type ProductDetail, type ProductSummary, type SeoMeta, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Heart, Minus, Plus, Share2, ShieldCheck, Truck } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

interface ProductShowProps {
    product: { data: ProductDetail };
    related?: { data: ProductSummary[] } | ProductSummary[];
    seo: SeoMeta;
}

export default function ProductPage({ product, related, seo }: ProductShowProps) {
    const item = unwrapData(product);
    const variants = unwrapList(item.variants);
    const detailImages = unwrapList(item.images);
    const { catalog = [], content } = usePage<SharedData>().props;
    const live = catalog.map(toPickle);
    const pickle = toPickle({
        ...item,
        title: item.title,
        name: item.name ?? item.title,
        slug: item.slug,
        description: item.description,
        story: item.story ?? item.metadata?.story ?? item.description,
        spice: item.spice ?? item.metadata?.spice,
        ingredients: item.ingredients ?? item.metadata?.ingredients,
        nutrition: item.nutrition ?? item.metadata?.nutrition,
        pairs_with: item.pairs_with ?? item.metadata?.pairs_with,
        badge: item.badge ?? item.metadata?.badge,
        in_stock: item.in_stock,
        inStock: item.in_stock,
        price_from: item.variants?.[0]?.price ?? { amount: 0, currency: 'INR', formatted: '₹0', decimal: '0' },
        compare_at_price: item.variants?.[0]?.compare_at_price ?? null,
        on_sale: false,
        variant_count: item.variants?.length ?? 0,
        image: detailImages[0] ? { url: detailImages[0].url, alt: detailImages[0].alt ?? item.title } : null,
        image_url: item.gallery?.[0]?.src ?? detailImages[0]?.url,
        weights: variants.map((variant) => ({
            label: variant.option1 || variant.display_title || variant.title,
            price: Number(variant.price.decimal),
            originalPrice: Number((variant.compare_at_price ?? variant.price).decimal),
            variant_id: variant.id,
        })),
        default_variant_id: item.default_variant_id ?? variants[0]?.id,
        rating: item.rating,
        reviews: item.review_count,
        vendor: item.vendor,
        product_type: item.product_type,
        url: item.url,
        category: item.category ?? item.product_type,
    });
    pickle.gallery = item.gallery?.length
        ? item.gallery
        : detailImages.map((image) => ({ src: image.url, alt: image.alt ?? item.title, position: 'center' }));

    const faqs = content?.faqs ?? [];
    const [active, setActive] = useState(0);
    const [weight, setWeight] = useState(pickle.weight);
    const [quantity, setQuantity] = useState(1);
    const [pin, setPin] = useState('');
    const [delivery, setDelivery] = useState('');
    const [saved, setSaved] = useState(false);
    const [zoom, setZoom] = useState({ x: 50, y: 50, on: false });

    useEffect(() => {
        rememberProduct(pickle.id);
        setWeight(pickle.weight);
        setActive(0);
        setSaved(readWishlist().includes(pickle.id));
    }, [pickle.id, pickle.weight]);

    const price = priceForWeight(pickle, weight);
    const original = originalPriceForWeight(pickle, weight);
    const gallery = pickle.gallery ?? [];
    const current = gallery[active] ?? gallery[0];
    const reviews = item.customer_reviews ?? [];
    const relatedItems = unwrapList(related).map(toPickle);
    const together = live.filter((entry) => entry.id !== pickle.id && entry.inStock).slice(0, 2);
    const togetherTotal = [pickle, ...together].reduce((sum, entry) => sum + entry.price, 0);

    function share() {
        const url = window.location.href;
        if (navigator.share) {
            void navigator.share({ title: pickle.name, text: pickle.description, url });
            return;
        }
        void navigator.clipboard.writeText(url);
    }

    const nutrition = (pickle.nutrition ?? []).map((row) => (typeof row === 'string' ? { label: row, value: '' } : row));

    return (
        <StorefrontLayout>
            <Head title={seo.title}>{seo.description && <meta name="description" content={seo.description} />}</Head>
            <Breadcrumbs items={[{ label: 'Shop', href: '/shop' }, { label: pickle.name }]} />
            <section className="section-shell grid gap-10 pb-16 lg:grid-cols-2">
                <div className="grid gap-3 sm:grid-cols-[88px_1fr]">
                    <div className="order-2 flex gap-2 sm:order-1 sm:flex-col">
                        {gallery.map((shot, index) => (
                            <button key={`${shot.src}-${index}`} className={`aspect-square w-20 overflow-hidden rounded-md border-2 ${index === active ? 'border-primary' : 'border-transparent'}`} aria-label={shot.alt} onClick={() => setActive(index)}>
                                <img src={shot.src} alt="" className="size-full object-cover" style={{ objectPosition: shot.position }} />
                            </button>
                        ))}
                    </div>
                    <div
                        className="bg-muted relative order-1 aspect-square overflow-hidden rounded-lg sm:order-2"
                        onMouseMove={(event) => {
                            const box = event.currentTarget.getBoundingClientRect();
                            setZoom({ on: true, x: ((event.clientX - box.left) / box.width) * 100, y: ((event.clientY - box.top) / box.height) * 100 });
                        }}
                        onMouseLeave={() => setZoom((value) => ({ ...value, on: false }))}
                    >
                        {current && (
                            <img
                                src={current.src}
                                alt={current.alt}
                                className="size-full object-cover"
                                style={{
                                    objectPosition: zoom.on ? `${zoom.x}% ${zoom.y}%` : current.position,
                                    transform: zoom.on ? 'scale(1.6)' : 'scale(1)',
                                    transition: 'transform 200ms ease',
                                }}
                            />
                        )}
                    </div>
                </div>
                <div className="py-2">
                    <p className="text-secondary text-sm font-bold uppercase">
                        {pickle.category} pickle · {pickle.spice}
                    </p>
                    <h1 className="mt-2 text-4xl font-extrabold sm:text-5xl">{pickle.name}</h1>
                    <div className="mt-4 flex items-center gap-2">
                        <Stars value={pickle.rating} />
                        <b>{pickle.rating}</b>
                        <a href="#reviews" className="text-muted-foreground text-sm underline">
                            {pickle.reviews} reviews
                        </a>
                    </div>
                    <p className="text-muted-foreground mt-5 text-lg leading-8">{pickle.description}</p>
                    <p className="text-muted-foreground mt-3 text-sm leading-7">{pickle.story}</p>
                    <div className="mt-5 flex items-baseline gap-3">
                        <b className="text-3xl">{formatPrice(price)}</b>
                        <s className="text-muted-foreground">{formatPrice(original)}</s>
                        {original > price && <span className="bg-accent rounded px-2 py-1 text-xs font-bold">Save {formatPrice(original - price)}</span>}
                    </div>
                    <p className="text-muted-foreground mt-1 text-xs">Inclusive of all taxes · Earn {Math.floor(price / 10)} loyalty points</p>
                    <div className="mt-7">
                        <b className="text-sm">Choose weight</b>
                        <div className="mt-3 flex flex-wrap gap-2">
                            {pickle.weights.map((option) => (
                                <Button key={option.label} variant={weight === option.label ? 'default' : 'outline'} onClick={() => setWeight(option.label)}>
                                    {option.label} · {formatPrice(option.price)}
                                </Button>
                            ))}
                        </div>
                    </div>
                    <div className="mt-7 grid grid-cols-[auto_1fr] gap-3">
                        <div className="border-input flex items-center rounded-md border">
                            <Button variant="ghost" size="icon" aria-label="Decrease quantity" onClick={() => setQuantity(Math.max(1, quantity - 1))}>
                                <Minus />
                            </Button>
                            <span className="w-8 text-center">{quantity}</span>
                            <Button variant="ghost" size="icon" aria-label="Increase quantity" onClick={() => setQuantity(quantity + 1)}>
                                <Plus />
                            </Button>
                        </div>
                        <Button size="lg" disabled={!pickle.inStock} onClick={() => addToCart(variantIdForWeight(pickle, weight), quantity)}>
                            Add to cart · {formatPrice(price * quantity)}
                        </Button>
                    </div>
                    <div className="mt-3 grid grid-cols-2 gap-3">
                        <Button variant="golden" size="lg" disabled={!pickle.inStock} onClick={() => addToCart(variantIdForWeight(pickle, weight), quantity, true)}>
                            Buy now
                        </Button>
                        <Button
                            variant="outline"
                            size="lg"
                            onClick={() => {
                                const next = saved ? readWishlist().filter((id) => id !== pickle.id) : [...readWishlist(), pickle.id];
                                writeWishlist(next);
                                setSaved(!saved);
                            }}
                        >
                            <Heart className={saved ? 'fill-primary text-primary' : ''} /> Wishlist
                        </Button>
                    </div>
                    <Button variant="ghost" className="mt-2" onClick={share}>
                        <Share2 /> Share product
                    </Button>
                    <div className="border-border mt-6 rounded-lg border p-4">
                        <label className="text-sm font-bold" htmlFor="pincode">
                            Delivery estimate
                        </label>
                        <div className="mt-2 flex gap-2">
                            <Input id="pincode" inputMode="numeric" maxLength={6} value={pin} onChange={(event) => setPin(event.target.value.replace(/\D/g, ''))} placeholder="Enter 6-digit pincode" />
                            <Button variant="outline" onClick={() => setDelivery(pin.length === 6 ? 'Fresh at your door in 3–5 working days. COD available.' : 'Please enter a valid pincode.')}>
                                Check
                            </Button>
                        </div>
                        {delivery && <p className="text-brand-leaf mt-2 text-sm">{delivery}</p>}
                    </div>
                    <div className="mt-5 grid grid-cols-2 gap-3 text-sm">
                        <span className="flex items-center gap-2">
                            <ShieldCheck className="text-primary" /> Secure checkout
                        </span>
                        <span className="flex items-center gap-2">
                            <Truck className="text-primary" /> Free above ₹499
                        </span>
                    </div>
                </div>
            </section>

            <section className="bg-muted py-16">
                <div className="section-shell grid gap-8 lg:grid-cols-2">
                    <div>
                        <h2 className="text-3xl font-extrabold">Ingredients</h2>
                        <p className="text-muted-foreground mt-4 leading-7">{(pickle.ingredients ?? []).join(', ')}. No artificial preservatives, colours or flavours.</p>
                        <h3 className="mt-8 text-xl font-extrabold">Nutrition information</h3>
                        <div className="mt-4 grid grid-cols-3 gap-2 text-center text-sm">
                            {nutrition.map((row) => (
                                <div key={`${row.label}-${row.value}`} className="bg-background rounded p-4">
                                    <b className="block text-lg">{row.value}</b>
                                    {row.label} / serve
                                </div>
                            ))}
                        </div>
                    </div>
                    <div>
                        <h2 className="text-3xl font-extrabold">Questions we hear often</h2>
                        <Accordion type="single" collapsible className="mt-4">
                            {faqs.slice(0, 4).map((entry) => (
                                <AccordionItem key={entry.q} value={entry.q}>
                                    <AccordionTrigger>{entry.q}</AccordionTrigger>
                                    <AccordionContent>{entry.a}</AccordionContent>
                                </AccordionItem>
                            ))}
                        </Accordion>
                    </div>
                </div>
            </section>

            <section id="reviews" className="section-shell py-16">
                <h2 className="text-3xl font-extrabold">Reviews</h2>
                <div className="mt-8 space-y-4">
                    {reviews.length === 0 && <p className="text-muted-foreground">Be the first to taste and tell.</p>}
                    {reviews.map((review) => (
                        <article key={`${review.name}-${review.date}`} className="border-border rounded-xl border p-5">
                            <div className="flex items-center justify-between gap-3">
                                <div>
                                    <b>{review.name}</b>
                                    <p className="text-muted-foreground text-sm">
                                        {review.city} · {review.date}
                                    </p>
                                </div>
                                <Stars value={review.rating} />
                            </div>
                            <p className="mt-3 leading-7">{review.text}</p>
                        </article>
                    ))}
                </div>
            </section>

            {together.length > 0 && (
                <section className="bg-muted py-16">
                    <div className="section-shell">
                        <h2 className="text-3xl font-extrabold">Frequently bought together</h2>
                        <div className="mt-8 grid gap-5 lg:grid-cols-[1fr_auto]">
                            <div className="grid gap-5 sm:grid-cols-3">
                                <ProductCard product={pickle} />
                                {together.map((entry) => (
                                    <ProductCard key={entry.id} product={entry} />
                                ))}
                            </div>
                            <div className="border-border bg-background flex flex-col justify-center rounded-2xl border p-6">
                                <p className="text-muted-foreground text-sm">Trio total</p>
                                <b className="text-3xl">{formatPrice(togetherTotal)}</b>
                                <Button
                                    className="mt-4"
                                    onClick={() =>
                                        addManyToCart([
                                            { variantId: variantIdForWeight(pickle, weight), quantity: 1 },
                                            ...together.map((entry) => ({ variantId: entry.default_variant_id, quantity: 1 })),
                                        ])
                                    }
                                >
                                    Add all three
                                </Button>
                            </div>
                        </div>
                    </div>
                </section>
            )}

            {relatedItems.length > 0 && (
                <section className="section-shell py-16">
                    <h2 className="text-3xl font-extrabold">Related products</h2>
                    <div className="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        {relatedItems.map((entry) => (
                            <ProductCard key={entry.id} product={entry} />
                        ))}
                    </div>
                </section>
            )}

            <RecentlyViewed exclude={pickle.id} />

            <div className="border-border bg-background fixed inset-x-0 bottom-0 z-30 grid grid-cols-[1fr_auto] items-center gap-3 border-t p-3 shadow-xl lg:hidden">
                <div className="min-w-0">
                    <b className="block truncate">{pickle.name}</b>
                    <span className="text-sm">{formatPrice(price)}</span>
                </div>
                <Button disabled={!pickle.inStock} onClick={() => addToCart(variantIdForWeight(pickle, weight), quantity)}>
                    Add to cart
                </Button>
            </div>
        </StorefrontLayout>
    );
}
