import { splitProductDetailsIntoSections } from '@/lib/product-details-html';
import { useMemo } from 'react';

interface ProductDetailsContentProps {
    html: string;
}

function sectionTone(title: string): 'default' | 'caution' | 'muted' {
    const lower = title.toLowerCase();

    if (lower.includes('disclaimer') || lower.includes('side effect') || lower.includes('avoid') || lower.includes('safety')) {
        return 'caution';
    }

    if (lower.includes('storage')) {
        return 'muted';
    }

    return 'default';
}

const toneClasses = {
    default: 'border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-950',
    caution: 'border-amber-200 bg-amber-50/60 dark:border-amber-900/50 dark:bg-amber-950/20',
    muted: 'border-neutral-200 bg-neutral-50/80 dark:border-neutral-800 dark:bg-neutral-900/40',
} as const;

export function ProductDetailsContent({ html }: ProductDetailsContentProps) {
    const sections = useMemo(() => splitProductDetailsIntoSections(html), [html]);

    if (sections.length === 0) {
        return null;
    }

    return (
        <div className="mt-6 flex flex-col gap-6">
            {sections.length > 1 && (
                <nav aria-label="On this page" className="rounded-xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-900/50">
                    <p className="text-xs font-semibold tracking-wide text-neutral-500 uppercase dark:text-neutral-400">On this page</p>
                    <ul className="mt-3 flex flex-wrap gap-2">
                        {sections.map((section) => (
                            <li key={section.id}>
                                <a
                                    href={`#${section.id}`}
                                    className="inline-flex rounded-full border border-neutral-200 bg-white px-3 py-1 text-sm text-neutral-700 transition-colors hover:border-neutral-400 hover:text-neutral-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-300 dark:hover:border-neutral-500 dark:hover:text-white"
                                >
                                    {section.title}
                                </a>
                            </li>
                        ))}
                    </ul>
                </nav>
            )}

            <div className="flex flex-col gap-4">
                {sections.map((section) => {
                    const tone = sectionTone(section.title);

                    return (
                        <article
                            key={section.id}
                            id={section.id}
                            className={`scroll-mt-28 rounded-xl border p-5 sm:p-6 ${toneClasses[tone]}`}
                        >
                            <h3 className="text-lg font-semibold tracking-tight text-neutral-900 dark:text-neutral-50">{section.title}</h3>
                            <div
                                className="product-details-body mt-4"
                                dangerouslySetInnerHTML={{ __html: section.html }}
                            />
                        </article>
                    );
                })}
            </div>
        </div>
    );
}
