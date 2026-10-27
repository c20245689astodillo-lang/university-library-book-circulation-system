import ResourceShow, { type DetailRow } from '@/components/library/resource-show';

type Fine = {
    id: number;
    calculated_fine: string | number;
    payment_status: string;
    loan: {
        id: number;
        student: { student_number: string; student_name: string };
        book: { book_isbn: string; book_title: string };
    };
};

export default function FineShow({ fine }: { fine: Fine }) {
    const rows: DetailRow[] = [
        { label: 'Loan', value: `#${fine.loan.id}` },
        { label: 'Student', value: `${fine.loan.student.student_name} (${fine.loan.student.student_number})` },
        { label: 'Book', value: `${fine.loan.book.book_title} (${fine.loan.book.book_isbn})` },
        { label: 'Fine amount', value: fine.calculated_fine },
        { label: 'Payment status', value: fine.payment_status },
    ];

    return <ResourceShow title={`Fine #${fine.id}`} path="/fines" editPath={`/fines/${fine.id}/edit`} rows={rows} />;
}
