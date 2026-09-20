import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';

interface FormFieldProps {
    label: string;
    htmlFor?: string;
    error?: string;
    hint?: string;
    required?: boolean;
    children: React.ReactNode;
    className?: string;
}

export function FormField({ label, htmlFor, error, hint, required, children, className }: FormFieldProps) {
    return (
        <div className={`space-y-2 ${className ?? ''}`}>
            <Label htmlFor={htmlFor}>
                {label}
                {required && <span className="text-rose-600"> *</span>}
            </Label>
            {children}
            {hint && !error && <p className="text-muted-foreground text-xs">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}

/** A bordered section used to group fields on the admin forms. */
export function FormCard({ title, description, children }: { title: string; description?: string; children: React.ReactNode }) {
    return (
        <section className="space-y-4 rounded-xl border border-neutral-200 p-4 md:p-6 dark:border-neutral-800">
            <div className="space-y-1">
                <h2 className="font-medium">{title}</h2>
                {description && <p className="text-muted-foreground text-sm">{description}</p>}
            </div>
            {children}
        </section>
    );
}
