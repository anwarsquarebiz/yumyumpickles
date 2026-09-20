import { type Paginated, type PaginationLink } from '@/types';
import { Link } from '@inertiajs/react';

interface PaginationProps {
    paginator: Pick<Paginated<unknown>, 'meta'> & {
        /** Raw LengthAwarePaginator shape (before JsonResource wrapping). */
        links?: PaginationLink[] | Paginated<unknown>['links'];
        from?: number | null;
        to?: number | null;
        total?: number;
    };
}

export function Pagination({ paginator }: PaginationProps) {
    const metaLinks = paginator.meta?.links;
    const rootLinks = Array.isArray(paginator.links) ? paginator.links : [];
    const links = Array.isArray(metaLinks) && metaLinks.length > 0 ? metaLinks : rootLinks;

    const from = paginator.meta?.from ?? paginator.from ?? null;
    const to = paginator.meta?.to ?? paginator.to ?? null;
    const total = paginator.meta?.total ?? paginator.total ?? 0;

    /** Laravel emits Previous/Next plus a page for each number; hide when there is one page. */
    if (!Array.isArray(links) || links.length <= 3) {
        return null;
    }

    return (
        <nav className="flex flex-col items-center justify-between gap-4 sm:flex-row" aria-label="Pagination">
            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                Showing {from ?? 0}–{to ?? 0} of {total}
            </p>

            <div className="flex flex-wrap items-center gap-1">
                {links.map((link, index) =>
                    link.url === null ? (
                        <span
                            key={index}
                            className="rounded-md px-3 py-2 text-sm text-neutral-400 dark:text-neutral-600"
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ) : (
                        <Link
                            key={index}
                            href={link.url}
                            preserveScroll
                            className={`rounded-md px-3 py-2 text-sm transition-colors ${
                                link.active
                                    ? 'bg-primary text-primary-foreground'
                                    : 'hover:bg-accent text-neutral-600 dark:text-neutral-300'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ),
                )}
            </div>
        </nav>
    );
}
