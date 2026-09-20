import { ConversionLayer } from '@/components/conversion';
import { FlashMessages } from '@/components/flash-messages';
import { GoogleAnalytics } from '@/components/storefront/google-analytics';
import { GoogleTagManager } from '@/components/storefront/google-tag-manager';
import { MetaPixel } from '@/components/storefront/meta-pixel';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { formatPrice, readWishlist, toPickle } from '@/lib/pickle';
import { images } from '@/lib/yumyum-images';
import { type SharedData } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import { Facebook, Heart, Instagram, Menu, MessageCircle, Minus, Plus, Search, ShieldCheck, ShoppingBag, Truck, User, X, Youtube } from 'lucide-react';
import { useEffect, useMemo, useRef, useState, type ReactNode } from 'react';

const nav = [
    ['Shop', '/shop'],
    ['Our Story', '/our-story'],
    ['Recipes', '/recipes'],
    ['Why YumYum', '/our-story'],
    ['Contact', '/contact'],
] as const;

export default function StorefrontLayout({ children }: { children: ReactNode }) {
    const { auth, cart, content, catalog, flash } = usePage<SharedData>().props;
    const brand = content?.brand;
    const announcements = content?.announcements?.length ? content.announcements : ['Free Shipping Above ₹499', 'COD Available', 'Pan India Delivery'];
    const [searchOpen, setSearchOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [announce, setAnnounce] = useState(0);
    const [cartOpen, setCartOpen] = useState(false);
    const [wishlistCount, setWishlistCount] = useState(0);
    const handledOpenCart = useRef(false);
    const products = (catalog ?? []).map(toPickle);
    const results = useMemo(
        () => products.filter((product) => product.name.toLowerCase().includes(query.toLowerCase())).slice(0, 4),
        [products, query],
    );
    const cartCount = cart?.item_count ?? 0;
    const items = cart?.items ?? [];

    useEffect(() => {
        setWishlistCount(readWishlist().length);
        const timer = window.setInterval(() => setAnnounce((value) => (value + 1) % announcements.length), 3200);
        return () => window.clearInterval(timer);
    }, [announcements.length]);

    useEffect(() => {
        if (flash.open_cart) {
            if (!handledOpenCart.current) {
                setCartOpen(true);
                handledOpenCart.current = true;
            }
            return;
        }
        handledOpenCart.current = false;
    }, [flash.open_cart]);

    return (
        <div className="bg-background text-foreground flex min-h-screen flex-col">
            <div className="bg-brand-deep text-primary-foreground flex h-8 items-center justify-center overflow-hidden px-4 text-center text-xs font-semibold sm:text-sm">
                <p key={announce} className="announce-fade">
                    {announcements[announce]}
                </p>
            </div>
            <header className="border-border/70 bg-background/95 sticky top-0 z-40 border-b backdrop-blur-xl">
                <div className="section-shell grid h-20 grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 lg:h-24 lg:grid-cols-[200px_minmax(0,1fr)_220px]">
                    <Sheet>
                        <SheetTrigger asChild>
                            <Button variant="ghost" size="icon" className="min-h-11 min-w-11 lg:hidden" aria-label="Open menu">
                                <Menu />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" className="w-[88%]">
                            <SheetHeader>
                                <SheetTitle>Menu</SheetTitle>
                            </SheetHeader>
                            <nav className="mt-8 grid gap-1">
                                {nav.map(([label, href]) => (
                                    <Link key={label} href={href} className="border-border font-display border-b py-4 text-xl font-bold">
                                        {label}
                                    </Link>
                                ))}
                                <Link href="/wishlist" className="border-border font-display border-b py-4 text-xl font-bold">
                                    Wishlist
                                </Link>
                                <Link href={auth.user ? '/account' : '/login'} className="font-display py-4 text-xl font-bold">
                                    Account
                                </Link>
                            </nav>
                        </SheetContent>
                    </Sheet>
                    <Link href="/" className="justify-self-center lg:justify-self-start" aria-label="YumYum Pickles home">
                        <img src={images.logoImage} alt="YumYum homemade pickle — The taste of tradition" className="h-16 w-auto object-contain drop-shadow-sm lg:h-[4.6rem]" width="120" height="74" />
                    </Link>
                    <nav className="hidden items-center justify-center gap-7 lg:flex">
                        <div className="group relative">
                            <Link href="/shop" className="hover:text-primary text-sm font-bold text-foreground transition-colors">
                                Categories
                            </Link>
                            <div className="border-border bg-background invisible absolute top-full left-1/2 z-50 mt-4 w-[520px] -translate-x-1/2 rounded-2xl border p-4 opacity-0 shadow-2xl transition group-hover:visible group-hover:opacity-100">
                                <div className="grid grid-cols-2 gap-2">
                                    {(content?.categories ?? []).map((category) => (
                                        <Link key={category.name} href={`/shop?category=${encodeURIComponent(category.name)}`} className="hover:bg-muted rounded-xl p-3 text-sm">
                                            <b className="block">{category.label}</b>
                                            <span className="text-muted-foreground">{category.tagline}</span>
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        </div>
                        {nav.map(([label, href]) => (
                            <Link key={label} href={href} className="hover:text-primary text-sm font-bold text-foreground transition-colors">
                                {label}
                            </Link>
                        ))}
                    </nav>
                    <div className="flex items-center justify-end gap-1">
                        <Button variant="ghost" size="icon" className="min-h-11 min-w-11" onClick={() => setSearchOpen((value) => !value)} aria-label="Search">
                            <Search />
                        </Button>
                        <Link href="/wishlist" className="hover:bg-accent relative hidden min-h-11 min-w-11 items-center justify-center rounded-md sm:flex" aria-label="Wishlist">
                            <Heart className="size-5" />
                            {wishlistCount > 0 && (
                                <span className="bg-primary text-primary-foreground absolute top-0.5 right-0.5 grid size-5 place-items-center rounded-full text-[10px] font-bold">{wishlistCount}</span>
                            )}
                        </Link>
                        <Link href={auth.user ? '/account' : '/login'} className="hover:bg-accent hidden min-h-11 min-w-11 items-center justify-center rounded-md sm:flex" aria-label="Account">
                            <User className="size-5" />
                        </Link>
                        <Button variant="ghost" size="icon" className="relative min-h-11 min-w-11" onClick={() => setCartOpen(true)} aria-label={`Cart with ${cartCount} items`}>
                            <ShoppingBag />
                            <span className="bg-primary text-primary-foreground absolute top-0.5 right-0.5 grid size-5 place-items-center rounded-full text-[10px] font-bold">{cartCount}</span>
                        </Button>
                    </div>
                </div>
                {searchOpen && (
                    <div className="border-border bg-background border-t py-4">
                        <div className="section-shell relative">
                            <Input autoFocus value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Search prawns, brinjal, bombil, tendli..." className="h-12 pr-12" aria-label="Search products" />
                            <Button variant="ghost" size="icon" className="absolute top-0.5 right-5 min-h-11 min-w-11 sm:right-7 lg:right-11" onClick={() => setSearchOpen(false)} aria-label="Close search">
                                <X />
                            </Button>
                            {query && (
                                <div className="border-border bg-background absolute inset-x-4 top-14 z-50 border p-3 shadow-xl sm:inset-x-6 lg:inset-x-10">
                                    {results.length === 0 && <p className="text-muted-foreground p-3 text-sm">No jars match that craving yet.</p>}
                                    {results.map((product) => (
                                        <Link key={product.id} href={product.url} onClick={() => setSearchOpen(false)} className="border-border flex items-center gap-3 border-b p-2 last:border-0">
                                            {product.image && <img src={product.image} alt="" className="size-12 object-cover" style={{ objectPosition: product.imagePosition }} />}
                                            <span className="font-semibold">{product.name}</span>
                                            <span className="text-primary ml-auto">{formatPrice(product.price)}</span>
                                        </Link>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </header>
            <div className="section-shell">
                <FlashMessages />
            </div>
            <main className="flex-1">{children}</main>
            <footer className="bg-brand-deep text-primary-foreground">
                <div className="section-shell grid gap-10 py-14 md:grid-cols-[1.5fr_1fr_1fr_1fr]">
                    <div>
                        <img src={images.logoImage} alt="YumYum Pickles" className="h-28 w-auto object-contain" width="176" height="112" />
                        <p className="text-primary-foreground/75 mt-4 max-w-sm text-sm leading-6">Homemade Indian pickles, prepared from family recipes, premium ingredients and hygienic processes. A little nostalgia in every jar.</p>
                        <div className="mt-5 flex gap-2">
                            <Button asChild variant="golden" size="icon" aria-label="Instagram">
                                <a href={brand?.instagram || 'https://instagram.com/yumyumpickles'} target="_blank" rel="noreferrer">
                                    <Instagram />
                                </a>
                            </Button>
                            <Button asChild variant="golden" size="icon" aria-label="Facebook">
                                <a href={brand?.facebook || 'https://facebook.com/yumyumpickles'} target="_blank" rel="noreferrer">
                                    <Facebook />
                                </a>
                            </Button>
                            <Button asChild variant="golden" size="icon" aria-label="YouTube">
                                <a href={brand?.youtube || 'https://youtube.com/@yumyumpickles'} target="_blank" rel="noreferrer">
                                    <Youtube />
                                </a>
                            </Button>
                            <Button asChild variant="golden" size="icon" aria-label="WhatsApp">
                                <a href={`https://wa.me/${brand?.whatsapp || '919999999999'}`} target="_blank" rel="noreferrer">
                                    <MessageCircle />
                                </a>
                            </Button>
                        </div>
                    </div>
                    <FooterColumn title="Company" links={[['About Us', '/our-story'], ['Contact Us', '/contact'], ['FAQs', '/faq'], ['Recipes', '/recipes']]} />
                    <FooterColumn title="Customer Service" links={[['Shipping Policy', '/shipping'], ['Refund Policy', '/refunds'], ['Privacy Policy', '/privacy'], ['Terms & Conditions', '/terms']]} />
                    <div>
                        <h3 className="text-accent mb-4 text-sm font-bold uppercase">We accept</h3>
                        <p className="text-primary-foreground/75 text-sm">Razorpay · UPI · Cards · Net banking · COD</p>
                        <div className="mt-6 flex items-center gap-2 text-sm">
                            <ShieldCheck className="text-accent size-5" /> Secure checkout
                        </div>
                        <div className="mt-3 flex items-center gap-2 text-sm">
                            <Truck className="text-accent size-5" /> Pan India delivery
                        </div>
                    </div>
                </div>
                <div className="border-primary-foreground/15 text-primary-foreground/60 border-t py-5 text-center text-xs">© {new Date().getFullYear()} YumYum Pickles. Made in India with love and achaar.</div>
            </footer>
            <a
                href={`https://wa.me/${brand?.whatsapp || '919999999999'}?text=Hello%20YumYum%20Pickles`}
                target="_blank"
                rel="noreferrer"
                className="bg-brand-leaf text-primary-foreground fixed right-5 bottom-5 z-40 grid size-14 place-items-center rounded-full shadow-xl transition-transform hover:scale-105"
                aria-label="Chat on WhatsApp"
            >
                <MessageCircle />
            </a>
            <Sheet open={cartOpen} onOpenChange={setCartOpen}>
                <SheetContent className="flex w-full flex-col sm:max-w-md">
                    <SheetHeader>
                        <SheetTitle>Your cart ({cartCount})</SheetTitle>
                    </SheetHeader>
                    <div className="mt-5 flex-1 space-y-4 overflow-y-auto">
                        {items.length === 0 ? (
                            <div className="text-muted-foreground grid h-48 place-items-center text-center">Your cart is waiting for something delicious.</div>
                        ) : (
                            items.map((line) => (
                                <div key={line.id} className="border-border grid grid-cols-[72px_1fr_auto] gap-3 border-b pb-4">
                                    {line.image ? (
                                        <img src={line.image.url} alt="" className="size-[72px] rounded-md object-cover" />
                                    ) : (
                                        <div className="bg-muted size-[72px] rounded-md" />
                                    )}
                                    <div>
                                        <p className="font-bold">{line.product.title}</p>
                                        <p className="text-muted-foreground text-sm">{line.variant.options.join(' / ')}</p>
                                        <div className="mt-2 flex items-center gap-2">
                                            <Button variant="outline" size="icon" className="size-8" aria-label="Decrease quantity" onClick={() => router.patch(`/cart/items/${line.id}`, { quantity: line.quantity - 1 }, { preserveScroll: true, preserveState: true })}>
                                                <Minus />
                                            </Button>
                                            <span className="w-5 text-center text-sm">{line.quantity}</span>
                                            <Button variant="outline" size="icon" className="size-8" aria-label="Increase quantity" onClick={() => router.patch(`/cart/items/${line.id}`, { quantity: line.quantity + 1 }, { preserveScroll: true, preserveState: true })}>
                                                <Plus />
                                            </Button>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <b>{line.line_total.formatted}</b>
                                        <Button variant="ghost" size="icon" className="mt-2 block size-8" aria-label={`Remove ${line.product.title}`} onClick={() => router.delete(`/cart/items/${line.id}`, { preserveScroll: true })}>
                                            <X />
                                        </Button>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                    <div className="border-border border-t pt-5">
                        {cart?.discount && cart.discount.amount > 0 && <p className="text-brand-leaf mb-2 text-sm">Offer applied · −{cart.discount.formatted}</p>}
                        <div className="mb-4 flex justify-between text-lg font-bold">
                            <span>Subtotal</span>
                            <span>{cart?.total?.formatted ?? '₹0'}</span>
                        </div>
                        <Button asChild size="lg" className="w-full">
                            <Link href="/cart" onClick={() => setCartOpen(false)}>
                                View cart & checkout
                            </Link>
                        </Button>
                    </div>
                </SheetContent>
            </Sheet>
            <ConversionLayer />
            <GoogleTagManager />
            <MetaPixel />
            <GoogleAnalytics />
        </div>
    );
}

function FooterColumn({ title, links }: { title: string; links: readonly (readonly [string, string])[] }) {
    return (
        <div>
            <h3 className="text-accent mb-4 text-sm font-bold uppercase">{title}</h3>
            <ul className="space-y-3">
                {links.map(([label, href]) => (
                    <li key={label}>
                        <Link href={href} className="text-primary-foreground/75 hover:text-accent text-sm">
                            {label}
                        </Link>
                    </li>
                ))}
            </ul>
        </div>
    );
}
