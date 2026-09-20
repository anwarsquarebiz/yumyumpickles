import { NavUser } from '@/components/nav-user';
import { StoreLogo } from '@/components/store-logo';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type NavGroup } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BookText, FileText, Images, LayoutGrid, Menu, MessageSquare, Package, Settings, ShoppingCart, Store, Tags, Ticket, Truck, Users } from 'lucide-react';

const navGroups: NavGroup[] = [
    {
        title: 'Overview',
        items: [{ title: 'Dashboard', url: '/admin', icon: LayoutGrid }],
    },
    {
        title: 'Catalog',
        items: [
            { title: 'Products', url: '/admin/products', icon: Package },
            { title: 'Collections', url: '/admin/collections', icon: Tags },
        ],
    },
    {
        title: 'Sales',
        items: [
            { title: 'Orders', url: '/admin/orders', icon: ShoppingCart },
            { title: 'Customers', url: '/admin/customers', icon: Users },
            { title: 'Coupons', url: '/admin/coupons', icon: Ticket },
            { title: 'Reviews', url: '/admin/reviews', icon: MessageSquare },
        ],
    },
    {
        title: 'Content',
        items: [
            { title: 'Pages', url: '/admin/pages', icon: FileText },
            { title: 'Banners', url: '/admin/banners', icon: Images },
            { title: 'Navigation', url: '/admin/navigation', icon: Menu },
            { title: 'Blogs', url: '/admin/blogs', icon: BookText },
        ],
    },
    {
        title: 'Configuration',
        items: [
            { title: 'Settings', url: '/admin/settings', icon: Settings },
            { title: 'Shipping methods', url: '/admin/shipping-methods', icon: Truck },
        ],
    },
];

export function AdminSidebar() {
    const { url } = usePage();

    /**
     * The dashboard lives at the prefix root, so it only matches exactly;
     * every other section also matches its nested create/edit screens.
     */
    const isActive = (itemUrl: string): boolean => (itemUrl === '/admin' ? url === '/admin' : url.startsWith(itemUrl));

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/admin" prefetch>
                                <div className="text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center overflow-hidden rounded-lg">
                                    <StoreLogo
                                        imageClassName="h-full w-full object-contain"
                                        fallbackClassName="size-4 fill-current text-sidebar-primary-foreground"
                                    />
                                </div>
                                <div className="grid flex-1 text-left text-sm leading-tight">
                                    <span className="truncate font-semibold">YumYum Pickles</span>
                                    <span className="text-muted-foreground truncate text-xs">Kitchen admin</span>
                                </div>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {navGroups.map((group) => (
                    <SidebarGroup key={group.title} className="px-2 py-0">
                        <SidebarGroupLabel>{group.title}</SidebarGroupLabel>
                        <SidebarMenu>
                            {group.items.map((item) => (
                                <SidebarMenuItem key={item.title}>
                                    <SidebarMenuButton asChild isActive={isActive(item.url)} tooltip={{ children: item.title }}>
                                        <Link href={item.url} prefetch>
                                            {item.icon && <item.icon />}
                                            <span>{item.title}</span>
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            ))}
                        </SidebarMenu>
                    </SidebarGroup>
                ))}
            </SidebarContent>

            <SidebarFooter>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton asChild tooltip={{ children: 'View storefront' }}>
                            <Link href="/" prefetch>
                                <Store />
                                <span>View storefront</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
