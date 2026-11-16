import ResourceForm, { type FormField, type FormValue } from '@/components/library/resource-form';

type Fine = { id: number; payment_status: 'unpaid' | 'paid' };

export default function FineForm({ fine }: { fine: Fine }) {
    const path = '/fines';
    const values: Record<string, FormValue> = {
        payment_status: fine.payment_status,
    };
    const fields: FormField[] = [
        { name: 'payment_status', label: 'Payment status', type: 'select', required: true, options: [{ value: 'unpaid', label: 'Unpaid' }, { value: 'paid', label: 'Paid' }] },
    ];

    return <ResourceForm title="Update payment status" path={path} action={`${path}/${fine.id}`} method="put" values={values} fields={fields} />;
}
