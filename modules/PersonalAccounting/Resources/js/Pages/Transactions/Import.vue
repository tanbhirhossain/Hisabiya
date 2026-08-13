<script setup lang="ts">
import ModuleLayout from '../../Layouts/ModuleLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';
import {
    UploadCloud,
    FileSpreadsheet,
    ArrowRight,
    ArrowLeft,
    CheckCircle2,
    XCircle,
    AlertTriangle,
    RefreshCcw,
} from 'lucide-vue-next';

const props = defineProps<{
    accounts: Array<{ id: number; name: string; type: string; currency: string }>;
    categories: Array<{ id: number; name: string; type: string }>;
}>();

// Wizard state
const step = ref(1);
const file = ref<File | null>(null);
const accountId = ref('');
const dragging = ref(false);

// Preview / mapping state
const filename = ref('');
const totalRows = ref(0);
const headers = ref<string[]>([]);
const columnMap = ref<Record<string, string>>({});
const previewRows = ref<any[]>([]);
const result = ref<{ imported: number; skipped: number; failed: number } | null>(null);
const loading = ref(false);
const error = ref('');

const columnFields = [
    { field: 'date', label: 'Date' },
    { field: 'amount', label: 'Amount' },
    { field: 'description', label: 'Description' },
    { field: 'type', label: 'Type' },
    { field: 'category', label: 'Category' },
];

const selectedAccount = computed(() => props.accounts.find((a) => a.id === accountId.value));

function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const f = target.files?.[0];
    if (f) {
        file.value = f;
        error.value = '';
    }
}

function onDrop(event: DragEvent) {
    dragging.value = false;
    const f = event.dataTransfer?.files?.[0];
    if (f) {
        file.value = f;
        error.value = '';
    }
}

async function uploadAndPreview() {
    if (!file.value) {
        error.value = 'Please choose a CSV file.';
        return;
    }
    if (!accountId.value) {
        error.value = 'Please select a target account.';
        return;
    }

    loading.value = true;
    error.value = '';
    const formData = new FormData();
    formData.append('file', file.value);
    formData.append('account_id', accountId.value);

    try {
        const res = await axios.post(route('personal.transactions.import.upload'), formData);
        filename.value = res.data.filename;
        totalRows.value = res.data.total_rows;
        headers.value = res.data.headers;
        columnMap.value = res.data.column_map;
        previewRows.value = res.data.preview;
        step.value = 2;
    } catch (e: any) {
        error.value = e.response?.data?.error ?? 'Upload failed. Please check the file.';
    } finally {
        loading.value = false;
    }
}

async function confirmImport() {
    loading.value = true;
    error.value = '';
    try {
        const res = await axios.post(route('personal.transactions.import.confirm'), {
            account_id: accountId.value,
            column_map: columnMap.value,
            rows: previewRows.value,
            filename: filename.value,
        });
        result.value = res.data.result;
        step.value = 4;
    } catch (e: any) {
        error.value = e.response?.data?.message ?? 'Import failed.';
        step.value = 3;
    } finally {
        loading.value = false;
    }
}

function reset() {
    step.value = 1;
    file.value = null;
    filename.value = '';
    result.value = null;
    error.value = '';
    columnMap.value = {};
    previewRows.value = [];
    totalRows.value = 0;
}

function previewCell(row: any, field: string): string {
    if (!row || !columnMap.value[field]) return '—';
    return row[columnMap.value[field]] ?? '—';
}
</script>

