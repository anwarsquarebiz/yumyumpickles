import { Breadcrumbs, PageIntro } from '@/components/yumyum';
import StorefrontLayout from '@/layouts/storefront-layout';
import { type SeoMeta, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';

interface RecipesProps {
    seo: SeoMeta;
}

export default function RecipesPage({ seo }: RecipesProps) {
    const recipes = usePage<SharedData>().props.content?.recipes ?? [];

    return (
        <StorefrontLayout>
            <Head title={seo.title}>{seo.description && <meta name="description" content={seo.description} />}</Head>
            <PageIntro eyebrow="Recipe inspiration" title="One spoon. Endless plates." copy="Everyday Indian meals made brighter with a jar from the YumYum kitchen." />
            <Breadcrumbs items={[{ label: 'Recipes' }]} />
            <div className="section-shell grid gap-8 py-16">
                {recipes.map((recipe) => (
                    <article key={recipe.id} id={recipe.id} className="border-border bg-card grid overflow-hidden rounded-2xl border lg:grid-cols-[1.1fr_1fr]">
                        {recipe.image && <img src={recipe.image} alt={recipe.name} className="min-h-72 w-full object-cover" style={{ objectPosition: recipe.position }} />}
                        <div className="p-6 sm:p-8">
                            <p className="text-secondary text-xs font-bold uppercase">
                                {recipe.time} · {recipe.difficulty} · Best with {recipe.pickle}
                            </p>
                            <h2 className="mt-2 text-3xl font-extrabold">{recipe.name}</h2>
                            <p className="text-muted-foreground mt-3 leading-7">{recipe.excerpt}</p>
                            <ol className="mt-6 space-y-3 text-sm leading-6">
                                {(recipe.steps ?? []).map((step, index) => (
                                    <li key={step}>
                                        <b className="text-primary">{index + 1}.</b> {step}
                                    </li>
                                ))}
                            </ol>
                        </div>
                    </article>
                ))}
            </div>
        </StorefrontLayout>
    );
}
