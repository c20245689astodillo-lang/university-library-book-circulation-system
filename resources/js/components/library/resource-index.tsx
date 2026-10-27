import AppLayout from '@/layouts/app-layout';
import { Head, Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { SharedData } from '@/types';

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
};

export type ResourceColumn<T> = {
    label: string;
    render: (row: T) => ReactNode;
};

type ResourceIndexProps<T extends { id: number }> = {
    title: string;
    path: string;
    rows: Paginated<T>;
    columns: ResourceColumn<T>[];
};

export default function ResourceIndex<T extends { id: number }>({ title, path, rows, columns }: ResourceIndexProps<T>) {
    const singularTitle = title.endsWith('s') ? title.slice(0, -1) : title;
    const { flash, errors = {} } = usePage<SharedData & { errors?: Record<string, string> }>().props;

    return (
        <AppLayout breadcrumbs={[{ title, href: path }]}>
            <Head title={title} />

            <main className="flex flex-col gap-6 p-4">
                <header className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">{title}</h1>
                        <p className="text-muted-foreground mt-1 text-sm">Manage {title.toLowerCase()} in the library.</p>
                    </div>
                    <Link href={`${path}/create`} className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground">
                        Add {singularTitle}
                    </Link>
                </header>

                {flash?.success && <p role="status" className="rounded-md border border-green-600/30 bg-green-600/10 px-4 py-3 text-sm">{flash.success}</p>}
                {Object.values(errors).map((error, index) => (
                    <p key={index} role="alert" className="rounded-md border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                        {error}
                    </p>
                ))}

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-left text-sm">
                        <thead className="bg-muted/50 text-muted-foreground">
                            <tr>
                                {columns.map((column) => (
                                    <th key={column.label} className="px-4 py-3 font-medium">{column.label}</th>
                                ))}
                                <th className="px-4 py-3 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {rows.data.length ? rows.data.map((row) => (
                                <tr key={row.id}>
                                    {columns.map((column) => (
                                        <td key={column.label} className="px-4 py-3">{column.render(row)}</td>
                                    ))}
                                    <td className="whitespace-nowrap px-4 py-3">
                                        <Link className="underline underline-offset-4" href={`${path}/${row.id}/edit`}>Edit</Link>
                                    </td>
                                </tr>
                            )) : (
                                <tr>
                                    <td colSpan={columns.length + 1} className="text-muted-foreground px-4 py-8 text-center">
                                        No {title.toLowerCase()} found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {rows.links.length > 3 && (
                    <nav className="flex flex-wrap gap-2" aria-label={`${title} pagination`}>
                        {rows.links.map((link, index) => {
                            const label = link.label.replace(/&laquo;|&raquo;/g, '').trim();
                            const classes = `rounded-md border px-3 py-1.5 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`;

                            return link.url ? (
                                <Link key={`${label}-${index}`} href={link.url} className={classes}>{label}</Link>
                            ) : (
                                <span key={`${label}-${index}`} className={`${classes} text-muted-foreground`}>{label}</span>
                            );
                        })}
                    </nav>
                )}
            </main>
        </AppLayout>
    );
}
