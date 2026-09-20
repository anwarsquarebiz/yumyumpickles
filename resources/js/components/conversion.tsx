import { Link } from '@inertiajs/react';
import { Gift, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

export function ConversionLayer() {
    return (
        <>
            <ExitIntent />
            <SocialProofToast />
        </>
    );
}

function ExitIntent() {
    const [open, setOpen] = useState(false);

    useEffect(() => {
        if (window.sessionStorage.getItem('yumyum-exit')) {
            return;
        }

        const onLeave = (event: MouseEvent) => {
            if (event.clientY <= 8) {
                setOpen(true);
                window.sessionStorage.setItem('yumyum-exit', '1');
                window.removeEventListener('mouseout', onLeave);
            }
        };

        window.addEventListener('mouseout', onLeave);

        return () => window.removeEventListener('mouseout', onLeave);
    }, []);

    if (!open) {
        return null;
    }

    return (
        <div className="bg-brand-deep/55 fixed inset-0 z-50 grid place-items-center p-4">
            <div className="bg-background relative w-full max-w-md rounded-2xl p-8 text-center shadow-2xl">
                <button type="button" className="hover:bg-muted absolute top-3 right-3 rounded-full p-2" onClick={() => setOpen(false)} aria-label="Close offer">
                    <X className="size-4" />
                </button>
                <Gift className="text-primary mx-auto size-10" />
                <p className="text-primary mt-4 font-script text-4xl">Wait — one more spoon?</p>
                <h2 className="mt-2 text-3xl font-extrabold">10% off your first jars</h2>
                <p className="text-muted-foreground mt-3 text-sm leading-6">
                    Use code <b>YUMYUM10</b> at checkout. Handmade, sun-cured, and packed like we are sending it home.
                </p>
                <Button asChild size="lg" className="mt-6 w-full" onClick={() => setOpen(false)}>
                    <Link href="/shop">Shop pickles</Link>
                </Button>
            </div>
        </div>
    );
}

function SocialProofToast() {
    const events = usePage<SharedData>().props.content?.socialProofEvents ?? [];
    const [event, setEvent] = useState<(typeof events)[number] | null>(null);

    useEffect(() => {
        if (events.length === 0) {
            return;
        }

        let index = 0;
        const tick = () => {
            setEvent(events[index % events.length] ?? null);
            index += 1;
            window.setTimeout(() => setEvent(null), 4200);
        };
        const start = window.setTimeout(tick, 5000);
        const loop = window.setInterval(tick, 14000);

        return () => {
            window.clearTimeout(start);
            window.clearInterval(loop);
        };
    }, [events]);

    if (!event) {
        return null;
    }

    return (
        <div className="border-border bg-background/95 pointer-events-none fixed bottom-24 left-4 z-40 max-w-xs rounded-xl border p-3 shadow-xl sm:left-6">
            <p className="text-sm font-bold">
                {event.name} in {event.city}
            </p>
            <p className="text-muted-foreground text-xs">just ordered {event.product}</p>
        </div>
    );
}
