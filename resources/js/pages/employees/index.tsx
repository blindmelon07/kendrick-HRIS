import { Head, Link, router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import {
    Avatar,
    Badge,
    Button,
    Card,
    Input,
    PageHeader,
    Pagination,
    Select,
    Table,
    Td,
    Th,
    buttonClasses,
} from '@/components/ui';
import { formatDate } from '@/lib/format';
import employees from '@/routes/employees';
import type { Employee, NamedRecord, Option, Paginated } from '@/types';

type Props = {
    employees: Paginated<Employee>;
    filters: {
        search?: string;
        department_id?: string;
        employee_status?: string;
    };
    departments: NamedRecord[];
    statuses: Option[];
};

export default function EmployeesIndex({
    employees: page,
    filters,
    departments,
    statuses,
}: Props) {
    const [form, setForm] = useState({
        search: filters.search ?? '',
        department_id: filters.department_id ?? '',
        employee_status: filters.employee_status ?? 'active',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        router.get(employees.index.url(), form, { preserveState: true });
    }

    return (
        <>
            <Head title="Employee Records" />
            <PageHeader
                title="Employee Records"
                description="Employee information, employment details, payroll and statutory information."
                actions={
                    <Link
                        href={employees.create.url()}
                        className={buttonClasses()}
                    >
                        Add employee
                    </Link>
                }
            />

            <Card>
                <form
                    onSubmit={submit}
                    className="mb-5 flex flex-wrap items-center gap-2"
                >
                    <Input
                        placeholder="Search name or employee no."
                        value={form.search}
                        onChange={(event) =>
                            setForm({ ...form, search: event.target.value })
                        }
                        className="w-64"
                    />
                    <Select
                        options={departments.map((department) => ({
                            value: String(department.id),
                            label: department.name,
                        }))}
                        placeholder="All departments"
                        value={form.department_id}
                        onChange={(event) =>
                            setForm({
                                ...form,
                                department_id: event.target.value,
                            })
                        }
                        className="w-52"
                    />
                    <Select
                        options={statuses}
                        value={form.employee_status}
                        onChange={(event) =>
                            setForm({
                                ...form,
                                employee_status: event.target.value,
                            })
                        }
                        className="w-36"
                    />
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </form>

                <Table
                    isEmpty={page.data.length === 0}
                    empty="No employees match these filters."
                    head={
                        <tr>
                            <Th>Employee No.</Th>
                            <Th>Name</Th>
                            <Th>Job Title</Th>
                            <Th>Department</Th>
                            <Th>Branch</Th>
                            <Th>Date Hired</Th>
                            <Th>Status</Th>
                        </tr>
                    }
                >
                    {page.data.map((employee) => (
                        <tr
                            key={employee.id}
                            className="hover:bg-surface-muted"
                        >
                            <Td className="font-mono text-xs">
                                {employee.employee_number}
                            </Td>
                            <Td>
                                <Link
                                    href={employees.show.url(employee.id)}
                                    className="flex items-center gap-3 font-medium text-brand hover:underline"
                                >
                                    <Avatar
                                        name={employee.full_name}
                                        photoUrl={employee.photo_url}
                                        size="sm"
                                    />
                                    {employee.full_name}
                                </Link>
                            </Td>
                            <Td>{employee.job_title}</Td>
                            <Td>{employee.department?.name}</Td>
                            <Td>{employee.branch?.name}</Td>
                            <Td>{formatDate(employee.date_hired)}</Td>
                            <Td>
                                <Badge
                                    tone={
                                        employee.employee_status === 'active'
                                            ? 'green'
                                            : 'gray'
                                    }
                                >
                                    {employee.employee_status}
                                </Badge>
                            </Td>
                        </tr>
                    ))}
                </Table>
            </Card>
            <Pagination page={page} />
        </>
    );
}
