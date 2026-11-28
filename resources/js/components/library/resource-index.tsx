import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import type { SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Plus, Search } from 'lucide-react';
import type { ReactNode } from 'react';

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

function RecordActions({ editHref, children }: { editHref?: string; children?: ReactNode }) {
    if (!editHref && !children) return null;

    return (
        <div className="flex flex-nowrap items-center gap-1.5 whitespace-nowrap">
            {editHref && (
                <Button variant="outline" size="sm" asChild>
                    <Link href={editHref}>Edit</Link>
                </Button>
            )}
            {children}
        </div>
    );
}

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
    const hasRecords = rows.data.length > 0;

    return (
        <AppLayout breadcrumbs={[{ title, href: path }]}>
            <Head title={title} />

            <main className="mx-auto flex w-full max-w-7xl min-w-0 flex-col gap-5 p-4 sm:p-5 lg:p-6">
                <header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div className="min-w-0">
                        <p className="text-muted-foreground text-sm font-medium">Library records</p>
                        <h1 className="mt-1 text-2xl font-semibold tracking-tight">{title}</h1>
                        <p className="text-muted-foreground mt-1 text-sm">Browse and manage your library {title.toLowerCase()}.</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        {headerAction}
                        {canCreate && (
                            <Button asChild>
                                <Link href={`${path}/create`}>
                                    <Plus aria-hidden="true" /> Add {singularTitle}
                                </Link>
                            </Button>
                        )}
                    </div>
                </header>

                {search !== undefined && (
                    <Card>
                        <CardContent className="p-3 pt-3 sm:p-4 sm:pt-4">
                            <form method="get" action={path} className="flex flex-col gap-2 sm:flex-row">
                                <div className="relative min-w-0 flex-1">
                                    <Search
                                        className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2"
                                        aria-hidden="true"
                                    />
                                    <Input
                                        type="search"
                                        name="search"
                                        defaultValue={search}
                                        placeholder={`Search ${title.toLowerCase()}...`}
                                        aria-label={`Search ${title.toLowerCase()}`}
                                        className="h-9 pl-9"
                                    />
                                </div>
                                <div className="flex gap-2">
                                    <Button type="submit" size="sm" className="flex-1 sm:flex-none">
                                        Search
                                    </Button>
                                    {search && (
                                        <Button variant="outline" size="sm" asChild className="flex-1 sm:flex-none">
                                            <Link href={path}>Clear</Link>
                                        </Button>
                                    )}
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}

                {flash?.success && (
                    <Alert className="border-primary/20 bg-primary/5 text-foreground">
                        <AlertDescription>{flash.success}</AlertDescription>
                    </Alert>
                )}
                {Object.values(errors).map((error, index) => (
                    <Alert key={index} variant="destructive">
                        <AlertDescription>{error}</AlertDescription>
                    </Alert>
                ))}

                <section className="grid gap-3 xl:hidden" aria-label={`${title} records`}>
                    {hasRecords ? (
                        rows.data.map((row) => {
                            const showEdit = !canEdit || canEdit(row);
                            const action = rowAction?.(row);

                            return (
                                <Card key={row.id}>
                                    <CardContent className="p-3 pt-3 sm:p-4 sm:pt-4">
                                        <dl className="space-y-2">
                                            {columns.map((column, index) => (
                                                <div
                                                    key={column.label}
                                                    className={`grid grid-cols-[minmax(5.75rem,0.38fr)_minmax(0,1fr)] gap-3 text-sm ${index === 0 ? 'pb-2' : ''}`}
                                                >
                                                    <dt className="text-muted-foreground">{column.label}</dt>
                                                    <dd className="min-w-0 font-medium break-words">
                                                        {column.render(row) ?? <span className="text-muted-foreground">—</span>}
                                                    </dd>
                                                </div>
                                            ))}
                                        </dl>
                                        {(showEdit || action) && (
                                            <div className="mt-3 border-t pt-2">
                                                <RecordActions editHref={showEdit ? `${path}/${row.id}/edit` : undefined}>{action}</RecordActions>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            );
                        })
                    ) : (
                        <Card>
                            <CardContent className="flex flex-col items-center px-6 py-12 pt-12 text-center">
                                <Badge variant="secondary" className="mb-4 size-12 rounded-full p-0">
                                    <Search className="size-5" aria-hidden="true" />
                                </Badge>
                                <p className="font-medium">No {title.toLowerCase()} found</p>
                                <p className="text-muted-foreground mt-1 text-sm">
                                    {search
                                        ? 'Try adjusting your search or clear the filter.'
                                        : `Add your first ${singularTitle.toLowerCase()} to get started.`}
                                </p>
                            </CardContent>
                        </Card>
                    )}
                </section>

                <Card className="hidden overflow-hidden xl:block">
                    <CardHeader className="flex-row items-center justify-between border-b px-4 py-3">
                        <div className="space-y-1">
                            <CardTitle className="text-sm">All {title.toLowerCase()}</CardTitle>
                            <CardDescription className="text-xs">Review the details and available actions for each record.</CardDescription>
                        </div>
                        <Badge variant="secondary" className="text-[11px]">
                            {rows.data.length} on this page
                        </Badge>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table aria-label={`${title} records`}>
                            <TableHeader className="bg-muted/50">
                                <TableRow>
                                    {columns.map((column) => (
                                        <TableHead key={column.label} className="h-9 px-3 text-xs">
                                            {column.label}
                                        </TableHead>
                                    ))}
                                    <TableHead className="h-9 w-48 px-3 text-xs whitespace-nowrap">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {hasRecords ? (
                                    rows.data.map((row) => {
                                        const showEdit = !canEdit || canEdit(row);
                                        const action = rowAction?.(row);

                                        return (
                                            <TableRow key={row.id}>
                                                {columns.map((column, index) => (
                                                    <TableCell key={column.label} className={`px-3 py-2 text-sm ${index === 0 ? 'font-medium' : ''}`}>
                                                        {column.render(row) ?? <span className="text-muted-foreground">—</span>}
                                                    </TableCell>
                                                ))}
                                                <TableCell className="px-3 py-2 whitespace-nowrap">
                                                    <RecordActions editHref={showEdit ? `${path}/${row.id}/edit` : undefined}>{action}</RecordActions>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={columns.length + 1} className="h-32 text-center">
                                            <p className="font-medium">No {title.toLowerCase()} found</p>
                                            <p className="text-muted-foreground mt-1 text-sm">
                                                {search ? 'Try another search.' : 'Add a record to get started.'}
                                            </p>
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                    {rows.links.length > 3 && (
                        <CardFooter className="justify-between gap-3 border-t px-4 py-3 pt-3">
                            <p className="text-muted-foreground text-sm">Showing {rows.data.length} records on this page</p>
                            <nav className="flex flex-wrap justify-end gap-2" aria-label={`${title} pagination`}>
                                {rows.links.map((link, index) => {
                                    const label = link.label.replace(/&laquo;|&raquo;/g, '').trim();
                                    const isPrevious = index === 0;
                                    const isNext = index === rows.links.length - 1;
                                    const content = (
                                        <>
                                            {isPrevious && <ArrowLeft aria-hidden="true" />}
                                            {!isPrevious && !isNext && label}
                                            {isNext && <ArrowRight aria-hidden="true" />}
                                        </>
                                    );

                                    return link.url ? (
                                        <Button
                                            key={`${label}-${index}`}
                                            size={isPrevious || isNext ? 'icon' : 'sm'}
                                            variant={link.active ? 'default' : 'outline'}
                                            asChild
                                        >
                                            <Link href={link.url} aria-label={isPrevious ? 'Previous page' : isNext ? 'Next page' : `Page ${label}`}>
                                                {content}
                                            </Link>
                                        </Button>
                                    ) : (
                                        <Button
                                            key={`${label}-${index}`}
                                            size={isPrevious || isNext ? 'icon' : 'sm'}
                                            variant="outline"
                                            disabled
                                            aria-label={`${isPrevious ? 'Previous' : isNext ? 'Next' : `Page ${label}`} page unavailable`}
                                        >
                                            {content}
                                        </Button>
                                    );
                                })}
                            </nav>
                        </CardFooter>
                    )}
                </Card>
            </main>
        </AppLayout>
    );
}
