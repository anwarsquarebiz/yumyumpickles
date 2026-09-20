import { FormCard, FormField } from '@/components/admin/form-field';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { type AdminCoupon, type SelectOption } from '@/types';
import { type FormDataConvertible } from '@inertiajs/core';
import { useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

interface CouponFormData {
    code: string;
    description: string;
    type: string;
    value: string;
    minimum_subtotal: string;
    usage_limit: string;
    usage_limit_per_customer: string;
    starts_at: string;
    expires_at: string;
    is_active: boolean;
    [key: string]: FormDataConvertible;
}

interface CouponFormProps {
    coupon?: AdminCoupon;
    types: SelectOption[];
}

const toLocalInput = (value: string | null): string => {
    if (!value) {
        return '';
    }

    return value.replace(' ', 'T').slice(0, 16);
};

export function CouponForm({ coupon, types }: CouponFormProps) {
    const isEditing = Boolean(coupon);

    const form = useForm<CouponFormData>({
        code: coupon?.code ?? '',
        description: coupon?.description ?? '',
        type: coupon?.type ?? 'fixed_amount',
        value: coupon?.value_input ?? '',
        minimum_subtotal: coupon?.minimum_subtotal_input ?? '',
        usage_limit: coupon?.usage_limit?.toString() ?? '',
        usage_limit_per_customer: coupon?.usage_limit_per_customer?.toString() ?? '',
        starts_at: toLocalInput(coupon?.starts_at ?? null),
        expires_at: toLocalInput(coupon?.expires_at ?? null),
        is_active: coupon?.is_active ?? true,
    });

    const { data, setData, errors, processing } = form;
    const isPercentage = data.type === 'percentage';

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        const payload = {
            ...data,
            usage_limit: data.usage_limit === '' ? null : Number(data.usage_limit),
            usage_limit_per_customer: data.usage_limit_per_customer === '' ? null : Number(data.usage_limit_per_customer),
            minimum_subtotal: data.minimum_subtotal === '' ? null : data.minimum_subtotal,
            starts_at: data.starts_at === '' ? null : data.starts_at,
            expires_at: data.expires_at === '' ? null : data.expires_at,
        };

        form.transform(() => payload);

        if (isEditing && coupon) {
            form.put(`/admin/coupons/${coupon.id}`, { preserveScroll: true });
        } else {
            form.post('/admin/coupons');
        }
    };

    return (
        <form onSubmit={submit} className="grid gap-6 lg:grid-cols-3">
            <div className="flex flex-col gap-6 lg:col-span-2">
                <FormCard title="Discount code">
                    <FormField label="Code" htmlFor="code" error={errors.code} required hint="Letters, numbers, dashes and underscores.">
                        <Input
                            id="code"
                            value={data.code}
                            onChange={(event) => setData('code', event.target.value.toUpperCase())}
                            autoFocus
                            autoComplete="off"
                        />
                    </FormField>

                    <FormField label="Description" htmlFor="description" error={errors.description}>
                        <Input id="description" value={data.description} onChange={(event) => setData('description', event.target.value)} />
                    </FormField>
                </FormCard>

                <FormCard title="Value">
                    <FormField label="Type" htmlFor="type" error={errors.type}>
                        <select
                            id="type"
                            value={data.type}
                            onChange={(event) => setData('type', event.target.value)}
                            className="h-9 w-full rounded-md border border-neutral-300 bg-transparent px-3 text-sm dark:border-neutral-700"
                        >
                            {types.map((type) => (
                                <option key={type.value} value={type.value}>
                                    {type.label}
                                </option>
                            ))}
                        </select>
                    </FormField>

                    <FormField
                        label={isPercentage ? 'Percent off' : 'Amount off'}
                        htmlFor="value"
                        error={errors.value}
                        required
                        hint={isPercentage ? 'For example 10 for 10% off.' : 'Entered in store currency, for example 5.00.'}
                    >
                        <Input
                            id="value"
                            type="number"
                            min="0"
                            step={isPercentage ? '1' : '0.01'}
                            value={data.value}
                            onChange={(event) => setData('value', event.target.value)}
                        />
                    </FormField>

                    <FormField label="Minimum subtotal" htmlFor="minimum_subtotal" error={errors.minimum_subtotal} hint="Leave blank for no minimum.">
                        <Input
                            id="minimum_subtotal"
                            type="number"
                            min="0"
                            step="0.01"
                            value={data.minimum_subtotal}
                            onChange={(event) => setData('minimum_subtotal', event.target.value)}
                        />
                    </FormField>
                </FormCard>
            </div>

            <div className="flex flex-col gap-6">
                <FormCard title="Availability">
                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox checked={data.is_active} onCheckedChange={(checked) => setData('is_active', checked === true)} />
                        Active
                    </label>

                    <FormField label="Starts at" htmlFor="starts_at" error={errors.starts_at}>
                        <Input
                            id="starts_at"
                            type="datetime-local"
                            value={data.starts_at}
                            onChange={(event) => setData('starts_at', event.target.value)}
                        />
                    </FormField>

                    <FormField label="Expires at" htmlFor="expires_at" error={errors.expires_at}>
                        <Input
                            id="expires_at"
                            type="datetime-local"
                            value={data.expires_at}
                            onChange={(event) => setData('expires_at', event.target.value)}
                        />
                    </FormField>
                </FormCard>

                <FormCard title="Usage limits">
                    <FormField label="Total uses" htmlFor="usage_limit" error={errors.usage_limit} hint="Leave blank for unlimited.">
                        <Input
                            id="usage_limit"
                            type="number"
                            min="1"
                            value={data.usage_limit}
                            onChange={(event) => setData('usage_limit', event.target.value)}
                        />
                    </FormField>

                    <FormField
                        label="Uses per customer"
                        htmlFor="usage_limit_per_customer"
                        error={errors.usage_limit_per_customer}
                        hint="Leave blank for unlimited."
                    >
                        <Input
                            id="usage_limit_per_customer"
                            type="number"
                            min="1"
                            value={data.usage_limit_per_customer}
                            onChange={(event) => setData('usage_limit_per_customer', event.target.value)}
                        />
                    </FormField>
                </FormCard>

                <Button type="submit" disabled={processing} className="w-full">
                    {isEditing ? 'Save discount' : 'Create discount'}
                </Button>
            </div>
        </form>
    );
}
