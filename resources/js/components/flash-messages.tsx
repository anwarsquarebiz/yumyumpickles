import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, X, XCircle } from 'lucide-react';
import { useEffect, useState } from 'react';

type FlashTone = 'success' | 'error' | 'warning';

const toneStyles: Record<FlashTone, { container: string; icon: typeof CheckCircle2 }> = {
    success: {
        container: 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100',
        icon: CheckCircle2,
    },
    error: {
        container: 'border-rose-200 bg-rose-50 text-rose-900 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-100',
        icon: XCircle,
    },
    warning: {
        container: 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100',
        icon: AlertTriangle,
    },
};

export function FlashMessages() {
    const { flash } = usePage<SharedData>().props;
    const [dismissed, setDismissed] = useState<FlashTone[]>([]);

    /**
     * Reset dismissals whenever a new flash arrives so a repeat of the same
     * message on a later request is still shown.
     */
    useEffect(() => {
        setDismissed([]);
    }, [flash.success, flash.error, flash.warning]);

    const messages = (['success', 'error', 'warning'] as FlashTone[])
        .map((tone) => ({ tone, message: flash[tone] }))
        .filter((entry): entry is { tone: FlashTone; message: string } => Boolean(entry.message) && !dismissed.includes(entry.tone));

    if (messages.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-col gap-2 pt-4" role="status" aria-live="polite">
            {messages.map(({ tone, message }) => {
                const { container, icon: Icon } = toneStyles[tone];

                return (
                    <div key={tone} className={`flex items-start gap-3 rounded-lg border px-4 py-3 text-sm ${container}`}>
                        <Icon className="mt-0.5 size-4 shrink-0" />
                        <p className="flex-1">{message}</p>
                        <button
                            type="button"
                            onClick={() => setDismissed((current) => [...current, tone])}
                            className="rounded p-0.5 opacity-60 transition-opacity hover:opacity-100"
                            aria-label="Dismiss"
                        >
                            <X className="size-4" />
                        </button>
                    </div>
                );
            })}
        </div>
    );
}
