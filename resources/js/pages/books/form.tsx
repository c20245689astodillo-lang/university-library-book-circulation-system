import ResourceForm, { type FormField, type FormValue } from '@/components/library/resource-form';

type Book = {
    id: number;
    publisher_id: number;
    book_isbn: string;
    book_title: string;
    author_name: string;
};

type Publisher = { id: number; publisher_name: string };

type Props = {
    book?: Book;
    publishers: Publisher[];
};

export default function BookForm({ book, publishers }: Props) {
    const editing = Boolean(book);
    const path = '/books';
    const action = book ? `${path}/${book.id}` : path;
    const values: Record<string, FormValue> = {
        publisher_id: book?.publisher_id ?? '',
        book_isbn: book?.book_isbn ?? '',
        book_title: book?.book_title ?? '',
        author_name: book?.author_name ?? '',
    };
    const fields: FormField[] = [
        { name: 'publisher_id', label: 'Publisher', type: 'select', required: true, options: publishers.map((publisher) => ({ value: publisher.id, label: publisher.publisher_name })) },
        { name: 'book_isbn', label: 'ISBN or book code', required: true, maxLength: 20, pattern: '([0-9]{3}-[0-9]{4}|[0-9]+(-[0-9]+)*(-?[xX])?)', title: 'Use a code like 568-0012 or a valid ISBN-10/13.' },
        { name: 'book_title', label: 'Title', required: true, maxLength: 255 },
        { name: 'author_name', label: 'Author', required: true, maxLength: 255 },
    ];

    return <ResourceForm title={editing ? 'Edit book' : 'Create book'} path={path} action={action} method={editing ? 'put' : 'post'} values={values} fields={fields} />;
}
