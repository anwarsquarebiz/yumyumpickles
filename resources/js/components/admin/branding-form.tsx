import { FormCard, FormField } from '@/components/admin/form-field';
import { Button } from '@/components/ui/button';
import { type FormDataConvertible } from '@inertiajs/core';
import { useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

interface BrandingFormData {
    logo: File | null;
    remove_logo: boolean;
    favicon: File | null;
    remove_favicon: boolean;
    [key: string]: FormDataConvertible;
}

interface BrandingFormProps {
    logoUrl: string | null;
    faviconUrl: string | null;
}

export function BrandingForm({ logoUrl, faviconUrl }: BrandingFormProps) {
    const form = useForm<BrandingFormData>({
        logo: null,
        remove_logo: false,
        favicon: null,
        remove_favicon: false,
    });

    const { data, setData, errors, processing } = form;

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        form.post('/admin/settings/branding', {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                form.reset();
                form.setData('remove_logo', false);
                form.setData('remove_favicon', false);
            },
        });
    };

    const showLogo = logoUrl && !data.remove_logo;
    const showFavicon = faviconUrl && !data.remove_favicon;

    return (
        <form onSubmit={submit} className="grid gap-6 lg:grid-cols-2">
            <FormCard
                title="Logo"
                description="Used in the storefront header, admin sidebar, and login pages. One logo everywhere."
            >
                {showLogo && (
                    <div className="space-y-2">
                        <img src={logoUrl} alt="Current store logo" className="h-16 w-auto max-w-full object-contain" />
                        <Button type="button" variant="outline" size="sm" onClick={() => setData('remove_logo', true)}>
                            Remove logo
                        </Button>
                    </div>
                )}

                {data.remove_logo && <p className="text-muted-foreground text-sm">Logo will be removed when you save branding.</p>}

                <FormField label="Upload logo" error={errors.logo} hint={showLogo ? 'Leave blank to keep the current logo.' : 'PNG, JPG, SVG or WebP up to 2 MB.'}>
                    <input
                        type="file"
                        accept="image/*"
                        aria-label="Store logo"
                        onChange={(event) => setData('logo', event.target.files?.[0] ?? null)}
                        className="text-sm"
                    />
                </FormField>
            </FormCard>

            <FormCard title="Favicon" description="Browser tab icon for the storefront and admin.">
                {showFavicon && (
                    <div className="space-y-2">
                        <img src={faviconUrl} alt="Current favicon" className="size-8 object-contain" />
                        <Button type="button" variant="outline" size="sm" onClick={() => setData('remove_favicon', true)}>
                            Remove favicon
                        </Button>
                    </div>
                )}

                {data.remove_favicon && <p className="text-muted-foreground text-sm">Favicon will be removed when you save branding.</p>}

                <FormField label="Upload favicon" error={errors.favicon} hint={showFavicon ? 'Leave blank to keep the current favicon.' : 'PNG, ICO or SVG up to 512 KB.'}>
                    <input
                        type="file"
                        accept="image/png,image/x-icon,image/vnd.microsoft.icon,image/svg+xml,.ico"
                        aria-label="Favicon"
                        onChange={(event) => setData('favicon', event.target.files?.[0] ?? null)}
                        className="text-sm"
                    />
                </FormField>
            </FormCard>

            <div className="lg:col-span-2">
                <Button type="submit" disabled={processing}>
                    Save branding
                </Button>
            </div>
        </form>
    );
}