<template>
    <ModuleLayout title="Import Transactions" :breadcrumbs="[{ title: 'Personal', href: '/personal/dashboard' }, { title: 'Transactions', href: '/personal/transactions' }, { title: 'Import', href: '/personal/transactions/import' }]">
        <div class="mx-auto max-w-3xl space-y-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">Import bank statement</h1>
                    <p class="text-sm text-muted-foreground">Upload a CSV and map the columns to import transactions.</p>
                </div>
                <Link :href="route('personal.transactions.index')" class="text-sm font-medium text-primary hover:underline">← Back to transactions</Link>
            </div>

            <!-- Steps indicator -->
            <div class="flex items-center gap-2">
                <div v-for="(s, i) in ['Upload', 'Map Columns', 'Confirm', 'Result']" :key="i" class="flex items-center gap-2">
                    <div class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-semibold"
                        :class="step >= i + 1 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'">
                        {{ i + 1 }}
                    </div>
                    <span class="text-xs font-medium" :class="step >= i + 1 ? 'text-foreground' : 'text-muted-foreground'">{{ s }}</span>
                    <ArrowRight v-if="i < 3" class="h-3.5 w-3.5 text-muted-foreground" />
                </div>
            </div>

            <!-- STEP 1: Upload -->
            <div v-if="step === 1" class="rounded-xl border border-border bg-card p-6 shadow-sm">
                <label class="mb-2 block text-sm font-medium text-foreground">Target account</label>
                <select v-model="accountId" class="mb-4 w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                    <option value="" disabled>Select account to import into</option>
                    <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }} ({{ a.type }})</option>
                </select>

                <div
                    class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-6 py-10 text-center transition"
                    :class="dragging ? 'border-primary bg-primary/5' : 'border-border hover:bg-muted/40'"
                    @dragover.prevent="dragging = true"
                    @dragleave="dragging = false"
                    @drop.prevent="onDrop"
                    @click="$refs.fileInput?.click()"
                >
                    <input ref="fileInput" type="file" accept=".csv,text/csv" class="hidden" @change="onFileChange" />
                    <UploadCloud v-if="!file" class="h-10 w-10 text-muted-foreground" />
                    <FileSpreadsheet v-else class="h-10 w-10 text-emerald-500" />
                    <p class="mt-3 text-sm font-medium text-foreground">
                        {{ file ? file.name : 'Drag & drop your CSV here, or click to browse' }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">CSV files only, up to 4MB</p>
                </div>

                <p v-if="error" class="mt-3 flex items-center gap-1.5 text-sm text-rose-500"><AlertTriangle class="h-4 w-4" /> {{ error }}</p>

                <div class="mt-5 flex justify-end">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90 disabled:opacity-50"
                        :disabled="loading || !file"
                        @click="uploadAndPreview"
                    >
                        <UploadCloud class="h-4 w-4" /> {{ loading ? 'Uploading…' : 'Upload & Preview' }}
                    </button>
                </div>
            </div>

            <!-- STEP 2: Map Columns -->
            <div v-else-if="step === 2" class="rounded-xl border border-border bg-card p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-foreground">Map columns</h2>
                        <p class="text-xs text-muted-foreground">{{ filename }} · {{ totalRows }} rows detected</p>
                    </div>
                    <button type="button" class="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground transition hover:text-foreground" @click="reset">
                        <RefreshCcw class="h-3.5 w-3.5" /> Re-upload
                    </button>
                </div>

                <div class="space-y-3">
                    <div v-for="field in columnFields" :key="field.field" class="flex items-center gap-3">
                        <span class="w-28 text-sm font-medium text-foreground capitalize">{{ field.label }}</span>
                        <select v-model="columnMap[field.field]" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                            <option value="" disabled>Select CSV column</option>
                            <option v-for="h in headers" :key="h" :value="h">{{ h }}</option>
                        </select>
                    </div>
                </div>

                <h3 class="mt-6 mb-2 text-sm font-semibold text-foreground">Preview (first {{ previewRows.length }} rows)</h3>
                <div class="overflow-x-auto rounded-lg border border-border">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-border bg-muted/40 text-left font-semibold text-muted-foreground">
                                <th v-for="field in columnFields" :key="field.field" class="px-3 py-2 capitalize">{{ field.label }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, i) in previewRows" :key="i" class="border-b border-border last:border-0">
                                <td class="px-3 py-2" v-for="field in columnFields" :key="field.field">{{ previewCell(row, field.field) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p v-if="error" class="mt-3 flex items-center gap-1.5 text-sm text-rose-500"><AlertTriangle class="h-4 w-4" /> {{ error }}</p>

                <div class="mt-5 flex justify-between">
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-border px-4 py-2.5 text-sm font-medium text-muted-foreground transition hover:bg-muted" @click="step = 1">
                        <ArrowLeft class="h-4 w-4" /> Back
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90 disabled:opacity-50"
                        :disabled="!columnMap.date || !columnMap.amount"
                        @click="step = 3"
                    >
                        Continue <ArrowRight class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- STEP 3: Confirm -->
            <div v-else-if="step === 3" class="rounded-xl border border-border bg-card p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-foreground">Confirm import</h2>
                <div class="mt-4 rounded-lg border border-primary/30 bg-primary/5 p-4">
                    <p class="text-sm text-foreground">
                        Will import <strong class="font-semibold">{{ previewRows.length }}</strong> transaction(s) into
                        <strong class="font-semibold">{{ selectedAccount?.name ?? 'account' }}</strong>.
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">Date ← {{ columnMap.date }} · Amount ← {{ columnMap.amount }} · Description ← {{ columnMap.description || '—' }}</p>
                </div>

                <p v-if="error" class="mt-3 flex items-center gap-1.5 text-sm text-rose-500"><AlertTriangle class="h-4 w-4" /> {{ error }}</p>

                <div class="mt-5 flex justify-between">
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-border px-4 py-2.5 text-sm font-medium text-muted-foreground transition hover:bg-muted" @click="step = 2">
                        <ArrowLeft class="h-4 w-4" /> Back
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-50"
                        :disabled="loading"
                        @click="confirmImport"
                    >
                        <CheckCircle2 class="h-4 w-4" /> {{ loading ? 'Importing…' : 'Import Now' }}
                    </button>
                </div>
            </div>

            <!-- STEP 4: Result -->
            <div v-else-if="step === 4" class="rounded-xl border border-border bg-card p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-foreground">Import complete</h2>
                <div class="mt-4 grid grid-cols-3 gap-3">
                    <div class="rounded-lg bg-emerald-500/10 p-4 text-center">
                        <CheckCircle2 class="mx-auto h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                        <p class="mt-1 text-2xl font-bold text-foreground">{{ result?.imported ?? 0 }}</p>
                        <p class="text-xs text-muted-foreground">Imported</p>
                    </div>
                    <div class="rounded-lg bg-amber-500/10 p-4 text-center">
                        <AlertTriangle class="mx-auto h-6 w-6 text-amber-600 dark:text-amber-400" />
                        <p class="mt-1 text-2xl font-bold text-foreground">{{ result?.skipped ?? 0 }}</p>
                        <p class="text-xs text-muted-foreground">Skipped</p>
                    </div>
                    <div class="rounded-lg bg-rose-500/10 p-4 text-center">
                        <XCircle class="mx-auto h-6 w-6 text-rose-600 dark:text-rose-400" />
                        <p class="mt-1 text-2xl font-bold text-foreground">{{ result?.failed ?? 0 }}</p>
                        <p class="text-xs text-muted-foreground">Failed</p>
                    </div>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-border px-4 py-2.5 text-sm font-medium text-muted-foreground transition hover:bg-muted" @click="reset">
                        Import another
                    </button>
                    <Link :href="route('personal.transactions.index')" class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90">
                        View transactions <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>
            </div>
        </div>
    </ModuleLayout>
</template>
