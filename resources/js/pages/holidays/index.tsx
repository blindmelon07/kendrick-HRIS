import { Head, router } from '@inertiajs/react';
import {
    Badge,
    Button,
    Card,
    Field,
    Input,
    PageHeader,
    Select,
    Table,
    Td,
    Th,
} from '@/components/ui';
import { useRecordForm } from '@/hooks/use-record-form';
import { formatDate, labelFor } from '@/lib/format';
import holidays from '@/routes/timekeeping/holidays';
import type { Option } from '@/types';

type Holiday = { id: number; date: string; name: string; type: string };

export default function HolidaysIndex({
    holidays: rows,
    year,
    types,
}: {
    holidays: Holiday[];
    year: number;
    types: Option[];
}) {
    const { form, editingId, edit, cancel, submit, destroy } = useRecordForm(
        { date: '', name: '', type: 'regular' },
        {
            store: () => holidays.store.url(),
            update: (id) => holidays.update.url(id),
            destroy: (id) => holidays.destroy.url(id),
        },
    );

    return (
        <>
            <Head title="Holidays" />
            <PageHeader
                title={`Holidays ${year}`}
                description="Regular holidays are paid when unworked (daily-rated) and earn 200% when worked; special non-working days earn 130% when worked."
                actions={
                    <>
                        <Button
                            variant="secondary"
                            onClick={() =>
                                router.get(holidays.index.url(), {
                                    year: year - 1,
                                })
                            }
                        >
                            ← {year - 1}
                        </Button>
                        <Button
                            variant="secondary"
                            onClick={() =>
                                router.get(holidays.index.url(), {
                                    year: year + 1,
                                })
                            }
                        >
                            {year + 1} →
                        </Button>
                    </>
                }
            />
            <div className="grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <Table
                        isEmpty={rows.length === 0}
                        empty="No holidays entered for this year."
                        head={
                            <tr>
                                <Th>Date</Th>
                                <Th>Holiday</Th>
                                <Th>Type</Th>
                                <Th />
                            </tr>
                        }
                    >
                        {rows.map((holiday) => (
                            <tr key={holiday.id}>
                                <Td className="whitespace-nowrap">
                                    {formatDate(holiday.date)}
                                </Td>
                                <Td className="font-medium">{holiday.name}</Td>
                                <Td>
                                    <Badge
                                        tone={
                                            holiday.type === 'regular'
                                                ? 'blue'
                                                : 'amber'
                                        }
                                    >
                                        {labelFor(types, holiday.type)}
                                    </Badge>
                                </Td>
                                <Td className="text-right whitespace-nowrap">
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        onClick={() =>
                                            edit(holiday.id, {
                                                date: holiday.date,
                                                name: holiday.name,
                                                type: holiday.type,
                                            })
                                        }
                                    >
                                        Edit
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        onClick={() => destroy(holiday.id)}
                                    >
                                        Delete
                                    </Button>
                                </Td>
                            </tr>
                        ))}
                    </Table>
                </Card>
                <Card title={editingId ? 'Edit holiday' : 'New holiday'}>
                    <form onSubmit={submit} className="flex flex-col gap-4">
                        <Field label="Date" error={form.errors.date}>
                            <Input
                                type="date"
                                value={form.data.date}
                                onChange={(event) =>
                                    form.setData('date', event.target.value)
                                }
                            />
                        </Field>
                        <Field label="Name" error={form.errors.name}>
                            <Input
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                            />
                        </Field>
                        <Field label="Type" error={form.errors.type}>
                            <Select
                                options={types}
                                value={form.data.type}
                                onChange={(event) =>
                                    form.setData('type', event.target.value)
                                }
                            />
                        </Field>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>
                                {editingId ? 'Update' : 'Add holiday'}
                            </Button>
                            {editingId && (
                                <Button variant="secondary" onClick={cancel}>
                                    Cancel
                                </Button>
                            )}
                        </div>
                    </form>
                </Card>
            </div>
        </>
    );
}
