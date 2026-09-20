import { ContactForm } from '@/components/storefront/contact-form';
import { Breadcrumbs, PageIntro } from '@/components/yumyum';
import StorefrontLayout from '@/layouts/storefront-layout';
import { type CmsPage, type SeoMeta, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';

interface ContactProps {
    page: { data: CmsPage };
    seo: SeoMeta;
}

export default function ContactPage({ page, seo }: ContactProps) {
    const brand = usePage<SharedData>().props.content?.brand;
    const item = page.data;

    return (
        <StorefrontLayout>
            <Head title={seo.title}>{seo.description && <meta name="description" content={seo.description} />}</Head>
            <PageIntro eyebrow="Contact Us" title="The kitchen is listening" copy="Questions about spice levels, gift hampers or a jar that travelled badly — write to us." />
            <Breadcrumbs items={[{ label: 'Contact' }]} />
            <div className="section-shell grid gap-10 py-16 lg:grid-cols-2">
                <div className="space-y-4 text-sm leading-7">
                    <p>
                        <b>Email:</b> {brand?.email}
                    </p>
                    <p>
                        <b>Phone / WhatsApp:</b> {brand?.phone}
                    </p>
                    <p>
                        <b>Hours:</b> Monday to Saturday, 10am – 6pm IST
                    </p>
                    <p>For NRI families, we happily pack extra-secure gift boxes to Indian addresses.</p>
                </div>
                <ContactForm slug={item.slug} />
            </div>
        </StorefrontLayout>
    );
}
