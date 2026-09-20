import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: AuthUser | null;
}

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'customer';
    is_admin: boolean;
    email_verified_at: string | null;
    avatar?: string | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface StorefrontContent {
    brand: {
        name: string;
        tagline: string;
        phone: string;
        whatsapp: string;
        email: string;
        instagram: string;
        facebook: string;
        youtube: string;
    };
    announcements: string[];
    storySteps: Array<{ title: string; copy: string; image?: string | null; position?: string }>;
    whyChoose: Array<{ title: string; copy: string }>;
    videos: Array<{ title: string; tag: string; views: string; image?: string | null }>;
    testimonials: Array<{ name: string; city: string; initials: string; rating: number; text: string }>;
    instagramPosts: Array<{ image?: string | null; alt: string; likes: number; reel?: boolean; position?: string }>;
    faqs: Array<{ q: string; a: string }>;
    socialProofEvents: Array<{ name: string; city: string; product: string }>;
    policies: Record<string, { title: string; intro: string; sections: Array<{ title: string; body: string }> }>;
    recipes: Array<{
        id: string;
        name: string;
        time: string;
        difficulty: string;
        pickle: string;
        excerpt: string | null;
        image?: string | null;
        position?: string;
        steps: string[];
    }>;
    categories: Array<{ name: string; slug: string; label: string; tagline: string | null; image?: string | null; position?: string }>;
}

export interface StoreInfo {
    name: string;
    email: string;
    phone: string;
    currency: string;
    social: Record<string, string>;
    logo_url: string | null;
    favicon_url: string | null;
    free_shipping_threshold: Money;
}

export interface MetaPixelConfig {
    enabled: boolean;
    pixel_id: string;
}

export interface GoogleAnalyticsConfig {
    enabled: boolean;
    measurement_id: string;
}

export interface GoogleTagManagerConfig {
    enabled: boolean;
    container_id: string;
}

export interface CurrencyOption {
    code: string;
    name: string;
    symbol: string;
}

export interface DisplayCurrency {
    current: string;
    base: string;
    options: CurrencyOption[];
}

export interface FlashMessages {
    success?: string | null;
    error?: string | null;
    warning?: string | null;
    /** When true, the storefront cart drawer should open (e.g. after add-to-cart). */
    open_cart?: boolean;
}

export interface NavLink {
    title: string;
    url: string;
    external?: boolean;
}

/** Storefront navigation, shared on every storefront request. */
export interface StorefrontNavigation {
    header: NavLink[];
    collections: NavLink[];
    pages: NavLink[];
}

/** Cart summary shared on every storefront request for the header and drawer. */
export interface CartSummary {
    item_count: number;
    subtotal: Money;
    discount: Money;
    total: Money;
    coupon_code: string | null;
    items: CartLine[];
}


