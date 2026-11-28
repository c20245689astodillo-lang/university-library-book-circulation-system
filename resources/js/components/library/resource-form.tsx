import { Combobox, ComboboxContent, ComboboxEmpty, ComboboxInput, ComboboxItem, ComboboxList } from '@/components/ui/combobox';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

export type FormValue = string | number | null;

export type SelectOption = {
    value: string | number;
    label: string;
    disabled?: boolean;
};

export type FormField = {
    name: string;
    label: string;
    type?: 'text' | 'email' | 'date' | 'number' | 'password' | 'select';
    required?: boolean;
    disabled?: boolean;
    readOnly?: boolean;
    pattern?: string;
    title?: string;
    description?: string;
    autoComplete?: string;
    maxLength?: number;
    min?: string | number;
    minFrom?: string;
    max?: string | number;
    maxDateOffset?: { source: string; days: number; preserveValue?: { value: string; whenSourceEquals: string } };
    decimalPlaces?: number;
    dateOffset?: { target: string; days: number };
    todayAction?: { label: string; value: string; disabledUntilField?: string };
    emptyMessage?: string;
    options?: SelectOption[];
};

function addDays(date: string, days: number): string | undefined {
    if (!date) return undefined;

    const result = new Date(`${date}T00:00:00Z`);

    if (Number.isNaN(result.getTime())) return undefined;

    result.setUTCDate(result.getUTCDate() + days);

    return result.toISOString().slice(0, 10);
}

type ResourceFormProps = {
    title: string;
    path: string;
    action: string;
    method: 'post' | 'put';
    values: Record<string, FormValue>;
    fields: FormField[];
};

