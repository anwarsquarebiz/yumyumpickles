import { ConversionLayer } from '@/components/conversion';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { addToCart, formatPrice, readRecentlyViewed, readWishlist, toPickle, variantIdForWeight, writeWishlist, type PickleProduct } from '@/lib/pickle';
import { cn } from '@/lib/utils';
import { type ProductSummary, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ArrowRight, ChevronLeft, ChevronRight, Eye, Heart, Play, Star } from 'lucide-react';
import { Children, useEffect, useMemo, useRef, useState, type ReactNode } from 'react';

export function PageIntro({ eyebrow, title, copy }: { eyebrow: string; title: string; copy: string }) {
    return (
        <section className="bg-brand-deep text-primary-foreground py-14 sm:py-20">
            <div className="section-shell">
                <p className="text-accent mb-3 font-script text-4xl">{eyebrow}</p>
                <h1 className="max-w-3xl text-4xl font-extrabold sm:text-6xl">{title}</h1>
                <p className="text-primary-foreground/75 mt-5 max-w-2xl text-base leading-7 sm:text-lg">{copy}</p>
            </div>
        </section>
    );
}

export function Breadcrumbs({ items }: { items: { label: string; href?: string }[] }) {
    return (
        <nav aria-label="Breadcrumb" className="section-shell text-muted-foreground py-4 text-sm">
            <ol className="flex flex-wrap gap-2">
                <li>
                    <Link href="/" className="hover:text-primary">
                        Home
                    </Link>
                </li>
                {items.map((item) => (
                    <li key={item.label} className="flex gap-2">
                        <span>/</span>
                        {item.href ? (
                            <Link href={item.href} className="hover:text-primary">
                                {item.label}
                            </Link>
                        ) : (
                            <span className="text-foreground">{item.label}</span>
                        )}
                    </li>
                ))}
            </ol>
        </nav>
    );
}

export function Stars({ value = 5 }: { value?: number }) {
    return (
        <span className="inline-flex gap-0.5" aria-label={`${value} out of 5 stars`}>
            {[0, 1, 2, 3, 4].map((n) => (
                <Star key={n} className={`size-4 ${n < Math.round(value) ? 'fill-accent text-accent' : 'text-border'}`} />
            ))}
        </span>
    );
}

export function SectionTitle({ script, title, copy, dark = false }: { script: string; title: string; copy: string; dark?: boolean }) {
    return (
        <div className="max-w-2xl">
            <p className="text-primary font-script text-4xl">{script}</p>
            <h2 className={`text-3xl font-extrabold sm:text-5xl ${dark ? 'text-primary-foreground' : ''}`}>{title}</h2>
            <p className={`mt-4 leading-7 ${dark ? 'text-primary-foreground/70' : 'text-muted-foreground'}`}>{copy}</p>
        </div>
    );
}

