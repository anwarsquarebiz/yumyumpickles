import { Pagination } from '@/components/pagination';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem, type Paginated } from '@/types';
import { Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { type FormEventHandler, useState } from 'react';

interface CustomerRow {
    id: number;
    name: string;
    email: string;
    orders_count: number;
}

interface AdminCustomersIndexProps {
    customers: Paginated<CustomerRow>;
    filters: { search: string | null };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin' },
    { title: 'Customers', href: '/admin/customers' },
];

export default function AdminCustomersIndex({ customers, filters }: AdminCustomersIndexProps) {
    const [search, setSearch] = useState(filters.search ?? '');

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        router.get('/admin/customers', search ? { search } : {}, { preserveState: true, replace: true });
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs} title="Customers" description="People who have registered or placed orders.">
            <form onSubmit={submit} className="relative w-full sm:max-w-xs">
                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-400" />
                <Input className="pl-9" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search name or email" />
            </form>

            <div className="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-800">
                <table className="w-full text-sm">
                    <thead className="bg-neutral-50 text-left dark:bg-neutral-900">
                        <tr>
                            <th className="px-4 py-3 font-medium">Customer</th>
                            <th className="px-4 py-3 font-medium">Orders</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                        {customers.data.map((customer) => (
                            <tr key={customer.id}>
                                <td className="px-4 py-3">
                                    <Link href={`/admin/customers/${customer.id}`} className="font-medium hover:underline">
                                        {customer.name}
                                    </Link>
                                    <p className="text-muted-foreground text-xs">{customer.email}</p>
                                </td>
                                <td className="px-4 py-3">{customer.orders_count}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <Pagination paginator={customers} />
        </AdminLayout>
    );
}
