import ResourceForm, { type FormField, type FormValue } from '@/components/library/resource-form';

type Student = { id: number; student_number: string; student_name: string; student_email: string };

export default function StudentForm({ student }: { student?: Student }) {
    const editing = Boolean(student);
    const path = '/students';
    const action = student ? `${path}/${student.id}` : path;
    const values: Record<string, FormValue> = {
        student_number: student?.student_number ?? '',
        student_name: student?.student_name ?? '',
        student_email: student?.student_email ?? '',
    };
    const fields: FormField[] = [
        { name: 'student_number', label: 'Student number', required: true },
        { name: 'student_name', label: 'Full name', required: true },
        { name: 'student_email', label: 'Email', type: 'email', required: true },
    ];

    return <ResourceForm title={editing ? 'Edit student' : 'Create student'} path={path} action={action} method={editing ? 'put' : 'post'} values={values} fields={fields} />;
}
