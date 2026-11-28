import ResourceIndex, { type Paginated, type ResourceColumn } from '@/components/library/resource-index';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';

type Fine = {
    id: number;
    calculated_fine: string | number;
    payment_status: string;
    loan: {
        student: { student_number: string; student_name: string };
        book: { book_isbn: string; book_title: string };
    };
};

export default function FinesIndex({ fines, filters }: { fines: Paginated<Fine>; filters: { search?: string } }) {
    const columns: ResourceColumn<Fine>[] = [
        { label: 'Student', render: (fine) => `${fine.loan.student.student_name} (${fine.loan.student.student_number})` },
        { label: 'Book', render: (fine) => `${fine.loan.book.book_title} (${fine.loan.book.book_isbn})` },
        { label: 'Fine', render: (fine) => fine.calculated_fine },
        { label: 'Payment status', render: (fine) => fine.payment_status },
    ];

    return (
        <ResourceIndex
            title="Fines"
            path="/fines"
            rows={fines}
            columns={columns}
            search={filters.search ?? ''}
            canCreate={false}
            headerAction={
                <Button variant="outline" asChild>
                    <Link href="/archives?type=fines">View Archives</Link>
                </Button>
            }
            rowAction={(fine) =>
                fine.payment_status === 'paid' ? (
                    <Button variant="outline" size="sm" asChild>
                        <Link href={`/archives/fines/${fine.id}/archive`} method="post" as="button">
                            Archive
                        </Link>
                    </Button>
                ) : (
                    <Button variant="outline" size="sm" disabled title="Mark the fine as paid before archiving it">
                        Archive
                    </Button>
                )
            }
        />
    );
}
