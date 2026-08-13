<script setup lang="ts">
import ModuleLayout from '../../Layouts/ModuleLayout.vue';
import MoneyText from '../../Components/MoneyText.vue';
import BaseChart from '../../Components/BaseChart.vue';
import { router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';
import { FileDown, RefreshCw, Mail, FileText } from 'lucide-vue-next';

const props = defineProps<{
    filters: { from: string; to: string };
    summary: { income: number; expense: number; net: number };
    incomeByCategory: Array<{ category: string; total: number }>;
    expenseByCategory: Array<{ category: string; total: number }>;
    monthlyTrend: { labels: string[]; income: number[]; expense: number[] };
    netWorth: Array<{ label: string; value: number }>;
    yearOverYear: { years: number[]; months: Array<{ month: string; current_income: number; current_expense: number; current_net: number; prev_income: number; prev_expense: number; prev_net: number }> };
    topSpending: Array<{ category: string; total: number; percent: number }>;
    cashFlow: { total_inflows: number; total_outflows: number; net_cash_flow: number; inflows: Array<{ category: string; total: number }>; outflows: Array<{ category: string; total: number }> };
    reportEmail: { enabled: boolean; day: number };
}>();

const from = ref(props.filters.from);
const to = ref(props.filters.to);
const activeTab = ref('overview');

const emailForm = useForm({
    personal_report_email_enabled: props.reportEmail.enabled,
    personal_report_email_day: props.reportEmail.day,
});

const categoryColors = ['#10b981', '#f43f5e', '#0ea5e9', '#f59e0b', '#8b5cf6', '#ec4899', '#14b8a6', '#6366f1'];

const expenseChart = computed(() => ({
    labels: props.expenseByCategory.map((c) => c.category),
    datasets: [{ data: props.expenseByCategory.map((c) => c.total), backgroundColor: categoryColors, borderWidth: 0 }],
}));
const incomeChart = computed(() => ({
    labels: props.incomeByCategory.map((c) => c.category),
    datasets: [{ data: props.incomeByCategory.map((c) => c.total), backgroundColor: categoryColors, borderWidth: 0 }],
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
    datasets: [{ label: 'Net worth', data: props.netWorth.map((n) => n.value), borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.1)', tension: 0.4, fill: true }],
}));

// Year vs Year grouped bar chart
const yoyChart = computed(() => {
    const [prevYear, curYear] = props.yearOverYear.years;
    return {
        labels: props.yearOverYear.months.map((m) => m.month),
        datasets: [
            { label: `${prevYear} net`, data: props.yearOverYear.months.map((m) => m.prev_net), backgroundColor: '#94a3b8', borderRadius: 4 },
            { label: `${curYear} net`, data: props.yearOverYear.months.map((m) => m.current_net), backgroundColor: '#6366f1', borderRadius: 4 },
        ],
    };
});

// Top spending horizontal bar
const topSpendingChart = computed(() => ({
    labels: props.topSpending.map((c) => c.category),
    datasets: [{ data: props.topSpending.map((c) => c.total), backgroundColor: '#f43f5e', borderRadius: 4 }],
}));

const donutOptions = { plugins: { legend: { position: 'bottom', labels: { boxWidth: 10 } } } };
const lineOptions = { plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } };
const barOptions = { indexAxis: 'y' as const, plugins: { legend: { display: false } } };

const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'yoy', label: 'Year vs Year' },
    { id: 'top', label: 'Top Spending' },
    { id: 'cashflow', label: 'Cash Flow' },
];

function applyRange() {
    router.get(route('personal.reports.index'), { from: from.value, to: to.value }, {
        preserveState: true, preserveScroll: true, replace: true,
        only: ['summary', 'incomeByCategory', 'expenseByCategory', 'netWorth', 'yearOverYear', 'topSpending', 'cashFlow', 'filters'],
    });
}

async function exportPdf() {
    try {
        const res = await axios.post(
            route('personal.reports.export-pdf'),
            { from: from.value, to: to.value },
            { responseType: 'blob' },
        );

        // Trigger a browser download from the returned blob.
        const url = window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }));
        const a = document.createElement('a');
        a.href = url;
        a.download = 'personal-finance-report.pdf';
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
    } catch (e) {
        // eslint-disable-next-line no-console
        console.error('PDF export failed', e);
    }
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

