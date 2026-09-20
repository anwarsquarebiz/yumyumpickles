import { FormCard, FormField } from '@/components/admin/form-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { type HomeBanner, type SelectOption } from '@/types';
import { type FormDataConvertible } from '@inertiajs/core';
import { useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

interface BannerFormData {
    title: string;
    subtitle: string;
    button_label: string;
    button_url: string;
    alt: string;
    position: number;
    status: string;
    starts_at: string;
    ends_at: string;
    image: File | null;
    remove_image: boolean;
    [key: string]: FormDataConvertible;
}

interface BannerFormProps {
    banner?: HomeBanner;
    statuses: SelectOption[];
}

const toLocalInput = (value: string | null): string => {
    if (!value) {
        return '';
    }

    return value.replace(' ', 'T').slice(0, 16);
};

export function BannerForm({ banner, statuses }: BannerFormProps) {
    const isEditing = Boolean(banner);

    const form = useForm<BannerFormData>({
        title: banner?.title ?? '',
        subtitle: banner?.subtitle ?? '',
        button_label: banner?.button_label ?? '',
        button_url: banner?.button_url ?? '',
        alt: banner?.alt ?? '',
        position: banner?.position ?? 0,
        status: banner?.status ?? 'draft',
        starts_at: toLocalInput(banner?.starts_at ?? null),
        ends_at: toLocalInput(banner?.ends_at ?? null),
        image: null,
        remove_image: false,
    });

    const { data, setData, errors, processing } = form;

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        if (isEditing && banner) {
            form.transform((payload) => ({ ...payload, _method: 'put' }));
            form.post(`/admin/banners/${banner.id}`, {
                preserveScroll: true,
                forceFormData: true,
            });
        } else {
            form.post('/admin/banners', { forceFormData: true });
        }
    };

    return (
        <form onSubmit={submit} className="grid gap-6 lg:grid-cols-3">
            <div className="flex flex-col gap-6 lg:col-span-2">
                <FormCard title="Slide copy" description="Shown over the image on the home page.">
                    <FormField label="Title" htmlFor="title" error={errors.title} required>
                        <Input id="title" value={data.title} onChange={(event) => setData('title', event.target.value)} autoFocus />
                    </FormField>

                    <FormField label="Subtitle" htmlFor="subtitle" error={errors.subtitle}>
                        <textarea
                            id="subtitle"
                            value={data.subtitle}
                            onChange={(event) => setData('subtitle', event.target.value)}
                            rows={3}
                            className="w-full rounded-md border border-neutral-300 bg-transparent px-3 py-2 text-sm dark:border-neutral-700"
                        />
                    </FormField>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <FormField label="Button label" htmlFor="button_label" error={errors.button_label}>
                            <Input
                                id="button_label"
                                value={data.button_label}
                                onChange={(event) => setData('button_label', event.target.value)}
                                placeholder="Shop now"
                            />
                        </FormField>

                        <FormField
                            label="Button URL"
                            htmlFor="button_url"
                            error={errors.button_url}
                            hint="Use a store path such as /products or a full https URL."
                        >
                            <Input
                                id="button_url"
                                value={data.button_url}
                                onChange={(event) => setData('button_url', event.target.value)}
                                placeholder="/products"
                            />
                        </FormField>
                    </div>
                </FormCard>
            </div>

            <div className="flex flex-col gap-6">
                <FormCard title="Publishing">
                    <FormField label="Status" htmlFor="status" error={errors.status}>
                        <select
                            id="status"
                            value={data.status}
                            onChange={(event) => setData('status', event.target.value)}
                            className="h-9 w-full rounded-md border border-neutral-300 bg-transparent px-3 text-sm dark:border-neutral-700"
                        >
                            {statuses.map((status) => (
                                <option key={status.value} value={status.value}>
                                    {status.label}
                                </option>
                            ))}
                        </select>
                    </FormField>

                    <FormField label="Sort position" htmlFor="position" error={errors.position} hint="Lower numbers appear first.">
                        <Input
                            id="position"
                            type="number"
                            min="0"
                            value={data.position}
                            onChange={(event) => setData('position', Number(event.target.value))}
                        />
                    </FormField>

                    <FormField label="Starts at" htmlFor="starts_at" error={errors.starts_at} hint="Optional. Leave blank to show immediately.">
                        <Input
                            id="starts_at"
                            type="datetime-local"
                            value={data.starts_at}
                            onChange={(event) => setData('starts_at', event.target.value)}
                        />
                    </FormField>

                    <FormField label="Ends at" htmlFor="ends_at" error={errors.ends_at} hint="Optional. Leave blank to keep showing.">
                        <Input
                            id="ends_at"
                            type="datetime-local"
                            value={data.ends_at}
                            onChange={(event) => setData('ends_at', event.target.value)}
                        />
                    </FormField>
                </FormCard>

                <FormCard title="Image" description="Wide landscape photos work best. This is the full-width slide.">
                    {banner?.image_url && !data.remove_image && (
                        <div className="space-y-2">
                            <img src={banner.image_url} alt={banner.alt ?? banner.title} className="aspect-[21/9] w-full rounded-lg object-cover" />
                            <Button type="button" variant="outline" size="sm" onClick={() => setData('remove_image', true)}>
                                Remove image
                            </Button>
                        </div>
                    )}

                    {data.remove_image && <p className="text-muted-foreground text-sm">Image will be removed when you save.</p>}

                    <FormField label="Upload" error={errors.image} required={!isEditing} hint={isEditing ? 'Leave blank to keep the current image.' : undefined}>
                        <input
                            type="file"
                            accept="image/*"
                            aria-label="Banner image"
                            onChange={(event) => setData('image', event.target.files?.[0] ?? null)}
                            className="text-sm"
                        />
                    </FormField>

                    <FormField label="Alt text" htmlFor="alt" error={errors.alt} hint="Describe the image for screen readers.">
                        <Input id="alt" value={data.alt} onChange={(event) => setData('alt', event.target.value)} />
                    </FormField>
                </FormCard>

                <Button type="submit" disabled={processing} className="w-full">
                    {isEditing ? 'Save banner' : 'Create banner'}
                </Button>
            </div>
        </form>
    );
}
