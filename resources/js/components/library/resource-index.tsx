import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
    canCreate?: boolean;
    canEdit?: (row: T) => boolean;
    rowAction?: (row: T) => ReactNode;
    headerAction?: ReactNode;
    search?: string;
};

export default function ResourceIndex<T extends { id: number }>({
    title,
    path,
    rows,
    columns,
    canCreate = true,
    canEdit,
    rowAction,
    headerAction,
    search,
}: ResourceIndexProps<T>) {
    const singularTitle = title.endsWith('s') ? title.slice(0, -1) : title;
    const { flash, errors = {} } = usePage<SharedData & { errors?: Record<string, string> }>().props;

    return (
        <AppLayout breadcrumbs={[{ title, href: path }]}>
            <Head title={title} />

            <main className="flex min-w-0 flex-col gap-6 p-4 sm:p-6">
                <header className="flex flex-wrap items-center justify-between gap-3">
                    <div className="min-w-0">
                        <h1 className="text-2xl font-semibold">{title}</h1>
                        <p className="text-muted-foreground mt-1 text-sm">Manage {title.toLowerCase()} in the library.</p>
                    </div>
                    <div className="flex shrink-0 items-center gap-2">
                        {headerAction}
                        {canCreate && (
                            <Button asChild>
                                <Link href={`${path}/create`}>Add {singularTitle}</Link>
                            </Button>
                        )}
                    </div>
                </header>

                {search !== undefined && (
                    <form method="get" action={path} className="flex w-full max-w-3xl flex-wrap items-center gap-2">
                        <Input
                            type="search"
                            name="search"
                            defaultValue={search}
                            placeholder={`Search ${title.toLowerCase()}...`}
                            aria-label={`Search ${title.toLowerCase()}`}
                            className="min-w-0 flex-1 text-sm"
                        />
                        <Button type="submit">
                            Search
                        </Button>
                        {search && <Button variant="outline" asChild><Link href={path}>Clear</Link></Button>}
                    </form>
                )}

                {flash?.success && <p role="status" className="rounded-md border border-green-600/30 bg-green-600/10 px-4 py-3 text-sm">{flash.success}</p>}
                {Object.values(errors).map((error, index) => (
                    <p key={index} role="alert" className="rounded-md border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                        {error}
                    </p>
                ))}

                <div className="grid gap-3 xl:hidden">
                    {rows.data.length ? rows.data.map((row) => {
                        const showEdit = !canEdit || canEdit(row);
                        const action = rowAction?.(row);

                        return (
                            <article key={row.id} className="min-w-0 rounded-lg border p-4">
                                <dl className="space-y-3">
                                    {columns.map((column) => (
                                        <div key={column.label} className="grid grid-cols-[minmax(5.5rem,0.38fr)_minmax(0,1fr)] gap-3 text-sm">
                                            <dt className="text-muted-foreground">{column.label}</dt>
                                            <dd className="min-w-0 break-words">{column.render(row)}</dd>
                                        </div>
                                    ))}
                                </dl>
                                {(showEdit || action) && (
                                    <div className="mt-4 flex items-center gap-4 border-t pt-3">
                                        {showEdit && (
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={`${path}/${row.id}/edit`}>Edit</Link>
                                            </Button>
                                        )}
                                        {action}
                                    </div>
                                )}
                            </article>
                        );
                    }) : (
                        <p className="text-muted-foreground rounded-lg border px-4 py-8 text-center">
                            No {title.toLowerCase()} found.
                        </p>
                    )}
                </div>

                <div className="hidden overflow-x-auto rounded-lg border xl:block">
                    <table className="w-full min-w-[52rem] text-left text-sm">
                        <thead className="bg-muted/50 text-muted-foreground">
                            <tr>
                                {columns.map((column) => (
                                    <th key={column.label} className="px-4 py-3 font-medium">{column.label}</th>
                                ))}
                                <th className="px-4 py-3 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {rows.data.length ? rows.data.map((row) => {
                                const showEdit = !canEdit || canEdit(row);
                                const action = rowAction?.(row);

                                return (
                                    <tr key={row.id}>
                                        {columns.map((column) => (
                                            <td key={column.label} className="max-w-xs break-words px-4 py-3">{column.render(row)}</td>
                                        ))}
                                        <td className="whitespace-nowrap px-4 py-3">
                                            <div className="flex items-center gap-4">
                                                {showEdit && (
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={`${path}/${row.id}/edit`}>Edit</Link>
                                                    </Button>
                                                )}
                                                {action}
                                            </div>
                                        </td>
                                    </tr>
                                );
                            }) : (
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
                            return link.url ? (
                                <Button key={`${label}-${index}`} size="sm" variant={link.active ? 'default' : 'outline'} asChild>
                                    <Link href={link.url}>{label}</Link>
                                </Button>
                            ) : (
                                <Button key={`${label}-${index}`} size="sm" variant="outline" disabled>{label}</Button>
                            );
                        })}
                    </nav>
                )}
            </main>
        </AppLayout>
    );
}
