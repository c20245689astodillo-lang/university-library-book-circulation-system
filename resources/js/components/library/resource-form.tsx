import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

export type FormValue = string | number | null;

export type SelectOption = {
    value: string | number;
    label: string;
};

export type FormField = {
    name: string;
    label: string;
    type?: 'text' | 'email' | 'date' | 'number' | 'select';
    required?: boolean;
    options?: SelectOption[];
};

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
        <AppLayout breadcrumbs={[{ title: path.split('/').at(-1) ?? title, href: path }, { title, href: action }]}>
            <Head title={title} />

            <main className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4">
                <h1 className="text-2xl font-semibold">{title}</h1>

                <form onSubmit={submit} className="flex flex-col gap-5 rounded-lg border p-5">
                    {fields.map((field) => {
                        const rawValue = form.data[field.name] ?? '';
                        const value = field.type === 'date' ? String(rawValue).slice(0, 10) : rawValue;
                        const error = form.errors[field.name];

                        return (
                            <div key={field.name} className="flex flex-col gap-1.5">
                                <label htmlFor={field.name} className="text-sm font-medium">{field.label}</label>
                                {field.type === 'select' ? (
                                    <select
                                        id={field.name}
                                        name={field.name}
                                        value={String(value)}
                                        required={field.required}
                                        onChange={(event) => {
                                            const next = event.target.value;
                                            const numericOption = field.options?.some((option) => typeof option.value === 'number' && String(option.value) === next);
                                            form.setData(field.name, next === '' ? '' : numericOption ? Number(next) : next);
                                        }}
                                        className="rounded-md border bg-background px-3 py-2 text-sm"
                                    >
                                        <option value="">Choose {field.label.toLowerCase()}</option>
                                        {field.options?.map((option) => (
                                            <option key={option.value} value={option.value}>{option.label}</option>
                                        ))}
                                    </select>
                                ) : (
                                    <input
                                        id={field.name}
                                        name={field.name}
                                        type={field.type ?? 'text'}
                                        value={String(value)}
                                        required={field.required}
                                        min={field.type === 'number' ? 0 : undefined}
                                        step={field.name === 'fine_per_day' || field.name === 'calculated_fine' ? '0.01' : undefined}
                                        onChange={(event) => {
                                            const next = event.target.value;
                                            form.setData(field.name, field.type === 'number' && next !== '' ? Number(next) : next);
                                        }}
                                        className="rounded-md border bg-background px-3 py-2 text-sm"
                                    />
                                )}
                                {error && <p className="text-destructive text-sm">{error}</p>}
                            </div>
                        );
                    })}

                    <div className="flex items-center gap-3">
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                        >
                            {form.processing ? 'Saving...' : 'Save'}
                        </button>
                        <Link href={path} className="text-sm underline underline-offset-4">Cancel</Link>
                    </div>
                </form>
            </main>
        </AppLayout>
    );
}
