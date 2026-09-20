import { StatusBadge } from '@/components/admin/status-badge';
import { Pagination } from '@/components/pagination';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem, type Paginated } from '@/types';
import { router } from '@inertiajs/react';

interface AdminReview {
    id: number;
    author_name: string;
    author_city: string | null;
    rating: number;
    body: string;
    status: string;
    reviewed_at: string | null;
    product?: { id: number; title: string; slug: string } | null;
}

interface AdminReviewsIndexProps {
    reviews: Paginated<AdminReview>;
    filters: { status: string | null };
    statuses: Array<{ value: string; label: string }>;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin' },
    { title: 'Reviews', href: '/admin/reviews' },
];

const toneFor = (status: string): string => {
    if (status === 'approved') {
        return 'emerald';
    }

    if (status === 'pending') {
        return 'amber';
    }

    return 'zinc';
};

export default function AdminReviewsIndex({ reviews, filters, statuses }: AdminReviewsIndexProps) {
    const setStatus = (id: number, status: string) => {
        router.patch(`/admin/reviews/${id}`, { status }, { preserveScroll: true });
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs} title="Reviews" description="Approve or hide customer reviews on product pages.">
            <select
                value={filters.status ?? ''}
                onChange={(event) =>
                    router.get('/admin/reviews', { status: event.target.value || undefined }, { preserveState: true, replace: true })
                }
                aria-label="Filter by status"
                className="h-9 w-full max-w-xs rounded-md border border-neutral-300 bg-transparent px-3 text-sm dark:border-neutral-700"
            >
                <option value="">All statuses</option>
                {statuses.map((status) => (
                    <option key={status.value} value={status.value}>
                        {status.label}
                    </option>
                ))}
            </select>

            {reviews.data.length === 0 ? (
                <div className="rounded-xl border border-neutral-200 p-12 text-center dark:border-neutral-800">
                    <p className="font-medium">No reviews yet</p>
                </div>
            ) : (
                <div className="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-800">
                    <table className="w-full text-sm">
                        <thead className="bg-neutral-50 text-left dark:bg-neutral-900">
                            <tr>
                                <th className="px-4 py-3 font-medium">Product</th>
                                <th className="px-4 py-3 font-medium">Review</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                            {reviews.data.map((review) => (
                                <tr key={review.id}>
                                    <td className="px-4 py-3 align-top">
                                        <p className="font-medium">{review.product?.title ?? 'Deleted product'}</p>
                                        <p className="text-muted-foreground text-xs">{review.reviewed_at}</p>
                                    </td>
                                    <td className="px-4 py-3 align-top">
                                        <p className="font-medium">
                                            {review.author_name}
                                            {review.author_city ? ` · ${review.author_city}` : ''} · {review.rating}/5
                                        </p>
                                        <p className="text-muted-foreground mt-1">{review.body}</p>
                                    </td>
                                    <td className="px-4 py-3 align-top">
                                        <StatusBadge label={review.status} tone={toneFor(review.status)} />
                                        <select
                                            value={review.status}
                                            onChange={(event) => setStatus(review.id, event.target.value)}
                                            className="mt-2 h-9 w-full rounded-md border border-neutral-300 bg-transparent px-3 text-sm dark:border-neutral-700"
                                        >
                                            {statuses.map((status) => (
                                                <option key={status.value} value={status.value}>
                                                    {status.label}
                                                </option>
                                            ))}
                                        </select>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            <Pagination paginator={reviews} />
        </AdminLayout>
    );
}
