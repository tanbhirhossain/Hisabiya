<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { ArrowDown, ArrowUp, ArrowUpDown, ChevronLeft, ChevronRight, FileDown, FileSpreadsheet, Loader2, Search, X } from 'lucide-vue-next';

export interface DataTableColumn {
    key: string;
    label: string;
    sortable?: boolean;
    className?: string;
}

export interface DataTableFilters {
    search?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
    per_page?: number;
    page?: number;
    [key: string]: unknown;
}

const props = defineProps<{
    columns: DataTableColumn[];
    rows: any[];
    filters: DataTableFilters;
    links?: any[];
    meta?: { current_page?: number; last_page?: number; from?: number | null; to?: number | null; total?: number };
    searchPlaceholder?: string;
    exportFilename?: string;
    loading?: boolean;
    exportable?: boolean;
    sortable?: boolean;
}>();

const emit = defineEmits<{
    (e: 'change', filters: DataTableFilters): void;
}>();

const search = ref<string>((props.filters.search as string) ?? '');
const sort = ref<string>((props.filters.sort as string) ?? '');
const direction = ref<'asc' | 'desc'>((props.filters.direction as 'asc' | 'desc') ?? 'asc');
const perPage = ref<number>((props.filters.per_page as number) ?? 10);

let debounceTimer: ReturnType<typeof setTimeout> | undefined;

watch(
    search,
    (value) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            emit('change', { ...currentFilters(), search: value || undefined, page: 1 });
        }, 350);
    },
    { immediate: false },
);

function currentFilters(): DataTableFilters {
    return { ...props.filters };
}

function applySort(column: DataTableColumn): void {
    if (!column.sortable) return;
    const key = column.key;

    if (sort.value === key) {
        direction.value = direction.value === 'asc' ? 'desc' : 'asc';
    } else {
        sort.value = key;
        direction.value = 'asc';
    }

    emit('change', { ...currentFilters(), sort: sort.value, direction: direction.value, page: 1 });
}

function updatePerPage(): void {
    emit('change', { ...currentFilters(), per_page: perPage.value, page: 1 });
}

function clearSearch(): void {
    search.value = '';
}

function goTo(url: string | null): void {
    if (!url) return;
    const query = new URL(url).searchParams;
    emit('change', { ...currentFilters(), page: Number(query.get('page')) || 1 });
}

const sortIcon = computed(() => (col: DataTableColumn) => {
    if (!col.sortable) return null;
    if (sort.value !== col.key) return ArrowUpDown;
    return direction.value === 'asc' ? ArrowUp : ArrowDown;
});

const pageInfo = computed(() => {
    const from = props.meta?.from ?? 0;
    const to = props.meta?.to ?? 0;
    const total = props.meta?.total ?? 0;
    return { from, to, total };
});

// --- Export helpers (lazy-loaded to keep the initial bundle small) ----------
const exporting = ref<string | null>(null);

async function exportExcel(): Promise<void> {
    exporting.value = 'excel';
    try {
        const XLSX = await import('xlsx');
        const filename = props.exportFilename ?? 'export';
        const worksheet = XLSX.utils.json_to_sheet(props.rows);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Data');
        XLSX.writeFile(workbook, `${filename}.xlsx`);
    } finally {
        exporting.value = null;
    }
}

async function exportPdf(): Promise<void> {
    exporting.value = 'pdf';
    try {
        const [{ jsPDF }, { default: autoTable }] = await Promise.all([import('jspdf'), import('jspdf-autotable')]);
        const doc = new jsPDF({ orientation: 'landscape' });
        const head = [props.columns.map((col) => col.label)];
        const body = props.rows.map((row) => props.columns.map((col) => String(row[col.key] ?? '')));
        autoTable(doc, {
            head,
            body,
            styles: { fontSize: 8 },
            headStyles: { fillColor: [37, 99, 235] },
            margin: { top: 20 },
        });
        doc.text(props.exportFilename ?? 'Export', 14, 12);
        doc.save(`${props.exportFilename ?? 'export'}.pdf`);
    } finally {
        exporting.value = null;
    }
}

onBeforeUnmount(() => clearTimeout(debounceTimer));
</script>

