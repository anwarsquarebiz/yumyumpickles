import { type HomeBanner } from '@/types';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useEffect, useState } from 'react';

interface HomeBannerSliderProps {
    banners: HomeBanner[];
}

const isExternalUrl = (url: string): boolean => /^https?:\/\//i.test(url);

function SlideAction({ banner }: { banner: HomeBanner }) {
    if (!banner.button_label || !banner.button_url) {
        return null;
    }

    const classes =
        'inline-flex rounded-lg bg-white px-5 py-3 text-sm font-medium text-neutral-900 transition-colors hover:bg-neutral-200 dark:bg-neutral-100 dark:hover:bg-white';

    if (isExternalUrl(banner.button_url)) {
        return (
            <a href={banner.button_url} className={classes} rel="noopener noreferrer">
                {banner.button_label}
            </a>
        );
    }

    return (
        <Link href={banner.button_url} prefetch className={classes}>
            {banner.button_label}
        </Link>
    );
}

export function HomeBannerSlider({ banners }: HomeBannerSliderProps) {
    const [index, setIndex] = useState(0);
    const [paused, setPaused] = useState(false);
    const count = banners.length;

    useEffect(() => {
        if (count < 2 || paused) {
            return;
        }

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        const timer = window.setInterval(() => {
            setIndex((current) => (current + 1) % count);
        }, 6000);

        return () => window.clearInterval(timer);
    }, [count, paused]);

    if (count === 0) {
        return null;
    }

    const goTo = (next: number) => {
        setIndex((next + count) % count);
    };

    return (
        <section
            aria-roledescription="carousel"
            aria-label="Featured promotions"
            className="relative w-full overflow-hidden bg-neutral-900"
            onMouseEnter={() => setPaused(true)}
            onMouseLeave={() => setPaused(false)}
        >
            <div className="relative min-h-[22rem] sm:min-h-[28rem] lg:min-h-[36rem]">
                {banners.map((banner, slideIndex) => {
                    const isActive = slideIndex === index;

                    return (
                        <div
                            key={banner.id}
                            className={`absolute inset-0 transition-opacity duration-700 ${isActive ? 'opacity-100' : 'pointer-events-none opacity-0'}`}
                            aria-hidden={!isActive}
                        >
                            {banner.image_url && (
                                <img
                                    src={banner.image_url}
                                    alt={banner.alt ?? ''}
                                    className="size-full object-cover"
                                    fetchPriority={slideIndex === 0 ? 'high' : 'low'}
                                />
                            )}
                            <div className="absolute inset-0 bg-gradient-to-r from-black/70 via-black/40 to-black/10 dark:from-black/80 dark:via-black/50 dark:to-black/20" />
                            <div className="absolute inset-0 flex items-center">
                                <div className="mx-auto w-full max-w-7xl px-4 sm:px-6">
                                    <div className="max-w-2xl space-y-4 text-white">
                                        {isActive ? (
                                            <h1 className="text-4xl font-semibold tracking-tight sm:text-5xl lg:text-6xl">{banner.title}</h1>
                                        ) : (
                                            <p className="text-4xl font-semibold tracking-tight sm:text-5xl lg:text-6xl">{banner.title}</p>
                                        )}
                                        {banner.subtitle && (
                                            <p className="max-w-xl text-base text-white/85 sm:text-lg">{banner.subtitle}</p>
                                        )}
                                        <SlideAction banner={banner} />
                                    </div>
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>

            {count > 1 && (
                <>
                    <button
                        type="button"
                        onClick={() => goTo(index - 1)}
                        className="absolute top-1/2 left-3 z-10 flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/40 text-white transition-colors hover:bg-black/60 sm:left-6"
                        aria-label="Previous slide"
                    >
                        <ChevronLeft className="size-5" />
                    </button>
                    <button
                        type="button"
                        onClick={() => goTo(index + 1)}
                        className="absolute top-1/2 right-3 z-10 flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/40 text-white transition-colors hover:bg-black/60 sm:right-6"
                        aria-label="Next slide"
                    >
                        <ChevronRight className="size-5" />
                    </button>
                    <div className="absolute bottom-4 left-1/2 z-10 flex -translate-x-1/2 gap-2">
                        {banners.map((banner, slideIndex) => (
                            <button
                                key={banner.id}
                                type="button"
                                onClick={() => goTo(slideIndex)}
                                className={`h-2.5 rounded-full transition-all ${
                                    slideIndex === index ? 'w-8 bg-white' : 'w-2.5 bg-white/50 hover:bg-white/80'
                                }`}
                                aria-label={`Go to slide ${slideIndex + 1}`}
                                aria-current={slideIndex === index}
                            />
                        ))}
                    </div>
                </>
            )}
        </section>
    );
}
