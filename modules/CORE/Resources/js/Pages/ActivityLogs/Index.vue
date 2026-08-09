<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import DataTable, { type DataTableColumn, type DataTableFilters } from '../../Components/DataTable.vue';
import type { BreadcrumbItem, ActivityLog, Paginated } from '@/types';
import { Activity, Clock, UserRound } from 'lucide-vue-next';

defineProps<{
    activities: Paginated<ActivityLog>;
    filters: Record<string, unknown>;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Activity Logs', href: '/admin/activity-logs' }];

const columns: DataTableColumn[] = [
    { key: 'description', label: 'Event', sortable: true },
    { key: 'event', label: 'Type', sortable: true },
    { key: 'causer', label: 'User', sortable: false },
    { key: 'created_at', label: 'When', sortable: true },
];

function onChange(filters: DataTableFilters): void {
    router.get(route('activity-logs.index'), filters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['activities', 'filters'],
    });
}

function formatDate(value: string): string {
    return new Date(value).toLocaleString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function eventColor(event?: string | null): string {
    switch (event) {
        case 'created':
            return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400';
        case 'updated':
            return 'bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400';
        case 'deleted':
            return 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400';
        case 'permissions':
            return 'bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400';
        default:
            return 'bg-zinc-100 text-zinc-600 dark:bg-zinc-500/10 dark:text-zinc-400';
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Activity Logs" />

        <div class="flex flex-col gap-6 p-4 md:p-6">
            <PageHeader title="Activity logs" description="A full audit trail of everything that happens on the platform." />

            <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                <DataTable
                    :columns="columns"
                    :rows="activities.data"
                    :links="activities.links"
                    :meta="activities.meta"
                    :filters="filters"
                    exportable
                    export-filename="activity-logs"
                    search-placeholder="Search activity…"
                    @change="onChange"
                >
                    <template #filters>
                        <div class="flex flex-wrap items-end gap-3">
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-muted-foreground">Type</label>
                                <select class="h-8 rounded-md border border-input bg-background px-2 text-xs outline-none" :value="(filters.event as string) ?? ''" @change="onChange({ ...filters, event: ($event.target as HTMLSelectElement).value || undefined, page: 1 })">
                                    <option value="">All types</option>
                                    <option value="created">Created</option>
                                    <option value="updated">Updated</option>
                                    <option value="deleted">Deleted</option>
                                    <option value="permissions">Permissions</option>
                                </select>
                            </div>
                        </div>
                    </template>

                    <template #cell.description="{ row }">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                <Activity class="h-4 w-4" />
                            </div>
                            <span class="text-sm text-foreground">{{ row.description }}</span>
                        </div>
                    </template>

                    <template #cell.event="{ row }">
                        <span v-if="row.event" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="eventColor(row.event)">
                            {{ row.event }}
                        </span>
                        <span v-else class="text-xs text-muted-foreground">—</span>
                    </template>

                    <template #cell.causer="{ row }">
                        <span v-if="row.causer" class="inline-flex items-center gap-1.5 text-sm text-foreground">
                            <UserRound class="h-3.5 w-3.5 text-muted-foreground" />{{ row.causer.name }}
                        </span>
                        <span v-else class="text-xs text-muted-foreground">System</span>
                    </template>

                    <template #cell.created_at="{ row }">
                        <span class="inline-flex items-center gap-1 text-xs text-muted-foreground"><Clock class="h-3 w-3" />{{ formatDate(row.created_at) }}</span>
                    </template>
                </DataTable>
            </div>
        </div>
    </AppLayout>
</template>
