<script setup lang="ts">
import ModuleLayout from '../../Layouts/ModuleLayout.vue';
import MoneyText from '../../Components/MoneyText.vue';
import TypeBadge from '../../Components/TypeBadge.vue';
import CategoryIcon from '../../Components/CategoryIcon.vue';
import ProgressBar from '../../Components/ProgressBar.vue';
import ProgressCircle from '../../Components/ProgressCircle.vue';
import TransactionForm from '../../Components/TransactionForm.vue';
import BaseChart from '../../Components/BaseChart.vue';
import { useTransactions } from '../../Composables/useTransactions';
import { useBudgets } from '../../Composables/useBudgets';
import { formatDate } from '../../Lib/format';
import { Link, router } from '@inertiajs/vue3';
import { Wallet, Plus, ArrowUpRight, ArrowDownRight, TrendingUp, ChevronRight, PiggyBank, Repeat, Sparkles } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    date_range: { period: string; from: string; to: string };
    balance: {
        total_balance: number;
        count: number;
        accounts: Array<{ id: number; name: string; type: string; balance: number; color?: string }>;
    };
    month: { income: number; expense: number; net: number; savings_rate: number };
    net_worth: { value: number; vs_last_month: string; change: number };
    spending_velocity: { spent_so_far: number; total_budget: number; days_elapsed: number; days_in_period: number; projected_total: number };
    upcoming_recurring: Array<{ id: number; name: string; type: string; amount: number; next_run_at: string; account: { id: number; name: string; color: string } | null }>;
    recentTransactions: any[];
    topBudgets: Array<{ budget_id: number; category: string; amount: number; actual: number; usage_percent: number; is_over: boolean }>;
    categories: any[];
    accounts: Array<{ id: number; name: string; type: string; color?: string }>;
    onboarding: { sample_data_loaded: boolean; has_transactions: boolean };
}>();

const { openCreate } = useTransactions();
const { progressColor } = useBudgets();

const net = computed(() => Number(props.month.income) - Number(props.month.expense));

const chartData = computed(() => ({
    labels: ['Income', 'Expense', 'Net'],
    datasets: [{
        label: 'Period',
        data: [props.month.income, props.month.expense, Math.max(props.month.net, 0)],
        backgroundColor: ['#10b981', '#f43f5e', '#0ea5e9'],
        borderRadius: 8,
    }],
}));

const chartOptions = {
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true } },
};

const defaultAccountId = computed(() => props.accounts.find((a) => a.id)?.id ?? '');

// Savings rate color: green >20, orange 10-20, red <10
const savingsColor = computed(() => {
    const r = props.month.savings_rate;
    if (r >= 20) return '#10b981';
    if (r >= 10) return '#f59e0b';
    return '#ef4444';
});

const netWorthUp = computed(() => props.net_worth.vs_last_month === 'up' || props.net_worth.change > 0);

const periods = [
    { id: 'today', label: 'Today' },
    { id: 'week', label: 'This Week' },
    { id: 'month', label: 'This Month' },
    { id: 'custom', label: 'Custom' },
];

function switchPeriod(period: string) {
    router.visit(route('personal.dashboard', { period }), {
        preserveState: true,
        preserveScroll: true,
        only: ['date_range', 'balance', 'month', 'net_worth', 'spending_velocity', 'upcoming_recurring', 'recentTransactions', 'topBudgets'],
    });
}

function daysUntil(value: string): string {
    const diff = Math.ceil((new Date(value).getTime() - Date.now()) / (1000 * 60 * 60 * 24));
    if (diff <= 0) return 'due now';
    return `in ${diff}d`;
}

function quickAdd(type: 'income' | 'expense' | 'transfer') {
    openCreate({ type, account_id: defaultAccountId.value });
}
</script>

