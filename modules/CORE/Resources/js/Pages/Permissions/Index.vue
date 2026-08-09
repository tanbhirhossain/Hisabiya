<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import DataTable, { type DataTableColumn, type DataTableFilters } from '../../Components/DataTable.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem, Permission, Paginated } from '@/types';
import { KeyRound, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    permissions: Paginated<Permission>;
    filters: Record<string, unknown>;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Permissions', href: '/admin/permissions' }];

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Permission', sortable: true },
    { key: 'guard_name', label: 'Guard', sortable: true },
    { key: 'roles_count', label: 'Roles', sortable: false },
    { key: 'created_at', label: 'Created', sortable: true },
];

const confirmDelete = ref(false);
const deletingId = ref<number | null>(null);
const deleting = ref(false);

function onChange(filters: DataTableFilters): void {
    router.get(route('permissions.index'), filters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['permissions', 'filters'],
    });
}

function openDelete(permission: Permission): void {
    deletingId.value = permission.id;
    confirmDelete.value = true;
}

function confirm(): void {
    deleting.value = true;
    router.delete(route('permissions.destroy', deletingId.value), {
        preserveScroll: true,
        onSuccess: () => {
            confirmDelete.value = false;
            deleting.value = false;
        },
        onFinish: () => (deleting.value = false),
    });
}

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Permissions" />

        <div class="flex flex-col gap-6 p-4 md:p-6">
            <PageHeader title="Permissions" description="Every capability available in the system.">
                <template #actions>
                    <Button as-child>
                        <Link :href="route('permissions.create')">
                            <Plus class="mr-1.5 h-4 w-4" /> New Permission
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                <DataTable
                    :columns="columns"
                    :rows="permissions.data"
                    :links="permissions.links"
                    :meta="permissions.meta"
                    :filters="filters"
                    exportable
                    export-filename="permissions"
                    search-placeholder="Search permissions…"
                    @change="onChange"
                >
                    <template #cell.name="{ row }">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                <KeyRound class="h-4 w-4" />
                            </div>
                            <div>
                                <p class="font-medium text-foreground">{{ row.name }}</p>
                            </div>
                        </div>
                    </template>

                    <template #cell.guard_name="{ row }">
                        <span class="rounded-md bg-muted px-2 py-0.5 text-xs font-medium">{{ row.guard_name }}</span>
                    </template>

                    <template #cell.roles_count="{ row }">
                        <span class="rounded-md bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-700 dark:bg-violet-500/10 dark:text-violet-400">{{ row.roles_count ?? 0 }}</span>
                    </template>

                    <template #cell.created_at="{ row }">
                        <span class="text-xs text-muted-foreground">{{ formatDate(row.created_at) }}</span>
                    </template>

                    <template #actions="{ row }">
                        <div class="flex items-center justify-end gap-1">
                            <Button variant="ghost" size="icon" as-child>
                                <Link :href="route('permissions.edit', row.id)" title="Edit">
                                    <Pencil class="h-4 w-4" />
                                </Link>
                            </Button>
                            <Button variant="ghost" size="icon" class="text-destructive hover:text-destructive" title="Delete" @click="openDelete(row)">
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>

        <ConfirmDialog
            v-model:open="confirmDelete"
            title="Delete permission?"
            description="Roles that reference this permission will lose it."
            :loading="deleting"
            @confirm="confirm"
            @close="confirmDelete = false"
        />
    </AppLayout>
</template>
