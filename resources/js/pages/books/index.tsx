import ResourceIndex, { type Paginated, type ResourceColumn } from '@/components/library/resource-index';

type Book = {
    id: number;
    book_isbn: string;
    book_title: string;
    author_name: string;
    publisher: { publisher_name: string } | null;
};

export default function BooksIndex({ books }: { books: Paginated<Book> }) {
    const columns: ResourceColumn<Book>[] = [
        { label: 'ISBN', render: (book) => book.book_isbn },
        { label: 'Title', render: (book) => book.book_title },
        { label: 'Author', render: (book) => book.author_name },
        { label: 'Publisher', render: (book) => book.publisher?.publisher_name ?? '—' },
    ];

    return <ResourceIndex title="Books" path="/books" rows={books} columns={columns} />;
}
