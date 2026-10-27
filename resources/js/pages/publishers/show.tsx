import ResourceShow, { type DetailRow } from '@/components/library/resource-show';

type Publisher = {
    id: number;
    publisher_name: string;
    books: { id: number; book_isbn: string; book_title: string; author_name: string }[];
};

export default function PublisherShow({ publisher }: { publisher: Publisher }) {
    const bookList = publisher.books.length ? (
        <ul className="list-inside list-disc">
            {publisher.books.map((book) => <li key={book.id}>{book.book_title} ({book.book_isbn}) — {book.author_name}</li>)}
        </ul>
    ) : 'No books assigned.';
    const rows: DetailRow[] = [
        { label: 'Publisher', value: publisher.publisher_name },
        { label: 'Books', value: bookList },
    ];

    return <ResourceShow title={publisher.publisher_name} path="/publishers" editPath={`/publishers/${publisher.id}/edit`} rows={rows} />;
}
