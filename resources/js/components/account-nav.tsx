import { Link } from '@inertiajs/react';

export function AccountNav({ current }: { current: 'profile' | 'orders' | 'addresses' }) {
    const items = [
        ['profile', '/account', 'Profile'],
        ['orders', '/account/orders', 'Orders'],
        ['addresses', '/account/addresses', 'Addresses'],
    ] as const;

    return (
        <nav className="mt-4 flex flex-wrap gap-4 text-sm">
            {items.map(([id, href, label]) => (
                <Link key={id} href={href} className={current === id ? 'font-extrabold text-primary' : 'text-muted-foreground hover:text-primary'}>
                    {label}
                </Link>
            ))}
        </nav>
    );
}
