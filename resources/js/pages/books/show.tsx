import ResourceShow, { type DetailRow } from '@/components/library/resource-show';

type Book = {
    id: number;
    book_isbn: string;
    book_title: string;
    author_name: string;
    publisher: { publisher_name: string } | null;
};

export default function BookShow({ book }: { book: Book }) {
    const rows: DetailRow[] = [
        { label: 'ISBN', value: book.book_isbn },
        { label: 'Title', value: book.book_title },
        { label: 'Author', value: book.author_name },
        { label: 'Publisher', value: book.publisher?.publisher_name ?? '—' },
    ];

    return <ResourceShow title={book.book_title} path="/books" editPath={`/books/${book.id}/edit`} rows={rows} />;
}
