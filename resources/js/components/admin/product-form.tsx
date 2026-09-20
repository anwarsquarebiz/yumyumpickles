import { FormCard, FormField } from '@/components/admin/form-field';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { type IdOption, type ProductDetail, type SelectOption } from '@/types';
import { type FormDataConvertible } from '@inertiajs/core';
import { useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { type FormEventHandler } from 'react';

/*
 | Declared as type aliases rather than interfaces: Inertia's useForm requires
 | every value to satisfy FormDataConvertible, and TypeScript only infers the
 | implicit index signature that check needs for type aliases.
 */
type VariantRow = {
    id: number | null;
    title: string;
    sku: string;
    barcode: string;
    price: string;
    compare_at_price: string;
    cost: string;
    option1: string;
    option2: string;
    option3: string;
    inventory_quantity: number;
    track_inventory: boolean;
    inventory_policy: string;
    weight: string;
    weight_unit: string;
};

type OptionRow = {
    name: string;
    position: number;
    /** Comma separated in the UI, split into an array before submitting. */
    values: string;
};

interface ProductFormData {
    title: string;
    slug: string;
    description: string;
    body_html: string;
    status: string;
    vendor: string;
    product_type: string;
    seo_title: string;
    seo_description: string;
    collection_ids: number[];
    options: OptionRow[];
    variants: VariantRow[];
    metadata: {
        spice: string;
        story: string;
        ingredients: string;
        nutrition: string;
        pairs_with: string;
        badge: string;
        is_new: boolean;
    };
    [key: string]: FormDataConvertible;
}

const emptyVariant = (position: number): VariantRow => ({
    id: null,
    title: position === 1 ? 'Default' : `Variant ${position}`,
    sku: '',
    barcode: '',
    price: '0.00',
    compare_at_price: '',
    cost: '',
    option1: '',
    option2: '',
    option3: '',
    inventory_quantity: 0,
    track_inventory: true,
    inventory_policy: 'deny',
    weight: '',
    weight_unit: 'kg',
});

interface ProductFormProps {
    product?: ProductDetail;
    statuses: SelectOption[];
    inventoryPolicies: SelectOption[];
    collections: IdOption[];
}

export function ProductForm({ product, statuses, inventoryPolicies, collections }: ProductFormProps) {
    const isEditing = Boolean(product);

    const form = useForm<ProductFormData>({
        title: product?.title ?? '',
        slug: product?.slug ?? '',
        description: product?.description ?? '',
        body_html: product?.body_html ?? '',
        status: product?.status ?? 'draft',
        vendor: product?.vendor ?? '',
        product_type: product?.product_type ?? '',
        seo_title: product?.seo_title ?? '',
        seo_description: product?.seo_description ?? '',
        collection_ids: product?.collections?.map((collection) => collection.id) ?? [],
        options:
            product?.options.map((option) => ({
                name: option.name,
                position: option.position,
                values: option.values.join(', '),
            })) ?? [],
        variants:
            product?.variants.map((variant) => ({
                id: variant.id,
                title: variant.title,
                sku: variant.sku ?? '',
                barcode: variant.barcode ?? '',
                price: variant.price.decimal,
                compare_at_price: variant.compare_at_price?.decimal ?? '',
                cost: '',
                option1: variant.option1 ?? '',
                option2: variant.option2 ?? '',
                option3: variant.option3 ?? '',
                inventory_quantity: variant.inventory_quantity,
                track_inventory: variant.track_inventory,
                inventory_policy: variant.inventory_policy,
                weight: variant.weight ?? '',
                weight_unit: variant.weight_unit,
            })) ?? [emptyVariant(1)],
        metadata: {
            spice: String(product?.metadata?.spice ?? ''),
            story: String(product?.metadata?.story ?? ''),
            ingredients: Array.isArray(product?.metadata?.ingredients) ? product.metadata.ingredients.join(', ') : '',
            nutrition: Array.isArray(product?.metadata?.nutrition)
                ? product.metadata.nutrition
                      .map((row) => (typeof row === 'object' && row && 'label' in row ? `${row.label}: ${row.value}` : String(row)))
                      .join('\n')
                : '',
            pairs_with: Array.isArray(product?.metadata?.pairs_with) ? product.metadata.pairs_with.join(', ') : '',
            badge: String(product?.metadata?.badge ?? ''),
            is_new: Boolean(product?.metadata?.is_new),
        },
    });

    const { data, setData, errors, processing } = form;

    const updateVariant = (index: number, changes: Partial<VariantRow>) => {
        setData(
            'variants',
            data.variants.map((variant, current) => (current === index ? { ...variant, ...changes } : variant)),
        );
    };

    const updateOption = (index: number, changes: Partial<OptionRow>) => {
        setData(
            'options',
            data.options.map((option, current) => (current === index ? { ...option, ...changes } : option)),
        );
    };

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        /*
         | Option values are edited as a comma separated string, and empty
         | numeric fields must be sent as null rather than "".
         */
        form.transform((payload) => ({
            ...payload,
            options: (payload.options as OptionRow[])
                .filter((option) => option.name.trim() !== '')
                .map((option, index) => ({
                    name: option.name.trim(),
                    position: index + 1,
                    values: option.values
                        .split(',')
                        .map((value) => value.trim())
                        .filter((value) => value !== ''),
                })),
            variants: (payload.variants as VariantRow[]).map((variant, index) => ({
                ...variant,
                position: index + 1,
                compare_at_price: variant.compare_at_price === '' ? null : variant.compare_at_price,
                cost: variant.cost === '' ? null : variant.cost,
                weight: variant.weight === '' ? null : variant.weight,
                sku: variant.sku === '' ? null : variant.sku,
                barcode: variant.barcode === '' ? null : variant.barcode,
            })),
            metadata: {
                spice: (payload.metadata as ProductFormData['metadata']).spice.trim() || null,
                story: (payload.metadata as ProductFormData['metadata']).story.trim() || null,
                ingredients: (payload.metadata as ProductFormData['metadata']).ingredients
                    .split(',')
                    .map((value) => value.trim())
                    .filter(Boolean),
                nutrition: (payload.metadata as ProductFormData['metadata']).nutrition
                    .split('\n')
                    .map((line) => line.trim())
                    .filter(Boolean)
                    .map((line) => {
                        const [label, ...rest] = line.split(':');
                        return { label: label.trim(), value: rest.join(':').trim() };
                    }),
                pairs_with: (payload.metadata as ProductFormData['metadata']).pairs_with
                    .split(',')
                    .map((value) => value.trim())
                    .filter(Boolean),
                badge: (payload.metadata as ProductFormData['metadata']).badge.trim() || null,
                is_new: (payload.metadata as ProductFormData['metadata']).is_new,
            },
        }));

        if (isEditing && product) {
            form.put(`/admin/products/${product.id}`, { preserveScroll: true });
        } else {
            form.post('/admin/products');
        }
    };

    /** Variant-level errors arrive keyed like "variants.0.sku". */
    const variantError = (index: number, field: string): string | undefined =>
        (errors as Record<string, string | undefined>)[`variants.${index}.${field}`];

    return (
        <form onSubmit={submit} className="grid gap-6 lg:grid-cols-3">
            <div className="flex flex-col gap-6 lg:col-span-2">
                <FormCard title="Details">
                    <FormField label="Title" htmlFor="title" error={errors.title} required>
                        <Input id="title" value={data.title} onChange={(event) => setData('title', event.target.value)} autoFocus />
                    </FormField>

                    <FormField
                        label="URL slug"
                        htmlFor="slug"
                        error={errors.slug}
                        hint={isEditing ? 'Changing this changes the public product URL.' : 'Leave blank to generate from the title.'}
                    >
                        <Input id="slug" value={data.slug} onChange={(event) => setData('slug', event.target.value)} placeholder="auto-generated" />
                    </FormField>

                    <FormField label="Short description" htmlFor="description" error={errors.description} hint="Shown on product cards.">
                        <textarea
                            id="description"
                            value={data.description}
                            onChange={(event) => setData('description', event.target.value)}
                            rows={3}
                            className="w-full rounded-md border border-neutral-300 bg-transparent px-3 py-2 text-sm dark:border-neutral-700"
                        />
                    </FormField>

                    <FormField label="Full description" htmlFor="body_html" error={errors.body_html} hint="HTML is allowed.">
                        <textarea
                            id="body_html"
                            value={data.body_html}
                            onChange={(event) => setData('body_html', event.target.value)}
                            rows={8}
                            className="w-full rounded-md border border-neutral-300 bg-transparent px-3 py-2 font-mono text-sm dark:border-neutral-700"
                        />
                    </FormField>
                </FormCard>

                <FormCard title="Pickle details" description="Storefront extras for spice, story, ingredients and pairing.">
                    <FormField label="Spice level" htmlFor="spice">
                        <select
                            id="spice"
                            value={data.metadata.spice}
                            onChange={(event) => setData('metadata', { ...data.metadata, spice: event.target.value })}
                            className="h-9 w-full rounded-md border border-neutral-300 bg-transparent px-3 text-sm dark:border-neutral-700"
                        >
                            <option value="">Not set</option>
                            <option value="Mild">Mild</option>
                            <option value="Medium">Medium</option>
                            <option value="Hot">Hot</option>
                        </select>
                    </FormField>
                    <FormField label="Story" htmlFor="story">
                        <textarea
                            id="story"
                            value={data.metadata.story}
                            onChange={(event) => setData('metadata', { ...data.metadata, story: event.target.value })}
                            rows={4}
                            className="w-full rounded-md border border-neutral-300 bg-transparent px-3 py-2 text-sm dark:border-neutral-700"
                        />
                    </FormField>
                    <FormField label="Ingredients" htmlFor="ingredients" hint="Comma separated.">
                        <Input
                            id="ingredients"
                            value={data.metadata.ingredients}
                            onChange={(event) => setData('metadata', { ...data.metadata, ingredients: event.target.value })}
                        />
                    </FormField>
                    <FormField label="Nutrition" htmlFor="nutrition" hint="One per line as Label: value.">
                        <textarea
                            id="nutrition"
                            value={data.metadata.nutrition}
                            onChange={(event) => setData('metadata', { ...data.metadata, nutrition: event.target.value })}
                            rows={5}
                            className="w-full rounded-md border border-neutral-300 bg-transparent px-3 py-2 text-sm dark:border-neutral-700"
                        />
                    </FormField>
                    <FormField label="Pairs with" htmlFor="pairs_with" hint="Product slugs, comma separated.">
                        <Input
                            id="pairs_with"
                            value={data.metadata.pairs_with}
                            onChange={(event) => setData('metadata', { ...data.metadata, pairs_with: event.target.value })}
                        />
                    </FormField>
                    <FormField label="Badge" htmlFor="badge">
                        <Input
                            id="badge"
                            value={data.metadata.badge}
                            onChange={(event) => setData('metadata', { ...data.metadata, badge: event.target.value })}
                        />
                    </FormField>
                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox
                            checked={data.metadata.is_new}
                            onCheckedChange={(checked) => setData('metadata', { ...data.metadata, is_new: checked === true })}
                        />
                        Mark as new
                    </label>
                </FormCard>

                <FormCard title="Options" description="Up to three options, for example Strength and Pack size. Separate values with commas.">
                    {data.options.length === 0 && <p className="text-muted-foreground text-sm">No options. This product has a single variant.</p>}

                    <div className="flex flex-col gap-4">
                        {data.options.map((option, index) => (
                            <div key={index} className="flex flex-col gap-3 rounded-lg border border-neutral-200 p-3 sm:flex-row dark:border-neutral-800">
                                <FormField label="Name" error={errors[`options.${index}.name` as keyof typeof errors] as string} className="sm:w-48">
                                    <Input value={option.name} onChange={(event) => updateOption(index, { name: event.target.value })} />
                                </FormField>
                                <FormField label="Values" className="flex-1">
                                    <Input
                                        value={option.values}
                                        onChange={(event) => updateOption(index, { values: event.target.value })}
                                        placeholder="250mg, 500mg"
                                    />
                                </FormField>
                                <div className="flex items-end">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Remove option"
                                        onClick={() =>
                                            setData(
                                                'options',
                                                data.options.filter((_, current) => current !== index),
                                            )
                                        }
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>

                    {data.options.length < 3 && (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => setData('options', [...data.options, { name: '', position: data.options.length + 1, values: '' }])}
                        >
                            <Plus className="mr-1 size-4" />
                            Add option
                        </Button>
                    )}
                </FormCard>

                <FormCard title="Variants" description="Pricing and stock are set per variant.">
                    {typeof errors.variants === 'string' && <p className="text-sm text-red-600">{errors.variants}</p>}

                    <div className="flex flex-col gap-4">
                        {data.variants.map((variant, index) => (
                            <div key={index} className="space-y-4 rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
                                <div className="flex items-center justify-between">
                                    <p className="text-sm font-medium">Variant {index + 1}</p>
                                    {data.variants.length > 1 && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            aria-label={`Remove variant ${index + 1}`}
                                            onClick={() =>
                                                setData(
                                                    'variants',
                                                    data.variants.filter((_, current) => current !== index),
                                                )
                                            }
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    )}
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <FormField label="Label" error={variantError(index, 'title')}>
                                        <Input value={variant.title} onChange={(event) => updateVariant(index, { title: event.target.value })} />
                                    </FormField>

                                    <FormField label="SKU" error={variantError(index, 'sku')}>
                                        <Input value={variant.sku} onChange={(event) => updateVariant(index, { sku: event.target.value })} />
                                    </FormField>

                                    <FormField label="Price" error={variantError(index, 'price')} required>
                                        <Input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={variant.price}
                                            onChange={(event) => updateVariant(index, { price: event.target.value })}
                                        />
                                    </FormField>

                                    <FormField
                                        label="Compare at price"
                                        error={variantError(index, 'compare_at_price')}
                                        hint="Set higher than the price to show a sale."
                                    >
                                        <Input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={variant.compare_at_price}
                                            onChange={(event) => updateVariant(index, { compare_at_price: event.target.value })}
                                        />
                                    </FormField>

                                    <FormField label="Stock quantity" error={variantError(index, 'inventory_quantity')} required>
                                        <Input
                                            type="number"
                                            min="0"
                                            value={variant.inventory_quantity}
                                            onChange={(event) => updateVariant(index, { inventory_quantity: Number(event.target.value) })}
                                        />
                                    </FormField>

                                    <FormField label="When out of stock" error={variantError(index, 'inventory_policy')}>
                                        <select
                                            value={variant.inventory_policy}
                                            onChange={(event) => updateVariant(index, { inventory_policy: event.target.value })}
                                            className="h-9 w-full rounded-md border border-neutral-300 bg-transparent px-3 text-sm dark:border-neutral-700"
                                        >
                                            {inventoryPolicies.map((policy) => (
                                                <option key={policy.value} value={policy.value}>
                                                    {policy.label}
                                                </option>
                                            ))}
                                        </select>
                                    </FormField>

                                    <FormField label="Weight (kg)" error={variantError(index, 'weight')}>
                                        <Input
                                            type="number"
                                            step="0.001"
                                            min="0"
                                            value={variant.weight}
                                            onChange={(event) => updateVariant(index, { weight: event.target.value })}
                                        />
                                    </FormField>

                                    <div className="flex items-end">
                                        <label className="flex items-center gap-2 text-sm">
                                            <Checkbox
                                                checked={variant.track_inventory}
                                                onCheckedChange={(checked) => updateVariant(index, { track_inventory: checked === true })}
                                            />
                                            Track inventory
                                        </label>
                                    </div>
                                </div>

                                {data.options.length > 0 && (
                                    <div className="grid gap-4 sm:grid-cols-3">
                                        {data.options.map((option, optionIndex) => (
                                            <FormField key={optionIndex} label={option.name || `Option ${optionIndex + 1}`}>
                                                <Input
                                                    value={
                                                        optionIndex === 0 ? variant.option1 : optionIndex === 1 ? variant.option2 : variant.option3
                                                    }
                                                    onChange={(event) =>
                                                        updateVariant(index, {
                                                            [optionIndex === 0 ? 'option1' : optionIndex === 1 ? 'option2' : 'option3']:
                                                                event.target.value,
                                                        })
                                                    }
                                                />
                                            </FormField>
                                        ))}
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => setData('variants', [...data.variants, emptyVariant(data.variants.length + 1)])}
                    >
                        <Plus className="mr-1 size-4" />
                        Add variant
                    </Button>
                </FormCard>
            </div>

            <div className="flex flex-col gap-6">
                <FormCard title="Publishing">
                    <FormField label="Status" htmlFor="status" error={errors.status} hint="Only active products appear on the storefront.">
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

                    <FormField label="Vendor" htmlFor="vendor" error={errors.vendor}>
                        <Input id="vendor" value={data.vendor} onChange={(event) => setData('vendor', event.target.value)} />
                    </FormField>

                    <FormField label="Product type" htmlFor="product_type" error={errors.product_type}>
                        <Input id="product_type" value={data.product_type} onChange={(event) => setData('product_type', event.target.value)} />
                    </FormField>
                </FormCard>

                <FormCard title="Collections">
                    {collections.length === 0 ? (
                        <p className="text-muted-foreground text-sm">No collections yet.</p>
                    ) : (
                        <div className="flex max-h-64 flex-col gap-2 overflow-y-auto">
                            {collections.map((collection) => (
                                <label key={collection.value} className="flex items-center gap-2 text-sm">
                                    <Checkbox
                                        checked={data.collection_ids.includes(collection.value)}
                                        onCheckedChange={(checked) =>
                                            setData(
                                                'collection_ids',
                                                checked === true
                                                    ? [...data.collection_ids, collection.value]
                                                    : data.collection_ids.filter((id) => id !== collection.value),
                                            )
                                        }
                                    />
                                    {collection.label}
                                </label>
                            ))}
                        </div>
                    )}
                </FormCard>

                <FormCard title="Search engine listing">
                    <FormField label="SEO title" htmlFor="seo_title" error={errors.seo_title} hint="Defaults to the product title.">
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
                    {isEditing ? 'Save product' : 'Create product'}
                </Button>
            </div>
        </form>
    );
}
