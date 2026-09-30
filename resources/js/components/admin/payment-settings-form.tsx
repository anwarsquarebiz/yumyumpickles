import { FormCard, FormField } from '@/components/admin/form-field';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

type RazorpayMode = 'test' | 'live';

interface RazorpayCredentialsProps {
    key_id: string;
    has_key_secret: boolean;
    has_webhook_secret: boolean;
}

export interface PaymentSettingsProps {
    cod_enabled: boolean;
    razorpay: {
        enabled: boolean;
        mode: RazorpayMode;
        webhook_url: string;
        test: RazorpayCredentialsProps;
        live: RazorpayCredentialsProps;
    };
}

type CredentialFields = {
    key_id: string;
    key_secret: string;
    webhook_secret: string;
};

type PaymentSettingsFormData = {
    cod_enabled: boolean;
    razorpay: {
        enabled: boolean;
        mode: RazorpayMode;
        test: CredentialFields;
        live: CredentialFields;
    };
};

const modeLabels: Record<RazorpayMode, string> = { test: 'Test', live: 'Live' };

export function PaymentSettingsForm({ payments }: { payments: PaymentSettingsProps }) {
    const { razorpay } = payments;

    const form = useForm<PaymentSettingsFormData>({
        cod_enabled: payments.cod_enabled,
        razorpay: {
            enabled: razorpay.enabled,
            mode: razorpay.mode,
            test: { key_id: razorpay.test.key_id, key_secret: '', webhook_secret: '' },
            live: { key_id: razorpay.live.key_id, key_secret: '', webhook_secret: '' },
        },
    });

    const { data, setData, errors, processing } = form;
    const error = (field: string): string | undefined => (errors as Record<string, string>)[field];

    const setRazorpay = (next: Partial<PaymentSettingsFormData['razorpay']>) => setData('razorpay', { ...data.razorpay, ...next });

    const setCredential = (mode: RazorpayMode, field: keyof CredentialFields, value: string) =>
        setRazorpay({ [mode]: { ...data.razorpay[mode], [field]: value } });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        form.put('/admin/settings/payments', {
            preserveScroll: true,
            onSuccess: () =>
                setData('razorpay', {
                    ...data.razorpay,
                    test: { ...data.razorpay.test, key_secret: '', webhook_secret: '' },
                    live: { ...data.razorpay.live, key_secret: '', webhook_secret: '' },
                }),
        });
    };

    const credentialsCard = (mode: RazorpayMode) => {
        const saved = razorpay[mode];
        const isActive = data.razorpay.mode === mode;

        return (
            <FormCard
                title={`Razorpay ${modeLabels[mode].toLowerCase()} keys${isActive ? ' (active)' : ''}`}
                description={`From Razorpay Dashboard → Account & Settings → API Keys, with the dashboard switched to ${modeLabels[mode]} mode.`}
            >
                <FormField label="Key ID" htmlFor={`razorpay_${mode}_key_id`} error={error(`razorpay.${mode}.key_id`)}>
                    <Input
                        id={`razorpay_${mode}_key_id`}
                        autoComplete="off"
                        placeholder={`rzp_${mode}_XXXXXXXXXXXXXX`}
                        value={data.razorpay[mode].key_id}
                        onChange={(event) => setCredential(mode, 'key_id', event.target.value.trim())}
                    />
                </FormField>
                <FormField
                    label="Key secret"
                    htmlFor={`razorpay_${mode}_key_secret`}
                    error={error(`razorpay.${mode}.key_secret`)}
                    hint={
                        saved.has_key_secret
                            ? 'A secret is saved. Leave blank to keep it, or paste a new one to replace it.'
                            : 'Stored encrypted and never shown again.'
                    }
                >
                    <Input
                        id={`razorpay_${mode}_key_secret`}
                        type="password"
                        autoComplete="new-password"
                        value={data.razorpay[mode].key_secret}
                        onChange={(event) => setCredential(mode, 'key_secret', event.target.value)}
                    />
                </FormField>
                <FormField
                    label="Webhook secret"
                    htmlFor={`razorpay_${mode}_webhook_secret`}
                    error={error(`razorpay.${mode}.webhook_secret`)}
                    hint={
                        saved.has_webhook_secret
                            ? 'A webhook secret is saved. Leave blank to keep it.'
                            : 'The secret you set when creating the webhook below. Orders are only marked paid once Razorpay calls it.'
                    }
                >
                    <Input
                        id={`razorpay_${mode}_webhook_secret`}
                        type="password"
                        autoComplete="new-password"
                        value={data.razorpay[mode].webhook_secret}
                        onChange={(event) => setCredential(mode, 'webhook_secret', event.target.value)}
                    />
                </FormField>
            </FormCard>
        );
    };

    return (
        <form onSubmit={submit} className="grid gap-6 lg:grid-cols-2">
            <FormCard title="Payment methods" description="Choose what customers can pick at checkout.">
                <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={data.cod_enabled} onChange={(event) => setData('cod_enabled', event.target.checked)} />
                    Offer cash on delivery
                </label>
                <InputError message={error('cod_enabled')} />

                <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={data.razorpay.enabled} onChange={(event) => setRazorpay({ enabled: event.target.checked })} />
                    Offer online payment with Razorpay
                </label>

                <FormField label="Razorpay mode" error={error('razorpay.mode')}>
                    <ToggleGroup
                        type="single"
                        variant="outline"
                        className="justify-start"
                        value={data.razorpay.mode}
                        onValueChange={(value) => value && setRazorpay({ mode: value as RazorpayMode })}
                    >
                        {(Object.keys(modeLabels) as RazorpayMode[]).map((mode) => (
                            <ToggleGroupItem
                                key={mode}
                                value={mode}
                                aria-label={`${modeLabels[mode]} mode`}
                                className="data-[state=on]:bg-primary data-[state=on]:text-primary-foreground px-4"
                            >
                                {modeLabels[mode]}
                            </ToggleGroupItem>
                        ))}
                    </ToggleGroup>
                </FormField>

                {data.razorpay.mode === 'live' ? (
                    <p className="rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200">
                        Live mode charges real money. Place a small test order after switching.
                    </p>
                ) : (
                    <p className="text-muted-foreground text-sm">Test mode uses Razorpay test cards and UPI IDs. No money moves.</p>
                )}

                <FormField label="Webhook URL" htmlFor="razorpay_webhook_url" hint="Add this in Razorpay Dashboard → Webhooks for each mode, with the payment.captured and order.paid events.">
                    <Input id="razorpay_webhook_url" readOnly value={razorpay.webhook_url} onFocus={(event) => event.target.select()} />
                </FormField>
            </FormCard>

            {credentialsCard('test')}
            {credentialsCard('live')}

            <div className="lg:col-span-2">
                <Button type="submit" disabled={processing}>
                    Save payment settings
                </Button>
            </div>
        </form>
    );
}
