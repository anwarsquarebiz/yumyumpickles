import { ProductForm } from '@/components/admin/product-form';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem, type IdOption, type SelectOption } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin' },
    { title: 'Products', href: '/admin/products' },
    { title: 'New product', href: '/admin/products/create' },
];

interface CreateProductProps {
    statuses: SelectOption[];
    inventoryPolicies: SelectOption[];
    collections: IdOption[];
}

export default function CreateProduct({ statuses, inventoryPolicies, collections }: CreateProductProps) {
    return (
        <AdminLayout breadcrumbs={breadcrumbs} title="New product" description="Add a product to your catalog.">
            <ProductForm statuses={statuses} inventoryPolicies={inventoryPolicies} collections={collections} />
        </AdminLayout>
    );
}
