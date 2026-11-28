import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import DeleteUser from '@/components/delete-user';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: '/settings/profile',
    },
];

export default function Profile({ mustVerifyEmail, status }: { mustVerifyEmail: boolean; status?: string }) {
    const { auth } = usePage<SharedData>().props;

    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
        name: auth.user.name,
        email: auth.user.email,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('profile.update'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profile settings" />

            <SettingsLayout>
                <Card className="rounded-2xl">
                    <CardHeader>
                        <HeadingSmall title="Profile information" description="Update your name and email address" />
                    </CardHeader>
                    <form onSubmit={submit}>
                        <CardContent className="grid gap-5 pt-0">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                    autoComplete="name"
                                    placeholder="Full name"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    required
                                    autoComplete="username"
                                    placeholder="Email address"
                                />
                                <InputError message={errors.email} />
                            </div>

                            {mustVerifyEmail && auth.user.email_verified_at === null && (
                                <Alert className="rounded-2xl">
                                    <AlertTitle>Email address not verified</AlertTitle>
                                    <AlertDescription>
                                        <span>Your email address is unverified.</span>
                                        <Button variant="link" size="sm" asChild className="mt-1 h-auto px-0">
                                            <Link href={route('verification.send')} method="post" as="button">
                                                Resend verification email
                                            </Link>
                                        </Button>
                                        {status === 'verification-link-sent' && <p className="mt-2">A new verification link has been sent.</p>}
                                    </AlertDescription>
                                </Alert>
                            )}
                        </CardContent>
                        <CardFooter className="gap-3 border-t pt-6">
                            <Button disabled={processing}>Save changes</Button>
                            {recentlySuccessful && <Badge variant="secondary">Saved</Badge>}
                        </CardFooter>
                    </form>
                </Card>

                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
