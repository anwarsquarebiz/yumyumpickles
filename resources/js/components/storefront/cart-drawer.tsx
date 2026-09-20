import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { type CartLine, type SharedData } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import { ImageOff, Minus, Plus, ShoppingBag, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface CartDrawerProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function useCartDrawer(): {
    open: boolean;
    setOpen: (open: boolean) => void;
    openCart: () => void;
} {
    const [open, setOpen] = useState(false);
    const { flash } = usePage<SharedData>().props;
    const handledOpenCart = useRef(false);

    useEffect(() => {
        if (flash.open_cart) {
            if (!handledOpenCart.current) {
                setOpen(true);
                handledOpenCart.current = true;
            }

            return;
        }

        handledOpenCart.current = false;
    }, [flash.open_cart]);

    return {
        open,
        setOpen,
        openCart: () => setOpen(true),
    };
}

export function CartDrawer({ open, onOpenChange }: CartDrawerProps) {
    const { cart } = usePage<SharedData>().props;
    const items = cart?.items ?? [];
    const itemCount = cart?.item_count ?? 0;
    const isEmpty = items.length === 0;

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent side="right" className="flex h-full w-full flex-col gap-0 overflow-hidden p-0 sm:max-w-md">
                <SheetHeader className="shrink-0 border-b border-neutral-200 px-4 py-4 pr-12 text-left dark:border-neutral-800 sm:px-6">
                    <SheetTitle className="text-lg">Your cart{itemCount > 0 ? ` (${itemCount})` : ''}</SheetTitle>
                    <SheetDescription className="sr-only">
                        Review items in your cart, then checkout or view the full cart page.
                    </SheetDescription>
                </SheetHeader>

                {isEmpty ? (
                    <div className="flex flex-1 flex-col items-center justify-center gap-4 px-6 py-12 text-center">
                        <span className="flex size-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-500 dark:bg-neutral-800">
                            <ShoppingBag className="size-6" />
                        </span>
                        <div className="space-y-1">
                            <p className="font-medium">Your cart is empty</p>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">Add something from the catalogue to get started.</p>
                        </div>
                        <Button
                            type="button"
                            onClick={() => {
                                onOpenChange(false);
                                router.visit('/products');
                            }}
                        >
                            Continue shopping
                        </Button>
                    </div>
                ) : (
                    <>
                        <ul className="min-h-0 flex-1 divide-y divide-neutral-200 overflow-y-auto overscroll-contain px-4 dark:divide-neutral-800 sm:px-6">
                            {items.map((line) => (
                                <CartDrawerLine key={line.id} line={line} onNavigate={() => onOpenChange(false)} />
                            ))}
                        </ul>

                        <div className="shrink-0 space-y-4 border-t border-neutral-200 bg-white p-4 pb-[max(1rem,env(safe-area-inset-bottom))] dark:border-neutral-800 dark:bg-neutral-950 sm:p-6 sm:pb-6">
                            <div className="flex items-baseline justify-between gap-4">
                                <div>
                                    <p className="text-sm font-medium">Subtotal</p>
                                    <p className="text-xs text-neutral-500 dark:text-neutral-400">Shipping calculated at checkout</p>
                                </div>
                                <p className="text-lg font-semibold tabular-nums">{cart?.subtotal.formatted}</p>
                            </div>

                            {cart && cart.discount.amount > 0 && (
                                <div className="flex justify-between text-sm text-emerald-700 dark:text-emerald-400">
                                    <span>Discount{cart.coupon_code ? ` (${cart.coupon_code})` : ''}</span>
                                    <span>-{cart.discount.formatted}</span>
                                </div>
                            )}

                            <div className="flex flex-col gap-2">
                                <Button asChild size="lg" className="h-11 w-full">
                                    <Link href="/checkout" onClick={() => onOpenChange(false)}>
                                        Checkout
                                    </Link>
                                </Button>
                                <Button asChild size="lg" variant="outline" className="h-11 w-full">
                                    <Link href="/cart" onClick={() => onOpenChange(false)}>
                                        View cart
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </>
                )}
            </SheetContent>
        </Sheet>
    );
}

function CartDrawerLine({ line, onNavigate }: { line: CartLine; onNavigate: () => void }) {
    const setQuantity = (quantity: number) => {
        if (quantity < 1) {
            return;
        }

        router.patch(
            `/cart/items/${line.id}`,
            { quantity },
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    };

    return (
        <li className="flex gap-3 py-4">
            <Link
                href={line.product.url}
                onClick={onNavigate}
                className="size-20 shrink-0 overflow-hidden rounded-lg bg-neutral-100 dark:bg-neutral-800"
            >
                {line.image ? (
                    <img src={line.image.url} alt={line.image.alt} className="size-full object-cover" />
                ) : (
                    <div className="flex size-full items-center justify-center text-neutral-400">
                        <ImageOff className="size-5" />
                    </div>
                )}
            </Link>

            <div className="flex min-w-0 flex-1 flex-col gap-2">
                <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                        <Link
                            href={line.product.url}
                            onClick={onNavigate}
                            className="line-clamp-2 text-sm font-medium underline-offset-4 hover:underline"
                        >
                            {line.product.title}
                        </Link>
                        {line.variant.options.length > 0 && (
                            <p className="text-xs text-neutral-500 dark:text-neutral-400">{line.variant.options.join(' / ')}</p>
                        )}
                    </div>
                    <p className="shrink-0 text-sm font-medium tabular-nums">{line.line_total.formatted}</p>
                </div>

                {!line.in_stock && <p className="text-xs text-amber-600 dark:text-amber-400">Reduce quantity — not enough stock.</p>}

                <div className="mt-auto flex items-center justify-between gap-2">
                    <div className="flex items-center rounded-md border border-neutral-200 dark:border-neutral-800">
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="size-8 rounded-r-none"
                            aria-label="Decrease quantity"
                            disabled={line.quantity <= 1}
                            onClick={() => setQuantity(line.quantity - 1)}
                        >
                            <Minus className="size-3.5" />
                        </Button>
                        <span className="w-8 text-center text-sm tabular-nums">{line.quantity}</span>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="size-8 rounded-l-none"
                            aria-label="Increase quantity"
                            disabled={line.quantity >= line.max_quantity}
                            onClick={() => setQuantity(line.quantity + 1)}
                        >
                            <Plus className="size-3.5" />
                        </Button>
                    </div>

                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="size-8 text-neutral-500 hover:text-rose-600"
                        aria-label={`Remove ${line.product.title}`}
                        onClick={() =>
                            router.delete(`/cart/items/${line.id}`, {
                                preserveScroll: true,
                                preserveState: true,
                            })
                        }
                    >
                        <Trash2 className="size-4" />
                    </Button>
                </div>
            </div>
        </li>
    );
}
