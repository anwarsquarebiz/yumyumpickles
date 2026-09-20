import { CouponForm } from '@/components/admin/coupon-form';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';
import { type AdminCoupon, type BreadcrumbItem, type SelectOption } from '@/types';
import { router } from '@inertiajs/react';

interface EditCouponProps {
    coupon: { data: AdminCoupon };
    types: SelectOption[];
}

export default function EditCoupon({ coupon, types }: EditCouponProps) {
    const item = coupon.data;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Coupons', href: '/admin/coupons' },
        { title: item.code, href: `/admin/coupons/${item.id}/edit` },
    ];

    return (
        <AdminLayout
            breadcrumbs={breadcrumbs}
            title={item.code}
            description="Update this discount code."
            actions={
                <Button
                    variant="outline"
                    onClick={() => {
                        if (confirm(`Delete ${item.code}? This cannot be undone.`)) {
                            router.delete(`/admin/coupons/${item.id}`);
                        }
                    }}
                >
                    Delete
                </Button>
            }
        >
            <CouponForm coupon={item} types={types} />
        </AdminLayout>
    );
}