<template>
    <ModuleLayout title="Dashboard" :breadcrumbs="[{ title: 'Personal', href: '/personal/dashboard' }, { title: 'Dashboard', href: '/personal/dashboard' }]">
        <div class="space-y-6">
            <!-- Onboarding banner -->
            <section
                v-if="!onboarding.sample_data_loaded && !onboarding.has_transactions"
                class="flex flex-col gap-4 rounded-2xl border border-indigo-300/60 bg-indigo-50/50 p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between dark:border-indigo-500/30 dark:bg-indigo-500/5"
            >
                <div class="flex items-start gap-3">
                    <div class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-500">
                        <Sparkles class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-foreground">Welcome to Personal Accounting 👋</p>
                        <p class="mt-0.5 text-sm text-muted-foreground">
                            Start from scratch, or load a set of sample transactions so you can explore every feature right away.
                        </p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                        @click="router.post(route('personal.dashboard.load-sample-data'))"
                    >
                        <Sparkles class="h-4 w-4" /> Load sample data
                    </button>
                </div>
            </section>

            <!-- Period switcher -->
            <div class="flex flex-wrap items-center gap-2">
                <button v-for="p in periods" :key="p.id" type="button"
                    class="rounded-full px-4 py-1.5 text-xs font-semibold transition"
                    :class="date_range.period === p.id ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-muted/70'"
                    @click="switchPeriod(p.id)">
                    {{ p.label }}
                </button>
                <span class="ml-auto text-xs text-muted-foreground">
                    {{ date_range.from }} → {{ date_range.to }}
                </span>
            </div>

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
                                <p class="mt-0.5 text-lg font-semibold"><MoneyText :value="month.net" compact /></p>
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

            <!-- Net worth + savings rate cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Net worth</p>
                            <p class="mt-1 text-3xl font-bold text-foreground"><MoneyText :value="net_worth.value" compact /></p>
                            <p class="mt-1 inline-flex items-center gap-1 text-xs font-medium"
                                :class="netWorthUp ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                <ArrowUpRight v-if="netWorthUp" class="h-3.5 w-3.5" />
                                <ArrowDownRight v-else class="h-3.5 w-3.5" />
                                {{ net_worth.change >= 0 ? '+' : '' }}<MoneyText :value="net_worth.change" compact /> vs last month
                            </p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg" :class="netWorthUp ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400'">
                            <TrendingUp class="h-5 w-5" />
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Savings rate</p>
                            <p class="mt-1 text-3xl font-bold" :style="{ color: savingsColor }">{{ month.savings_rate }}%</p>
                            <p class="mt-1 text-xs text-muted-foreground">of income saved this period</p>
                        </div>
                        <ProgressCircle :value="month.savings_rate" :color="savingsColor" :size="72" :stroke="8" />
                    </div>
                </div>
            </div>

            <!-- Spending velocity -->
            <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-foreground">Spending velocity</h2>
                        <p class="text-xs text-muted-foreground">
                            You've spent <MoneyText :value="spending_velocity.spent_so_far" compact /> of <MoneyText :value="spending_velocity.total_budget" compact /> budget. At this pace you'll spend <MoneyText :value="spending_velocity.projected_total" compact /> this month.
                        </p>
                    </div>
                    <span class="text-xs text-muted-foreground">{{ spending_velocity.days_elapsed }}/{{ spending_velocity.days_in_period }} days</span>
                </div>
                <div class="mt-3">
                    <ProgressBar :value="spending_velocity.total_budget > 0 ? (spending_velocity.spent_so_far / spending_velocity.total_budget) * 100 : 0" :color="spending_velocity.total_budget > 0 && (spending_velocity.spent_so_far / spending_velocity.total_budget) * 100 > 100 ? '#ef4444' : '#6366f1'" height="h-2.5" />
                </div>
            </div>

            <!-- Upcoming bills + budget / chart -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Upcoming bills -->
                <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-foreground">Upcoming bills</h2>
                        <Link :href="route('personal.recurring.index')" class="inline-flex items-center text-xs font-medium text-primary hover:underline">All <ChevronRight class="h-3.5 w-3.5" /></Link>
                    </div>
                    <div v-if="upcoming_recurring.length" class="space-y-3">
                        <div v-for="r in upcoming_recurring" :key="r.id" class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg" :class="r.type === 'income' ? 'bg-emerald-500/10 text-emerald-600' : r.type === 'transfer' ? 'bg-sky-500/10 text-sky-600' : 'bg-rose-500/10 text-rose-600'">
                                <Repeat class="h-4 w-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-foreground">{{ r.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ r.account?.name ?? '—' }} · <span class="text-primary">{{ daysUntil(r.next_run_at) }}</span></p>
                            </div>
                            <MoneyText :value="r.amount" :type="r.type" compact class="font-semibold" />
                        </div>
                    </div>
                    <div v-else class="py-6 text-center text-sm text-muted-foreground">
                        <Sparkles class="mx-auto mb-2 h-6 w-6 text-muted-foreground" />
                        No bills due in the next 7 days.
                    </div>
                </div>

                <!-- Income vs Expense chart -->
                <div class="rounded-xl border border-border bg-card p-5 shadow-sm lg:col-span-2">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-semibold text-foreground">Income vs Expenses</h2>
                            <p class="text-xs text-muted-foreground">{{ date_range.from }} → {{ date_range.to }}</p>
                        </div>
                    </div>
                    <BaseChart type="bar" :data="chartData" :options="chartOptions" :height="240" />
                </div>
            </div>

            <!-- Budgets -->
            <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-foreground">Budgets</h2>
                    <Link :href="route('personal.budgets.index')" class="inline-flex items-center text-xs font-medium text-primary hover:underline">
                        All <ChevronRight class="h-3.5 w-3.5" />
                    </Link>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
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
                    <div v-if="topBudgets.length === 0" class="text-center text-sm text-muted-foreground">No budgets yet.</div>
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
                            <p class="text-xs text-muted-foreground">
                                {{ formatDate(txn.date) }} ·
                                <template v-if="txn.type === 'transfer'">
                                    {{ txn.account?.name }} → {{ txn.to_account?.name }}
                                </template>
                                <template v-else>{{ txn.account?.name }}</template>
                            </p>
                        </div>
                        <div class="text-right">
                            <MoneyText :value="txn.amount" :type="txn.type" :signed="txn.type !== 'transfer'" class="font-semibold" />
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
