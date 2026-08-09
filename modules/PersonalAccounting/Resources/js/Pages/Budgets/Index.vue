<script setup lang="ts">
import ModuleLayout from '../../Layouts/ModuleLayout.vue';
import MoneyText from '../../Components/MoneyText.vue';
import ProgressBar from '../../Components/ProgressBar.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import AddBudgetModal from '../../Components/AddBudgetModal.vue';
import { useBudgets } from '../../Composables/useBudgets';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Plus, Trash2, Target } from 'lucide-vue-next';

const props = defineProps<{
    budgets: Array<{ budget_id: number; category: string; amount: number; actual: number; remaining: number; usage_percent: number; is_over: boolean }>;
    categories: Array<{ id: number; name: string; icon?: string; color?: string }>;
}>();

const { progressColor } = useBudgets();
const modal = ref<InstanceType<typeof AddBudgetModal> | null>(null);
const confirmOpen = ref(false);
const deletingId = ref<number | null>(null);
const deleting = ref(false);

function confirmDelete(id: number) {
    deletingId.value = id;
    confirmOpen.value = true;
}

function doDelete() {
    deleting.value = true;
    router.delete(route('personal.budgets.destroy', deletingId.value), {
        preserveScroll: true,
        onSuccess: () => { confirmOpen.value = false; deleting.value = false; },
        onFinish: () => (deleting.value = false),
    });
}
</script>

<template>
    <ModuleLayout title="Budgets" :breadcrumbs="[{ title: 'Personal', href: '/personal/dashboard' }, { title: 'Budgets', href: '/personal/budgets' }]">
        <div class="space-y-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">Budgets</h1>
                    <p class="text-sm text-muted-foreground">Track your spending against monthly limits.</p>
                </div>
                <button type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90" @click="modal?.openModal()">
                    <Plus class="h-4 w-4" /> New budget
                </button>
            </div>

            <div v-if="budgets.length === 0" class="rounded-xl border border-dashed border-border p-12 text-center">
                <Target class="mx-auto h-10 w-10 text-muted-foreground" />
                <p class="mt-3 text-sm text-muted-foreground">No budgets yet. Create your first spending limit.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div v-for="budget in budgets" :key="budget.budget_id" class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-foreground">{{ budget.category }}</h3>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                <MoneyText :value="budget.actual" compact /> spent of <MoneyText :value="budget.amount" compact />
                            </p>
                        </div>
                        <button type="button" class="rounded-md p-1.5 text-muted-foreground transition hover:bg-muted hover:text-destructive" @click="confirmDelete(budget.budget_id)">
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="mt-3">
                        <ProgressBar :value="budget.usage_percent" :color="progressColor(budget.usage_percent)" height="h-2.5" />
                        <div class="mt-2 flex items-center justify-between text-xs">
                            <span class="font-semibold" :style="{ color: progressColor(budget.usage_percent) }">{{ budget.usage_percent }}% used</span>
                            <span class="text-muted-foreground">
                                <MoneyText :value="budget.remaining" compact /> left
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <AddBudgetModal ref="modal" :categories="categories" />
        <ConfirmDialog
            :open="confirmOpen"
            title="Delete this budget?"
            description="This will remove the spending limit."
            :loading="deleting"
            @close="confirmOpen = false"
            @confirm="doDelete"
        />
    </ModuleLayout>
</template>
