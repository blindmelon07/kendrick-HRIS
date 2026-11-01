import type { FormDataType } from '@inertiajs/core';
import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';

/**
 * Shared create/edit state for list pages with a side form.
 */
export function useRecordForm<T extends FormDataType<T>>(
    defaults: T,
    urls: {
        store: () => string;
        update: (id: number) => string;
        destroy: (id: number) => string;
    },
) {
    const form = useForm<T>(defaults);
    const [editingId, setEditingId] = useState<number | null>(null);

    function edit(id: number, values: T) {
        setEditingId(id);
        form.clearErrors();
        form.setData(values);
    }

    function cancel() {
        setEditingId(null);
        form.clearErrors();
        form.setData(defaults);
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: cancel };

        if (editingId) {
            form.put(urls.update(editingId), options);
        } else {
            form.post(urls.store(), options);
        }
    }

    function destroy(id: number, message = 'Delete this record?') {
        if (window.confirm(message)) {
            router.delete(urls.destroy(id), { preserveScroll: true });
        }
    }

    return { form, editingId, edit, cancel, submit, destroy };
}
