import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { ArrowRight, BookOpen, CalendarDays, CircleAlert, Clock3, Plus, ReceiptText, UsersRound } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

type DashboardStats = {
    books: number;
    students: number;
    activeLoans: number;
    overdueLoans: number;
    dueSoon: number;
    unpaidFines: number;
    unpaidFinesCount: number;
};

type RecentLoan = {
    id: number;
    issue_date: string;
    due_date: string;
    return_date: string | null;
    student: { student_number: string; student_name: string };
    book: { book_title: string };
};

type LoanStatus = {
    label: string;
    variant: 'default' | 'secondary' | 'destructive' | 'outline';
};

const formatDate = (value: string) =>
    new Intl.DateTimeFormat('en-PH', { month: 'short', day: 'numeric' }).format(new Date(`${value.slice(0, 10)}T12:00:00`));

const formatMoney = (value: number) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 2 }).format(value);

function loanStatus(loan: RecentLoan, today: string): LoanStatus {
    if (loan.return_date) return { label: 'Returned', variant: 'outline' };
    if (loan.issue_date.slice(0, 10) > today) return { label: 'Reserved', variant: 'secondary' };

    const dueDate = loan.due_date.slice(0, 10);
    if (dueDate < today) return { label: 'Overdue', variant: 'destructive' };
    if (dueDate === today) return { label: 'Due today', variant: 'secondary' };

    const soon = new Date(`${today}T12:00:00`);
    soon.setDate(soon.getDate() + 3);
    const soonDate = `${soon.getFullYear()}-${String(soon.getMonth() + 1).padStart(2, '0')}-${String(soon.getDate()).padStart(2, '0')}`;

    if (dueDate <= soonDate) return { label: 'Due soon', variant: 'secondary' };
    return { label: 'On loan', variant: 'secondary' };
}

function MetricCard({ title, value, note, href, icon: Icon }: { title: string; value: number; note: string; href: string; icon: LucideIcon }) {
    return (
        <Link
            href={href}
            className="focus-visible:ring-ring block h-full rounded-xl focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
        >
            <Card className="hover:bg-accent/40 h-full transition-colors">
                <CardContent className="p-5">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="text-muted-foreground text-sm font-medium">{title}</p>
                            <p className="mt-3 text-3xl font-semibold tracking-tight tabular-nums">{value.toLocaleString()}</p>
                            <p className="text-muted-foreground mt-1 text-xs">{note}</p>
                        </div>
                        <Badge variant="secondary" className="grid size-10 shrink-0 place-items-center p-0">
                            <Icon className="size-4" aria-hidden="true" />
                        </Badge>
                    </div>
                </CardContent>
            </Card>
        </Link>
    );
}