export function Carousel({
    children,
    className,
    tone = 'light',
    label,
}: {
    children: ReactNode;
    className?: string;
    tone?: 'light' | 'dark';
    label: string;
}) {
    const trackRef = useRef<HTMLDivElement>(null);
    const items = Children.toArray(children);
    const [page, setPage] = useState(0);
    const [pages, setPages] = useState(1);

    const sync = () => {
        const track = trackRef.current;

        if (!track) {
            return;
        }

        const max = Math.max(track.scrollWidth - track.clientWidth, 1);
        const nextPages = Math.max(Math.ceil(track.scrollWidth / Math.max(track.clientWidth, 1)), 1);
        setPages(nextPages);
        setPage(Math.min(Math.round(track.scrollLeft / max * (nextPages - 1)), nextPages - 1));
    };

    useEffect(() => {
        const track = trackRef.current;

        if (!track) {
            return;
        }

        sync();
        const observer = new ResizeObserver(sync);
        observer.observe(track);
        track.addEventListener('scroll', sync, { passive: true });

        return () => {
            observer.disconnect();
            track.removeEventListener('scroll', sync);
        };
    }, [items.length]);

    const scrollByPage = (direction: -1 | 1) => {
        const track = trackRef.current;

        if (!track) {
            return;
        }

        track.scrollBy({ left: direction * Math.max(track.clientWidth * 0.85, 240), behavior: 'smooth' });
    };

    const goTo = (index: number) => {
        const track = trackRef.current;

        if (!track) {
            return;
        }

        const max = Math.max(track.scrollWidth - track.clientWidth, 1);
        track.scrollTo({ left: (index / Math.max(pages - 1, 1)) * max, behavior: 'smooth' });
    };

    const arrowClass =
        tone === 'dark'
            ? 'border-primary-foreground/25 bg-brand-deep/80 text-primary-foreground hover:bg-brand-deep'
            : 'border-border bg-background/95 text-foreground hover:bg-muted';

    return (
        <div className={cn('relative', className)}>
            <div
                ref={trackRef}
                className="hide-scrollbar flex snap-x snap-mandatory items-start gap-4 overflow-x-auto scroll-smooth pb-2 sm:gap-5"
                aria-label={label}
                role="region"
            >
                {items.map((child, index) => (
                    <div key={index} className="shrink-0 snap-start">
                        {child}
                    </div>
                ))}
            </div>
            {pages > 1 && (
                <>
                    <Button
                        type="button"
                        size="icon"
                        variant="outline"
                        className={cn('absolute top-[42%] left-2 z-20 size-10 -translate-y-1/2 rounded-full shadow-md sm:left-0 sm:-translate-x-1/2', arrowClass)}
                        onClick={() => scrollByPage(-1)}
                        disabled={page === 0}
                        aria-label={`Previous ${label}`}
                    >
                        <ChevronLeft />
                    </Button>
                    <Button
                        type="button"
                        size="icon"
                        variant="outline"
                        className={cn('absolute top-[42%] right-2 z-20 size-10 -translate-y-1/2 rounded-full shadow-md sm:right-0 sm:translate-x-1/2', arrowClass)}
                        onClick={() => scrollByPage(1)}
                        disabled={page >= pages - 1}
                        aria-label={`Next ${label}`}
                    >
                        <ChevronRight />
                    </Button>
                    <div className="mt-5 flex justify-center gap-2">
                        {Array.from({ length: pages }, (_, index) => (
                            <button
                                key={index}
                                type="button"
                                aria-label={`Go to ${label} ${index + 1}`}
                                aria-current={page === index ? true : undefined}
                                className={cn(
                                    'h-2.5 rounded-full transition-all',
                                    page === index ? 'bg-primary w-6' : tone === 'dark' ? 'bg-primary-foreground/35 w-2.5' : 'bg-border w-2.5',
                                )}
                                onClick={() => goTo(index)}
                            />
                        ))}
                    </div>
                </>
            )}
        </div>
    );
}

export function Newsletter() {
    const [email, setEmail] = useState('');
    const [done, setDone] = useState(false);

    return (
        <section className="bg-primary text-primary-foreground py-14">
            <div className="section-shell grid items-center gap-7 md:grid-cols-2">
                <div>
                    <p className="text-accent font-script text-4xl">A little extra achar?</p>
                    <h2 className="text-3xl font-extrabold">Get Exclusive Offers & New Flavours</h2>
                    <p className="text-primary-foreground/80 mt-2">Fresh batch alerts, recipes and members-only savings.</p>
                </div>
                {done ? (
                    <p className="text-lg font-bold">You’re on the YumYum list. Thank you!</p>
                ) : (
                    <form
                        className="grid gap-3 sm:grid-cols-[1fr_auto]"
                        onSubmit={(event) => {
                            event.preventDefault();
                            if (email.includes('@')) {
                                setDone(true);
                            }
                        }}
                    >
                        <Input
                            required
                            type="email"
                            maxLength={255}
                            value={email}
                            onChange={(event) => setEmail(event.target.value)}
                            placeholder="Your email address"
                            className="bg-background text-foreground h-12"
                            aria-label="Email address"
                        />
                        <Button type="submit" variant="golden" size="lg">
                            Join the family <ArrowRight />
                        </Button>
                    </form>
                )}
            </div>
        </section>
    );
}

