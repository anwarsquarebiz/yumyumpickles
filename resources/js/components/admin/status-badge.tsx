const tones: Record<string, string> = {
    amber: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
    emerald: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
    blue: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200',
    indigo: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-200',
    green: 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-200',
    rose: 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-200',
    zinc: 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200',
};

/** Maps the publish/product statuses onto tones when no tone is supplied. */
const statusTones: Record<string, string> = {
    active: 'emerald',
    published: 'emerald',
    draft: 'zinc',
    archived: 'rose',
};

interface StatusBadgeProps {
    label: string;
    status?: string;
    tone?: string;
}

export function StatusBadge({ label, status, tone }: StatusBadgeProps) {
    const resolved = tone ?? statusTones[status ?? ''] ?? 'zinc';

    return <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${tones[resolved] ?? tones.zinc}`}>{label}</span>;
}