export interface SharedData {
    name: string;
    auth: Auth;
    store: StoreInfo;
    currency: DisplayCurrency;
    flash: FlashMessages;
    navigation?: StorefrontNavigation;
    cart?: CartSummary;
    content?: StorefrontContent | null;
    catalog?: ProductSummary[];
    meta_pixel?: MetaPixelConfig | null;
    google_analytics?: GoogleAnalyticsConfig | null;
    google_tag_manager?: GoogleTagManagerConfig | null;
    quote?: { message: string; author: string };
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    role?: 'admin' | 'customer';
    phone?: string | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

/**
 * A monetary amount serialised by App\Support\Money. `amount` is in minor units
 * (for example cents); use `formatted` for display and `decimal` for form inputs.
 */
export interface Money {
    amount: number;
    currency: string;
    formatted: string;
    decimal: string;
}

/**
 * A JsonResource collection wrapping a length-aware paginator.
 * Laravel serialises this as `{ data, links: { first, last, prev, next }, meta }`.
 */
export interface Paginated<T> {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        from: number | null;
        to: number | null;
        total: number;
        path: string;
        links: PaginationLink[];
    };
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface SelectOption {
    value: string;
    label: string;
}

export interface IdOption {
    value: number;
    label: string;
}

export interface SeoMeta {
    title: string;
    description?: string | null;
}

export interface HomeBanner {
    id: number;
    title: string;
    subtitle: string | null;
    button_label: string | null;
    button_url: string | null;
    image_url: string | null;
    alt: string | null;
    position: number;
    status: PublishStatus;
    published_at: string | null;
    starts_at: string | null;
    ends_at: string | null;
    is_published: boolean;
}

/* Catalog ---------------------------------------------------------------- */

export type ProductStatus = 'draft' | 'active' | 'archived';
export type PublishStatus = 'draft' | 'published';
export type InventoryPolicyValue = 'deny' | 'continue';

export interface ProductImage {
    id: number;
    url: string;
    alt: string | null;
    position: number;
    product_variant_id: number | null;
}

export interface ProductVariant {
    id: number;
    title: string;
    display_title: string;
    sku: string | null;
    barcode: string | null;
    price: Money;
    compare_at_price: Money | null;
    option1: string | null;
    option2: string | null;
    option3: string | null;
    option_values: string[];
    inventory_quantity: number;
    track_inventory: boolean;
    inventory_policy: InventoryPolicyValue;
    weight: string | null;
    weight_unit: string;
    position: number;
    in_stock: boolean;
    low_stock: boolean;
    on_sale: boolean;
    max_quantity: number;
}

export interface ProductOption {
    id: number;
    name: string;
    position: number;
    values: string[];
}

/** A product as rendered in a grid. */
export interface ProductSummary {
    id: number;
    title: string;
    name?: string;
    slug: string;
    description: string | null;
    story?: string | null;
    vendor: string | null;
    product_type: string | null;
    category?: string | null;
    spice?: string;
    badge?: string | null;
    is_new?: boolean;
    ingredients?: string[];
    nutrition?: Array<{ label?: string; value?: string } | string>;
    pairs_with?: string[];
    url: string;
    price_from: Money;
    compare_at_price: Money | null;
    price?: number;
    originalPrice?: number;
    weight?: string;
    weights?: Array<{ label: string; price: number; originalPrice: number; variant_id: number }>;
    default_variant_id?: number;
    rating?: number;
    reviews?: number;
    on_sale: boolean;
    in_stock: boolean;
    inStock?: boolean;
    variant_count: number;
    image_url?: string | null;
    image_position?: string;
    imagePosition?: string;
    image: { url: string; alt: string } | null;
}

export interface ProductDetail {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    body_html: string | null;
    status: ProductStatus;
    vendor: string | null;
    product_type: string | null;
    seo_title: string | null;
    seo_description: string | null;
    meta_title: string;
    meta_description: string | null;
    published_at: string | null;
    is_published: boolean;
    in_stock: boolean;
    url: string;
    variants: ProductVariant[];
    images: ProductImage[];
    options: ProductOption[];
    collections?: CollectionSummary[];
    metadata?: {
        spice?: string;
        story?: string;
        ingredients?: string[];
        nutrition?: Array<{ label?: string; value?: string } | string>;
        pairs_with?: string[];
        badge?: string;
        is_new?: boolean;
        [key: string]: unknown;
    };
    name?: string;
    category?: string | null;
    spice?: string;
    story?: string | null;
    ingredients?: string[];
    nutrition?: Array<{ label?: string; value?: string } | string>;
    pairs_with?: string[];
    badge?: string | null;
    is_new?: boolean;
    rating?: number;
    review_count?: number;
    customer_reviews?: Array<{ name: string; city: string | null; rating: number; date: string | null; text: string }>;
    gallery?: Array<{ src: string; alt: string; position: string }>;
    default_variant_id?: number;
}

export interface CollectionSummary {
    id: number;
    title: string;
    slug: string;
    url: string;
    image_url: string | null;
}

export interface CollectionDetail {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    status: PublishStatus;
    seo_title: string | null;
    seo_description: string | null;
    meta_title: string;
    meta_description: string | null;
    position: number;
    published_at: string | null;
    is_published: boolean;
    image_url: string | null;
    url: string;
    products_count?: number;
    product_ids?: number[];
}

/** Admin listing row, as produced by ProductRepository::paginateForAdmin(). */
export interface AdminProductRow {
    id: number;
    title: string;
    slug: string;
    status: ProductStatus;
    vendor: string | null;
    product_type: string | null;
    published_at: string | null;
    variants_count: number;
    min_price_amount: number | null;
    total_inventory: number | null;
    images: ProductImage[];
}

export interface CatalogFilters {
    search?: string | null;
    sort?: string | null;
    in_stock?: boolean | null;
    min_price?: string | null;
    max_price?: string | null;
}

/* Cart and coupons ------------------------------------------------------- */

export interface CartLine {
    id: number;
    quantity: number;
    unit_price: Money;
    line_total: Money;
    /** Upper bound for the quantity stepper, derived from stock. */
    max_quantity: number;
    in_stock: boolean;
    variant: {
        id: number;
        title: string;
        sku: string | null;
        options: string[];
    };
    product: {
        id: number;
        title: string;
        slug: string;
        url: string;
    };
    image: { url: string; alt: string } | null;
}

export interface CartTotals {
    subtotal: Money;
    discount: Money;
    total: Money;
    item_count: number;
    coupon_code: string | null;
}

export interface AppliedCoupon {
    code: string;
    description: string | null;
    value: string;
    /** False when the code is on the cart but no longer eligible. */
    applied: boolean;
}

export interface CartDetail {
    id: number;
    currency: string;
    items: CartLine[];
    totals: CartTotals;
    coupon: AppliedCoupon | null;
}

export interface AdminCoupon {
    id: number;
    code: string;
    description: string | null;
    type: string;
    type_label: string;
    value: number;
    /** Percent for percentage codes, decimal currency for fixed ones. */
    value_input: string;
    display_value: string;
    minimum_subtotal: Money | null;
    minimum_subtotal_input: string | null;
    usage_limit: number | null;
    usage_limit_per_customer: number | null;
    used_count: number;
    starts_at: string | null;
    expires_at: string | null;
    is_active: boolean;
    redeemable: boolean;
    status_label: string;
}

export interface CouponFilters {
    search?: string | null;
    status?: string | null;
}

export interface AddressRecord {
    id: number;
    type: 'shipping' | 'billing';
    first_name: string;
    last_name: string;
    company: string | null;
    address_line1: string;
    address_line2: string | null;
    city: string;
    province: string | null;
    postal_code: string;
    country_code: string;
    phone: string | null;
    is_default: boolean;
    one_line: string;
}

export interface OrderItemRow {
    id: number;
    product_id: number | null;
    product_variant_id: number | null;
    product_title: string;
    variant_title: string | null;
    sku: string | null;
    quantity: number;
    unit_price: Money;
    subtotal: Money;
    discount: Money;
    total: Money;
}

export interface OrderTimelineEvent {
    id: number;
    from_status: string | null;
    to_status: string;
    note: string | null;
    actor: string | null;
    created_at: string | null;
}

export interface PaymentRow {
    id: number;
    gateway: string;
    gateway_reference: string | null;
    status: string;
    status_label: string;
    amount: Money;
    failure_reason: string | null;
    paid_at: string | null;
}

export interface RefundRow {
    id: number;
    amount: Money;
    reason: string | null;
    status: string;
    status_label: string;
    restock: boolean;
    processed_at: string | null;
}

export interface OrderDetail {
    id: number;
    order_number: string;
    customer_name: string | null;
    email: string;
    phone: string | null;
    status: string;
    status_label: string;
    status_tone: string;
    currency: string;
    subtotal: Money;
    discount: Money;
    shipping: Money;
    tax: Money;
    grand_total: Money;
    refunded: Money;
    refundable: Money;
    coupon_code: string | null;
    shipping_address: Record<string, string | null> | null;
    billing_address: Record<string, string | null> | null;
    shipping_address_lines: string[];
    billing_address_lines: string[];
    shipping_method_name: string | null;
    customer_note: string | null;
    staff_note: string | null;
    placed_at: string | null;
    created_at: string | null;
    items?: OrderItemRow[];
    timeline?: OrderTimelineEvent[];
    payments?: PaymentRow[];
    refunds?: RefundRow[];
    customer?: { id: number; name: string; email: string } | null;
    items_count?: number;
    allowed_transitions: { value: string; label: string }[];
    is_refundable: boolean;
    is_paid: boolean;
    meta_event_id?: string | null;
}

export interface CmsPage {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    content: string | null;
    status: PublishStatus;
    template: 'default' | 'contact';
    seo_title: string | null;
    seo_description: string | null;
    meta_title: string;
    meta_description: string | null;
    published_at: string | null;
    is_published: boolean;
    url: string;
}

export interface BlogRecord {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    seo_title: string | null;
    seo_description: string | null;
    meta_title: string;
    meta_description: string | null;
    url: string;
    posts_count?: number;
}

export interface BlogPostRecord {
    id: number;
    blog_id: number;
    blog_category_id: number | null;
    title: string;
    slug: string;
    excerpt: string | null;
    content: string | null;
    status: PublishStatus;
    seo_title: string | null;
    seo_description: string | null;
    meta_title: string;
    meta_description: string | null;
    published_at: string | null;
    is_published: boolean;
    featured_image_url: string | null;
    url: string | null;
    category?: { id: number; name: string; slug: string } | null;
    author?: { id: number; name: string } | null;
}

export interface ShippingQuote {
    id: number;
    name: string;
    description: string | null;
    amount: Money;
}

export interface DashboardMetrics {
    orders: number;
    open_orders: number;
    customers: number;
    products: number;
    revenue: Money;
    today_revenue: Money;
}
