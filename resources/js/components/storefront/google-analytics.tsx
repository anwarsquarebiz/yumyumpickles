import { trackGoogleEvent } from '@/lib/google-analytics';
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

const GTAG_SCRIPT_ID = 'google-analytics-gtag';

function loadGtag(measurementId: string): void {
    if (typeof window === 'undefined' || document.getElementById(GTAG_SCRIPT_ID)) {
        return;
    }

    window.dataLayer = window.dataLayer ?? [];

    if (typeof window.gtag !== 'function') {
        window.gtag = function gtag(...args: unknown[]) {
            window.dataLayer?.push(args);
        };
    }

    const script = document.createElement('script');
    script.id = GTAG_SCRIPT_ID;
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(measurementId)}`;
    document.head.appendChild(script);

    window.gtag('js', new Date());
    window.gtag('config', measurementId, { send_page_view: false });
}

function pageViewParams(): { page_title: string; page_location: string; page_path: string } {
    return {
        page_title: document.title,
        page_location: window.location.href,
        page_path: `${window.location.pathname}${window.location.search}`,
    };
}

/**
 * Fires SPA page_view events. gtag.js itself lives in the document head.
 */
export function GoogleAnalytics() {
    const { google_analytics } = usePage<SharedData>().props;
    const measurementId = google_analytics?.enabled ? google_analytics.measurement_id : null;

    useEffect(() => {
        if (!measurementId) {
            return;
        }

        const alreadyLoaded = Boolean(document.getElementById(GTAG_SCRIPT_ID));

        if (!alreadyLoaded) {
            loadGtag(measurementId);
        }

        let skipFirst = alreadyLoaded;

        return router.on('navigate', () => {
            if (skipFirst) {
                skipFirst = false;

                return;
            }

            trackGoogleEvent('page_view', pageViewParams());
        });
    }, [measurementId]);

    return null;
}
