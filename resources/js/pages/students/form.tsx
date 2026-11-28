import ResourceForm, { type FormField, type FormValue } from '@/components/library/resource-form';

type Student = { id: number; student_number: string; student_name: string; student_email: string };
type Props = { student?: Student; suggestedStudentNumber?: string; studentNumberOptions?: string[] };

export default function StudentForm({ student, suggestedStudentNumber, studentNumberOptions = [] }: Props) {
    const editing = Boolean(student);
    const path = '/students';
    const action = student ? `${path}/${student.id}` : path;
    const values: Record<string, FormValue> = {
        student_number: student?.student_number ?? suggestedStudentNumber ?? '',
        student_name: student?.student_name ?? '',
        student_email: student?.student_email ?? '',
        password_confirmation: '',
    };
    const fields: FormField[] = [
        {
            name: 'student_number',
            label: 'Student number',
            type: editing ? 'text' : 'select',
            required: true,
            readOnly: !editing,
            maxLength: 20,
            description: editing ? undefined : 'Choose the next generated number to keep student numbers in order.',
            options: !editing
                ? studentNumberOptions.map((number, index) => ({ value: number, label: number, disabled: index > 0 }))
                : undefined,
        },
        { name: 'student_name', label: 'Full name', required: true, maxLength: 255 },
        {
            name: 'student_email',
            label: 'Gmail address',
            type: 'email',
            required: true,
            maxLength: 255,
            pattern:
                '(?=.{6,30}(?:\\x2b|\\x40))[A-Za-z0-9]+(?:\\x2e[A-Za-z0-9]+)*(?:\\x2b[A-Za-z0-9\\x2e\\x5f\\x2d]+)?\\x40[Gg][Mm][Aa][Ii][Ll]\\x2e[Cc][Oo][Mm]',
            title: 'Use a 6–30 character Gmail username ending exactly in @gmail.com. Optional +tags are allowed.',
            description: editing ? undefined : 'The initial password is uni123. Students can change it from this edit page after signing in.',
        },
    ];

    if (editing) {
        fields.push(
            {
                name: 'password',
                label: 'New password (optional)',
                type: 'password',
                autoComplete: 'new-password',
                description: 'Leave blank to keep the current password.',
            },
            {
                name: 'password_confirmation',
                label: 'Confirm new password',
                type: 'password',
                autoComplete: 'new-password',
            },
        );
    }

    return (
        <ResourceForm
            title={editing ? 'Edit student' : 'Create student'}
            path={path}
            action={action}
            method={editing ? 'put' : 'post'}
            values={values}
            fields={fields}
        />
    );
}
