import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type SharedData } from '@/types';
import { useForm, usePage } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

export function ContactForm({ slug }: { slug: string }) {
    const { auth } = usePage<SharedData>().props;
    const form = useForm({
        name: auth.user?.name ?? '',
        email: auth.user?.email ?? '',
        phone: '',
        message: '',
        website: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        form.post(`/pages/${slug}/contact`, { preserveScroll: true });
    };

    return (
        <section className="mt-10 rounded-xl border border-neutral-200 p-6 dark:border-neutral-800">
            <h2 className="text-xl font-semibold tracking-tight">Send us a message</h2>
            <p className="text-muted-foreground mt-1 text-sm">We usually reply within one business day.</p>

            <form onSubmit={submit} className="mt-6 grid gap-4">
                <div className="hidden" aria-hidden="true">
                    <label htmlFor="contact-website">Website</label>
                    <input
                        id="contact-website"
                        tabIndex={-1}
                        autoComplete="off"
                        value={form.data.website}
                        onChange={(event) => form.setData('website', event.target.value)}
                    />
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="contact-name">
                            Name <span className="text-rose-600">*</span>
                        </Label>
                        <Input
                            id="contact-name"
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                            required
                        />
                        <InputError message={form.errors.name} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="contact-email">
                            Email <span className="text-rose-600">*</span>
                        </Label>
                        <Input
                            id="contact-email"
                            type="email"
                            value={form.data.email}
                            onChange={(event) => form.setData('email', event.target.value)}
                            required
                        />
                        <InputError message={form.errors.email} />
                    </div>
                </div>
                <div className="space-y-2">
                    <Label htmlFor="contact-phone">Phone</Label>
                    <Input
                        id="contact-phone"
                        type="tel"
                        value={form.data.phone}
                        onChange={(event) => form.setData('phone', event.target.value)}
                    />
                    <InputError message={form.errors.phone} />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="contact-message">
                        Message <span className="text-rose-600">*</span>
                    </Label>
                    <textarea
                        id="contact-message"
                        rows={6}
                        value={form.data.message}
                        onChange={(event) => form.setData('message', event.target.value)}
                        required
                        className="w-full rounded-md border border-neutral-300 bg-transparent px-3 py-2 text-sm dark:border-neutral-700"
                    />
                    <InputError message={form.errors.message} />
                </div>
                <Button type="submit" disabled={form.processing}>
                    Send message
                </Button>
            </form>
        </section>
    );
}
