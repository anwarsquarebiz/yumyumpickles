import { moneyValue } from '@/lib/meta-pixel';

export type GoogleAnalyticsEventName = 'page_view' | 'view_item' | 'add_to_cart' | 'begin_checkout' | 'purchase';

export type GoogleAnalyticsItem = {
    item_id: string;
    item_name?: string;
    price?: number;
    quantity?: number;
};

export type GoogleAnalyticsParams = {
    currency?: string;
    value?: number;
    items?: GoogleAnalyticsItem[];
    transaction_id?: string;
    page_title?: string;
    page_location?: string;
    page_path?: string;
};

declare global {
    interface Window {
        dataLayer?: unknown[];
        gtag?: (...args: unknown[]) => void;
    }
}

export function trackGoogleEvent(event: GoogleAnalyticsEventName, params?: GoogleAnalyticsParams): void {
    if (typeof window === 'undefined') {
        return;
    }

    window.dataLayer = window.dataLayer ?? [];
    window.dataLayer.push(params === undefined ? { event } : { event, ...params });

    if (typeof window.gtag !== 'function') {
        return;
    }

    if (params === undefined) {
        window.gtag('event', event);

        return;
    }

    window.gtag('event', event, params);
}

export function googleItem(id: string, price: string | number, quantity = 1, name?: string): GoogleAnalyticsItem {
    return {
        item_id: id,
        item_name: name,
        price: typeof price === 'number' ? price : moneyValue(price),
        quantity,
    };
}
