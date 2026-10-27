import ResourceIndex, { type Paginated, type ResourceColumn } from '@/components/library/resource-index';

type Loan = {
    id: number;
    issue_date: string;
    due_date: string;
    return_date: string | null;
    student: { student_number: string; student_name: string };
    book: { book_isbn: string; book_title: string };
    fine: { calculated_fine: string | number; payment_status: string } | null;
};

export default function LoansIndex({ loans }: { loans: Paginated<Loan> }) {
    const columns: ResourceColumn<Loan>[] = [
        { label: 'Student', render: (loan) => `${loan.student.student_name} (${loan.student.student_number})` },
        { label: 'Book', render: (loan) => `${loan.book.book_title} (${loan.book.book_isbn})` },
        { label: 'Issued', render: (loan) => loan.issue_date.slice(0, 10) },
        { label: 'Due', render: (loan) => loan.due_date.slice(0, 10) },
        { label: 'Returned', render: (loan) => loan.return_date?.slice(0, 10) ?? 'Not returned' },
        { label: 'Fine', render: (loan) => loan.fine ? `${loan.fine.calculated_fine} (${loan.fine.payment_status})` : '—' },
    ];

    return <ResourceIndex title="Loans" path="/loans" rows={loans} columns={columns} />;
}
