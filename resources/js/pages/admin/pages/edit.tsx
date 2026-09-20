import { PageEditor } from '@/pages/admin/pages/index';
import { type CmsPage, type SelectOption } from '@/types';

export default function EditPage({
    page,
    statuses,
    templates,
}: {
    page: { data: CmsPage };
    statuses: SelectOption[];
    templates: SelectOption[];
}) {
    return <PageEditor page={page.data} statuses={statuses} templates={templates} />;
}
