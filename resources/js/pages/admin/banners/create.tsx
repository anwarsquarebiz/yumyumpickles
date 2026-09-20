import { BannerForm } from '@/components/admin/banner-form';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem, type SelectOption } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin' },
    { title: 'Banners', href: '/admin/banners' },
    { title: 'New banner', href: '/admin/banners/create' },
];

export default function CreateBanner({ statuses }: { statuses: SelectOption[] }) {
    return (
        <AdminLayout breadcrumbs={breadcrumbs} title="New banner" description="Add a full-width home page slide.">
            <BannerForm statuses={statuses} />
        </AdminLayout>
    );
}