<template>
    <div class="w-full">
        <!-- Toolbar -->
        <div class="flex flex-col gap-3 border-b border-border bg-muted/20 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative max-w-sm flex-1">
                <Search class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <input
                    v-model="search"
                    type="search"
                    :placeholder="searchPlaceholder ?? 'Search…'"
                    class="h-9 w-full rounded-md border border-input bg-background pr-8 pl-9 text-sm shadow-sm outline-none transition placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30"
                />
                <button
                    v-if="search"
                    type="button"
                    class="absolute top-1/2 right-2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                    @click="clearSearch"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <slot name="toolbar" />

                <div v-if="exportable" class="flex items-center gap-2">
                    <Button variant="outline" size="sm" :disabled="exporting !== null" @click="exportExcel">
                        <Loader2 v-if="exporting === 'excel'" class="mr-1.5 h-4 w-4 animate-spin" />
                        <FileSpreadsheet v-else class="mr-1.5 h-4 w-4 text-emerald-600" />
                        Excel
                    </Button>
                    <Button variant="outline" size="sm" :disabled="exporting !== null" @click="exportPdf">
                        <Loader2 v-if="exporting === 'pdf'" class="mr-1.5 h-4 w-4 animate-spin" />
                        <FileDown v-else class="mr-1.5 h-4 w-4 text-red-600" />
                        PDF
                    </Button>
                </div>
            </div>
        </div>

        <!-- Custom filters slot -->
        <div v-if="$slots.filters" class="flex flex-wrap items-end gap-3 border-b border-border bg-muted/20 px-4 py-3">
            <slot name="filters" />
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b border-border bg-muted/40 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            class="cursor-pointer px-4 py-3 font-semibold transition hover:text-foreground select-none"
                            :class="col.className ?? ''"
                            @click="applySort(col)"
                        >
                            <span class="inline-flex items-center gap-1.5">
                                {{ col.label }}
                                <component :is="sortIcon(col)" v-if="col.sortable" class="h-3.5 w-3.5" />
                            </span>
                        </th>
                        <th v-if="$slots.actions" class="px-4 py-3 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(row, index) in rows"
                        :key="row.id ?? index"
                        class="border-b border-border transition last:border-0 hover:bg-muted/30"
                    >
                        <td
                            v-for="col in columns"
                            :key="col.key"
                            class="px-4 py-3 align-middle"
                            :class="col.className ?? ''"
                        >
                            <slot :name="`cell.${col.key}`" :row="row" :value="row[col.key]">
                                {{ row[col.key] }}
                            </slot>
                        </td>
                        <td v-if="$slots.actions" class="px-4 py-3 text-right align-middle">
                            <slot name="actions" :row="row" />
                        </td>
                    </tr>

                    <tr v-if="rows.length === 0">
                        <td :colspan="columns.length + ($slots.actions ? 1 : 0)" class="px-4 py-16 text-center">
                            <slot name="empty">
                                <p class="text-sm text-muted-foreground">No records found.</p>
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer / pagination -->
        <div v-if="meta && meta.total !== undefined && meta.total > 0" class="flex flex-col gap-3 border-t border-border px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3 text-xs text-muted-foreground">
                <span>
                    Showing <strong class="font-semibold text-foreground">{{ pageInfo.from }}</strong>–<strong class="font-semibold text-foreground">{{ pageInfo.to }}</strong>
                    of <strong class="font-semibold text-foreground">{{ pageInfo.total }}</strong>
                </span>
                <select
                    v-model.number="perPage"
                    class="h-8 rounded-md border border-input bg-background px-2 text-xs outline-none focus-visible:ring-2 focus-visible:ring-ring/30"
                    @change="updatePerPage"
                >
                    <option :value="10">10 / page</option>
                    <option :value="15">15 / page</option>
                    <option :value="25">25 / page</option>
                    <option :value="50">50 / page</option>
                    <option :value="100">100 / page</option>
                </select>
            </div>

            <div v-if="links && links.length > 0" class="flex items-center gap-1">
                <template v-for="(link, i) in links" :key="i">
                    <button
                        v-if="i === 0 || i === links.length - 1"
                        type="button"
                        :disabled="!link.url"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-border text-muted-foreground transition disabled:cursor-not-allowed disabled:opacity-40 hover:bg-muted"
                        @click="goTo(link.url)"
                    >
                        <ChevronLeft v-if="i === 0" class="h-4 w-4" />
                        <ChevronRight v-else class="h-4 w-4" />
                    </button>
                    <button
                        v-else
                        type="button"
                        :class="link.active
                            ? 'inline-flex h-8 min-w-8 items-center justify-center rounded-md bg-primary px-2 text-xs font-medium text-primary-foreground shadow-sm'
                            : 'inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-xs text-muted-foreground transition hover:bg-muted hover:text-foreground'"
                        @click="goTo(link.url)"
                    >
                        {{ link.label }}
                    </button>
                </template>
            </div>
        </div>
    </div>
</template>
