import { FormCard, FormField } from '@/components/admin/form-field';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { type CollectionDetail, type IdOption, type SelectOption } from '@/types';
import { type FormDataConvertible } from '@inertiajs/core';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { type FormEventHandler } from 'react';

interface CollectionFormData {
    title: string;
    slug: string;
    description: string;
    status: string;
    seo_title: string;
    seo_description: string;
    position: number;
    product_ids: number[];
    image: File | null;
    remove_image: boolean;
    [key: string]: FormDataConvertible;
}

interface CollectionFormProps {
    collection?: CollectionDetail;
    statuses: SelectOption[];
    products: IdOption[];
}

export function CollectionForm({ collection, statuses, products }: CollectionFormProps) {
    const isEditing = Boolean(collection);
    const [productSearch, setProductSearch] = useState('');

    const form = useForm<CollectionFormData>({
        title: collection?.title ?? '',
        slug: collection?.slug ?? '',
        description: collection?.description ?? '',
        status: collection?.status ?? 'draft',
        seo_title: collection?.seo_title ?? '',
        seo_description: collection?.seo_description ?? '',
        position: collection?.position ?? 0,
        product_ids: collection?.product_ids ?? [],
        image: null,
        remove_image: false,
    });

    const { data, setData, errors, processing } = form;

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        if (isEditing && collection) {
            /*
             | A file upload cannot ride a PUT through Inertia, so the update is
             | posted with a method spoof.
             */
            form.transform((payload) => ({ ...payload, _method: 'put' }));
            form.post(`/admin/collections/${collection.id}`, {
                preserveScroll: true,
                forceFormData: true,
            });
        } else {
            form.post('/admin/collections', { forceFormData: true });
        }
    };

    const visibleProducts = products.filter((product) => product.label.toLowerCase().includes(productSearch.toLowerCase()));

    return (
        <form onSubmit={submit} className="grid gap-6 lg:grid-cols-3">
            <div className="flex flex-col gap-6 lg:col-span-2">
                <FormCard title="Details">
                    <FormField label="Title" htmlFor="title" error={errors.title} required>
                        <Input id="title" value={data.title} onChange={(event) => setData('title', event.target.value)} autoFocus />
                    </FormField>

                    <FormField label="URL slug" htmlFor="slug" error={errors.slug} hint="Leave blank to generate from the title.">
                        <Input id="slug" value={data.slug} onChange={(event) => setData('slug', event.target.value)} placeholder="auto-generated" />
                    </FormField>

                    <FormField label="Description" htmlFor="description" error={errors.description}>
                        <textarea
                            id="description"
                            value={data.description}
                            onChange={(event) => setData('description', event.target.value)}
                            rows={4}
                            className="w-full rounded-md border border-neutral-300 bg-transparent px-3 py-2 text-sm dark:border-neutral-700"
                        />
                    </FormField>
                </FormCard>

                <FormCard title="Products" description="Choose which products belong to this collection.">
                    <Input
                        type="search"
                        value={productSearch}
                        onChange={(event) => setProductSearch(event.target.value)}
                        placeholder="Filter products"
                        aria-label="Filter products"
                    />

                    {visibleProducts.length === 0 ? (
                        <p className="text-muted-foreground text-sm">No matching products.</p>
                    ) : (
                        <div className="flex max-h-80 flex-col gap-2 overflow-y-auto">
                            {visibleProducts.map((product) => (
                                <label key={product.value} className="flex items-center gap-2 text-sm">
                                    <Checkbox
                                        checked={data.product_ids.includes(product.value)}
                                        onCheckedChange={(checked) =>
                                            setData(
                                                'product_ids',
                                                checked === true
                                                    ? [...data.product_ids, product.value]
                                                    : data.product_ids.filter((id) => id !== product.value),
                                            )
                                        }
                                    />
                                    {product.label}
                                </label>
                            ))}
                        </div>
                    )}

                    <p className="text-muted-foreground text-xs">{data.product_ids.length} selected</p>
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
                </FormCard>

                <FormCard title="Image">
                    {collection?.image_url && !data.remove_image && (
                        <div className="space-y-2">
                            <img src={collection.image_url} alt={collection.title} className="aspect-video w-full rounded-lg object-cover" />
                            <Button type="button" variant="outline" size="sm" onClick={() => setData('remove_image', true)}>
                                Remove image
                            </Button>
                        </div>
                    )}

                    {data.remove_image && <p className="text-muted-foreground text-sm">Image will be removed when you save.</p>}

                    <FormField label="Upload" error={errors.image}>
                        <input
                            type="file"
                            accept="image/*"
                            aria-label="Collection image"
                            onChange={(event) => setData('image', event.target.files?.[0] ?? null)}
                            className="text-sm"
                        />
                    </FormField>
                </FormCard>

                <FormCard title="Search engine listing">
                    <FormField label="SEO title" htmlFor="seo_title" error={errors.seo_title}>
                        <Input id="seo_title" value={data.seo_title} onChange={(event) => setData('seo_title', event.target.value)} />
                    </FormField>

                    <FormField label="SEO description" htmlFor="seo_description" error={errors.seo_description}>
                        <textarea
                            id="seo_description"
                            value={data.seo_description}
                            onChange={(event) => setData('seo_description', event.target.value)}
                            rows={3}
                            className="w-full rounded-md border border-neutral-300 bg-transparent px-3 py-2 text-sm dark:border-neutral-700"
                        />
                    </FormField>
                </FormCard>

                <Button type="submit" disabled={processing} className="w-full">
                    {isEditing ? 'Save collection' : 'Create collection'}
                </Button>
            </div>
        </form>
    );
}
