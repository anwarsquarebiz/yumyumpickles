import { Breadcrumbs, PageIntro, SectionTitle } from '@/components/yumyum';
import { Button } from '@/components/ui/button';
import StorefrontLayout from '@/layouts/storefront-layout';
import { images } from '@/lib/yumyum-images';
import { type CmsPage, type SeoMeta, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

interface StoryProps {
    page: { data: CmsPage };
    seo: SeoMeta;
}

export default function StoryPage({ seo }: StoryProps) {
    const { content } = usePage<SharedData>().props;
    const storySteps = content?.storySteps ?? [];
    const whyChoose = content?.whyChoose ?? [];

    return (
        <StorefrontLayout>
            <Head title={seo.title}>{seo.description && <meta name="description" content={seo.description} />}</Head>
            <PageIntro eyebrow="About Us" title="From Our Kitchen To Your Table" copy="YumYum Pickles is a homemade pickle brand offering authentic Indian pickles prepared using traditional family recipes, premium ingredients, and hygienic processes." />
            <Breadcrumbs items={[{ label: 'Our Story' }]} />
            <section className="section-shell grid items-center gap-10 py-16 lg:grid-cols-2">
                <img src={images.lifestyleImage} alt="A family table with homemade pickle" className="image-warm rounded-2xl object-cover" />
                <div>
                    <p className="text-primary font-script text-4xl">The Taste of Tradition</p>
                    <h2 className="mt-2 text-4xl font-extrabold">We cook the way we were taught</h2>
                    <p className="text-muted-foreground mt-4 leading-7">This brand began in a home kitchen, not a factory brief. The first jars were packed for relatives, then neighbours, then people who missed the pickle their nani never wrote down. We still choose fruit by hand and let the sun finish the work.</p>
                </div>
            </section>
            <section className="bg-muted py-16">
                <div className="section-shell">
                    <SectionTitle script="How a jar is born" title="A visual storytelling timeline" copy="Six quiet steps. No hurry. No artificial keepers." />
                    <div className="mt-10 grid gap-8">
                        {storySteps.map((step, index) => (
                            <article key={step.title} className={`border-border bg-background grid items-center gap-6 overflow-hidden rounded-2xl border lg:grid-cols-2 ${index % 2 ? 'lg:[&>img]:order-2' : ''}`}>
                                {step.image && <img src={step.image} alt={step.title} className="h-full min-h-72 w-full object-cover" style={{ objectPosition: step.position }} />}
                                <div className="p-8">
                                    <span className="text-primary text-4xl font-extrabold">0{index + 1}</span>
                                    <h3 className="mt-3 text-3xl font-extrabold">{step.title}</h3>
                                    <p className="text-muted-foreground mt-4 leading-7">{step.copy}</p>
                                </div>
                            </article>
                        ))}
                    </div>
                </div>
            </section>
            <section className="section-shell py-16">
                <h2 className="text-3xl font-extrabold">Why families choose YumYum</h2>
                <div className="mt-8 grid gap-4 md:grid-cols-2">
                    {whyChoose.map((item) => (
                        <article key={item.title} className="border-border rounded-xl border p-5">
                            <h3 className="font-extrabold">{item.title}</h3>
                            <p className="text-muted-foreground mt-2 text-sm leading-6">{item.copy}</p>
                        </article>
                    ))}
                </div>
                <Button asChild size="lg" className="mt-10">
                    <Link href="/shop">Shop the pantry</Link>
                </Button>
            </section>
        </StorefrontLayout>
    );
}
