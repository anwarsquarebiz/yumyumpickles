import { PageEditor } from '@/pages/admin/pages/index';
import { type SelectOption } from '@/types';

export default function CreatePage({ statuses, templates }: { statuses: SelectOption[]; templates: SelectOption[] }) {
    return <PageEditor statuses={statuses} templates={templates} />;
}
