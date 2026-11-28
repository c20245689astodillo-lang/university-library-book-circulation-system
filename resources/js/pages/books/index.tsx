import ResourceIndex, { type Paginated, type ResourceColumn } from '@/components/library/resource-index';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';

type Book = {
    id: number;
    book_isbn: string;
    book_title: string;
    author_name: string;
    active_loans_count: number;
    publisher: { publisher_name: string } | null;
};

type ArchiveType = 'books' | 'students' | 'publishers' | 'loans' | 'fines';
type ArchivedRecord = { id: number; type: ArchiveType; name: string; details: string; archived_at: string };
type Filters = { search?: string };
type BooksPageProps =
    | { isArchives: true; records: Paginated<ArchivedRecord>; archiveType: ArchiveType; filters: Filters }
    | { isArchives?: false; books: Paginated<Book>; filters: Filters };

const archiveCategories: { type: ArchiveType; label: string }[] = [
    { type: 'books', label: 'Books' },
    { type: 'students', label: 'Students' },
    { type: 'publishers', label: 'Publishers' },
    { type: 'loans', label: 'Loans' },
    { type: 'fines', label: 'Fines' },
];

export default function BooksIndex(props: BooksPageProps) {
    if (props.isArchives) {
        const columns: ResourceColumn<ArchivedRecord>[] = [
            { label: 'Record', render: (record) => record.name },
            { label: 'Details', render: (record) => record.details },
            { label: 'Archived on', render: (record) => record.archived_at },
        ];

        return (
            <ResourceIndex
                title="Archives"
                path={`/archives?type=${props.archiveType}`}
                rows={props.records}
                columns={columns}
                canCreate={false}
                canEdit={() => false}
                search={props.filters.search ?? ''}
                headerAction={(
                    <nav aria-label="Archive categories" className="flex flex-wrap gap-1 rounded-lg border p-1">
                        {archiveCategories.map((category) => (
                            <Button key={category.type} variant={props.archiveType === category.type ? 'default' : 'ghost'} size="sm" asChild>
                                <Link href={`/archives?type=${category.type}`}>{category.label}</Link>
                            </Button>
                        ))}
                    </nav>
                )}
                rowAction={(record) => (
                    <Button variant="outline" size="sm" asChild>
                        <Link href={`/archives/${record.type}/${record.id}/restore`} method="post" as="button">Restore</Link>
                    </Button>
                )}
            />
        );
    }

    const columns: ResourceColumn<Book>[] = [
        { label: 'ISBN / Book code', render: (book) => book.book_isbn },
        { label: 'Title', render: (book) => book.book_title },
        { label: 'Author', render: (book) => book.author_name },
        { label: 'Publisher', render: (book) => book.publisher?.publisher_name ?? '-' },
    ];

    return (
        <ResourceIndex
            title="Books"
            path="/books"
            rows={props.books}
            columns={columns}
            search={props.filters.search ?? ''}
            headerAction={(
                <Button variant="outline" asChild>
                    <Link href="/archives">View Archives</Link>
                </Button>
            )}
            rowAction={(book) => book.active_loans_count > 0 ? (
                <Button variant="outline" size="sm" disabled title="Return the book before archiving it">Archive</Button>
            ) : (
                <Button variant="outline" size="sm" asChild>
                    <Link href={`/archives/books/${book.id}/archive`} method="post" as="button">Archive</Link>
                </Button>
            )}
        />
    );
}
