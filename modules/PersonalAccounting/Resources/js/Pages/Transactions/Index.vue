<script setup lang="ts">
import ModuleLayout from '../../Layouts/ModuleLayout.vue';
import MoneyText from '../../Components/MoneyText.vue';
import TypeBadge from '../../Components/TypeBadge.vue';
import CategoryIcon from '../../Components/CategoryIcon.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import TransactionForm from '../../Components/TransactionForm.vue';
import { useTransactions } from '../../Composables/useTransactions';
import { formatDate } from '../../Lib/format';
import { router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Plus, Search, Download, Trash2, FileSpreadsheet, FileDown, ChevronLeft, ChevronRight } from 'lucide-vue-next';

const props = defineProps<{
    transactions: any;
    filters: Record<string, any>;
    accounts: Array<{ id: number; name: string; type: string; color?: string }>;
    categories: Array<{ id: number; name: string; type: string; icon?: string; color?: string }>;
}>();

const { openCreate, openEdit, selected, navigate } = useTransactions();
const page = usePage();

const confirmOpen = ref(false);
const deleting = ref(false);

const filters = ref({ ...props.filters });

const currentType = ref(filters.value.type ?? '');

const canDelete = computed(() => selected.value.length > 0);

function resetFilters() {
    filters.value = {};
    currentType.value = '';
    navigate({});
}

function applyFilters() {
    const clean: Record<string, any> = {};
    Object.entries(filters.value).forEach(([k, v]) => {
        if (v !== '' && v !== null && v !== undefined) clean[k] = v;
    });
    navigate(clean);
}

function toggleSelect(id: number) {
    const i = selected.value.indexOf(id);
    if (i === -1) selected.value.push(id);
    else selected.value.splice(i, 1);
}

function toggleSelectAll() {
    const ids = props.transactions.data.map((t: any) => t.id);
    if (selected.value.length === ids.length) selected.value = [];
    else selected.value = [...ids];
}

function confirmBulkDelete() {
    deleting.value = true;
    router.post(route('personal.transactions.bulk-destroy'), { ids: selected.value }, {
        preserveScroll: true,
        onSuccess: () => {
            selected.value = [];
            confirmOpen.value = false;
            deleting.value = false;
        },
        onFinish: () => (deleting.value = false),
    });
}

function exportCsv() {
    const rows = props.transactions.data.map((t: any) => ({
        Date: t.date,
        Type: t.type,
        Category: t.category?.name ?? '',
        Account: t.account?.name ?? '',
        Note: t.note ?? '',
        Amount: t.amount,
    }));
    const header = Object.keys(rows[0] ?? { Date: '', Type: '', Category: '', Account: '', Note: '', Amount: '' });
    const csv = [header.join(','), ...rows.map((r: any) => header.map((h) => `"${String(r[h]).replace(/"/g, '""')}"`).join(','))].join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'transactions.csv';
    a.click();
    URL.revokeObjectURL(url);
}

function exportPdf() {
    const win = window.open('', '_blank');
    if (!win) return;
    const rows = props.transactions.data.map((t: any) => `<tr><td>${t.date}</td><td>${t.type}</td><td>${t.category?.name ?? ''}</td><td>${t.account?.name ?? ''}</td><td>${t.note ?? ''}</td><td>${Number(t.amount).toLocaleString('en-IN')}</td></tr>`).join('');
    win.document.write(`<html><head><title>Transactions</title><style>table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:6px;text-align:left}th{background:#f1f5f9}</style></head><body><h2>Transactions</h2><table><thead><tr><th>Date</th><th>Type</th><th>Category</th><th>Account</th><th>Note</th><th>Amount</th></tr></thead><tbody>${rows}</tbody></table><script>window.print();<\/script></body></html>`);
    win.document.close();
}

const pagination = computed(() => props.transactions);

function goto(url: string | null) {
    if (!url) return;
    const q = new URL(url).searchParams;
    navigate({ ...filters.value, page: Number(q.get('page')) || 1 });
}
</script>

