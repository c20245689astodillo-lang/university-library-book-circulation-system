import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Combobox,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxLabel,
    ComboboxList,
} from '@/components/ui/combobox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { BookOpen, CalendarDays, UserRound } from 'lucide-react';
import type { FormEvent } from 'react';

export type FormValue = string | number | null;

export type SelectOption = {
    value: string | number;
    label: string;
    disabled?: boolean;
};

export type TextSuggestion = {
    value: string;
};

export type FormField = {
    name: string;
    label: string;
    type?: 'text' | 'email' | 'date' | 'number' | 'password' | 'select' | 'autocomplete';
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
    suggestions?: TextSuggestion[];
    suggestionGroupLabel?: string;
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
    uniquePairConstraint?: {
        firstField: string;
        secondField: string;
        existingPairs: { first: string; second: string }[];
        message: string;
    };
};

export default function ResourceForm({ title, path, action, method, values, fields, uniquePairConstraint }: ResourceFormProps) {
    const form = useForm<Record<string, FormValue>>(values);
    const missingRequiredOptions = fields.some((field) => field.type === 'select' && field.required && !field.options?.length);
    const firstPairValue = String(form.data[uniquePairConstraint?.firstField ?? ''] ?? '')
        .trim()
        .toLocaleLowerCase();
    const secondPairValue = String(form.data[uniquePairConstraint?.secondField ?? ''] ?? '')
        .trim()
        .toLocaleLowerCase();
    const duplicatePairExists = Boolean(
        firstPairValue &&
            secondPairValue &&
            uniquePairConstraint?.existingPairs.some(
                (pair) => pair.first.trim().toLocaleLowerCase() === firstPairValue && pair.second.trim().toLocaleLowerCase() === secondPairValue,
            ),
    );

    function clearPairErrors(changedField: string) {
        if (!uniquePairConstraint || ![uniquePairConstraint.firstField, uniquePairConstraint.secondField].includes(changedField)) return;

        form.clearErrors(uniquePairConstraint.firstField);
        form.clearErrors(uniquePairConstraint.secondField);
    }

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        if (duplicatePairExists) return;

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

            <main className="mx-auto flex w-full max-w-3xl min-w-0 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <div>
                    <p className="text-muted-foreground text-sm font-medium">Library records</p>
                    <h1 className="mt-1 text-3xl font-semibold tracking-tight">{title}</h1>
                </div>

                <form onSubmit={submit} className="min-w-0">
                    <Card>
                        <CardHeader className="border-b">
                            <CardTitle className="text-lg">Record details</CardTitle>
                            <CardDescription>Fill in the information below. Fields marked with * are required.</CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-x-6 gap-y-5 p-5 pt-5 sm:grid-cols-2 sm:p-6 sm:pt-6">
                            {fields.map((field) => {
                                const rawValue = form.data[field.name] ?? '';
                                const value = field.type === 'date' ? String(rawValue).slice(0, 10) : rawValue;
                                const error = form.errors[field.name];
                                const hasOptions = Boolean(field.options?.length);
                                const selectedOption = field.options?.find((option) => String(option.value) === String(value)) ?? null;
                                const SuggestionIcon = field.name === 'book_title' ? BookOpen : field.name === 'author_name' ? UserRound : null;
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
                                    ? [calculatedMaximum, preservedMaximum]
                                          .filter((date): date is string => Boolean(date))
                                          .sort()
                                          .at(-1)
                                    : field.max;
                                const minimum = field.minFrom
                                    ? [String(form.data[field.minFrom] ?? ''), field.min === undefined ? '' : String(field.min)]
                                          .filter(Boolean)
                                          .sort()
                                          .at(-1)
                                    : (field.min ?? (field.type === 'number' ? 0 : undefined));

                                const errorId = `${field.name}-error`;
                                const descriptionId = `${field.name}-description`;
                                const describedBy =
                                    [
                                        field.description ? descriptionId : undefined,
                                        error || (duplicatePairExists && field.name === uniquePairConstraint?.firstField) ? errorId : undefined,
                                    ]
                                        .filter(Boolean)
                                        .join(' ') || undefined;

                                return (
                                    <div key={field.name} className="flex min-w-0 flex-col gap-2">
                                        <Label htmlFor={field.name}>
                                            {field.label}
                                            {field.required && (
                                                <span className="text-destructive ml-1" aria-hidden="true">
                                                    *
                                                </span>
                                            )}
                                        </Label>
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
                                                    aria-invalid={Boolean(error)}
                                                    aria-describedby={describedBy}
                                                    className="w-full min-w-0"
                                                    readOnly={field.readOnly}
                                                    placeholder={
                                                        hasOptions
                                                            ? `Choose ${field.label.toLowerCase()}`
                                                            : `No ${field.label.toLowerCase()} available`
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
                                        ) : field.type === 'autocomplete' ? (
                                            <>
                                                <Combobox<TextSuggestion>
                                                    items={field.suggestions ?? []}
                                                    value={
                                                        field.suggestions?.find(
                                                            (suggestion) =>
                                                                suggestion.value.toLocaleLowerCase() === String(value).trim().toLocaleLowerCase(),
                                                        ) ?? null
                                                    }
                                                    inputValue={String(value)}
                                                    itemToStringLabel={(suggestion) => suggestion.value}
                                                    itemToStringValue={(suggestion) => suggestion.value}
                                                    onInputValueChange={(next) => {
                                                        form.setData(field.name, next);
                                                        form.clearErrors(field.name);
                                                        clearPairErrors(field.name);
                                                    }}
                                                    onValueChange={(suggestion) => {
                                                        if (!suggestion) return;

                                                        form.setData(field.name, suggestion.value);
                                                        form.clearErrors(field.name);
                                                        clearPairErrors(field.name);
                                                    }}
                                                >
                                                    <ComboboxInput
                                                        id={field.name}
                                                        aria-label={field.label}
                                                        aria-invalid={Boolean(error)}
                                                        aria-describedby={describedBy}
                                                        className="w-full min-w-0"
                                                        required={field.required}
                                                        disabled={field.disabled}
                                                        pattern={field.pattern}
                                                        title={field.title}
                                                        maxLength={field.maxLength}
                                                        autoComplete={field.autoComplete ?? 'off'}
                                                        placeholder={`Choose or type ${field.label.toLowerCase()}`}
                                                    />
                                                    <ComboboxContent collisionAvoidance={{ side: 'shift', align: 'shift', fallbackAxisSide: 'none' }}>
                                                        <ComboboxEmpty>
                                                            No matching {field.label.toLowerCase()} found. You can still enter a new value.
                                                        </ComboboxEmpty>
                                                        <ComboboxGroup>
                                                            <ComboboxLabel>
                                                                {field.suggestionGroupLabel ?? `Existing ${field.label.toLowerCase()}s`}
                                                            </ComboboxLabel>
                                                            <ComboboxList>
                                                                {(suggestion: TextSuggestion) => (
                                                                    <ComboboxItem key={suggestion.value} value={suggestion}>
                                                                        {SuggestionIcon && (
                                                                            <SuggestionIcon
                                                                                className="text-muted-foreground size-4"
                                                                                aria-hidden="true"
                                                                            />
                                                                        )}
                                                                        <span className="truncate">{suggestion.value}</span>
                                                                    </ComboboxItem>
                                                                )}
                                                            </ComboboxList>
                                                        </ComboboxGroup>
                                                    </ComboboxContent>
                                                </Combobox>
                                                <input type="hidden" name={field.name} value={String(value)} />
                                            </>
                                        ) : (
                                            <Input
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
                                                aria-invalid={Boolean(error)}
                                                aria-describedby={describedBy}
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
                                                            (field.max !== undefined &&
                                                                next !== '' &&
                                                                next !== '.' &&
                                                                Number(next) > Number(field.max))
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
                                            />
                                        )}
                                        {field.disabled && <input type="hidden" name={field.name} value={String(value)} />}
                                        {field.description && (
                                            <p id={descriptionId} className="text-muted-foreground text-sm">
                                                {field.description}
                                            </p>
                                        )}
                                        {field.type === 'select' && !hasOptions && (
                                            <p className="text-muted-foreground text-sm">
                                                {field.emptyMessage ?? `No ${field.label.toLowerCase()} options are available.`}
                                            </p>
                                        )}
                                        {todayAction && !value && (
                                            <Button
                                                type="button"
                                                onClick={() => {
                                                    form.setData(field.name, todayAction.value);
                                                    form.clearErrors(field.name);
                                                }}
                                                disabled={todayActionDisabled}
                                                variant="link"
                                                size="sm"
                                                className="h-auto w-fit px-0 py-0"
                                            >
                                                <CalendarDays aria-hidden="true" /> {todayAction.label}
                                            </Button>
                                        )}
                                        {error ? (
                                            <p id={errorId} role="alert" className="text-destructive text-sm">
                                                {error}
                                            </p>
                                        ) : duplicatePairExists && field.name === uniquePairConstraint?.firstField ? (
                                            <p id={errorId} role="alert" className="text-destructive text-sm">
                                                {uniquePairConstraint.message}
                                            </p>
                                        ) : null}
                                    </div>
                                );
                            })}
                        </CardContent>
                        <CardFooter className="flex-col-reverse items-stretch gap-2 border-t p-5 pt-5 sm:flex-row sm:justify-end sm:p-6 sm:pt-6">
                            <Button variant="outline" asChild>
                                <Link href={path}>Cancel</Link>
                            </Button>
                            <Button type="submit" disabled={form.processing || missingRequiredOptions || duplicatePairExists}>
                                {form.processing ? 'Saving...' : 'Save'}
                            </Button>
                        </CardFooter>
                    </Card>
                </form>
            </main>
        </AppLayout>
    );
}
