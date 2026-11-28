import ResourceIndex, { type Paginated, type ResourceColumn } from '@/components/library/resource-index';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';

type Student = {
    id: number;
    student_number: string;
    student_name: string;
    student_email: string;
    loans_count: number;
    active_loans_count: number;
};

export default function StudentsIndex({
    students,
    currentStudentId,
    filters,
}: {
    students: Paginated<Student>;
    currentStudentId: number | null;
    filters: { search?: string };
}) {
    const columns: ResourceColumn<Student>[] = [
        { label: 'Student', render: (student) => `${student.student_name} (${student.student_number})` },
        { label: 'Email', render: (student) => student.student_email },
        { label: 'Loans', render: (student) => student.loans_count },
    ];

    return (
        <ResourceIndex
            title="Students"
            path="/students"
            rows={students}
            columns={columns}
            search={filters.search ?? ''}
            headerAction={(
                <Button variant="outline" asChild>
                    <Link href="/archives?type=students">View Archives</Link>
                </Button>
            )}
            canEdit={(student) => student.id === currentStudentId}
            rowAction={(student) => student.active_loans_count > 0 ? (
                <Button variant="outline" size="sm" disabled title="Return the student's books before archiving">Archive</Button>
            ) : (
                <Button variant="outline" size="sm" asChild>
                    <Link href={`/archives/students/${student.id}/archive`} method="post" as="button">Archive</Link>
                </Button>
            )}
        />
    );
}
