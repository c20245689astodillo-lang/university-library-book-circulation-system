import ResourceShow, { type DetailRow } from '@/components/library/resource-show';

type Loan = {
    id: number;
    issue_date: string;
    due_date: string;
    return_date: string | null;
    fine_per_day: string | number;
    student: { student_number: string; student_name: string };
    book: { book_isbn: string; book_title: string; publisher?: { publisher_name: string } | null };
    fine: { calculated_fine: string | number; payment_status: string } | null;
};

export default function LoanShow({ loan }: { loan: Loan }) {
    const rows: DetailRow[] = [
        { label: 'Student', value: `${loan.student.student_name} (${loan.student.student_number})` },
        { label: 'Book', value: `${loan.book.book_title} (${loan.book.book_isbn})` },
        { label: 'Publisher', value: loan.book.publisher?.publisher_name ?? '—' },
        { label: 'Issue date', value: loan.issue_date.slice(0, 10) },
        { label: 'Due date', value: loan.due_date.slice(0, 10) },
        { label: 'Return date', value: loan.return_date?.slice(0, 10) ?? 'Not returned' },
        { label: 'Fine per day', value: loan.fine_per_day },
        { label: 'Fine record', value: loan.fine ? `${loan.fine.calculated_fine} (${loan.fine.payment_status})` : 'None' },
    ];

    return <ResourceShow title={`Loan #${loan.id}`} path="/loans" editPath={`/loans/${loan.id}/edit`} rows={rows} />;
}
