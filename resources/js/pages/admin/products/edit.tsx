import { FormCard } from '@/components/admin/form-field';
import { ProductForm } from '@/components/admin/product-form';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem, type IdOption, type ProductDetail, type SelectOption } from '@/types';
import { Link, router, useForm } from '@inertiajs/react';
import { ExternalLink, Trash2, Upload } from 'lucide-react';
import { type FormEventHandler, useRef } from 'react';

interface EditProductProps {
    product: { data: ProductDetail };
    statuses: SelectOption[];
    inventoryPolicies: SelectOption[];
    collections: IdOption[];
}

export default function EditProduct({ product, statuses, inventoryPolicies, collections }: EditProductProps) {
    const { data: item } = product;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Products', href: '/admin/products' },
        { title: item.title, href: `/admin/products/${item.id}/edit` },
    ];

    const fileInput = useRef<HTMLInputElement>(null);
    const upload = useForm<{ image: File | null; alt: string }>({ image: null, alt: '' });

    const submitImage: FormEventHandler = (event) => {
        event.preventDefault();

        upload.post(`/admin/products/${item.id}/images`, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                upload.reset();
                if (fileInput.current) {
                    fileInput.current.value = '';
                }
            },
        });
    };

    const destroy = () => {
        if (window.confirm(`Delete "${item.title}"? This cannot be undone from the admin.`)) {
            router.delete(`/admin/products/${item.id}`);
        }
    };

    return (
        <AdminLayout
            breadcrumbs={breadcrumbs}
            title={item.title}
            description={item.is_published ? 'Live on the storefront.' : 'Not visible on the storefront.'}
            actions={
                <>
                    {item.is_published && (
                        <Button asChild variant="outline">
                            <a href={item.url} target="_blank" rel="noopener noreferrer">
                                <ExternalLink className="mr-1 size-4" />
                                View
                            </a>
                        </Button>
                    )}
                    <Button variant="outline" onClick={destroy}>
                        <Trash2 className="mr-1 size-4" />
                        Delete
                    </Button>
                </>
            }
        >
            <FormCard title="Images" description="The first image is used on product cards.">
                {item.images.length > 0 && (
                    <div className="grid grid-cols-3 gap-3 sm:grid-cols-5 lg:grid-cols-8">
                        {item.images.map((image) => (
                            <div key={image.id} className="group relative aspect-square overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-800">
                                <img src={image.url} alt={image.alt ?? ''} className="size-full object-cover" />
                                <button
                                    type="button"
                                    aria-label="Delete image"
                                    onClick={() => router.delete(`/admin/products/${item.id}/images/${image.id}`, { preserveScroll: true })}
                                    className="absolute top-1 right-1 rounded-md bg-white/90 p-1 opacity-0 transition-opacity group-hover:opacity-100 dark:bg-neutral-900/90"
                                >
                                    <Trash2 className="size-4 text-rose-600" />
                                </button>
                            </div>
                        ))}
                    </div>
                )}

                <form onSubmit={submitImage} className="flex flex-wrap items-end gap-3">
                    <input
                        ref={fileInput}
                        type="file"
                        accept="image/*"
                        aria-label="Choose an image"
                        onChange={(event) => upload.setData('image', event.target.files?.[0] ?? null)}
                        className="text-sm"
                    />
                    <Button type="submit" variant="outline" size="sm" disabled={!upload.data.image || upload.processing}>
                        <Upload className="mr-1 size-4" />
                        Upload
                    </Button>
                    {upload.errors.image && <p className="text-sm text-red-600">{upload.errors.image}</p>}
                </form>
            </FormCard>

            <ProductForm product={item} statuses={statuses} inventoryPolicies={inventoryPolicies} collections={collections} />

            <p className="text-muted-foreground text-xs">
                Slug: <Link href={item.url} className="underline">{item.slug}</Link>
            </p>
        </AdminLayout>
    );
}
