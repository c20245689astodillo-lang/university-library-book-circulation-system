import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, ArrowUpRight, BookOpen, LibraryBig, UsersRound } from 'lucide-react';

const features = [
    {
        icon: BookOpen,
        title: 'A clear catalog',
        description: 'Keep book titles, authors, publishers, and availability organized in one place.',
    },
    {
        icon: UsersRound,
        title: 'Reader records',
        description: 'Find student details quickly and keep circulation connected to the right reader.',
    },
    {
        icon: LibraryBig,
        title: 'Confident circulation',
        description: 'Track due dates, returns, and fines with a focused view of daily activity.',
    },
];

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="University Library">
                <meta name="description" content="A clear, reliable workspace for university library circulation." />
            </Head>
            <div className="bg-background text-foreground min-h-screen">
                <header className="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-4 py-5 sm:px-6 lg:px-8">
                    <Link
                        href="/"
                        className="focus-visible:ring-ring flex items-center gap-3 rounded-md focus-visible:ring-2 focus-visible:outline-none"
                    >
                        <span className="bg-primary text-primary-foreground grid size-10 place-items-center rounded-xl shadow-sm">
                            <BookOpen className="size-5" aria-hidden="true" />
                        </span>
                        <span className="grid gap-1">
                            <span className="text-sm leading-none font-semibold tracking-tight">University Library BCS</span>
                            <span className="text-muted-foreground text-xs">Circulation system</span>
                        </span>
                    </Link>
                    <nav className="flex items-center gap-2" aria-label="Account">
                        {auth.user ? (
                            <Button asChild>
                                <Link href={route('dashboard')}>
                                    Open dashboard <ArrowRight aria-hidden="true" />
                                </Link>
                            </Button>
                        ) : (
                            <>
                                <Button variant="ghost" asChild>
                                    <Link href={route('login')}>Log in</Link>
                                </Button>
                                <Button asChild>
                                    <Link href={route('register')}>
                                        Get started <ArrowRight aria-hidden="true" />
                                    </Link>
                                </Button>
                            </>
                        )}
                    </nav>
                </header>

                <main className="mx-auto w-full max-w-7xl px-4 pb-12 sm:px-6 lg:px-8">
                    <section className="grid items-center gap-8 py-10 sm:py-16 lg:grid-cols-[minmax(0,1.15fr)_minmax(20rem,0.85fr)] lg:gap-16 lg:py-24">
                        <div className="max-w-2xl">
                            <Badge variant="secondary" className="mb-5 gap-2 rounded-full px-3 py-1">
                                <span className="bg-primary size-1.5 rounded-full" aria-hidden="true" />
                                Library operations, made clear
                            </Badge>
                            <h1 className="text-4xl leading-[1.08] font-semibold tracking-tight sm:text-5xl lg:text-6xl">Keep knowledge moving.</h1>
                            <p className="text-muted-foreground mt-5 max-w-xl text-base leading-7 sm:text-lg">
                                A calmer way to manage your collection, support student readers, and stay on top of every loan.
                            </p>
                            <div className="mt-8 flex flex-wrap items-center gap-3">
                                {auth.user ? (
                                    <Button size="lg" asChild>
                                        <Link href={route('dashboard')}>
                                            Go to your dashboard <ArrowRight aria-hidden="true" />
                                        </Link>
                                    </Button>
                                ) : (
                                    <>
                                        <Button size="lg" asChild>
                                            <Link href={route('login')}>
                                                Sign in to your library <ArrowRight aria-hidden="true" />
                                            </Link>
                                        </Button>
                                        <Button size="lg" variant="outline" asChild>
                                            <Link href={route('register')}>Create an account</Link>
                                        </Button>
                                    </>
                                )}
                            </div>
                            <p className="text-muted-foreground mt-4 text-xs">Built for the everyday work of a university library.</p>
                        </div>

                        <Card className="border-primary/15 shadow-primary/5 overflow-hidden shadow-lg">
                            <div className="bg-primary text-primary-foreground relative overflow-hidden p-6 sm:p-8">
                                <div className="bg-primary-foreground/10 absolute -top-16 -right-12 size-56 rounded-full" aria-hidden="true" />
                                <div className="relative">
                                    <div className="flex items-center justify-between gap-3">
                                        <Badge
                                            variant="secondary"
                                            className="bg-primary-foreground/15 text-primary-foreground hover:bg-primary-foreground/15"
                                        >
                                            Your library workspace
                                        </Badge>
                                        <BookOpen className="size-5 opacity-80" aria-hidden="true" />
                                    </div>
                                    <h2 className="mt-10 max-w-sm text-2xl font-semibold tracking-tight sm:text-3xl">
                                        Everything in its right place.
                                    </h2>
                                    <p className="text-primary-foreground/75 mt-3 max-w-sm text-sm leading-6">
                                        Move from catalog updates to checkouts and returns without losing the details that matter.
                                    </p>
                                </div>
                            </div>
                            <CardContent className="grid gap-0 p-0">
                                {[
                                    { icon: BookOpen, title: 'Books and publishers', detail: 'Keep your collection organized' },
                                    { icon: UsersRound, title: 'Students', detail: 'Find reader records with ease' },
                                    { icon: ArrowRight, title: 'Loans and fines', detail: 'See what needs attention' },
                                ].map(({ icon: Icon, title, detail }) => (
                                    <div key={title} className="flex items-center gap-4 border-t px-6 py-4 first:border-t-0 sm:px-8">
                                        <span className="bg-secondary text-primary grid size-10 shrink-0 place-items-center rounded-lg">
                                            <Icon className="size-4" aria-hidden="true" />
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <p className="text-sm font-medium">{title}</p>
                                            <p className="text-muted-foreground mt-1 text-xs">{detail}</p>
                                        </div>
                                        <ArrowUpRight className="text-muted-foreground size-4" aria-hidden="true" />
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    </section>

                    <section aria-labelledby="features-title" className="border-t pt-8 sm:pt-10">
                        <div className="mb-6 flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
                            <div>
                                <p className="text-muted-foreground text-sm font-medium">One connected workspace</p>
                                <h2 id="features-title" className="mt-1 text-xl font-semibold tracking-tight">
                                    Made for library work
                                </h2>
                            </div>
                            <p className="text-muted-foreground max-w-md text-sm">
                                Spend less time searching for information and more time helping readers.
                            </p>
                        </div>
                        <div className="grid gap-4 md:grid-cols-3">
                            {features.map(({ icon: Icon, title, description }) => (
                                <Card key={title} className="hover:bg-accent/30 shadow-none transition-colors">
                                    <CardContent className="p-5 pt-5 sm:p-6 sm:pt-6">
                                        <span className="bg-secondary text-primary mb-5 grid size-10 place-items-center rounded-lg">
                                            <Icon className="size-5" aria-hidden="true" />
                                        </span>
                                        <h3 className="font-semibold tracking-tight">{title}</h3>
                                        <p className="text-muted-foreground mt-2 text-sm leading-6">{description}</p>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </section>
                </main>

                <footer className="text-muted-foreground mx-auto flex w-full max-w-7xl flex-col gap-2 border-t px-4 py-5 text-xs sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                    <p>University Library BCS</p>
                    <p>Supporting a well-connected campus collection.</p>
                </footer>
            </div>
        </>
    );
}
