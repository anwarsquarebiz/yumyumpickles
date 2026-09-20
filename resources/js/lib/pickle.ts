import { router } from '@inertiajs/react';
import { type ProductSummary } from '@/types';

export type PickleWeight = {
    label: string;
    price: number;
    originalPrice: number;
    variant_id: number;
};

export type PickleProduct = {
    id: string;
    numericId: number;
    name: string;
    slug: string;
    category: string;
    spice: string;
    price: number;
    originalPrice: number;
    rating: number;
    reviews: number;
    weight: string;
    weights: PickleWeight[];
    description: string;
    story: string;
    ingredients: string[];
    nutrition: Array<{ label?: string; value?: string } | string>;
    image: string;
    gallery: Array<{ src: string; position: string; alt: string }>;
    imagePosition: string;
    inStock: boolean;
    pairsWith: string[];
    isNew?: boolean;
    badge?: string | null;
    default_variant_id: number;
    url: string;
};

export function formatPrice(value: number): string {
    return `₹${Math.round(Number(value) || 0).toLocaleString('en-IN')}`;
}

export function unwrapList<T>(value: T[] | { data: T[] } | undefined | null): T[] {
    if (Array.isArray(value)) {
        return value;
    }

    if (value && Array.isArray(value.data)) {
        return value.data;
    }

    return [];
}

export function unwrapData<T>(value: T | { data: T } | null | undefined): T {
    if (value && typeof value === 'object' && 'data' in value) {
        return (value as { data: T }).data;
    }

    return value as T;
}

export function toPickle(product: ProductSummary): PickleProduct {
    const image = product.image_url ?? product.image?.url ?? '';

    return {
        id: product.slug,
        numericId: product.id,
        name: product.name ?? product.title,
        slug: product.slug,
        category: product.category ?? product.product_type ?? '',
        spice: product.spice ?? 'Medium',
        price: product.price ?? Number(product.price_from?.decimal ?? 0),
        originalPrice: product.originalPrice ?? Number(product.compare_at_price?.decimal ?? product.price_from?.decimal ?? 0),
        rating: product.rating ?? 4.8,
        reviews: product.reviews ?? 0,
        weight: product.weight ?? '400g',
        weights: product.weights ?? [],
        description: product.description ?? '',
        story: product.story ?? product.description ?? '',
        ingredients: product.ingredients ?? [],
        nutrition: product.nutrition ?? [],
        image,
        gallery: image ? [{ src: image, position: 'center', alt: product.title }] : [],
        imagePosition: product.imagePosition ?? product.image_position ?? 'center',
        inStock: product.inStock ?? product.in_stock,
        pairsWith: product.pairs_with ?? [],
        isNew: product.is_new,
        badge: product.badge,
        default_variant_id: product.default_variant_id ?? 0,
        url: product.url,
    };
}

export function variantIdForWeight(product: PickleProduct, weight: string): number {
    return product.weights.find((option) => option.label === weight)?.variant_id ?? product.default_variant_id;
}

export function priceForWeight(product: PickleProduct, weight: string): number {
    return product.weights.find((option) => option.label === weight)?.price ?? product.price;
}

export function originalPriceForWeight(product: PickleProduct, weight: string): number {
    return product.weights.find((option) => option.label === weight)?.originalPrice ?? product.originalPrice;
}

export function addToCart(variantId: number, quantity = 1, visitCheckout = false): void {
    if (!variantId) {
        return;
    }

    router.post(
        '/cart/items',
        { product_variant_id: variantId, quantity },
        {
            preserveScroll: true,
            onSuccess: () => {
                if (visitCheckout) {
                    router.visit('/checkout');
                }
            },
        },
    );
}

export function addManyToCart(items: Array<{ variantId: number; quantity?: number }>): void {
    const payload = items
        .filter((item) => item.variantId)
        .map((item) => ({ product_variant_id: item.variantId, quantity: item.quantity ?? 1 }));

    if (payload.length === 0) {
        return;
    }

    router.post('/cart/items', { items: payload }, { preserveScroll: true });
}

const WISHLIST_KEY = 'yumyum-wishlist';
const RECENT_KEY = 'yumyum-recent';

export function readWishlist(): string[] {
    if (typeof window === 'undefined') {
        return [];
    }

    try {
        const raw = window.localStorage.getItem(WISHLIST_KEY);
        return raw ? (JSON.parse(raw) as string[]) : [];
    } catch {
        return [];
    }
}

export function writeWishlist(ids: string[]): void {
    window.localStorage.setItem(WISHLIST_KEY, JSON.stringify(ids));
}

export function readRecentlyViewed(): string[] {
    if (typeof window === 'undefined') {
        return [];
    }

    try {
        const raw = window.localStorage.getItem(RECENT_KEY);
        return raw ? (JSON.parse(raw) as string[]) : [];
    } catch {
        return [];
    }
}

export function rememberProduct(slug: string): void {
    const next = [slug, ...readRecentlyViewed().filter((id) => id !== slug)].slice(0, 8);
    window.localStorage.setItem(RECENT_KEY, JSON.stringify(next));
}
