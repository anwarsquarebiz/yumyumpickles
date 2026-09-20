import { FormCard, FormField } from '@/components/admin/form-field';
import { RichTextEditor } from '@/components/admin/rich-text-editor';
import { StatusBadge } from '@/components/admin/status-badge';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem, type CmsPage, type Paginated, type SelectOption } from '@/types';
import { Link, router, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { type FormEventHandler } from 'react';

interface AdminPagesIndexProps {
    pages: Paginated<CmsPage>;
    filters: { search: string | null };
}

export function AdminPagesIndex({ pages, filters }: AdminPagesIndexProps) {
    return (
        <AdminLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/admin' },
                { title: 'Pages', href: '/admin/pages' },
            ]}
            title="Pages"
            description="Store policies and informational pages."
            actions={
                <Button asChild>
                    <Link href="/admin/pages/create">
                        <Plus className="mr-1 size-4" />
                        New page
                    </Link>
                </Button>
            }
        >
            <div className="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-800">
                <table className="w-full text-sm">
                    <thead className="bg-neutral-50 text-left dark:bg-neutral-900">
                        <tr>
                            <th className="px-4 py-3 font-medium">Page</th>
                            <th className="px-4 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                        {pages.data.map((page) => (
                            <tr key={page.id}>
                                <td className="px-4 py-3">
                                    <Link href={`/admin/pages/${page.id}/edit`} className="font-medium hover:underline">
                                        {page.title}
                                    </Link>
                                    <p className="text-muted-foreground text-xs">/{page.slug}</p>
                                </td>
                                <td className="px-4 py-3">
                                    <StatusBadge label={page.status} status={page.status} />
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <Pagination paginator={pages} />
        </AdminLayout>
    );
}

export default AdminPagesIndex;

export function PageEditor({
    page,
    statuses,
    templates,
}: {
    page?: CmsPage;
    statuses: SelectOption[];
    templates: SelectOption[];
}) {
    const isEditing = Boolean(page);
    const form = useForm({
        title: page?.title ?? '',
        slug: page?.slug ?? '',
        excerpt: page?.excerpt ?? '',
        content: page?.content ?? '',
        status: page?.status ?? 'draft',
        template: page?.template ?? 'default',
        seo_title: page?.seo_title ?? '',
        seo_description: page?.seo_description ?? '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Pages', href: '/admin/pages' },
        { title: isEditing ? page!.title : 'New page', href: isEditing ? `/admin/pages/${page!.id}/edit` : '/admin/pages/create' },
    ];

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        if (isEditing && page) {
            form.put(`/admin/pages/${page.id}`);
        } else {
            form.post('/admin/pages');
        }
    };

    return (
        <AdminLayout
            breadcrumbs={breadcrumbs}
            title={isEditing ? page!.title : 'New page'}
            actions={
                isEditing && page ? (
                    <Button variant="outline" onClick={() => confirm('Delete this page?') && router.delete(`/admin/pages/${page.id}`)}>
                        Delete
                    </Button>
                ) : undefined
            }
        >
            <form onSubmit={submit} className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <FormCard title="Content">
                        <FormField label="Title" htmlFor="title" error={form.errors.title} required>
                            <Input id="title" value={form.data.title} onChange={(event) => form.setData('title', event.target.value)} />
                        </FormField>
                        <FormField label="Slug" htmlFor="slug" error={form.errors.slug}>
                            <Input id="slug" value={form.data.slug} onChange={(event) => form.setData('slug', event.target.value)} />
                        </FormField>
                        <FormField label="Excerpt" htmlFor="excerpt" error={form.errors.excerpt}>
                            <Input id="excerpt" value={form.data.excerpt} onChange={(event) => form.setData('excerpt', event.target.value)} />
                        </FormField>
                        <FormField label="Content" htmlFor="content" error={form.errors.content}>
                            <RichTextEditor
                                id="content"
                                value={form.data.content}
                                onChange={(value) => form.setData('content', value)}
                                placeholder="Headings, lists, links, and formatted text…"
                            />
                        </FormField>
                    </FormCard>
                </div>
                <div className="space-y-6">
                    <FormCard title="Publishing">
                        <FormField label="Status" htmlFor="status" error={form.errors.status}>
                            <select
                                id="status"
                                value={form.data.status}
                                onChange={(event) => form.setData('status', event.target.value as 'draft' | 'published')}
                                className="h-9 w-full rounded-md border border-neutral-300 bg-transparent px-3 text-sm dark:border-neutral-700"
                            >
                                {statuses.map((status) => (
                                    <option key={status.value} value={status.value}>
                                        {status.label}
                                    </option>
                                ))}
                            </select>
                        </FormField>
                        <FormField
                            label="Template"
                            htmlFor="template"
                            error={form.errors.template}
                            hint="Contact pages show a message form under the content, the same way Shopify contact templates work."
                        >
                            <select
                                id="template"
                                value={form.data.template}
                                onChange={(event) => form.setData('template', event.target.value as 'default' | 'contact')}
                                className="h-9 w-full rounded-md border border-neutral-300 bg-transparent px-3 text-sm dark:border-neutral-700"
                            >
                                {templates.map((template) => (
                                    <option key={template.value} value={template.value}>
                                        {template.label}
                                    </option>
                                ))}
                            </select>
                        </FormField>
                    </FormCard>
                    <Button type="submit" disabled={form.processing} className="w-full">
                        {isEditing ? 'Save page' : 'Create page'}
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
