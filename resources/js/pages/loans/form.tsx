import ResourceForm, { type FormField, type FormValue } from '@/components/library/resource-form';

type StudentOption = { id: number; student_number: string; student_name: string };
type BookOption = { id: number; book_isbn: string; book_title: string };
type Loan = {
    id: number;
    student_id: number;
    book_id: number;
    issue_date: string;
    due_date: string;
    return_date: string | null;
    fine_per_day: string | number;
};

type Props = { loan?: Loan; students: StudentOption[]; books: BookOption[]; today: string };

function addDays(date: string, days: number): string {
    const result = new Date(`${date}T00:00:00Z`);
    result.setUTCDate(result.getUTCDate() + days);

    return result.toISOString().slice(0, 10);
}

export default function LoanForm({ loan, students, books, today }: Props) {
    const editing = Boolean(loan);
    const path = '/loans';
    const action = loan ? `${path}/${loan.id}` : path;
    const initialIssueDate = loan?.issue_date.slice(0, 10) ?? today;
    const values: Record<string, FormValue> = {
        student_id: loan?.student_id ?? '',
        book_id: loan?.book_id ?? '',
        issue_date: initialIssueDate,
        due_date: loan?.due_date ?? addDays(initialIssueDate, 5),
        return_date: loan?.return_date ?? '',
        fine_per_day: loan?.fine_per_day ?? 0,
    };
    const fields: FormField[] = [
        { name: 'student_id', label: 'Student', type: 'select', required: true, options: students.map((student) => ({ value: student.id, label: `${student.student_name} (${student.student_number})` })) },
        { name: 'book_id', label: 'Book', type: 'select', required: true, options: books.map((book) => ({ value: book.id, label: `${book.book_title} (${book.book_isbn})` })) },
        { name: 'issue_date', label: 'Issue date', type: 'date', required: true, disabled: Boolean(loan), min: loan?.issue_date.slice(0, 10) ?? today, max: loan?.issue_date.slice(0, 10) ?? today, dateOffset: loan ? undefined : { target: 'due_date', days: 5 } },
        { name: 'due_date', label: 'Due date', type: 'date', required: true, minFrom: 'issue_date', max: addDays(initialIssueDate, 5) },
        { name: 'return_date', label: 'Return date', type: 'date', minFrom: 'issue_date', maxDateOffset: { source: 'due_date', days: 5 } },
        { name: 'fine_per_day', label: 'Fine per overdue day', type: 'number', required: true, max: 999999.99, decimalPlaces: 2 },
    ];

    return <ResourceForm title={editing ? 'Edit loan' : 'Create loan'} path={path} action={action} method={editing ? 'put' : 'post'} values={values} fields={fields} />;
}
