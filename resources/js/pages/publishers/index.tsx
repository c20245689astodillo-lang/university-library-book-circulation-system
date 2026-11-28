import ResourceIndex, { type Paginated, type ResourceColumn } from '@/components/library/resource-index';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';

type Publisher = { id: number; publisher_name: string; books_count: number };

export default function PublishersIndex({ publishers, filters }: { publishers: Paginated<Publisher>; filters: { search?: string } }) {
    const columns: ResourceColumn<Publisher>[] = [
        { label: 'Publisher', render: (publisher) => publisher.publisher_name },
        { label: 'Books', render: (publisher) => publisher.books_count },
    ];

    return <ResourceIndex
        title="Publishers"
        path="/publishers"
        rows={publishers}
        columns={columns}
        search={filters.search ?? ''}
        headerAction={(
            <Button variant="outline" asChild>
                <Link href="/archives?type=publishers">View Archives</Link>
            </Button>
        )}
        rowAction={(publisher) => (
            <Button variant="outline" size="sm" asChild>
                <Link href={`/archives/publishers/${publisher.id}/archive`} method="post" as="button">Archive</Link>
            </Button>
        )}
    />;
}
