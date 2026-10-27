import ResourceIndex, { type Paginated, type ResourceColumn } from '@/components/library/resource-index';

type Publisher = { id: number; publisher_name: string; books_count: number };

export default function PublishersIndex({ publishers }: { publishers: Paginated<Publisher> }) {
    const columns: ResourceColumn<Publisher>[] = [
        { label: 'Publisher', render: (publisher) => publisher.publisher_name },
        { label: 'Books', render: (publisher) => publisher.books_count },
    ];

    return <ResourceIndex title="Publishers" path="/publishers" rows={publishers} columns={columns} />;
}
