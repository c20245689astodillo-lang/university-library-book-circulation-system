import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Pencil } from 'lucide-react';
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
        <AppLayout
            breadcrumbs={[
                { title: path.split('/').at(-1) ?? title, href: path },
                { title, href: editPath },
            ]}
        >
            <Head title={title} />
            <main className="mx-auto flex w-full max-w-3xl min-w-0 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div className="min-w-0">
                        <p className="text-muted-foreground text-sm font-medium">Library record</p>
                        <h1 className="mt-1 text-3xl font-semibold tracking-tight break-words">{title}</h1>
                    </div>
                    <Button asChild className="shrink-0">
                        <Link href={editPath}>
                            <Pencil aria-hidden="true" /> Edit record
                        </Link>
                    </Button>
                </header>
                <Card>
                    <CardHeader className="border-b">
                        <CardTitle className="text-lg">Record details</CardTitle>
                        <CardDescription>Information currently saved for this library record.</CardDescription>
                    </CardHeader>
                    <CardContent className="p-5 pt-5 sm:p-6 sm:pt-6">
                        <dl className="divide-y">
                            {rows.map((row) => (
                                <div key={row.label} className="grid gap-1 py-4 first:pt-0 last:pb-0 sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-5">
                                    <dt className="text-muted-foreground text-sm">{row.label}</dt>
                                    <dd className="min-w-0 text-sm font-medium break-words">
                                        {row.value === null || row.value === undefined || row.value === '' ? (
                                            <span className="text-muted-foreground">—</span>
                                        ) : (
                                            row.value
                                        )}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </CardContent>
                </Card>
                <Button variant="ghost" className="w-fit px-2" asChild>
                    <Link href={path}>
                        <ArrowLeft aria-hidden="true" /> Back to list
                    </Link>
                </Button>
            </main>
        </AppLayout>
    );
}
