import { PolicyPage } from '@/components/yumyum';
import StorefrontLayout from '@/layouts/storefront-layout';
import { type CmsPage, type SeoMeta, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';

interface PolicyProps {
    page: { data: CmsPage };
    policyKey: string;
    seo: SeoMeta;
}

export default function PolicyRoute({ page, policyKey, seo }: PolicyProps) {
    const policies = usePage<SharedData>().props.content?.policies ?? {};
    const fallback = page.data;
    const policy = policies[policyKey] ?? {
        title: fallback.title,
        intro: fallback.excerpt ?? '',
        sections: [{ title: fallback.title, body: fallback.excerpt ?? '' }],
    };

    return (
        <StorefrontLayout>
            <Head title={seo.title}>{seo.description && <meta name="description" content={seo.description} />}</Head>
            <PolicyPage {...policy} />
        </StorefrontLayout>
    );
}
