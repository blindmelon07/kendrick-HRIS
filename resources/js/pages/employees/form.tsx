import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { useEffect, useState } from 'react';
import {
    Avatar,
    Button,
    Card,
    Checkbox,
    Field,
    Input,
    PageHeader,
    Select,
    Textarea,
    buttonClasses,
} from '@/components/ui';
import { formatTime } from '@/lib/format';
import employees from '@/routes/employees';
import type { Employee, NamedRecord, Option, Shift } from '@/types';

type Props = {
    employee:
        | (Employee & { user?: { id: number; email: string } | null })
        | null;
    branches: NamedRecord[];
    departments: NamedRecord[];
    shifts: Shift[];
    levels: Option[];
    employeeStatuses: Option[];
    employmentStatuses: Option[];
    payTypes: Option[];
    paymentMethods: Option[];
};

const toOptions = (records: NamedRecord[]): Option[] =>
    records.map((record) => ({ value: String(record.id), label: record.name }));

function Section({
    title,
    description,
    children,
}: {
    title: string;
    description?: string;
    children: ReactNode;
}) {
    return (
        <Card title={title} description={description}>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {children}
            </div>
        </Card>
    );
}

export default function EmployeeForm({
    employee,
    branches,
    departments,
    shifts,
    levels,
    employeeStatuses,
    employmentStatuses,
    payTypes,
    paymentMethods,
}: Props) {
    const form = useForm({
        employee_number: employee?.employee_number ?? '',
        first_name: employee?.first_name ?? '',
        middle_name: employee?.middle_name ?? '',
        last_name: employee?.last_name ?? '',
        suffix: employee?.suffix ?? '',
        birth_date: employee?.birth_date ?? '',
        gender: employee?.gender ?? '',
        civil_status: employee?.civil_status ?? '',
        mobile_number: employee?.mobile_number ?? '',
        email: employee?.email ?? '',
        address: employee?.address ?? '',
        branch_id: String(employee?.branch_id ?? ''),
        department_id: String(employee?.department_id ?? ''),
        shift_id: String(employee?.shift_id ?? ''),
        level: employee?.level ?? 'rank_and_file',
        job_title: employee?.job_title ?? '',
        employee_status: employee?.employee_status ?? 'active',
        employment_status: employee?.employment_status ?? 'probationary',
        date_hired: employee?.date_hired ?? '',
        date_separated: employee?.date_separated ?? '',
        pay_type: employee?.pay_type ?? 'daily',
        rate: employee?.rate ?? '',
        payment_method: employee?.payment_method ?? 'cash',
        bank_account_number: employee?.bank_account_number ?? '',
        sss_number: employee?.sss_number ?? '',
        philhealth_number: employee?.philhealth_number ?? '',
        pagibig_number: employee?.pagibig_number ?? '',
        tin: employee?.tin ?? '',
        has_statutory_deductions: employee?.has_statutory_deductions ?? false,
        custom_contributions: employee?.custom_contributions ?? false,
        sss_contribution: employee?.sss_contribution ?? '0',
        philhealth_contribution: employee?.philhealth_contribution ?? '0',
        pagibig_contribution: employee?.pagibig_contribution ?? '0',
        emergency_contact_name: employee?.emergency_contact_name ?? '',
        emergency_contact_relationship:
            employee?.emergency_contact_relationship ?? '',
        emergency_contact_number: employee?.emergency_contact_number ?? '',
        portal_email: employee?.user?.email ?? '',
        portal_password: '',
        photo: null as File | null,
        remove_photo: false,
    });
    const { data, setData, errors, processing } = form;

    function submit(event: FormEvent) {
        event.preventDefault();

        if (!employee) {
            form.post(employees.store.url());
        } else if (data.photo) {
            // File uploads must be sent as POST; Laravel treats _method=put as an update.
            form.transform((values) => ({ ...values, _method: 'put' }));
            form.post(employees.update.url(employee.id), {
                forceFormData: true,
            });
        } else {
            form.transform((values) => values);
            form.put(employees.update.url(employee.id));
        }
    }

    const [preview, setPreview] = useState<string | null>(null);

    useEffect(() => {
        if (!data.photo) {
            setPreview(null);

            return;
        }

        const url = URL.createObjectURL(data.photo);
        setPreview(url);

        return () => URL.revokeObjectURL(url);
    }, [data.photo]);

    const shownPhoto =
        preview ?? (data.remove_photo ? null : (employee?.photo_url ?? null));

    const text = (name: Exclude<keyof typeof data, 'photo'>) => ({
        value: String(data[name] ?? ''),
        onChange: (event: { target: { value: string } }) =>
            setData(name, event.target.value as never),
    });

    return (
        <>
            <Head
                title={employee ? `Edit ${employee.full_name}` : 'Add Employee'}
            />
            <PageHeader
                title={employee ? `Edit ${employee.full_name}` : 'Add Employee'}
                description="Fields marked as required must be completed before saving."
            />

            <form onSubmit={submit} className="flex flex-col gap-6">
                <Card
                    title="Profile Photo"
                    description="A clear, front-facing photo. JPG, PNG or WebP, up to 2 MB."
                >
                    <div className="flex flex-wrap items-center gap-5">
                        <Avatar
                            name={
                                [data.last_name, data.first_name]
                                    .filter(Boolean)
                                    .join(', ') || 'New employee'
                            }
                            photoUrl={shownPhoto}
                            size="lg"
                        />
                        <div className="flex flex-col gap-2">
                            <Field label="Choose photo" error={errors.photo}>
                                <Input
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    onChange={(event) => {
                                        setData(
                                            'photo',
                                            event.target.files?.[0] ?? null,
                                        );
                                        setData('remove_photo', false);
                                    }}
                                />
                            </Field>
                            {employee?.photo_url && !data.photo && (
                                <Checkbox
                                    label="Remove current photo"
                                    checked={data.remove_photo}
                                    onChange={(event) =>
                                        setData(
                                            'remove_photo',
                                            event.target.checked,
                                        )
                                    }
                                />
                            )}
                        </div>
                    </div>
                </Card>

                <Section title="Employee Information">
                    <Field
                        label="Employee No. *"
                        error={errors.employee_number}
                    >
                        <Input {...text('employee_number')} />
                    </Field>
                    <Field label="First name *" error={errors.first_name}>
                        <Input {...text('first_name')} />
                    </Field>
                    <Field label="Middle name" error={errors.middle_name}>
                        <Input {...text('middle_name')} />
                    </Field>
                    <Field label="Last name *" error={errors.last_name}>
                        <Input {...text('last_name')} />
                    </Field>
                    <Field label="Suffix" error={errors.suffix}>
                        <Input {...text('suffix')} placeholder="Jr., III" />
                    </Field>
                    <Field label="Birth date" error={errors.birth_date}>
                        <Input type="date" {...text('birth_date')} />
                    </Field>
                    <Field label="Gender" error={errors.gender}>
                        <Select
                            options={[
                                { value: 'Male', label: 'Male' },
                                { value: 'Female', label: 'Female' },
                            ]}
                            placeholder="—"
                            {...text('gender')}
                        />
                    </Field>
                    <Field label="Civil status" error={errors.civil_status}>
                        <Select
                            options={[
                                'Single',
                                'Married',
                                'Widowed',
                                'Separated',
                            ].map((status) => ({
                                value: status,
                                label: status,
                            }))}
                            placeholder="—"
                            {...text('civil_status')}
                        />
                    </Field>
                    <Field label="Mobile number" error={errors.mobile_number}>
                        <Input {...text('mobile_number')} />
                    </Field>
                    <Field label="Personal email" error={errors.email}>
                        <Input type="email" {...text('email')} />
                    </Field>
                    <Field
                        label="Address"
                        error={errors.address}
                        className="sm:col-span-2"
                    >
                        <Textarea rows={2} {...text('address')} />
                    </Field>
                </Section>

                <Section title="Employment">
                    <Field label="Branch *" error={errors.branch_id}>
                        <Select
                            options={toOptions(branches)}
                            placeholder="Select…"
                            {...text('branch_id')}
                        />
                    </Field>
                    <Field label="Department *" error={errors.department_id}>
                        <Select
                            options={toOptions(departments)}
                            placeholder="Select…"
                            {...text('department_id')}
                        />
                    </Field>
                    <Field label="Job title *" error={errors.job_title}>
                        <Input {...text('job_title')} />
                    </Field>
                    <Field label="Level *" error={errors.level}>
                        <Select options={levels} {...text('level')} />
                    </Field>
                    <Field label="Shift *" error={errors.shift_id}>
                        <Select
                            options={shifts.map((shift) => ({
                                value: String(shift.id),
                                label: `${shift.name} (${formatTime(shift.start_time)}–${formatTime(shift.end_time)})`,
                            }))}
                            placeholder="Select…"
                            {...text('shift_id')}
                        />
                    </Field>
                    <Field
                        label="Employment status *"
                        error={errors.employment_status}
                    >
                        <Select
                            options={employmentStatuses}
                            {...text('employment_status')}
                        />
                    </Field>
                    <Field
                        label="Employee status *"
                        error={errors.employee_status}
                    >
                        <Select
                            options={employeeStatuses}
                            {...text('employee_status')}
                        />
                    </Field>
                    <Field label="Date hired *" error={errors.date_hired}>
                        <Input type="date" {...text('date_hired')} />
                    </Field>
                    {data.employee_status !== 'active' && (
                        <Field
                            label="Date separated *"
                            error={errors.date_separated}
                        >
                            <Input type="date" {...text('date_separated')} />
                        </Field>
                    )}
                </Section>

                <Section title="Payroll Information">
                    <Field label="Pay type *" error={errors.pay_type}>
                        <Select options={payTypes} {...text('pay_type')} />
                    </Field>
                    <Field
                        label={
                            data.pay_type === 'monthly'
                                ? 'Monthly rate *'
                                : 'Daily rate *'
                        }
                        error={errors.rate}
                        hint={
                            data.pay_type === 'per_biyahe'
                                ? 'Basis for 13th month and contributions (e.g. 600 Trece, 550 Tanza). Pay comes from uploaded trips.'
                                : undefined
                        }
                    >
                        <Input
                            type="number"
                            step="0.01"
                            min="0"
                            {...text('rate')}
                        />
                    </Field>
                    <Field
                        label="Payment method *"
                        error={errors.payment_method}
                    >
                        <Select
                            options={paymentMethods}
                            {...text('payment_method')}
                        />
                    </Field>
                    {data.payment_method === 'atm' && (
                        <Field
                            label="BDO account number *"
                            error={errors.bank_account_number}
                        >
                            <Input {...text('bank_account_number')} />
                        </Field>
                    )}
                </Section>

                <Section
                    title="Government & Statutory Information"
                    description="SSS, PhilHealth and Pag-IBIG are computed from the 2026 contribution tables using the employee's monthly salary, and deducted once a month on the 1st-half payroll (after one month from the date hired)."
                >
                    <Field label="SSS No." error={errors.sss_number}>
                        <Input {...text('sss_number')} />
                    </Field>
                    <Field
                        label="PhilHealth No."
                        error={errors.philhealth_number}
                    >
                        <Input {...text('philhealth_number')} />
                    </Field>
                    <Field
                        label="Pag-IBIG (HDMF) No."
                        error={errors.pagibig_number}
                    >
                        <Input {...text('pagibig_number')} />
                    </Field>
                    <Field label="TIN" error={errors.tin}>
                        <Input {...text('tin')} />
                    </Field>
                    <div className="flex flex-col justify-end gap-2 pb-2 sm:col-span-2">
                        <Checkbox
                            label="Apply statutory deductions"
                            checked={data.has_statutory_deductions}
                            onChange={(event) =>
                                setData(
                                    'has_statutory_deductions',
                                    event.target.checked,
                                )
                            }
                        />
                        <Checkbox
                            label="Use custom amounts instead of the contribution tables"
                            checked={data.custom_contributions}
                            onChange={(event) =>
                                setData(
                                    'custom_contributions',
                                    event.target.checked,
                                )
                            }
                        />
                    </div>
                    {data.custom_contributions && (
                        <>
                            <Field
                                label="SSS employee share (monthly)"
                                error={errors.sss_contribution}
                            >
                                <Input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    {...text('sss_contribution')}
                                />
                            </Field>
                            <Field
                                label="PhilHealth employee share (monthly)"
                                error={errors.philhealth_contribution}
                            >
                                <Input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    {...text('philhealth_contribution')}
                                />
                            </Field>
                            <Field
                                label="Pag-IBIG employee share (monthly)"
                                error={errors.pagibig_contribution}
                            >
                                <Input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    {...text('pagibig_contribution')}
                                />
                            </Field>
                        </>
                    )}
                </Section>

                <Section title="Emergency Contact">
                    <Field label="Name" error={errors.emergency_contact_name}>
                        <Input {...text('emergency_contact_name')} />
                    </Field>
                    <Field
                        label="Relationship"
                        error={errors.emergency_contact_relationship}
                    >
                        <Input {...text('emergency_contact_relationship')} />
                    </Field>
                    <Field
                        label="Contact number"
                        error={errors.emergency_contact_number}
                    >
                        <Input {...text('emergency_contact_number')} />
                    </Field>
                </Section>

                <Section
                    title="Kiosk Access"
                    description="Lets the employee sign in to review logs, file requests and view payslips. Leave blank to skip."
                >
                    <Field label="Login email" error={errors.portal_email}>
                        <Input
                            type="email"
                            autoComplete="off"
                            {...text('portal_email')}
                        />
                    </Field>
                    <Field
                        label={employee?.user ? 'New password' : 'Password'}
                        error={errors.portal_password}
                        hint={
                            employee?.user
                                ? 'Leave blank to keep the current password.'
                                : undefined
                        }
                    >
                        <Input
                            type="password"
                            autoComplete="new-password"
                            {...text('portal_password')}
                        />
                    </Field>
                </Section>

                <div className="flex justify-end gap-2">
                    <Link
                        href={
                            employee
                                ? employees.show.url(employee.id)
                                : employees.index.url()
                        }
                        className={buttonClasses('secondary')}
                    >
                        Cancel
                    </Link>
                    <Button type="submit" disabled={processing}>
                        {employee ? 'Update record' : 'Save record'}
                    </Button>
                </div>
            </form>
        </>
    );
}
