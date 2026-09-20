import { BrandingForm } from '@/components/admin/branding-form';
import { FormCard, FormField } from '@/components/admin/form-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem } from '@/types';
import { useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

interface MetaAdsProps {
    enabled: boolean;
    pixel_id: string;
    test_event_code: string;
    advanced_matching: boolean;
    has_access_token: boolean;
}

interface GoogleAnalyticsProps {
    enabled: boolean;
    measurement_id: string;
}

interface GoogleTagManagerProps {
    enabled: boolean;
    container_id: string;
}

interface AdminSettingsProps {
    settings: Record<string, unknown>;
    meta_ads: MetaAdsProps;
    google_analytics: GoogleAnalyticsProps;
    google_tag_manager: GoogleTagManagerProps;
    branding: {
        logo_url: string | null;
        favicon_url: string | null;
    };
}

const str = (value: unknown): string => (value === null || value === undefined ? '' : String(value));

export default function AdminSettings({ settings, meta_ads, google_analytics, google_tag_manager, branding }: AdminSettingsProps) {
    const form = useForm({
        store: {
            name: str(settings['store.name']),
            email: str(settings['store.email']),
            phone: str(settings['store.phone']),
            address: str(settings['store.address']),
            tagline: str(settings['store.tagline']),
            whatsapp: str(settings['store.whatsapp']),
        },
        storefront: {
            announcements: Array.isArray(settings['storefront.announcements'])
                ? (settings['storefront.announcements'] as string[]).join('\n')
                : '',
            content_json: JSON.stringify(
                {
                    story_steps: settings['storefront.story_steps'] ?? [],
                    why_choose: settings['storefront.why_choose'] ?? [],
                    videos: settings['storefront.videos'] ?? [],
                    testimonials: settings['storefront.testimonials'] ?? [],
                    instagram: settings['storefront.instagram'] ?? [],
                    faqs: settings['storefront.faqs'] ?? [],
                    social_proof: settings['storefront.social_proof'] ?? [],
                    policies: settings['storefront.policies'] ?? {},
                },
                null,
                2,
            ),
        },
        checkout: {
            tax_rate_basis_points: Number(settings['checkout.tax_rate_basis_points'] ?? 0),
            guest_checkout_enabled: Boolean(settings['checkout.guest_checkout_enabled']),
        },
        seo: {
            default_title: str(settings['seo.default_title']),
            default_description: str(settings['seo.default_description']),
        },
        social: {
            facebook: str(settings['social.facebook']),
            instagram: str(settings['social.instagram']),
            twitter: str(settings['social.twitter']),
        },
        ads: {
            enabled: Boolean(meta_ads.enabled),
            pixel_id: meta_ads.pixel_id,
            access_token: '',
            test_event_code: meta_ads.test_event_code,
            advanced_matching: Boolean(meta_ads.advanced_matching),
        },
        google: {
            enabled: Boolean(google_analytics.enabled),
            measurement_id: google_analytics.measurement_id,
        },
        gtm: {
            enabled: Boolean(google_tag_manager.enabled),
            container_id: google_tag_manager.container_id,
        },
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        form.put('/admin/settings');
    };

    const adsError = (field: string): string | undefined => form.errors[`ads.${field}`];
    const googleError = (field: string): string | undefined => form.errors[`google.${field}`];
    const gtmError = (field: string): string | undefined => form.errors[`gtm.${field}`];

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Settings', href: '/admin/settings' },
    ];

    return (
        <AdminLayout breadcrumbs={breadcrumbs} title="Settings" description="Store details, branding, checkout, SEO and analytics.">
            <form onSubmit={submit} className="grid gap-6 lg:grid-cols-2">
                <FormCard title="Store">
                    <FormField label="Name" htmlFor="store_name">
                        <Input id="store_name" value={form.data.store.name} onChange={(event) => form.setData('store', { ...form.data.store, name: event.target.value })} />
                    </FormField>
                    <FormField label="Email" htmlFor="store_email">
                        <Input id="store_email" value={form.data.store.email} onChange={(event) => form.setData('store', { ...form.data.store, email: event.target.value })} />
                    </FormField>
                    <FormField label="Phone" htmlFor="store_phone">
                        <Input id="store_phone" value={form.data.store.phone} onChange={(event) => form.setData('store', { ...form.data.store, phone: event.target.value })} />
                    </FormField>
                    <FormField label="Tagline" htmlFor="store_tagline">
                        <Input id="store_tagline" value={form.data.store.tagline} onChange={(event) => form.setData('store', { ...form.data.store, tagline: event.target.value })} />
                    </FormField>
                    <FormField label="WhatsApp" htmlFor="store_whatsapp">
                        <Input id="store_whatsapp" value={form.data.store.whatsapp} onChange={(event) => form.setData('store', { ...form.data.store, whatsapp: event.target.value })} />
                    </FormField>
                </FormCard>
                <FormCard title="Storefront content">
                    <FormField label="Announcement ticker" htmlFor="announcements" hint="One message per line.">
                        <textarea
                            id="announcements"
                            value={form.data.storefront.announcements}
                            onChange={(event) => form.setData('storefront', { ...form.data.storefront, announcements: event.target.value })}
                            rows={4}
                            className="w-full rounded-md border border-neutral-300 bg-transparent px-3 py-2 text-sm dark:border-neutral-700"
                        />
                    </FormField>
                    <FormField label="Home / story JSON" htmlFor="content_json" hint="Recipes stay in Blogs. Policies can also be edited as Pages.">
                        <textarea
                            id="content_json"
                            value={form.data.storefront.content_json}
                            onChange={(event) => form.setData('storefront', { ...form.data.storefront, content_json: event.target.value })}
                            rows={12}
                            className="w-full rounded-md border border-neutral-300 bg-transparent px-3 py-2 font-mono text-xs dark:border-neutral-700"
                        />
                    </FormField>
                </FormCard>
                <FormCard title="Checkout">
                    <FormField label="Tax rate (basis points)" htmlFor="tax" hint="1000 = 10%.">
                        <Input
                            id="tax"
                            type="number"
                            value={form.data.checkout.tax_rate_basis_points}
                            onChange={(event) =>
                                form.setData('checkout', { ...form.data.checkout, tax_rate_basis_points: Number(event.target.value) })
                            }
                        />
                    </FormField>
                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={form.data.checkout.guest_checkout_enabled}
                            onChange={(event) => form.setData('checkout', { ...form.data.checkout, guest_checkout_enabled: event.target.checked })}
                        />
                        Allow guest checkout
                    </label>
                </FormCard>
                <FormCard title="SEO defaults">
                    <FormField label="Default title" htmlFor="seo_title">
                        <Input
                            id="seo_title"
                            value={form.data.seo.default_title}
                            onChange={(event) => form.setData('seo', { ...form.data.seo, default_title: event.target.value })}
                        />
                    </FormField>
                    <FormField label="Default description" htmlFor="seo_description">
                        <Input
                            id="seo_description"
                            value={form.data.seo.default_description}
                            onChange={(event) => form.setData('seo', { ...form.data.seo, default_description: event.target.value })}
                        />
                    </FormField>
                </FormCard>
                <FormCard
                    title="Meta ads"
                    description="Facebook and Instagram Pixel plus Conversions API. Ad spend is billed in Meta Ads Manager, not here."
                >
                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={form.data.ads.enabled}
                            onChange={(event) => form.setData('ads', { ...form.data.ads, enabled: event.target.checked })}
                        />
                        Enable Meta Pixel
                    </label>
                    <FormField
                        label="Pixel ID"
                        htmlFor="ads_pixel_id"
                        error={adsError('pixel_id')}
                        hint="The numeric Pixel / Dataset ID from Meta Events Manager."
                    >
                        <Input
                            id="ads_pixel_id"
                            inputMode="numeric"
                            value={form.data.ads.pixel_id}
                            onChange={(event) => form.setData('ads', { ...form.data.ads, pixel_id: event.target.value })}
                        />
                    </FormField>
                    <FormField
                        label="Conversions API access token"
                        htmlFor="ads_access_token"
                        error={adsError('access_token')}
                        hint={
                            meta_ads.has_access_token
                                ? 'A token is saved. Leave blank to keep it, or paste a new one to replace it.'
                                : 'Generate a system user token in Events Manager. It is stored encrypted and never shown again.'
                        }
                    >
                        <Input
                            id="ads_access_token"
                            type="password"
                            autoComplete="off"
                            value={form.data.ads.access_token}
                            onChange={(event) => form.setData('ads', { ...form.data.ads, access_token: event.target.value })}
                        />
                    </FormField>
                    <FormField
                        label="Test event code"
                        htmlFor="ads_test_event_code"
                        error={adsError('test_event_code')}
                        hint="Optional. Use while verifying events in Events Manager, then clear it."
                    >
                        <Input
                            id="ads_test_event_code"
                            value={form.data.ads.test_event_code}
                            onChange={(event) => form.setData('ads', { ...form.data.ads, test_event_code: event.target.value })}
                        />
                    </FormField>
                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={form.data.ads.advanced_matching}
                            onChange={(event) => form.setData('ads', { ...form.data.ads, advanced_matching: event.target.checked })}
                        />
                        Send hashed email and phone with Purchase events
                    </label>
                </FormCard>
                <FormCard
                    title="Google Tag Manager"
                    description="Paste the GTM- container ID. The storefront injects the head script and body noscript for you. If this container already loads Google Analytics, leave the Google Analytics card off to avoid double counting."
                >
                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={form.data.gtm.enabled}
                            onChange={(event) => form.setData('gtm', { ...form.data.gtm, enabled: event.target.checked })}
                        />
                        Enable Google Tag Manager
                    </label>
                    <FormField
                        label="Container ID"
                        htmlFor="gtm_container_id"
                        error={gtmError('container_id')}
                        hint="From Tag Manager. Starts with GTM-."
                    >
                        <Input
                            id="gtm_container_id"
                            value={form.data.gtm.container_id}
                            placeholder="GTM-XXXXXXX"
                            onChange={(event) => form.setData('gtm', { ...form.data.gtm, container_id: event.target.value })}
                        />
                    </FormField>
                </FormCard>
                <FormCard
                    title="Google Analytics"
                    description="Direct GA4 measurement ID. Use this only if you are not already firing GA4 from Tag Manager."
                >
                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={form.data.google.enabled}
                            onChange={(event) => form.setData('google', { ...form.data.google, enabled: event.target.checked })}
                        />
                        Enable Google Analytics
                    </label>
                    <FormField
                        label="Measurement ID"
                        htmlFor="google_measurement_id"
                        error={googleError('measurement_id')}
                        hint="From Google Analytics Admin → Data streams. Starts with G- or GT-."
                    >
                        <Input
                            id="google_measurement_id"
                            value={form.data.google.measurement_id}
                            placeholder="G-XXXXXXXXXX"
                            onChange={(event) => form.setData('google', { ...form.data.google, measurement_id: event.target.value })}
                        />
                    </FormField>
                </FormCard>
                <div className="lg:col-span-2">
                    <Button type="submit" disabled={form.processing}>
                        Save settings
                    </Button>
                </div>
            </form>

            <section className="space-y-4">
                <div>
                    <h2 className="text-lg font-medium">Branding</h2>
                    <p className="text-muted-foreground text-sm">Logo and favicon uploads are saved separately from store text settings.</p>
                </div>
                <BrandingForm logoUrl={branding.logo_url} faviconUrl={branding.favicon_url} />
            </section>
        </AdminLayout>
    );
}
