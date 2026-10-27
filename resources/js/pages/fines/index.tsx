import ResourceIndex, { type Paginated, type ResourceColumn } from '@/components/library/resource-index';

type Fine = {
    id: number;
    calculated_fine: string | number;
    payment_status: string;
    loan: {
        student: { student_number: string; student_name: string };
        book: { book_isbn: string; book_title: string };
    };
};

export default function FinesIndex({ fines }: { fines: Paginated<Fine> }) {
    const columns: ResourceColumn<Fine>[] = [
        { label: 'Student', render: (fine) => `${fine.loan.student.student_name} (${fine.loan.student.student_number})` },
        { label: 'Book', render: (fine) => `${fine.loan.book.book_title} (${fine.loan.book.book_isbn})` },
        { label: 'Fine', render: (fine) => fine.calculated_fine },
        { label: 'Payment status', render: (fine) => fine.payment_status },
    ];

    return <ResourceIndex title="Fines" path="/fines" rows={fines} columns={columns} />;
}
