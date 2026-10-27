import ResourceShow, { type DetailRow } from '@/components/library/resource-show';

type Student = {
    id: number;
    student_number: string;
    student_name: string;
    student_email: string;
    loans: { id: number }[];
};

export default function StudentShow({ student }: { student: Student }) {
    const rows: DetailRow[] = [
        { label: 'Student number', value: student.student_number },
        { label: 'Name', value: student.student_name },
        { label: 'Email', value: student.student_email },
        { label: 'Total loans', value: student.loans.length },
    ];

    return <ResourceShow title={student.student_name} path="/students" editPath={`/students/${student.id}/edit`} rows={rows} />;
}
