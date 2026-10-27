import ResourceForm, { type FormField, type FormValue } from '@/components/library/resource-form';

type LoanOption = {
    id: number;
    student: { student_number: string; student_name: string };
    book: { book_isbn: string; book_title: string };
};
type Fine = { id: number; loan_id: number; calculated_fine: string | number; payment_status: 'unpaid' | 'paid' };

type Props = { fine?: Fine; loans: LoanOption[] };

export default function FineForm({ fine, loans }: Props) {
    const editing = Boolean(fine);
    const path = '/fines';
    const action = fine ? `${path}/${fine.id}` : path;
    const values: Record<string, FormValue> = {
        loan_id: fine?.loan_id ?? '',
        calculated_fine: fine?.calculated_fine ?? 0,
        payment_status: fine?.payment_status ?? 'unpaid',
    };
    const fields: FormField[] = [
        {
            name: 'loan_id',
            label: 'Loan',
            type: 'select',
            required: true,
            options: loans.map((loan) => ({
                value: loan.id,
                label: `#${loan.id}: ${loan.book.book_title} (${loan.book.book_isbn}) — ${loan.student.student_name} (${loan.student.student_number})`,
            })),
        },
        { name: 'calculated_fine', label: 'Fine amount', type: 'number', required: true },
        { name: 'payment_status', label: 'Payment status', type: 'select', required: true, options: [{ value: 'unpaid', label: 'Unpaid' }, { value: 'paid', label: 'Paid' }] },
    ];

    return <ResourceForm title={editing ? 'Edit fine' : 'Create fine'} path={path} action={action} method={editing ? 'put' : 'post'} values={values} fields={fields} />;
}
