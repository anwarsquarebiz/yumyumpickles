import { CollectionForm } from '@/components/admin/collection-form';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem, type IdOption, type SelectOption } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin' },
    { title: 'Collections', href: '/admin/collections' },
    { title: 'New collection', href: '/admin/collections/create' },
];

interface CreateCollectionProps {
    statuses: SelectOption[];
    products: IdOption[];
}

export default function CreateCollection({ statuses, products }: CreateCollectionProps) {
    return (
        <AdminLayout breadcrumbs={breadcrumbs} title="New collection" description="Group related products together.">
            <CollectionForm statuses={statuses} products={products} />
        </AdminLayout>
    );
}
