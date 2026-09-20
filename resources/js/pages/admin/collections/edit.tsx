import { CollectionForm } from '@/components/admin/collection-form';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem, type CollectionDetail, type IdOption, type SelectOption } from '@/types';
import { router } from '@inertiajs/react';
import { ExternalLink, Trash2 } from 'lucide-react';

interface EditCollectionProps {
    collection: { data: CollectionDetail };
    statuses: SelectOption[];
    products: IdOption[];
}

export default function EditCollection({ collection, statuses, products }: EditCollectionProps) {
    const { data: item } = collection;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Collections', href: '/admin/collections' },
        { title: item.title, href: `/admin/collections/${item.id}/edit` },
    ];

    const destroy = () => {
        if (window.confirm(`Delete "${item.title}"? Products in it will not be deleted.`)) {
            router.delete(`/admin/collections/${item.id}`);
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
            <CollectionForm collection={item} statuses={statuses} products={products} />
        </AdminLayout>
    );
}
