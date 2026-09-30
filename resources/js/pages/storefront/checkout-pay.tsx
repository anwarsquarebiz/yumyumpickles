import { Button } from '@/components/ui/button';
import { Breadcrumbs, PageIntro } from '@/components/yumyum';
import StorefrontLayout from '@/layouts/storefront-layout';
import { type Money, type SeoMeta } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';

interface RazorpayOptions {
    key: string;
    order_id: string;
    amount: number;
    currency: string;
    name: string;
    description: string;
    prefill: { name: string; email: string; contact: string };
    notes: Record<string, string>;
}

interface RazorpaySuccessResponse {
    razorpay_payment_id: string;
    razorpay_order_id: string;
    razorpay_signature: string;
}

interface RazorpayInstance {
    open: () => void;
    on: (event: 'payment.failed', handler: (response: { error?: { description?: string } }) => void) => void;
}

declare global {
    interface Window {
        Razorpay?: new (
            options: RazorpayOptions & {
                handler: (response: RazorpaySuccessResponse) => void;
                modal: { ondismiss: () => void };
            },
        ) => RazorpayInstance;
    }
}

interface CheckoutPayProps {
    order_number: string;
    grand_total: Money;
    callback_url: string;
    complete_url: string;
    checkout_script: string;
    razorpay: RazorpayOptions;
    seo: SeoMeta;
}

type PayState = 'loading' | 'ready' | 'open' | 'dismissed' | 'submitting' | 'unavailable';

const loadCheckoutScript = (src: string): Promise<void> =>
    new Promise((resolve, reject) => {
        if (window.Razorpay) {
            resolve();

            return;
        }

        const existing = document.querySelector<HTMLScriptElement>(`script[src="${src}"]`);
        const script = existing ?? document.createElement('script');

        script.addEventListener('load', () => resolve(), { once: true });
        script.addEventListener('error', () => reject(new Error('Razorpay checkout failed to load.')), { once: true });

        if (!existing) {
            script.src = src;
            script.async = true;
            document.body.appendChild(script);
        }
    });

export default function CheckoutPay({ order_number, grand_total, callback_url, complete_url, checkout_script, razorpay, seo }: CheckoutPayProps) {
    const [state, setState] = useState<PayState>('loading');
    const [failure, setFailure] = useState<string | null>(null);

    const openCheckout = useCallback(() => {
        if (!window.Razorpay) {
            setState('unavailable');

            return;
        }

        const instance = new window.Razorpay({
            ...razorpay,
            handler: (response) => {
                setState('submitting');
                router.post(callback_url, { ...response });
            },
            modal: { ondismiss: () => setState((current) => (current === 'submitting' ? current : 'dismissed')) },
        });

        instance.on('payment.failed', (response) => setFailure(response.error?.description ?? 'The payment did not go through.'));

        setFailure(null);
        setState('open');
        instance.open();
    }, [razorpay, callback_url]);

    useEffect(() => {
        let cancelled = false;

        loadCheckoutScript(checkout_script)
            .then(() => {
                if (!cancelled) {
                    setState('ready');
                    openCheckout();
                }
            })
            .catch(() => !cancelled && setState('unavailable'));

        return () => {
            cancelled = true;
        };
    }, [checkout_script, openCheckout]);

    return (
        <StorefrontLayout>
            <Head title={seo.title} />
            <PageIntro eyebrow="Almost there" title="Complete your payment" copy={`Order ${order_number} is reserved for you. Pay securely with Razorpay to confirm it.`} />
            <Breadcrumbs items={[{ label: 'Cart', href: '/cart' }, { label: 'Checkout', href: '/checkout' }, { label: 'Payment' }]} />

            <div className="section-shell pb-20">
                <div className="border-border mx-auto flex max-w-lg flex-col items-center gap-4 rounded-2xl border p-8 text-center">
                    <p className="text-muted-foreground text-sm">Amount to pay</p>
                    <p className="text-3xl font-extrabold">{grand_total.formatted}</p>

                    {state === 'loading' && <p className="text-muted-foreground text-sm">Opening secure payment…</p>}
                    {state === 'submitting' && <p className="text-muted-foreground text-sm">Confirming your payment…</p>}
                    {state === 'unavailable' && (
                        <p className="text-sm text-red-600 dark:text-red-400">We could not load the payment window. Check your connection and try again.</p>
                    )}
                    {failure && <p className="text-sm text-red-600 dark:text-red-400">{failure}</p>}
                    {state === 'dismissed' && !failure && <p className="text-muted-foreground text-sm">Payment was not completed. Your order is saved; you can try again.</p>}

                    <div className="flex flex-wrap justify-center gap-3">
                        <Button onClick={state === 'unavailable' ? () => window.location.reload() : openCheckout} disabled={state === 'loading' || state === 'open' || state === 'submitting'}>
                            {state === 'dismissed' || state === 'unavailable' ? 'Try again' : 'Pay now'}
                        </Button>
                        <Button asChild variant="ghost">
                            <Link href={complete_url}>View order</Link>
                        </Button>
                    </div>
                </div>
            </div>
        </StorefrontLayout>
    );
}
