import { newMetaEventId, trackMetaEvent } from '@/lib/meta-pixel';
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

const PIXEL_SCRIPT_ID = 'meta-pixel-sdk';

type FbqFn = ((...args: unknown[]) => void) & { queue: unknown[]; loaded: boolean; version: string };

function loadPixel(pixelId: string): void {
    if (typeof window === 'undefined' || typeof window.fbq === 'function') {
        return;
    }

    const fbq = ((...args: unknown[]) => {
        fbq.queue.push(args);
    }) as FbqFn;

    fbq.queue = [];
    fbq.loaded = true;
    fbq.version = '2.0';

    window.fbq = fbq;
    window._fbq = fbq;

    if (!document.getElementById(PIXEL_SCRIPT_ID)) {
        const script = document.createElement('script');
        script.id = PIXEL_SCRIPT_ID;
        script.async = true;
        script.src = 'https://connect.facebook.net/en_US/fbevents.js';
        document.head.appendChild(script);
    }

    window.fbq('init', pixelId);
}

/**
 * Fires SPA PageView events. The Pixel snippet itself lives in the document head.
 */
export function MetaPixel() {
    const { meta_pixel } = usePage<SharedData>().props;
    const pixelId = meta_pixel?.enabled ? meta_pixel.pixel_id : null;

    useEffect(() => {
        if (!pixelId) {
            return;
        }

        const alreadyLoaded = typeof window.fbq === 'function';

        if (!alreadyLoaded) {
            loadPixel(pixelId);
        }

        let skipFirst = alreadyLoaded;

        return router.on('navigate', () => {
            if (skipFirst) {
                skipFirst = false;

                return;
            }

            trackMetaEvent('PageView', undefined, newMetaEventId());
        });
    }, [pixelId]);

    return null;
}
