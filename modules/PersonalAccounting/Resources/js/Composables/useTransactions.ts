import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface TransactionPayload {
    type: 'income' | 'expense' | 'transfer';
    amount: number | string;
    account_id: number | string;
    to_account_id?: number | string | null;
    category_id?: number | string | null;
    date: string;
    note?: string;
    status?: 'cleared' | 'pending';
    is_recurring?: boolean;
    frequency?: string;
    end_type?: 'never' | 'on_date' | 'after_occurrences';
    end_date?: string;
    max_occurrences?: number | string;
    force?: boolean;
}

// Module-level singleton state so the page and the slide-over form share it.
const slideOpen = ref(false);
const editing = ref<Record<string, any> | null>(null);
const selected = ref<number[]>([]);

const form = useForm<TransactionPayload>({
    type: 'expense',
    amount: '',
    account_id: '',
    to_account_id: null,
    category_id: null,
    date: new Date().toISOString().slice(0, 10),
    note: '',
    status: 'cleared',
    is_recurring: false,
    frequency: 'monthly',
    end_type: 'never',
    end_date: '',
    max_occurrences: '',
});

function resetForm(initial?: Partial<TransactionPayload>) {
    form.reset();
    form.defaults();
    form.clearErrors();
    if (initial) {
        form.type = initial.type ?? 'expense';
        if (initial.account_id) form.account_id = initial.account_id;
    }
}

function openCreate(initial?: Partial<TransactionPayload>) {
    editing.value = null;
    resetForm(initial);
    slideOpen.value = true;
}

function openEdit(transaction: Record<string, any>) {
    editing.value = transaction;
    resetForm();
    form.type = transaction.type;
    form.amount = transaction.amount;
    form.account_id = transaction.account_id;
    form.to_account_id = transaction.to_account_id ?? null;
    form.category_id = transaction.category_id;
    form.date = transaction.date;
    form.note = transaction.note ?? '';
    form.status = transaction.status ?? 'cleared';
    form.is_recurring = transaction.is_recurring ?? false;
    form.frequency = transaction.frequency ?? 'monthly';
    form.end_type = transaction.end_type ?? 'never';
    form.end_date = transaction.end_date ?? '';
    form.max_occurrences = transaction.max_occurrences ?? '';
    slideOpen.value = true;
}

function close() {
    slideOpen.value = false;
    editing.value = null;
    resetForm();
}

function submit(force = false) {
    if (editing.value) {
        form.put(route('personal.transactions.update', editing.value.id), {
            preserveScroll: true,
            onSuccess: close,
        });
    } else {
        form.post(route('personal.transactions.store'), {
            preserveScroll: true,
            force,
            onSuccess: close,
        });
    }
}

function navigate(filters: Record<string, any>) {
    router.get(route('personal.transactions.index'), filters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['transactions', 'filters'],
    });
}

export function useTransactions() {
    return { form, slideOpen, editing, selected, openCreate, openEdit, close, submit, navigate };
}
