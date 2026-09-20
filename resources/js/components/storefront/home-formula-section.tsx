import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ArrowRight, Layers, Sparkles, Timer, Zap, type LucideIcon } from 'lucide-react';
import { useId, useState } from 'react';

interface Formula {
    id: string;
    kicker: string;
    title: string;
    description: string;
    cta: string;
    href: string;
    icon: LucideIcon;
}

const formulas: Formula[] = [
    {
        id: 'maximum-drive',
        kicker: 'Maximum Drive',
        title: 'Pure Potential',
        description: 'Designed to help you reclaim your energy and reach your peak. A powerful formula for those who demand more from every moment.',
        cta: 'Explore Driving Force',
        href: '/products',
        icon: Zap,
    },
    {
        id: 'lasting-authority',
        kicker: 'Lasting Authority',
        title: 'Extended Power',
        description:
            'Built for steady strength and a lifestyle of long-lasting confidence. Experience a trusted formula that keeps you ahead of the curve.',
        cta: 'Discover Strength',
        href: '/products',
        icon: Timer,
    },
    {
        id: 'dual-action',
        kicker: 'Dual-Action Control',
        title: 'Total Confidence',
        description: 'Our advanced combination solutions provide the ultimate balance. Stay in charge and lead with unmatched confidence.',
        cta: 'View Advanced Care',
        href: '/products',
        icon: Layers,
    },
    {
        id: 'instant-reaction',
        kicker: 'Instant Reaction',
        title: 'Private Edge',
        description: 'Fast-acting support for your personal needs. Designed for comfort, speed, and total satisfaction in a discrete way.',
        cta: 'Shop The Edge',
        href: '/products',
        icon: Sparkles,
    },
];

export function HomeFormulaSection() {
    const [activeId, setActiveId] = useState(formulas[0].id);
    const instanceId = useId();
    const activeIndex = formulas.findIndex((formula) => formula.id === activeId);
    const active = formulas[activeIndex] ?? formulas[0];
    const ActiveIcon = active.icon;

    return (
        <section className="bg-neutral-950 text-white dark:bg-black">
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-10 px-4 py-16 sm:px-6 lg:py-24">
                <header className="text-center">
                    <h2 className="text-3xl font-semibold tracking-tight sm:text-4xl lg:text-5xl">
                        Advanced <em className="text-primary italic">Performance</em> Formula
                    </h2>
                </header>

                <div className="grid gap-6 lg:grid-cols-12 lg:gap-8">
                    <div
                        role="tablist"
                        aria-label="Advanced Performance Formula"
                        className="flex gap-3 overflow-x-auto pb-1 lg:col-span-4 lg:flex-col lg:overflow-visible"
                    >
                        {formulas.map((formula, index) => {
                            const selected = formula.id === active.id;
                            const Icon = formula.icon;
                            const tabId = `${instanceId}-tab-${formula.id}`;
                            const panelId = `${instanceId}-panel-${formula.id}`;

                            return (
                                <button
                                    key={formula.id}
                                    id={tabId}
                                    type="button"
                                    role="tab"
                                    aria-selected={selected}
                                    aria-controls={panelId}
                                    tabIndex={selected ? 0 : -1}
                                    onClick={() => setActiveId(formula.id)}
                                    onKeyDown={(event) => {
                                        if (
                                            event.key !== 'ArrowDown' &&
                                            event.key !== 'ArrowUp' &&
                                            event.key !== 'ArrowRight' &&
                                            event.key !== 'ArrowLeft'
                                        ) {
                                            return;
                                        }

                                        event.preventDefault();
                                        const step = event.key === 'ArrowDown' || event.key === 'ArrowRight' ? 1 : -1;
                                        const next = formulas[(index + step + formulas.length) % formulas.length];
                                        setActiveId(next.id);
                                        document.getElementById(`${instanceId}-tab-${next.id}`)?.focus();
                                    }}
                                    className={cn(
                                        'flex min-w-[16rem] flex-1 items-start gap-3 rounded-2xl border px-4 py-4 text-left transition-colors lg:min-w-0',
                                        selected
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-white/10 bg-white/5 text-white hover:bg-white/10',
                                    )}
                                >
                                    <span
                                        className={cn(
                                            'flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                                            selected ? 'bg-primary-foreground/15' : 'bg-white/10',
                                        )}
                                    >
                                        {String(index + 1).padStart(2, '0')}
                                    </span>
                                    <span className="flex min-w-0 flex-1 flex-col gap-1">
                                        <span className="flex items-center gap-2">
                                            <Icon className="size-4 shrink-0" />
                                            <span className="font-semibold">{formula.kicker}</span>
                                        </span>
                                        <span className={cn('block text-sm', selected ? 'text-primary-foreground/80' : 'text-white/60')}>
                                            {formula.title}
                                        </span>
                                    </span>
                                </button>
                            );
                        })}
                    </div>

                    <div
                        id={`${instanceId}-panel-${active.id}`}
                        role="tabpanel"
                        aria-labelledby={`${instanceId}-tab-${active.id}`}
                        className="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-white/10 via-white/5 to-transparent p-8 lg:col-span-8 lg:p-12"
                    >
                        <p className="pointer-events-none absolute -right-4 -bottom-8 text-[8rem] font-semibold text-white/5 sm:text-[10rem]">
                            {String(activeIndex + 1).padStart(2, '0')}
                        </p>

                        <div className="relative flex max-w-xl flex-col gap-6">
                            <span className="bg-primary/20 text-primary flex size-12 items-center justify-center rounded-2xl">
                                <ActiveIcon className="size-6" />
                            </span>
                            <div className="flex flex-col gap-3">
                                <p className="text-xs font-semibold tracking-[0.2em] text-white/50 uppercase">
                                    Formula {String(activeIndex + 1).padStart(2, '0')} of {String(formulas.length).padStart(2, '0')}
                                </p>
                                <h3 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                                    <em className="text-primary not-italic sm:italic">{active.kicker}</em>
                                    <span className="text-white/80"> — {active.title}</span>
                                </h3>
                                <p className="text-base leading-relaxed text-white/75 sm:text-lg">{active.description}</p>
                            </div>
                            <Link
                                href={active.href}
                                prefetch
                                className="bg-primary text-primary-foreground hover:bg-primary/90 inline-flex w-fit items-center gap-2 rounded-lg px-5 py-3 text-sm font-medium tracking-wide uppercase transition-colors"
                            >
                                {active.cta}
                                <ArrowRight className="size-4" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
