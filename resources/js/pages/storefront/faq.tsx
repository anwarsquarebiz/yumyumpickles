import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { Breadcrumbs, PageIntro } from '@/components/yumyum';
import StorefrontLayout from '@/layouts/storefront-layout';
import { type SeoMeta, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';

interface FaqProps {
    seo: SeoMeta;
}

export default function FaqPage({ seo }: FaqProps) {
    const faqs = usePage<SharedData>().props.content?.faqs ?? [];

    return (
        <StorefrontLayout>
            <Head title={seo.title}>{seo.description && <meta name="description" content={seo.description} />}</Head>
            <PageIntro eyebrow="FAQs" title="The questions we get over chai" copy="Preservatives, spice, shipping and how long a jar lasts once it finds your fridge." />
            <Breadcrumbs items={[{ label: 'FAQs' }]} />
            <div className="section-shell max-w-3xl py-16">
                <Accordion type="single" collapsible>
                    {faqs.map((item) => (
                        <AccordionItem key={item.q} value={item.q}>
                            <AccordionTrigger>{item.q}</AccordionTrigger>
                            <AccordionContent>{item.a}</AccordionContent>
                        </AccordionItem>
                    ))}
                </Accordion>
            </div>
        </StorefrontLayout>
    );
}