export function VideoCard({ title, tag, views, image }: { title: string; tag: string; views: string; image?: string | null }) {
    const [open, setOpen] = useState(false);

    return (
        <>
            <article className="relative aspect-[9/16] w-[220px] shrink-0 overflow-hidden rounded-lg sm:w-[260px]">
                {image && <img src={image} alt="" loading="lazy" className="size-full object-cover" />}
                <div className="absolute inset-0 bg-gradient-to-t from-brand-deep/90 via-transparent to-transparent" />
                <Button
                    size="icon"
                    variant="golden"
                    className="absolute top-1/2 left-1/2 size-14 -translate-x-1/2 -translate-y-1/2 rounded-full"
                    aria-label={`Play ${title}`}
                    onClick={() => setOpen(true)}
                >
                    <Play className="fill-current" />
                </Button>
                <div className="absolute inset-x-0 bottom-0 p-4">
                    <span className="text-accent text-xs font-bold">{tag.toUpperCase()}</span>
                    <h3 className="mt-1 text-lg font-extrabold">{title}</h3>
                    <p className="text-primary-foreground/70 text-xs">{views} views</p>
                </div>
            </article>
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-w-md overflow-hidden p-0">
                    {image && <img src={image} alt={title} className="aspect-[9/16] w-full object-cover" />}
                    <div className="from-brand-deep text-primary-foreground absolute inset-x-0 bottom-0 bg-gradient-to-t p-5">
                        <p className="text-accent text-xs font-bold">{tag}</p>
                        <h3 className="text-xl font-extrabold">{title}</h3>
                        <p className="text-primary-foreground/75 mt-2 text-sm">A short from the YumYum kitchen — spice, sunshine and the sound of a fresh jar opening.</p>
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}

export function PolicyPage({ title, intro, sections }: { title: string; intro: string; sections: { title: string; body: string }[] }) {
    return (
        <>
            <PageIntro eyebrow="The fine print, said warmly" title={title} copy={intro} />
            <div className="section-shell grid gap-6 py-16 md:grid-cols-2">
                {sections.map((section) => (
                    <article key={section.title} className="border-border bg-card rounded-2xl border p-6 shadow-sm">
                        <h2 className="text-xl font-extrabold">{section.title}</h2>
                        <p className="text-muted-foreground mt-3 leading-7">{section.body}</p>
                    </article>
                ))}
            </div>
        </>
    );
}

export function AvatarMark({ initials }: { initials: string }) {
    return <span className="bg-primary text-primary-foreground grid size-12 place-items-center rounded-full text-sm font-extrabold">{initials}</span>;
}

export function ProductCard({ product, compact = false }: { product: PickleProduct; compact?: boolean }) {
    const [quick, setQuick] = useState(false);
    const [saved, setSaved] = useState(() => readWishlist().includes(product.id));

    const toggleWishlist = () => {
        const next = saved ? readWishlist().filter((id) => id !== product.id) : [...readWishlist(), product.id];
        writeWishlist(next);
        setSaved(!saved);
    };

    return (
        <>
            <article className={`border-border bg-card group overflow-hidden rounded-lg border shadow-sm transition-all hover:-translate-y-1 hover:shadow-xl ${compact ? 'grid grid-cols-[140px_1fr]' : ''}`}>
                <div className={`bg-muted relative overflow-hidden ${compact ? 'min-h-44' : 'aspect-[4/4]'}`}>
                    {product.image && (
                        <img
                            src={product.image}
                            alt={`${product.name} jar`}
                            loading="lazy"
                            className="image-warm size-full object-cover transition-transform duration-500 group-hover:scale-105"
                            style={{ objectPosition: product.imagePosition }}
                        />
                    )}
                    {product.badge && <span className="bg-primary text-primary-foreground absolute top-3 left-3 rounded px-2 py-1 text-xs font-bold">{product.badge}</span>}
                    {!product.inStock && (
                        <span className="bg-brand-deep/80 text-primary-foreground absolute inset-x-3 bottom-3 rounded px-2 py-1 text-center text-xs font-bold">Currently resting</span>
                    )}
                    <Button variant="outline" size="icon" className="absolute top-3 right-3 rounded-full" onClick={toggleWishlist} aria-label={saved ? `Remove ${product.name} from wishlist` : `Add ${product.name} to wishlist`}>
                        <Heart className={saved ? 'fill-primary text-primary' : ''} />
                    </Button>
                </div>
                <div className="p-4">
                    <p className="text-secondary text-xs font-bold uppercase">
                        {product.category} · {product.spice}
                    </p>
                    <Link href={product.url} className="hover:text-primary mt-1 block font-display text-lg leading-tight font-extrabold">
                        {product.name}
                    </Link>
                    <div className="mt-2 flex items-center gap-1 text-sm">
                        <Star className="fill-accent text-accent size-4" />
                        <b>{product.rating}</b>
                        <span className="text-muted-foreground">({product.reviews})</span>
                    </div>
                    <div className="mt-3 flex items-center gap-2">
                        <b className="text-lg">{formatPrice(product.price)}</b>
                        <s className="text-muted-foreground text-sm">{formatPrice(product.originalPrice)}</s>
                        <span className="text-muted-foreground ml-auto text-xs">{product.weight}</span>
                    </div>
                    <div className="mt-4 grid grid-cols-[1fr_auto_auto] gap-2">
                        <Button disabled={!product.inStock} onClick={() => addToCart(variantIdForWeight(product, product.weight))}>
                            Add to cart
                        </Button>
                        <Button variant="outline" size="icon" aria-label={`Quick view ${product.name}`} onClick={() => setQuick(true)}>
                            <Eye />
                        </Button>
                        <Button asChild variant="outline" size="icon" aria-label={`View ${product.name}`}>
                            <Link href={product.url}>
                                <ArrowRight />
                            </Link>
                        </Button>
                    </div>
                </div>
            </article>
            <Dialog open={quick} onOpenChange={setQuick}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>{product.name}</DialogTitle>
                    </DialogHeader>
                    <div className="grid gap-5 sm:grid-cols-2">
                        {product.image && (
                            <img src={product.image} alt={product.name} className="aspect-square rounded-lg object-cover" style={{ objectPosition: product.imagePosition }} />
                        )}
                        <div>
                            <Stars value={product.rating} />
                            <p className="text-muted-foreground mt-3 text-sm leading-6">{product.description}</p>
                            <p className="mt-4 text-2xl font-extrabold">{formatPrice(product.price)}</p>
                            <div className="mt-5 flex gap-2">
                                <Button
                                    disabled={!product.inStock}
                                    onClick={() => {
                                        addToCart(variantIdForWeight(product, product.weight));
                                        setQuick(false);
                                    }}
                                >
                                    Add to cart
                                </Button>
                                <Button asChild variant="outline">
                                    <Link href={product.url}>Full details</Link>
                                </Button>
                            </div>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}

export function RecentlyViewed({ exclude }: { exclude?: string }) {
    const catalog = usePage<SharedData>().props.catalog ?? [];
    const items = useMemo(() => {
        const products = catalog.map(toPickle);
        return readRecentlyViewed()
            .map((id) => products.find((product) => product.id === id))
            .filter((product): product is PickleProduct => product !== undefined && product.id !== exclude)
            .slice(0, 4);
    }, [catalog, exclude]);

    if (items.length === 0) {
        return null;
    }

    return (
        <section className="section-shell py-16">
            <p className="text-primary font-script text-4xl">Still thinking about</p>
            <h2 className="text-3xl font-extrabold">Recently viewed jars</h2>
            <div className="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                {items.map((product) => (
                    <ProductCard key={product.id} product={product} />
                ))}
            </div>
        </section>
    );
}

export function CatalogCards({ products, compact = false }: { products: ProductSummary[]; compact?: boolean }) {
    return (
        <>
            {products.map((product) => (
                <ProductCard key={product.id} product={toPickle(product)} compact={compact} />
            ))}
        </>
    );
}

export { ConversionLayer };
