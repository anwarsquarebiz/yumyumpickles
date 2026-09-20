import { BannerForm } from '@/components/admin/banner-form';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem, type HomeBanner, type SelectOption } from '@/types';
import { router } from '@inertiajs/react';

interface EditBannerProps {
    banner: { data: HomeBanner };
    statuses: SelectOption[];
}

export default function EditBanner({ banner, statuses }: EditBannerProps) {
    const item = banner.data;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Banners', href: '/admin/banners' },
        { title: item.title, href: `/admin/banners/${item.id}/edit` },
    ];

    return (
        <AdminLayout
            breadcrumbs={breadcrumbs}
            title={item.title}
            description="Update this home page slide."
            actions={
                <Button
                    variant="outline"
                    onClick={() => confirm('Delete this banner?') && router.delete(`/admin/banners/${item.id}`)}
                >
                    Delete
                </Button>
            }
        >
            <BannerForm banner={item} statuses={statuses} />
        </AdminLayout>
    );
}
