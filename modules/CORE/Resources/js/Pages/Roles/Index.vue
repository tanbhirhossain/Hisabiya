<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import DataTable, { type DataTableColumn, type DataTableFilters } from '../../Components/DataTable.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem, Role, Paginated } from '@/types';
import { Pencil, Plus, ShieldCheck, Trash2, Users } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    roles: Paginated<Role>;
    filters: Record<string, unknown>;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Roles', href: '/admin/roles' }];

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Role', sortable: true },
    { key: 'guard_name', label: 'Guard', sortable: true },
    { key: 'permissions_count', label: 'Permissions', sortable: false },
    { key: 'users_count', label: 'Users', sortable: false },
    { key: 'created_at', label: 'Created', sortable: true },
];

const confirmDelete = ref(false);
const deletingId = ref<number | null>(null);
const deleting = ref(false);

function onChange(filters: DataTableFilters): void {
    router.get(route('roles.index'), filters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['roles', 'filters'],
    });
}

function openDelete(role: Role): void {
    deletingId.value = role.id;
    confirmDelete.value = true;
}

function confirm(): void {
    deleting.value = true;
    router.delete(route('roles.destroy', deletingId.value), {
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
        <Head title="Roles" />

        <div class="flex flex-col gap-6 p-4 md:p-6">
            <PageHeader title="Roles" description="Define what each user can and cannot do.">
                <template #actions>
                    <Button as-child>
                        <Link :href="route('roles.create')">
                            <Plus class="mr-1.5 h-4 w-4" /> New Role
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                <DataTable
                    :columns="columns"
                    :rows="roles.data"
                    :links="roles.links"
                    :meta="roles.meta"
                    :filters="filters"
                    exportable
                    export-filename="roles"
                    search-placeholder="Search roles…"
                    @change="onChange"
                >
                    <template #cell.name="{ row }">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-violet-500/10 text-violet-600 dark:text-violet-400">
                                <ShieldCheck class="h-4 w-4" />
                            </div>
                            <span class="font-medium text-foreground">{{ row.name }}</span>
                        </div>
                    </template>

                    <template #cell.guard_name="{ row }">
                        <span class="rounded-md bg-muted px-2 py-0.5 text-xs font-medium">{{ row.guard_name }}</span>
                    </template>

                    <template #cell.permissions_count="{ row }">
                        <span class="rounded-md bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-700 dark:bg-sky-500/10 dark:text-sky-400">{{ row.permissions_count ?? 0 }}</span>
                    </template>

                    <template #cell.users_count="{ row }">
                        <span class="inline-flex items-center gap-1 text-sm text-muted-foreground"><Users class="h-3.5 w-3.5" />{{ row.users_count ?? 0 }}</span>
                    </template>

                    <template #cell.created_at="{ row }">
                        <span class="text-xs text-muted-foreground">{{ formatDate(row.created_at) }}</span>
                    </template>

                    <template #actions="{ row }">
                        <div class="flex items-center justify-end gap-1">
                            <Button variant="ghost" size="icon" as-child>
                                <Link :href="route('roles.edit', row.id)" title="Edit">
                                    <Pencil class="h-4 w-4" />
                                </Link>
                            </Button>
                            <Button v-if="row.name !== 'super-admin'" variant="ghost" size="icon" class="text-destructive hover:text-destructive" title="Delete" @click="openDelete(row)">
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>

        <ConfirmDialog
            v-model:open="confirmDelete"
            title="Delete role?"
            description="Users holding this role will lose its permissions."
            :loading="deleting"
            @confirm="confirm"
            @close="confirmDelete = false"
        />
    </AppLayout>
</template>
