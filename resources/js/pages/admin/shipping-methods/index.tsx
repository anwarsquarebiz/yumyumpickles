import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem, type SelectOption } from '@/types';
import { router, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

interface ShippingMethodRow {
    id: number;
    name: string;
    description: string | null;
    type: string;
    type_label: string;
    rate: { formatted: string; decimal: string };
    free_over: { formatted: string; decimal: string } | null;
    is_active: boolean;
    position: number;
}

interface AdminShippingMethodsIndexProps {
    shipping_methods: { data: ShippingMethodRow[] } | ShippingMethodRow[];
    shipping_types: SelectOption[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin' },
    { title: 'Shipping methods', href: '/admin/shipping-methods' },
];

export default function AdminShippingMethodsIndex({ shipping_methods, shipping_types }: AdminShippingMethodsIndexProps) {
    const methods = Array.isArray(shipping_methods) ? shipping_methods : shipping_methods.data;

    const shippingForm = useForm({
        name: 'Standard shipping',
        description: '',
        type: 'flat_rate',
        rate: '5.99',
        free_over: '',
        is_active: true,
        position: 1,
    });

    const addShipping: FormEventHandler = (event) => {
        event.preventDefault();
        shippingForm.post('/admin/shipping-methods', { onSuccess: () => shippingForm.reset() });
    };

    return (
        <AdminLayout
            breadcrumbs={breadcrumbs}
            title="Shipping methods"
            description="Configure flat rate, free-over-threshold, and weight-based delivery options."
        >
            <div className="space-y-4">
                {methods.length === 0 ? (
                    <div className="rounded-xl border border-neutral-200 p-12 text-center dark:border-neutral-800">
                        <p className="font-medium">No shipping methods yet</p>
                        <p className="text-muted-foreground mt-1 text-sm">Add a method below so customers can choose delivery at checkout.</p>
                    </div>
                ) : (
                    <ul className="divide-y divide-neutral-200 rounded-xl border border-neutral-200 dark:divide-neutral-800 dark:border-neutral-800">
                        {methods.map((method) => (
                            <li key={method.id} className="flex items-center justify-between px-4 py-3 text-sm">
                                <span>
                                    {method.name} · {method.rate.formatted}
                                </span>
                                <Button variant="ghost" size="sm" onClick={() => router.delete(`/admin/shipping-methods/${method.id}`)}>
                                    Remove
                                </Button>
                            </li>
                        ))}
                    </ul>
                )}
                <form onSubmit={addShipping} className="grid gap-3 rounded-xl border border-neutral-200 p-4 sm:grid-cols-4 dark:border-neutral-800">
                    <Input placeholder="Name" value={shippingForm.data.name} onChange={(event) => shippingForm.setData('name', event.target.value)} />
                    <select
                        value={shippingForm.data.type}
                        onChange={(event) => shippingForm.setData('type', event.target.value)}
                        className="h-9 rounded-md border border-neutral-300 bg-transparent px-3 text-sm dark:border-neutral-700"
                    >
                        {shipping_types.map((type) => (
                            <option key={type.value} value={type.value}>
                                {type.label}
                            </option>
                        ))}
                    </select>
                    <Input placeholder="Rate" value={shippingForm.data.rate} onChange={(event) => shippingForm.setData('rate', event.target.value)} />
                    <Button type="submit" disabled={shippingForm.processing}>
                        Add method
                    </Button>
                </form>
            </div>
        </AdminLayout>
    );
}
