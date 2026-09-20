import { ContactForm } from '@/components/storefront/contact-form';
import { Breadcrumbs, PageIntro } from '@/components/yumyum';
import StorefrontLayout from '@/layouts/storefront-layout';
import { type CmsPage, type SeoMeta } from '@/types';
import { Head } from '@inertiajs/react';

interface PageShowProps {
    page: { data: CmsPage };
    seo: SeoMeta;
}

export default function StorefrontPageShow({ page, seo }: PageShowProps) {
    const item = page.data;

    return (
        <StorefrontLayout>
            <Head title={seo.title}>{seo.description && <meta name="description" content={seo.description} />}</Head>
            <PageIntro eyebrow="YumYum Pickles" title={item.title} copy={item.excerpt ?? ''} />
            <Breadcrumbs items={[{ label: item.title }]} />
            <article className="section-shell max-w-3xl pb-20">
                {item.content && <div className="page-content mt-8" dangerouslySetInnerHTML={{ __html: item.content }} />}
                {item.template === 'contact' && <ContactForm slug={item.slug} />}
            </article>
        </StorefrontLayout>
    );
}
