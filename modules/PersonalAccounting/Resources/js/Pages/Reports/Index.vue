<script setup lang="ts">
import ModuleLayout from '../../Layouts/ModuleLayout.vue';
import MoneyText from '../../Components/MoneyText.vue';
import BaseChart from '../../Components/BaseChart.vue';
import { router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { FileDown, RefreshCw } from 'lucide-vue-next';

const props = defineProps<{
    filters: { from: string; to: string };
    summary: { income: number; expense: number; net: number };
    incomeByCategory: Array<{ category: string; total: number }>;
    expenseByCategory: Array<{ category: string; total: number }>;
    monthlyTrend: { labels: string[]; income: number[]; expense: number[] };
    netWorth: Array<{ label: string; value: number }>;
}>();

const from = ref(props.filters.from);
const to = ref(props.filters.to);

const categoryColors = ['#10b981', '#f43f5e', '#0ea5e9', '#f59e0b', '#8b5cf6', '#ec4899', '#14b8a6', '#6366f1'];

const expenseChart = computed(() => ({
    labels: props.expenseByCategory.map((c) => c.category),
    datasets: [{
        data: props.expenseByCategory.map((c) => c.total),
        backgroundColor: categoryColors,
        borderWidth: 0,
    }],
}));

const incomeChart = computed(() => ({
    labels: props.incomeByCategory.map((c) => c.category),
    datasets: [{
        data: props.incomeByCategory.map((c) => c.total),
        backgroundColor: categoryColors,
        borderWidth: 0,
    }],
}));

const trendChart = computed(() => ({
    labels: props.monthlyTrend.labels,
    datasets: [
        { label: 'Income', data: props.monthlyTrend.income, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', tension: 0.4, fill: true },
        { label: 'Expense', data: props.monthlyTrend.expense, borderColor: '#f43f5e', backgroundColor: 'rgba(244,63,94,0.1)', tension: 0.4, fill: true },
    ],
}));

const netWorthChart = computed(() => ({
    labels: props.netWorth.map((n) => n.label),
    datasets: [{
        label: 'Net worth',
        data: props.netWorth.map((n) => n.value),
        borderColor: '#6366f1',
        backgroundColor: 'rgba(99,102,241,0.1)',
        tension: 0.4,
        fill: true,
    }],
}));

const donutOptions = { plugins: { legend: { position: 'bottom', labels: { boxWidth: 10 } } } };
const lineOptions = { plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } };

function applyRange() {
    router.get(route('personal.reports.index'), { from: from.value, to: to.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['summary', 'incomeByCategory', 'expenseByCategory', 'netWorth', 'filters'],
    });
}

function exportCsv() {
    const rows = props.expenseByCategory.map((c) => ({ Category: c.category, Total: c.total }));
    const csv = ['Category,Total', ...rows.map((r) => `"${r.Category}","${r.Total}"`)].join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'personal-report.csv';
    a.click();
}
</script>

<template>
    <ModuleLayout title="Reports" :breadcrumbs="[{ title: 'Personal', href: '/personal/dashboard' }, { title: 'Reports', href: '/personal/reports' }]">
        <div class="space-y-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">Reports &amp; analytics</h1>
                    <p class="text-sm text-muted-foreground">Understand your income, spending and net worth.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <input v-model="from" type="date" class="rounded-lg border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                    <span class="text-muted-foreground">to</span>
                    <input v-model="to" type="date" class="rounded-lg border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-primary/10 px-3 py-2 text-sm font-semibold text-primary transition hover:bg-primary/20" @click="applyRange">
                        <RefreshCw class="h-4 w-4" /> Apply
                    </button>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-border px-3 py-2 text-sm font-medium text-muted-foreground transition hover:bg-muted" @click="exportCsv">
                        <FileDown class="h-4 w-4 text-emerald-600" /> Export
                    </button>
                </div>
            </div>

            <!-- Summary -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <p class="text-sm text-muted-foreground">Income</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400"><MoneyText :value="summary.income" compact /></p>
                </div>
                <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <p class="text-sm text-muted-foreground">Expenses</p>
                    <p class="mt-1 text-2xl font-bold text-rose-600 dark:text-rose-400"><MoneyText :value="summary.expense" compact /></p>
                </div>
                <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <p class="text-sm text-muted-foreground">Net</p>
                    <p class="mt-1 text-2xl font-bold text-foreground"><MoneyText :value="summary.net" compact /></p>
                </div>
            </div>

            <!-- Monthly trend -->
            <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-foreground">Monthly trend</h2>
                <BaseChart type="line" :data="trendChart" :options="lineOptions" :height="280" />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-semibold text-foreground">Expenses by category</h2>
                    <BaseChart v-if="expenseByCategory.length" type="doughnut" :data="expenseChart" :options="donutOptions" :height="260" />
                    <p v-else class="py-12 text-center text-sm text-muted-foreground">No expense data in this range.</p>
                </div>
                <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-semibold text-foreground">Income by category</h2>
                    <BaseChart v-if="incomeByCategory.length" type="doughnut" :data="incomeChart" :options="donutOptions" :height="260" />
                    <p v-else class="py-12 text-center text-sm text-muted-foreground">No income data in this range.</p>
                </div>
            </div>

            <!-- Net worth -->
            <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-foreground">Net worth history</h2>
                <BaseChart v-if="netWorth.length" type="line" :data="netWorthChart" :options="lineOptions" :height="260" />
                <p v-else class="py-12 text-center text-sm text-muted-foreground">No data available.</p>
            </div>
        </div>
    </ModuleLayout>
</template>
