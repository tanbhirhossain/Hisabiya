import { useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

interface BudgetPayload {
    category_id: number | string;
    amount: number | string;
    period: string;
    start_date: string;
    end_date?: string;
}

/**
 * Shared logic for budget management (create + progress colouring).
 */
export function useBudgets() {
    const page = usePage();
    const modalOpen = ref(false);

    const form = useForm<BudgetPayload>({
        category_id: '',
        amount: '',
        period: 'monthly',
        start_date: new Date().toISOString().slice(0, 10),
        end_date: '',
    });

    function openCreate() {
        form.reset();
        form.defaults();
        modalOpen.value = true;
    }

    function close() {
        modalOpen.value = false;
        form.reset();
    }

    function submit() {
        form.post(route('personal.budgets.store'), {
            preserveScroll: true,
            onSuccess: close,
        });
    }

    function progressColor(usage: number): string {
        if (usage >= 100) return '#ef4444';
        if (usage > 70) return '#f59e0b';
        return '#10b981';
    }

    return { page, form, modalOpen, openCreate, close, submit, progressColor };
}
