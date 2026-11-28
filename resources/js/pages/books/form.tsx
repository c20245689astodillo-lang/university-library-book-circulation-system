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
    suggestedBookCode?: string;
    bookCodeOptions?: string[];
};

export default function BookForm({ book, publishers, suggestedBookCode, bookCodeOptions = [] }: Props) {
    const editing = Boolean(book);
    const path = '/books';
    const action = book ? `${path}/${book.id}` : path;
    const values: Record<string, FormValue> = {
        publisher_id: book?.publisher_id ?? '',
        book_isbn: book?.book_isbn ?? suggestedBookCode ?? '',
        book_title: book?.book_title ?? '',
        author_name: book?.author_name ?? '',
    };
    const fields: FormField[] = [
        {
            name: 'publisher_id',
            label: 'Publisher',
            type: 'select',
            required: true,
            options: publishers.map((publisher) => ({ value: publisher.id, label: publisher.publisher_name })),
        },
        {
            name: 'book_isbn',
            label: editing ? 'ISBN or book code' : 'Book code',
            type: editing ? 'text' : 'select',
            required: true,
            readOnly: !editing,
            maxLength: 20,
            pattern: '([0-9]{1,3}-[0-9]{1,4}|[0-9]+(-[0-9]+)*(-?[xX])?)',
            title: 'Use a project code with 1 to 3 digits before the hyphen and 1 to 4 after, or a valid ISBN-10/13.',
            description: editing ? undefined : 'The next five codes are shown. Only the next code can be selected, so the sequence stays in order.',
            options: !editing
                ? bookCodeOptions.map((code, index) => ({ value: code, label: code, disabled: index > 0 }))
                : undefined,
        },
        {
            name: 'book_title',
            label: 'Title',
            required: true,
            maxLength: 255,
            pattern: '(?!.*([\\p{N}])\\1{2})(?!.*[\\p{N}]{4,})[\\p{L}\\p{M}\\p{N}\\s\\x2e\\x2c\\x3a\\x3b\\x21\\x3f\\x26\\x27\\u2019\\x22\\x28\\x29\\x5b\\x5d\\x2f\\x5f\\x23\\x40\\x2b\\x2d]+',
            title: 'Use letters, numbers, spaces, and common title punctuation. Limit numbers to three digits and avoid three repeated digits.',
        },
        {
            name: 'author_name',
            label: 'Author',
            required: true,
            maxLength: 255,
            pattern: '[\\p{L}\\p{M}]+(?:[\\x20\\x2e\\x2c\\x27\\u2019\\x2d]+[\\p{L}\\p{M}]+)*\\x2e?',
            title: 'Use letters, spaces, periods, commas, apostrophes, or hyphens only.',
        },
    ];

    return (
        <ResourceForm
            title={editing ? 'Edit book' : 'Create book'}
            path={path}
            action={action}
            method={editing ? 'put' : 'post'}
            values={values}
            fields={fields}
        />
    );
}
