import { AvatarMark, Carousel, Newsletter, ProductCard, RecentlyViewed, SectionTitle, Stars, VideoCard } from '@/components/yumyum';
import { Button } from '@/components/ui/button';
import StorefrontLayout from '@/layouts/storefront-layout';
import { toPickle, unwrapData } from '@/lib/pickle';
import { images } from '@/lib/yumyum-images';
import { type HomeBanner, type ProductSummary, type SeoMeta, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, Check, ChefHat, HeartHandshake, Leaf, PackageCheck, Sparkles, Sun, Truck } from 'lucide-react';
import { useEffect, useState } from 'react';

const heroSlides = [
    { src: images.productsImage, alt: 'YumYum Prawns Balchao, Brinjal, Bombil and Tendli pickle jars' },
    { src: images.prawnsImage, alt: 'YumYum Prawns Balchao homemade pickle' },
    { src: images.lifestyleImage, alt: 'Family dining with homemade pickle' },
];

const whyIcons = [HeartHandshake, ChefHat, Leaf, Sparkles, PackageCheck, Truck];

interface HomeProps {
    banners: { data: HomeBanner[] };
    featuredProducts: { data: ProductSummary[] };
    seo: SeoMeta;
}

export default function Home({ featuredProducts, seo }: HomeProps) {
    const { content, catalog } = usePage<SharedData>().props;
    const products = (unwrapData(featuredProducts) || catalog || []).map(toPickle);
    const { categories = [], instagramPosts = [], recipes = [], storySteps = [], testimonials = [], videos = [], whyChoose = [], brand } = content ?? {};
    const [slide, setSlide] = useState(0);

    useEffect(() => {
        const timer = window.setInterval(() => setSlide((value) => (value + 1) % heroSlides.length), 6000);
        return () => window.clearInterval(timer);
    }, []);

    return (
        <StorefrontLayout>
            <Head title={seo.title || 'YumYum Pickles — The Taste of Tradition'}>
                {seo.description && <meta name="description" content={seo.description} />}
            </Head>

            <section className="bg-brand-deep relative min-h-[680px] overflow-hidden lg:min-h-[calc(100vh-128px)]">
                {heroSlides.map((item, index) => (
                    <img
                        key={item.src}
                        src={item.src}
                        alt={item.alt}
                        width="1920"
                        height="1088"
                        className={`image-warm kenburns absolute inset-0 size-full object-cover transition-opacity duration-1000 ${index === slide ? 'opacity-100' : 'opacity-0'}`}
                    />
                ))}
                <div className="from-brand-deep via-brand-deep/75 absolute inset-0 bg-gradient-to-r to-transparent" />
                <div className="section-shell relative flex min-h-[680px] items-center py-16 lg:min-h-[calc(100vh-128px)]">
                    <div className="text-primary-foreground max-w-2xl">
                        <p className="text-accent mb-4 font-script text-4xl sm:text-5xl">The Taste of Tradition</p>
                        <h1 className="text-5xl leading-[1.05] font-extrabold sm:text-7xl">The Taste Of Tradition In Every Jar</h1>
                        <p className="text-primary-foreground/80 mt-6 max-w-xl text-base leading-7 sm:text-lg">
                            Handcrafted homemade pickles made from authentic recipes, premium ingredients, and generations of culinary heritage.
                        </p>
                        <div className="mt-8 flex flex-wrap gap-3">
                            <Button asChild variant="hero" size="lg">
                                <Link href="/shop">
                                    Shop Pickles <ArrowRight />
                                </Link>
                            </Button>
                            <Button asChild variant="outline" size="lg" className="border-primary-foreground/50 text-primary-foreground hover:text-brand-deep bg-transparent hover:bg-primary-foreground">
                                <Link href="/our-story">Our Story</Link>
                            </Button>
                        </div>
                        <div className="mt-10 grid grid-cols-2 gap-3 text-xs font-semibold sm:flex sm:flex-wrap">
                            {['Homemade Recipes', 'Premium Ingredients', 'No Artificial Preservatives', 'Hygienically Packed', 'Pan India Delivery'].map((item) => (
                                <span key={item} className="flex items-center gap-2">
                                    <Check className="text-accent size-4" />
                                    {item}
                                </span>
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            <section className="py-16 sm:py-24">
                <div className="section-shell">
                    <SectionTitle script="Find your favourite" title="Featured categories" copy="From beloved classics to regional treasures, each jar carries a distinct story." />
                    <div className="mt-10 grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
                        {categories.map((category) => (
                            <Link key={category.name} href={`/shop?category=${encodeURIComponent(category.name)}`} className="group relative aspect-[3/4] overflow-hidden rounded-lg">
                                {category.image && (
                                    <img src={category.image} alt={category.label} loading="lazy" className="size-full object-cover transition-transform duration-500 group-hover:scale-110" style={{ objectPosition: category.position }} />
                                )}
                                <div className="absolute inset-0 bg-gradient-to-t from-brand-deep/95 via-transparent to-transparent" />
                                <div className="text-primary-foreground absolute inset-x-0 bottom-0 p-4">
                                    <h3 className="text-lg font-extrabold">{category.label}</h3>
                                    <p className="text-primary-foreground/75 mt-1 text-xs">{category.tagline}</p>
                                </div>
                            </Link>
                        ))}
                    </div>
                </div>
            </section>

            <section className="bg-muted py-16 sm:py-24">
                <div className="section-shell">
                    <div className="flex items-end justify-between gap-4">
                        <SectionTitle script="Most loved" title="Best selling products" copy="The flavours our customers come back for — and tell their neighbours about." />
                        <Button asChild variant="outline" className="hidden sm:inline-flex">
                            <Link href="/shop">
                                View all <ArrowRight />
                            </Link>
                        </Button>
                    </div>
                    <div className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        {products
                            .filter((product) => product.inStock)
                            .slice(0, 4)
                            .map((product) => (
                                <ProductCard key={product.id} product={product} />
                            ))}
                    </div>
                    <div className="border-primary/20 bg-background mt-8 rounded-2xl border p-5 text-center sm:text-left">
                        <p className="font-extrabold">Buy 2 Get 1 on every cart of 3 jars</p>
                        <p className="text-muted-foreground mt-1 text-sm">The cheapest jar is on us. Pair mango with lemon, or build a regional tasting set.</p>
                    </div>
                </div>
            </section>

            <section className="py-16 sm:py-24">
                <div className="section-shell">
                    <SectionTitle script="From Our Kitchen To Your Table" title="A family ritual, bottled with care" copy="We choose each fruit by hand, blend whole spices in small batches, and let time and sunshine do what shortcuts never can." />
                    <div className="mt-12 grid gap-6 lg:grid-cols-3">
                        {storySteps.slice(0, 3).map((step, index) => (
                            <article key={step.title} className="border-border bg-card overflow-hidden rounded-2xl border shadow-sm">
                                <div className="aspect-[4/3] overflow-hidden">
                                    {step.image && <img src={step.image} alt={step.title} loading="lazy" className="image-warm size-full object-cover" style={{ objectPosition: step.position }} />}
                                </div>
                                <div className="p-5">
                                    <span className="text-primary text-2xl font-extrabold">0{index + 1}</span>
                                    <h3 className="mt-2 text-xl font-extrabold">{step.title}</h3>
                                    <p className="text-muted-foreground mt-2 text-sm leading-6">{step.copy}</p>
                                </div>
                            </article>
                        ))}
                    </div>
                    <Button asChild variant="golden" size="lg" className="mt-10">
                        <Link href="/our-story">
                            Read our story <ArrowRight />
                        </Link>
                    </Button>
                </div>
            </section>

            <section className="bg-muted py-16 sm:py-24">
                <div className="section-shell">
                    <SectionTitle script="Why YumYum" title="Why choose YumYum" copy="Good pickle needs patience, clean ingredients, and hands that know the recipe by heart." />
                    <div className="border-border bg-border mt-10 grid gap-px overflow-hidden rounded-lg border sm:grid-cols-2 lg:grid-cols-3">
                        {whyChoose.map((item, index) => {
                            const Icon = whyIcons[index] ?? Sun;
                            return (
                                <div key={item.title} className="bg-background p-7">
                                    <Icon className="text-primary size-8" />
                                    <h3 className="mt-4 text-lg font-extrabold">{item.title}</h3>
                                    <p className="text-muted-foreground mt-2 text-sm leading-6">{item.copy}</p>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </section>

            <section className="bg-brand-deep text-primary-foreground py-16 sm:py-24">
                <div className="section-shell">
                    <SectionTitle dark script="Watch the magic" title="See YumYum Pickles In Action" copy="Instagram Reels, YouTube Shorts, recipes, behind the scenes and honest customer reactions." />
                    <Carousel className="mt-10" tone="dark" label="Videos">
                        {videos.map((video) => (
                            <VideoCard key={video.title} {...video} />
                        ))}
                    </Carousel>
                </div>
            </section>

            <section className="py-16 sm:py-24">
                <div className="section-shell">
                    <SectionTitle script="Serve it your way" title="Recipe inspiration" copy="Everyday meals transformed with a bright, spicy little twist." />
                    <div className="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                        {recipes.map((recipe) => (
                            <Link href="/recipes" key={recipe.id} className="border-border bg-card group overflow-hidden rounded-lg border">
                                <div className="aspect-square overflow-hidden">
                                    {recipe.image && <img src={recipe.image} alt={recipe.name} loading="lazy" className="size-full object-cover transition-transform duration-500 group-hover:scale-105" style={{ objectPosition: recipe.position }} />}
                                </div>
                                <div className="p-4">
                                    <h3 className="font-extrabold">{recipe.name}</h3>
                                    <p className="text-muted-foreground mt-1 text-xs">
                                        {recipe.time} · {recipe.difficulty}
                                    </p>
                                </div>
                            </Link>
                        ))}
                    </div>
                </div>
            </section>

            <section className="bg-muted py-16 sm:py-24">
                <div className="section-shell">
                    <SectionTitle script="From the YumYum family" title="Loved across India" copy="Real notes from tables where our jars have found a home." />
                    <Carousel className="mt-10" label="Testimonials">
                        {testimonials.map((item) => (
                            <blockquote key={item.name} className="border-border bg-background w-[min(100vw-3rem,360px)] rounded-lg border p-7 shadow-sm">
                                <div className="flex items-center gap-3">
                                    <AvatarMark initials={item.initials} />
                                    <div>
                                        <b>{item.name}</b>
                                        <p className="text-muted-foreground text-sm">{item.city}</p>
                                    </div>
                                </div>
                                <div className="mt-4">
                                    <Stars value={item.rating} />
                                </div>
                                <p className="mt-4 leading-7">“{item.text}”</p>
                            </blockquote>
                        ))}
                    </Carousel>
                </div>
            </section>

            <section className="py-16 sm:py-24">
                <div className="section-shell">
                    <div className="flex items-end justify-between gap-4">
                        <SectionTitle script="On the gram" title="Instagram feed" copy="Jars, kitchens and first tastes from the YumYum table." />
                        <Button asChild variant="outline">
                            <a href={brand?.instagram} target="_blank" rel="noreferrer">
                                Follow @yumyumpickles
                            </a>
                        </Button>
                    </div>
                    <div className="masonry mt-10">
                        {instagramPosts.map((post) => (
                            <a key={`${post.alt}-${post.likes}`} href={brand?.instagram} target="_blank" rel="noreferrer" className="group relative mb-3 block overflow-hidden rounded-xl">
                                {post.image && (
                                    <img src={post.image} alt={post.alt} loading="lazy" className="w-full object-cover transition duration-500 group-hover:scale-105" style={{ objectPosition: post.position, aspectRatio: post.reel ? '3/4' : '1/1' }} />
                                )}
                                <div className="bg-brand-deep/0 text-primary-foreground absolute inset-0 flex items-end justify-between p-3 text-xs font-bold opacity-0 transition group-hover:bg-brand-deep/45 group-hover:opacity-100">
                                    <span>♥ {post.likes.toLocaleString('en-IN')}</span>
                                    {post.reel && <span className="bg-accent text-accent-foreground rounded px-2 py-1">Reel</span>}
                                </div>
                            </a>
                        ))}
                    </div>
                </div>
            </section>

            <RecentlyViewed />
            <Newsletter />
        </StorefrontLayout>
    );
}
