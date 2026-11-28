import ResourceForm, { type FormField, type FormValue } from '@/components/library/resource-form';

type Publisher = { id: number; publisher_name: string };

export default function PublisherForm({ publisher }: { publisher?: Publisher }) {
    const editing = Boolean(publisher);
    const path = '/publishers';
    const action = publisher ? `${path}/${publisher.id}` : path;
    const values: Record<string, FormValue> = { publisher_name: publisher?.publisher_name ?? '' };
    const fields: FormField[] = [
        {
            name: 'publisher_name',
            label: 'Publisher name',
            required: true,
            maxLength: 255,
            pattern: '(?=.*[\\p{L}])[\\p{L}\\p{M}\\x26\\x2e\\x2c\\x27\\u2019\\x28\\x29\\s\\x2d]+',
            title: 'Use letters, spaces, apostrophes, ampersands, periods, commas, parentheses, or hyphens. Numbers are not allowed.',
        },
    ];

    return <ResourceForm title={editing ? 'Edit publisher' : 'Create publisher'} path={path} action={action} method={editing ? 'put' : 'post'} values={values} fields={fields} />;
}