<template>
    <ModuleLayout title="Transactions" :breadcrumbs="[{ title: 'Personal', href: '/personal/dashboard' }, { title: 'Transactions', href: '/personal/transactions' }]">
        <div class="space-y-5">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">Transactions</h1>
                    <p class="text-sm text-muted-foreground">Filter, manage and export your transactions.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-border bg-background px-3 py-2 text-sm font-medium text-muted-foreground transition hover:bg-muted" @click="exportCsv">
                        <FileSpreadsheet class="h-4 w-4 text-emerald-600" /> CSV
                    </button>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-border bg-background px-3 py-2 text-sm font-medium text-muted-foreground transition hover:bg-muted" @click="exportPdf">
                        <FileDown class="h-4 w-4 text-rose-600" /> PDF
                    </button>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90" @click="openCreate()">
                        <Plus class="h-4 w-4" /> Add
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="rounded-xl border border-border bg-card p-4 shadow-sm">
                <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
                    <div>
                        <label class="text-xs font-medium text-muted-foreground">From</label>
                        <input v-model="filters.from" type="date" class="mt-1 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-muted-foreground">To</label>
                        <input v-model="filters.to" type="date" class="mt-1 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-muted-foreground">Type</label>
                        <select v-model="filters.type" class="mt-1 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                            <option value="">All</option>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                            <option value="transfer">Transfer</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-muted-foreground">Category</label>
                        <select v-model="filters.category_id" class="mt-1 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                            <option value="">All</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-muted-foreground">Account</label>
                        <select v-model="filters.account_id" class="mt-1 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                            <option value="">All</option>
                            <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between">
                    <div class="relative">
                        <Search class="pointer-events-none absolute top-2.5 left-3 h-4 w-4 text-muted-foreground" />
                        <input v-model="filters.search" type="text" placeholder="Search notes…" class="w-full rounded-lg border border-input bg-background py-2 pl-9 pr-3 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                    </div>
                    <div class="flex gap-2">
                        <button type="button" class="rounded-lg px-3 py-2 text-sm font-medium text-muted-foreground transition hover:bg-muted" @click="resetFilters">Reset</button>
                        <button type="button" class="rounded-lg bg-primary/10 px-3 py-2 text-sm font-semibold text-primary transition hover:bg-primary/20" @click="applyFilters">Apply</button>
                    </div>
                </div>
            </div>

            <!-- Bulk actions -->
            <div v-if="canDelete" class="flex items-center gap-3 rounded-xl border border-destructive/30 bg-destructive/5 px-4 py-3">
                <p class="text-sm font-medium text-foreground">{{ selected.length }} selected</p>
                <button type="button" class="ml-auto inline-flex items-center gap-1.5 rounded-lg bg-destructive px-3 py-1.5 text-sm font-semibold text-destructive-foreground transition hover:bg-destructive/90" @click="confirmOpen = true">
                    <Trash2 class="h-4 w-4" /> Delete selected
                </button>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/40 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                <th class="w-10 px-4 py-3">
                                    <input type="checkbox" class="h-4 w-4 rounded accent-primary" :checked="selected.length === transactions.data.length && transactions.data.length > 0" @change="toggleSelectAll" />
                                </th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Account</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="txn in transactions.data" :key="txn.id" class="cursor-pointer border-b border-border transition last:border-0 hover:bg-muted/30" @click="openEdit(txn)">
                                <td class="px-4 py-3" @click.stop>
                                    <input type="checkbox" class="h-4 w-4 rounded accent-primary" :checked="selected.includes(txn.id)" @change="toggleSelect(txn.id)" />
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">{{ formatDate(txn.date) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <CategoryIcon :icon="txn.category?.icon" :color="txn.category?.color" size="sm" />
                                        <span class="font-medium text-foreground">{{ txn.category?.name ?? '—' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">{{ txn.account?.name }}</td>
                                <td class="px-4 py-3"><TypeBadge :type="txn.type" /></td>
                                <td class="px-4 py-3 text-right"><MoneyText :value="txn.amount" :type="txn.type" signed class="font-semibold" /></td>
                            </tr>
                            <tr v-if="transactions.data.length === 0">
                                <td colspan="6" class="px-4 py-12 text-center text-sm text-muted-foreground">No transactions match your filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="transactions.links?.length" class="flex items-center justify-between border-t border-border px-4 py-3">
                    <p class="text-xs text-muted-foreground">Showing {{ transactions.from ?? 0 }}–{{ transactions.to ?? 0 }} of {{ transactions.total ?? 0 }}</p>
                    <div class="flex items-center gap-1">
                        <button v-for="(link, i) in transactions.links" :key="i" type="button"
                            class="inline-flex h-8 items-center rounded-md px-2 text-xs transition"
                            :class="link.active ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted'"
                            :disabled="!link.url"
                            @click="goto(link.url)">
                            <span v-if="i === 0"><ChevronLeft class="h-4 w-4" /></span>
                            <span v-else-if="i === transactions.links.length - 1"><ChevronRight class="h-4 w-4" /></span>
                            <span v-else>{{ link.label }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <TransactionForm :accounts="accounts" :categories="categories" />
        <ConfirmDialog
            :open="confirmOpen"
            title="Delete selected transactions?"
            :description="`This will delete ${selected.length} transaction(s) and adjust account balances.`"
            :loading="deleting"
            @close="confirmOpen = false"
            @confirm="confirmBulkDelete"
        />
    </ModuleLayout>
</template>
