import { AdminSidebar } from '@/components/admin-sidebar';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { FlashMessages } from '@/components/flash-messages';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface AdminLayoutProps {
    children: React.ReactNode;
    breadcrumbs?: BreadcrumbItem[];
    title: string;
    description?: string;
    actions?: React.ReactNode;
}

export default function AdminLayout({ children, breadcrumbs = [], title, description, actions }: AdminLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <Head title={`${title} · Admin`} />
            <AdminSidebar />
            <AppContent variant="sidebar">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />

                <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="space-y-1">
                            <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
                            {description && <p className="text-muted-foreground text-sm">{description}</p>}
                        </div>
                        {actions && <div className="flex shrink-0 items-center gap-2">{actions}</div>}
                    </div>

                    <FlashMessages />

                    {children}
                </div>
            </AppContent>
        </AppShell>
    );
}
