import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

export type DetailRow = {
    label: string;
    value: ReactNode;
};

type ResourceShowProps = {
    title: string;
    path: string;
    editPath: string;
    rows: DetailRow[];
};

export default function ResourceShow({ title, path, editPath, rows }: ResourceShowProps) {
    return (
        <AppLayout breadcrumbs={[{ title: path.split('/').at(-1) ?? title, href: path }, { title, href: editPath }]}>
            <Head title={title} />
            <main className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4">
                <header className="flex items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">{title}</h1>
                    <Link href={editPath} className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground">Edit</Link>
                </header>
                <dl className="divide-y rounded-lg border px-5">
                    {rows.map((row) => (
                        <div key={row.label} className="grid gap-1 py-4 sm:grid-cols-[10rem_1fr]">
                            <dt className="text-muted-foreground text-sm">{row.label}</dt>
                            <dd className="text-sm font-medium">{row.value || '-'}</dd>
                        </div>
                    ))}
                </dl>
                <Link href={path} className="text-sm underline underline-offset-4">Back to list</Link>
            </main>
        </AppLayout>
    );
}