export default function Dashboard({ today, stats, recentLoans }: { today: string; stats: DashboardStats; recentLoans: RecentLoan[] }) {
    const displayDate = new Intl.DateTimeFormat('en-PH', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(`${today}T12:00:00`));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <main className="flex min-w-0 flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <h1 className="mt-1 text-3xl font-semibold tracking-tight">Library overview</h1>
                        <p className="text-muted-foreground mt-2 text-sm">A clear view of your collection and what needs attention.</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant="outline" className="text-muted-foreground h-9 gap-2 rounded-md px-3 font-normal">
                            <CalendarDays className="size-4" aria-hidden="true" /> {displayDate}
                        </Badge>
                        <Button variant="outline" asChild>
                            <Link href="/books/create">
                                <Plus aria-hidden="true" /> Add a book
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href="/loans/create">
                                <Plus aria-hidden="true" /> Record a loan
                            </Link>
                        </Button>
                    </div>
                </header>

                <Card className="border-primary bg-primary text-primary-foreground">
                    <CardContent className="flex flex-col justify-between gap-5 p-6 sm:flex-row sm:items-center sm:p-8">
                        <div>
                            <p className="text-primary-foreground/70 text-sm font-medium">Today at the library</p>
                            <h2 className="mt-2 text-2xl font-semibold tracking-tight">Keep every book moving.</h2>
                            <p className="text-primary-foreground/80 mt-2 text-sm">
                                {stats.dueSoon === 1 ? 'One loan is' : `${stats.dueSoon} loans are`} due within the next three days.
                            </p>
                        </div>
                        <Button
                            variant="outline"
                            asChild
                            className="border-primary-foreground/30 text-primary-foreground hover:bg-primary-foreground/10 hover:text-primary-foreground bg-transparent"
                        >
                            <Link href="/loans">
                                Review circulation <ArrowRight aria-hidden="true" />
                            </Link>
                        </Button>
                    </CardContent>
                </Card>

                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Library statistics">
                    <MetricCard title="Books in catalog" value={stats.books} note="Registered titles" href="/books" icon={BookOpen} />
                    <MetricCard title="Students" value={stats.students} note="Registered readers" href="/students" icon={UsersRound} />
                    <MetricCard title="On loan" value={stats.activeLoans} note="Awaiting return" href="/loans" icon={Clock3} />
                    <MetricCard title="Overdue" value={stats.overdueLoans} note="Past the due date" href="/loans" icon={CircleAlert} />
                </section>

                <div className="grid gap-5 xl:grid-cols-[minmax(0,1.7fr)_minmax(18rem,0.8fr)]">
                    <section aria-labelledby="recent-circulation-title">
                        <Card>
                            <CardHeader className="flex-col items-start justify-between gap-2 border-b sm:flex-row sm:items-center sm:space-y-0">
                                <div className="space-y-1.5">
                                    <CardTitle id="recent-circulation-title" className="text-base">
                                        Recent circulation
                                    </CardTitle>
                                    <CardDescription>The latest books issued to students.</CardDescription>
                                </div>
                                <Button variant="ghost" size="sm" asChild>
                                    <Link href="/loans">
                                        All loans <ArrowRight aria-hidden="true" />
                                    </Link>
                                </Button>
                            </CardHeader>
                            {recentLoans.length ? (
                                <>
                                    <CardContent className="grid gap-3 p-4 lg:hidden">
                                        {recentLoans.map((loan) => {
                                            const status = loanStatus(loan, today);

                                            return (
                                                <article key={loan.id} className="min-w-0 rounded-lg border p-3">
                                                    <div className="flex min-w-0 items-start justify-between gap-3">
                                                        <div className="min-w-0">
                                                            <Link
                                                                href={`/loans/${loan.id}`}
                                                                className="text-sm font-medium break-words hover:underline"
                                                            >
                                                                {loan.book.book_title}
                                                            </Link>
                                                            <p className="text-muted-foreground mt-1 text-xs break-words">
                                                                {loan.student.student_name} · {loan.student.student_number}
                                                            </p>
                                                        </div>
                                                        <Badge className="shrink-0" variant={status.variant}>
                                                            {status.label}
                                                        </Badge>
                                                    </div>
                                                    <p className="text-muted-foreground mt-3 text-sm">
                                                        {loan.return_date
                                                            ? 'Returned ' + formatDate(loan.return_date)
                                                            : loan.issue_date.slice(0, 10) > today
                                                              ? 'Starts ' + formatDate(loan.issue_date)
                                                              : 'Due ' + formatDate(loan.due_date)}
                                                    </p>
                                                </article>
                                            );
                                        })}
                                    </CardContent>
                                    <CardContent className="hidden p-0 lg:block">
                                        <Table className="min-w-[36rem]">
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Book and student</TableHead>
                                                    <TableHead>Schedule</TableHead>
                                                    <TableHead>Status</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {recentLoans.map((loan) => {
                                                    const status = loanStatus(loan, today);

                                                    return (
                                                        <TableRow key={loan.id}>
                                                            <TableCell>
                                                                <div className="min-w-48">
                                                                    <Link href={'/loans/' + loan.id} className="font-medium hover:underline">
                                                                        {loan.book.book_title}
                                                                    </Link>
                                                                    <p className="text-muted-foreground mt-1 text-xs">
                                                                        {loan.student.student_name} · {loan.student.student_number}
                                                                    </p>
                                                                </div>
                                                            </TableCell>
                                                            <TableCell className="text-muted-foreground whitespace-nowrap">
                                                                {loan.return_date
                                                                    ? 'Returned ' + formatDate(loan.return_date)
                                                                    : loan.issue_date.slice(0, 10) > today
                                                                      ? 'Starts ' + formatDate(loan.issue_date)
                                                                      : 'Due ' + formatDate(loan.due_date)}
                                                            </TableCell>
                                                            <TableCell>
                                                                <Badge variant={status.variant}>{status.label}</Badge>
                                                            </TableCell>
                                                        </TableRow>
                                                    );
                                                })}
                                            </TableBody>
                                        </Table>
                                    </CardContent>
                                </>
                            ) : (
                                <CardContent className="py-12 text-center">
                                    <BookOpen className="text-muted-foreground mx-auto size-8" aria-hidden="true" />
                                    <p className="mt-3 text-sm font-medium">No loans yet</p>
                                    <p className="text-muted-foreground mt-1 text-sm">Your recent circulation will show up here.</p>
                                    <Button variant="link" asChild className="mt-2">
                                        <Link href="/loans/create">
                                            Record the first loan <ArrowRight aria-hidden="true" />
                                        </Link>
                                    </Button>
                                </CardContent>
                            )}
                        </Card>
                    </section>

                    <aside className="flex flex-col gap-5">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Needs attention</CardTitle>
                                <CardDescription>A few things to keep an eye on.</CardDescription>
                            </CardHeader>
                            <CardContent className="grid gap-1">
                                <Button variant="ghost" asChild className="h-auto justify-between px-2 py-2 font-normal">
                                    <Link href="/loans">
                                        <span className="flex items-center gap-2">
                                            <CircleAlert className="text-destructive size-4" aria-hidden="true" /> Overdue loans
                                        </span>
                                        <span className="font-medium tabular-nums">{stats.overdueLoans}</span>
                                    </Link>
                                </Button>
                                <Button variant="ghost" asChild className="h-auto justify-between px-2 py-2 font-normal">
                                    <Link href="/loans">
                                        <span className="flex items-center gap-2">
                                            <Clock3 className="text-muted-foreground size-4" aria-hidden="true" /> Due within 3 days
                                        </span>
                                        <span className="font-medium tabular-nums">{stats.dueSoon}</span>
                                    </Link>
                                </Button>
                                <Button variant="ghost" asChild className="h-auto justify-between px-2 py-2 font-normal">
                                    <Link href="/fines">
                                        <span className="flex items-center gap-2">
                                            <ReceiptText className="text-muted-foreground size-4" aria-hidden="true" /> Unpaid fines{' '}
                                            <span className="text-muted-foreground text-xs">({stats.unpaidFinesCount})</span>
                                        </span>
                                        <span className="font-medium tabular-nums">{formatMoney(stats.unpaidFines)}</span>
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base">Quick links</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-1">
                                <Button variant="ghost" asChild className="justify-between px-2 font-normal">
                                    <Link href="/students/create">
                                        Register a student <ArrowRight aria-hidden="true" />
                                    </Link>
                                </Button>
                                <Button variant="ghost" asChild className="justify-between px-2 font-normal">
                                    <Link href="/publishers/create">
                                        Add a publisher <ArrowRight aria-hidden="true" />
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>
                    </aside>
                </div>
            </main>
        </AppLayout>
    );
}