function saveEmailSettings() {
    emailForm.post(route('personal.reports.email-settings'));
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
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-3 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90" @click="exportPdf">
                        <FileText class="h-4 w-4" /> Export PDF
                    </button>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-border px-3 py-2 text-sm font-medium text-muted-foreground transition hover:bg-muted" @click="exportCsv">
                        <FileDown class="h-4 w-4 text-emerald-600" /> CSV
                    </button>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-2 border-b border-border pb-2">
                <button v-for="t in tabs" :key="t.id" type="button" class="rounded-lg px-4 py-2 text-sm font-semibold transition"
                    :class="activeTab === t.id ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted'"
                    @click="activeTab = t.id">
                    {{ t.label }}
                </button>
            </div>

            <!-- OVERVIEW TAB -->
            <div v-if="activeTab === 'overview'" class="space-y-6">
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

                <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-semibold text-foreground">Net worth history</h2>
                    <BaseChart v-if="netWorth.length" type="line" :data="netWorthChart" :options="lineOptions" :height="260" />
                    <p v-else class="py-12 text-center text-sm text-muted-foreground">No data available.</p>
                </div>
            </div>

            <!-- YEAR VS YEAR TAB -->
            <div v-else-if="activeTab === 'yoy'" class="rounded-xl border border-border bg-card p-5 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-foreground">Net by month — {{ yearOverYear.years[0] }} vs {{ yearOverYear.years[1] }}</h2>
                <BaseChart type="bar" :data="yoyChart" :options="lineOptions" :height="320" />
            </div>

            <!-- TOP SPENDING TAB -->
            <div v-else-if="activeTab === 'top'" class="rounded-xl border border-border bg-card p-5 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-foreground">Top spending categories</h2>
                <BaseChart v-if="topSpending.length" type="bar" :data="topSpendingChart" :options="barOptions" :height="Math.max(200, topSpending.length * 60)" />
                <p v-else class="py-12 text-center text-sm text-muted-foreground">No spending in this range.</p>
                <div v-if="topSpending.length" class="mt-4 space-y-2">
                    <div v-for="c in topSpending" :key="c.category" class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">{{ c.category }}</span>
                        <span class="font-semibold text-foreground"><MoneyText :value="c.total" compact /> ({{ c.percent }}%)</span>
                    </div>
                </div>
            </div>

            <!-- CASH FLOW TAB -->
            <div v-else-if="activeTab === 'cashflow'" class="space-y-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                        <p class="text-sm text-muted-foreground">Total inflows</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400"><MoneyText :value="cashFlow.total_inflows" compact /></p>
                    </div>
                    <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                        <p class="text-sm text-muted-foreground">Total outflows</p>
                        <p class="mt-1 text-2xl font-bold text-rose-600 dark:text-rose-400"><MoneyText :value="cashFlow.total_outflows" compact /></p>
                    </div>
                    <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                        <p class="text-sm text-muted-foreground">Net cash flow</p>
                        <p class="mt-1 text-2xl font-bold text-foreground"><MoneyText :value="cashFlow.net_cash_flow" compact /></p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                        <h2 class="mb-3 text-sm font-semibold text-foreground">Income sources</h2>
                        <table class="w-full text-sm">
                            <tbody>
                                <tr v-for="i in cashFlow.inflows" :key="i.category" class="border-b border-border last:border-0">
                                    <td class="py-2 text-muted-foreground">{{ i.category }}</td>
                                    <td class="py-2 text-right font-semibold text-emerald-600 dark:text-emerald-400"><MoneyText :value="i.total" compact /></td>
                                </tr>
                                <tr v-if="cashFlow.inflows.length === 0"><td class="py-6 text-center text-muted-foreground">No inflows.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                        <h2 class="mb-3 text-sm font-semibold text-foreground">Expense categories</h2>
                        <table class="w-full text-sm">
                            <tbody>
                                <tr v-for="o in cashFlow.outflows" :key="o.category" class="border-b border-border last:border-0">
                                    <td class="py-2 text-muted-foreground">{{ o.category }}</td>
                                    <td class="py-2 text-right font-semibold text-rose-600 dark:text-rose-400"><MoneyText :value="o.total" compact /></td>
                                </tr>
                                <tr v-if="cashFlow.outflows.length === 0"><td class="py-6 text-center text-muted-foreground">No outflows.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Email settings -->
            <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="flex items-center gap-2 text-sm font-semibold text-foreground"><Mail class="h-4 w-4" /> Monthly report email</h2>
                        <p class="text-xs text-muted-foreground">Receive your monthly summary by email.</p>
                    </div>
                    <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-foreground">
                        <input v-model="emailForm.personal_report_email_enabled" type="checkbox" class="h-4 w-4 rounded accent-primary" />
                        Enabled
                    </label>
                </div>
                <div v-if="emailForm.personal_report_email_enabled" class="mt-3 flex flex-wrap items-center gap-3">
                    <label class="text-sm text-muted-foreground">Send on day</label>
                    <input v-model.number="emailForm.personal_report_email_day" type="number" min="1" max="28" class="w-20 rounded-lg border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                    <button type="button" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:opacity-50" :disabled="emailForm.processing" @click="saveEmailSettings">
                        Save settings
                    </button>
                </div>
            </div>
        </div>
    </ModuleLayout>
</template>
