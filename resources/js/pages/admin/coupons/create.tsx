import { CouponForm } from '@/components/admin/coupon-form';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem, type SelectOption } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin' },
    { title: 'Coupons', href: '/admin/coupons' },
    { title: 'New discount', href: '/admin/coupons/create' },
];

interface CreateCouponProps {
    types: SelectOption[];
}

export default function CreateCoupon({ types }: CreateCouponProps) {
    return (
        <AdminLayout breadcrumbs={breadcrumbs} title="New discount" description="Create a fixed or percentage discount code.">
            <CouponForm types={types} />
        </AdminLayout>
    );
}
