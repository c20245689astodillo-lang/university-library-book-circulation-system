import ResourceIndex, { type Paginated, type ResourceColumn } from '@/components/library/resource-index';

type Student = {
    id: number;
    student_number: string;
    student_name: string;
    student_email: string;
    loans_count: number;
};

export default function StudentsIndex({ students }: { students: Paginated<Student> }) {
    const columns: ResourceColumn<Student>[] = [
        { label: 'Student number', render: (student) => student.student_number },
        { label: 'Name', render: (student) => student.student_name },
        { label: 'Email', render: (student) => student.student_email },
        { label: 'Loans', render: (student) => student.loans_count },
    ];

    return (
        <ResourceIndex
            title="Students"
            path="/students"
            rows={students}
            columns={columns}
        />
    );
}
