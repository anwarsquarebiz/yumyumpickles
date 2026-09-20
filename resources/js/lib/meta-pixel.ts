export type MetaPixelEventName = 'PageView' | 'ViewContent' | 'AddToCart' | 'InitiateCheckout' | 'Purchase';

export type MetaPixelParams = Record<string, unknown>;

declare global {
    interface Window {
        fbq?: (...args: unknown[]) => void;
        _fbq?: (...args: unknown[]) => void;
    }
}

export function trackMetaEvent(event: MetaPixelEventName, params?: MetaPixelParams, eventId?: string): void {
    if (typeof window === 'undefined' || typeof window.fbq !== 'function') {
        return;
    }

    const options = eventId ? { eventID: eventId } : undefined;

    if (params !== undefined && options !== undefined) {
        window.fbq('track', event, params, options);

        return;
    }

    if (params !== undefined) {
        window.fbq('track', event, params);

        return;
    }

    if (options !== undefined) {
        window.fbq('track', event, {}, options);

        return;
    }

    window.fbq('track', event);
}

export function moneyValue(decimal: string): number {
    const parsed = Number.parseFloat(decimal);

    return Number.isFinite(parsed) ? parsed : 0;
}

export function newMetaEventId(): string {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    return `${Date.now()}-${Math.random().toString(16).slice(2)}`;
}
