import { Head } from '@inertiajs/react';

import AppearanceTabs from '@/components/appearance-tabs';
import HeadingSmall from '@/components/heading-small';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Appearance settings',
        href: '/settings/appearance',
    },
];

export default function Appearance() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Appearance settings" />

            <SettingsLayout>
                <Card className="rounded-2xl">
                    <CardHeader>
                        <HeadingSmall title="Appearance settings" description="Update your account's appearance settings" />
                    </CardHeader>
                    <CardContent className="pt-0">
                        <AppearanceTabs />
                    </CardContent>
                </Card>
            </SettingsLayout>
        </AppLayout>
    );
}
