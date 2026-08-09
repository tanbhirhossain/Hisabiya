<script setup lang="ts">
import ModuleLayout from '../../Layouts/ModuleLayout.vue';
import MoneyText from '../../Components/MoneyText.vue';
import TypeBadge from '../../Components/TypeBadge.vue';
import CategoryIcon from '../../Components/CategoryIcon.vue';
import ProgressBar from '../../Components/ProgressBar.vue';
import TransactionForm from '../../Components/TransactionForm.vue';
import BaseChart from '../../Components/BaseChart.vue';
import { useTransactions } from '../../Composables/useTransactions';
import { useBudgets } from '../../Composables/useBudgets';
import { formatDate, monthLabel } from '../../Lib/format';
import { Link } from '@inertiajs/vue3';
import { Wallet, Plus, ArrowUpRight, ArrowDownRight, TrendingUp, PiggyBank, ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    balance: {
        total_balance: number;
        count: number;
        accounts: Array<{ id: number; name: string; type: string; balance: number; color?: string }>;
    };
    month: { income: number; expense: number };
    recentTransactions: any[];
    topBudgets: Array<{ budget_id: number; category: string; amount: number; actual: number; usage_percent: number; is_over: boolean }>;
    categories: any[];
    accounts: Array<{ id: number; name: string; type: string; color?: string }>;
}>();

const { openCreate } = useTransactions();
const { progressColor } = useBudgets();

const net = computed(() => Number(props.month.income) - Number(props.month.expense));

const chartData = computed(() => ({
    labels: ['Income', 'Expense', 'Net'],
    datasets: [{
        label: 'This month',
        data: [props.month.income, props.month.expense, Math.max(net.value, 0)],
        backgroundColor: ['#10b981', '#f43f5e', '#0ea5e9'],
        borderRadius: 8,
    }],
}));

const chartOptions = {
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true } },
};

const defaultAccountId = computed(() => props.accounts.find((a) => a.id)?.id ?? '');

function quickAdd(type: 'income' | 'expense' | 'transfer') {
    openCreate({ type, account_id: defaultAccountId.value });
}
</script>

<template>
    <ModuleLayout title="Dashboard" :breadcrumbs="[{ title: 'Personal', href: '/personal/dashboard' }, { title: 'Dashboard', href: '/personal/dashboard' }]">
        <div class="space-y-6">
            <!-- Hero summary -->
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-600 via-teal-600 to-emerald-700 p-6 text-white shadow-lg md:p-8">
                <div class="pointer-events-none absolute -right-10 -top-10 h-48 w-48 rounded-full bg-white/10 blur-2xl" />
                <div class="relative flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="flex items-center gap-2 text-sm font-medium text-emerald-100">
                            <Wallet class="h-4 w-4" /> Total balance across {{ balance.count }} account(s)
                        </p>
                        <p class="mt-2 text-4xl font-bold tracking-tight">
                            <MoneyText :value="balance.total_balance" class="text-white" />
                        </p>
                        <div class="mt-4 flex flex-wrap gap-4">
                            <div class="rounded-xl bg-white/10 px-4 py-3 backdrop-blur">
                                <p class="flex items-center gap-1 text-xs text-emerald-100"><ArrowUpRight class="h-3.5 w-3.5" /> Income</p>
                                <p class="mt-0.5 text-lg font-semibold"><MoneyText :value="month.income" compact /></p>
                            </div>
                            <div class="rounded-xl bg-white/10 px-4 py-3 backdrop-blur">
                                <p class="flex items-center gap-1 text-xs text-emerald-100"><ArrowDownRight class="h-3.5 w-3.5" /> Expenses</p>
                                <p class="mt-0.5 text-lg font-semibold"><MoneyText :value="month.expense" compact /></p>
                            </div>
                            <div class="rounded-xl bg-white/10 px-4 py-3 backdrop-blur">
                                <p class="flex items-center gap-1 text-xs text-emerald-100"><TrendingUp class="h-3.5 w-3.5" /> Net</p>
                                <p class="mt-0.5 text-lg font-semibold"><MoneyText :value="net" compact /></p>
                            </div>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 self-start rounded-xl bg-white px-5 py-3 text-sm font-semibold text-emerald-700 shadow-md transition hover:bg-emerald-50 md:self-auto"
                        @click="quickAdd('expense')"
                    >
                        <Plus class="h-4 w-4" /> Quick add
                    </button>
                </div>
            </section>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Income vs Expense chart -->
                <div class="rounded-xl border border-border bg-card p-5 shadow-sm lg:col-span-2">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-semibold text-foreground">Income vs Expenses</h2>
                            <p class="text-xs text-muted-foreground">{{ monthLabel(new Date()) }}</p>
                        </div>
                    </div>
                    <BaseChart type="bar" :data="chartData" :options="chartOptions" :height="240" />
                </div>

                <!-- Budgets -->
                <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-foreground">Budgets</h2>
                        <Link :href="route('personal.budgets.index')" class="inline-flex items-center text-xs font-medium text-primary hover:underline">
                            All <ChevronRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                    <div class="space-y-4">
                        <div v-for="budget in topBudgets" :key="budget.budget_id">
                            <div class="mb-1.5 flex items-center justify-between text-sm">
                                <span class="font-medium text-foreground">{{ budget.category }}</span>
                                <span class="text-xs text-muted-foreground">{{ budget.usage_percent }}%</span>
                            </div>
                            <ProgressBar :value="budget.usage_percent" :color="progressColor(budget.usage_percent)" />
                            <p class="mt-1 text-xs text-muted-foreground">
                                <MoneyText :value="budget.actual" compact /> / <MoneyText :value="budget.amount" compact />
                            </p>
                        </div>
                        <div v-if="topBudgets.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                            No budgets yet.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent transactions -->
            <div class="rounded-xl border border-border bg-card shadow-sm">
                <div class="flex items-center justify-between border-b border-border px-5 py-4">
                    <div>
                        <h2 class="text-sm font-semibold text-foreground">Recent transactions</h2>
                        <p class="text-xs text-muted-foreground">Your latest 10 movements</p>
                    </div>
                    <Link :href="route('personal.transactions.index')" class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline">
                        View all <ChevronRight class="h-3.5 w-3.5" />
                    </Link>
                </div>
                <ul class="divide-y divide-border">
                    <li v-for="txn in recentTransactions" :key="txn.id" class="flex items-center gap-4 px-5 py-3">
                        <CategoryIcon :icon="txn.category?.icon" :color="txn.category?.color ?? txn.account?.color" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-foreground">{{ txn.note || txn.category?.name || txn.type }}</p>
                            <p class="text-xs text-muted-foreground">{{ formatDate(txn.date) }} · {{ txn.account?.name }}</p>
                        </div>
                        <div class="text-right">
                            <MoneyText :value="txn.amount" :type="txn.type" signed class="font-semibold" />
                        </div>
                    </li>
                    <li v-if="recentTransactions.length === 0" class="px-5 py-10 text-center text-sm text-muted-foreground">
                        No transactions yet. Tap <span class="font-medium text-primary">Quick add</span> to get started.
                    </li>
                </ul>
            </div>

            <!-- Floating action button -->
            <button
                type="button"
                class="fixed right-6 bottom-6 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-xl shadow-primary/30 transition hover:scale-105 hover:bg-primary/90"
                title="Add transaction"
                @click="quickAdd('expense')"
            >
                <Plus class="h-6 w-6" />
            </button>

            <TransactionForm :accounts="accounts" :categories="categories" />
        </div>
    </ModuleLayout>
</template>
