import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

const GTM_SCRIPT_ID = 'google-tag-manager';

function pageViewParams(): { page_title: string; page_location: string; page_path: string } {
    return {
        page_title: document.title,
        page_location: window.location.href,
        page_path: `${window.location.pathname}${window.location.search}`,
    };
}

function loadGtm(containerId: string): void {
    if (typeof window === 'undefined' || document.getElementById(GTM_SCRIPT_ID)) {
        return;
    }

    window.dataLayer = window.dataLayer ?? [];
    window.dataLayer.push({
        'gtm.start': Date.now(),
        event: 'gtm.js',
    });

    const script = document.createElement('script');
    script.id = GTM_SCRIPT_ID;
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtm.js?id=${encodeURIComponent(containerId)}`;
    document.head.appendChild(script);
}

/**
 * Fires SPA page_view events. The container snippet itself lives in the document head.
 */
export function GoogleTagManager() {
    const { google_tag_manager } = usePage<SharedData>().props;
    const containerId = google_tag_manager?.enabled ? google_tag_manager.container_id : null;

    useEffect(() => {
        if (!containerId) {
            return;
        }

        const alreadyLoaded = Boolean(document.getElementById(GTM_SCRIPT_ID));

        if (!alreadyLoaded) {
            loadGtm(containerId);
        }

        let skipFirst = alreadyLoaded;

        return router.on('navigate', () => {
            if (skipFirst) {
                skipFirst = false;

                return;
            }

            window.dataLayer = window.dataLayer ?? [];
            window.dataLayer.push({
                event: 'page_view',
                ...pageViewParams(),
            });
        });
    }, [containerId]);

    return null;
}