export default function ResourceForm({ title, path, action, method, values, fields }: ResourceFormProps) {
    const form = useForm<Record<string, FormValue>>(values);
    const missingRequiredOptions = fields.some((field) => field.type === 'select' && field.required && !field.options?.length);

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const submittedData: Record<string, FormValue> = {};
        new FormData(event.currentTarget).forEach((value, name) => {
            submittedData[name] = typeof value === 'string' ? value : value.name;
        });
        form.transform(() => submittedData);

        if (method === 'put') {
            form.put(action);
        } else {
            form.post(action);
        }
    }

    return (
        <AppLayout
            breadcrumbs={[
                { title: path.split('/').at(-1) ?? title, href: path },
                { title, href: action },
            ]}
        >
            <Head title={title} />

            <main className="mx-auto flex min-w-0 w-full max-w-2xl flex-col gap-6 p-4 sm:p-6">
                <h1 className="text-xl font-semibold sm:text-2xl">{title}</h1>

                <form onSubmit={submit} className="flex min-w-0 flex-col gap-5 rounded-lg border p-4 sm:p-5">
                    {fields.map((field) => {
                        const rawValue = form.data[field.name] ?? '';
                        const value = field.type === 'date' ? String(rawValue).slice(0, 10) : rawValue;
                        const error = form.errors[field.name];
                        const hasOptions = Boolean(field.options?.length);
                        const selectedOption = field.options?.find((option) => String(option.value) === String(value)) ?? null;
                        const todayAction = field.todayAction;
                        const todayActionDisabled =
                            todayAction?.disabledUntilField !== undefined &&
                            String(form.data[todayAction.disabledUntilField] ?? '') > todayAction.value;
                        const calculatedMaximum = field.maxDateOffset
                            ? addDays(String(form.data[field.maxDateOffset.source] ?? ''), field.maxDateOffset.days)
                            : undefined;
                        const preservedMaximum =
                            field.maxDateOffset?.preserveValue &&
                            String(form.data[field.maxDateOffset.source] ?? '') === field.maxDateOffset.preserveValue.whenSourceEquals
                                ? field.maxDateOffset.preserveValue.value
                                : undefined;
                        const maximum = field.maxDateOffset
                            ? [calculatedMaximum, preservedMaximum].filter((date): date is string => Boolean(date)).sort().at(-1)
                            : field.max;
                        const minimum = field.minFrom
                            ? [String(form.data[field.minFrom] ?? ''), field.min === undefined ? '' : String(field.min)].filter(Boolean).sort().at(-1)
                            : (field.min ?? (field.type === 'number' ? 0 : undefined));

                        return (
                            <div key={field.name} className="flex flex-col gap-1.5">
                                <label htmlFor={field.name} className="text-sm font-medium">
                                    {field.label}
                                </label>
                                {field.type === 'select' ? (
                                    <Combobox<SelectOption>
                                        name={field.name}
                                        items={field.options ?? []}
                                        value={selectedOption}
                                        required={field.required}
                                        disabled={!hasOptions || field.disabled}
                                        itemToStringLabel={(option) => option.label}
                                        itemToStringValue={(option) => String(option.value)}
                                        onValueChange={(option) => {
                                            form.setData(field.name, option?.value ?? '');
                                            form.clearErrors(field.name);
                                        }}
                                    >
                                        <ComboboxInput
                                            id={field.name}
                                            aria-label={field.label}
                                            className="w-full min-w-0"
                                            readOnly={field.readOnly}
                                            placeholder={
                                                hasOptions ? `Choose ${field.label.toLowerCase()}` : `No ${field.label.toLowerCase()} available`
                                            }
                                        />
                                        <ComboboxContent>
                                            <ComboboxEmpty>No matching {field.label.toLowerCase()} found.</ComboboxEmpty>
                                            <ComboboxList>
                                                {(option: SelectOption) => (
                                                        <ComboboxItem key={option.value} value={option} disabled={option.disabled}>
                                                        {option.label}
                                                    </ComboboxItem>
                                                )}
                                            </ComboboxList>
                                        </ComboboxContent>
                                    </Combobox>
                                ) : (
                                    <input
                                        id={field.name}
                                        name={field.name}
                                        type={field.type ?? 'text'}
                                        value={String(value)}
                                        required={field.required}
                                        disabled={field.disabled}
                                        pattern={field.pattern}
                                        title={field.title}
                                        autoComplete={field.autoComplete}
                                        maxLength={field.maxLength}
                                        min={minimum}
                                        max={maximum}
                                        step={
                                            field.decimalPlaces !== undefined
                                                ? String(1 / 10 ** field.decimalPlaces)
                                                : field.name === 'fine_per_day' || field.name === 'calculated_fine'
                                                  ? '0.01'
                                                  : undefined
                                        }
                                        onChange={(event) => {
                                            const next = event.target.value;

                                            if (field.type === 'date') {
                                                form.setData(field.name, next);
                                                form.clearErrors(field.name);

                                                if (field.dateOffset) {
                                                    if (!next) {
                                                        form.setData(field.dateOffset.target, '');
                                                    } else {
                                                        const offsetDate = new Date(`${next}T00:00:00Z`);
                                                        offsetDate.setUTCDate(offsetDate.getUTCDate() + field.dateOffset.days);
                                                        form.setData(field.dateOffset.target, offsetDate.toISOString().slice(0, 10));
                                                    }
                                                }

                                                return;
                                            }

                                            if (field.decimalPlaces !== undefined) {
                                                const decimalPattern = new RegExp(`^\\d*(?:\\.\\d{0,${field.decimalPlaces}})?$`);

                                                if (
                                                    !decimalPattern.test(next) ||
                                                    (field.max !== undefined && next !== '' && next !== '.' && Number(next) > Number(field.max))
                                                ) {
                                                    return;
                                                }

                                                form.setData(field.name, next);
                                                form.clearErrors(field.name);
                                                return;
                                            }

                                            form.setData(field.name, field.type === 'number' && next !== '' ? Number(next) : next);
                                            form.clearErrors(field.name);
                                        }}
                                        className="bg-background w-full min-w-0 rounded-md border px-3 py-2 text-sm"
                                    />
                                )}
                                {field.disabled && <input type="hidden" name={field.name} value={String(value)} />}
                                {field.description && <p className="text-muted-foreground text-sm">{field.description}</p>}
                                {field.type === 'select' && !hasOptions && (
                                    <p className="text-muted-foreground text-sm">
                                        {field.emptyMessage ?? `No ${field.label.toLowerCase()} options are available.`}
                                    </p>
                                )}
                                {todayAction && !value && (
                                    <button
                                        type="button"
                                        onClick={() => {
                                            form.setData(field.name, todayAction.value);
                                            form.clearErrors(field.name);
                                        }}
                                        disabled={todayActionDisabled}
                                        className="text-primary w-fit text-sm underline underline-offset-4 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {todayAction.label}
                                    </button>
                                )}
                                {error && <p className="text-destructive text-sm">{error}</p>}
                            </div>
                        );
                    })}

                    <div className="flex items-center gap-3">
                        <button
                            type="submit"
                            disabled={form.processing || missingRequiredOptions}
                            className="bg-primary text-primary-foreground rounded-md px-4 py-2 text-sm font-medium disabled:opacity-50"
                        >
                            {form.processing ? 'Saving...' : 'Save'}
                        </button>
                        <Link href={path} className="text-sm underline underline-offset-4">
                            Cancel
                        </Link>
                    </div>
                </form>
            </main>
        </AppLayout>
    );
}
