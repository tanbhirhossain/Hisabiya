<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import DataTable, { type DataTableColumn, type DataTableFilters } from '../../Components/DataTable.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem, User, Paginated } from '@/types';
import { Pencil, Plus, Trash2, UserRound } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    users: Paginated<User>;
    options: { tenants: Array<{ id: number; name: string }>; roles: Array<{ id: number; name: string }> };
    filters: Record<string, unknown>;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Users', href: '/admin/users' }];

const columns: DataTableColumn[] = [
    { key: 'name', label: 'User', sortable: true },
    { key: 'email', label: 'Email', sortable: true },
    { key: 'roles', label: 'Roles', sortable: false },
    { key: 'tenant', label: 'Tenant', sortable: false },
    { key: 'is_active', label: 'Status', sortable: true },
    { key: 'created_at', label: 'Joined', sortable: true },
];

const confirmDelete = ref(false);
const deletingId = ref<number | null>(null);
const deleting = ref(false);

function onChange(filters: DataTableFilters): void {
    router.get(route('users.index'), filters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['users', 'filters'],
    });
}

function openDelete(user: User): void {
    deletingId.value = user.id;
    confirmDelete.value = true;
}

function confirm(): void {
    deleting.value = true;
    router.delete(route('users.destroy', deletingId.value), {
        preserveScroll: true,
        onSuccess: () => {
            confirmDelete.value = false;
            deleting.value = false;
        },
        onFinish: () => (deleting.value = false),
    });
}

function initials(name: string): string {
    return name
        .split(' ')
        .slice(0, 2)
        .map((n) => n[0])
        .join('')
        .toUpperCase();
}

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Users" />

        <div class="flex flex-col gap-6 p-4 md:p-6">
            <PageHeader title="Users" description="Manage platform users and the roles they hold.">
                <template #actions>
                    <Button as-child>
                        <Link :href="route('users.create')">
                            <Plus class="mr-1.5 h-4 w-4" /> New User
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                <DataTable
                    :columns="columns"
                    :rows="users.data"
                    :links="users.links"
                    :meta="users.meta"
                    :filters="filters"
                    exportable
                    export-filename="users"
                    search-placeholder="Search users…"
                    @change="onChange"
                >
                    <template #filters>
                        <div class="flex flex-wrap items-end gap-3">
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-muted-foreground">Role</label>
                                <select class="h-8 rounded-md border border-input bg-background px-2 text-xs outline-none" :value="(filters.role as string) ?? ''" @change="onChange({ ...filters, role: ($event.target as HTMLSelectElement).value || undefined, page: 1 })">
                                    <option value="">All roles</option>
                                    <option v-for="role in options.roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-muted-foreground">Tenant</label>
                                <select class="h-8 rounded-md border border-input bg-background px-2 text-xs outline-none" :value="(filters.tenant_id as string) ?? ''" @change="onChange({ ...filters, tenant_id: ($event.target as HTMLSelectElement).value || undefined, page: 1 })">
                                    <option value="">All tenants</option>
                                    <option v-for="tenant in options.tenants" :key="tenant.id" :value="tenant.id">{{ tenant.name }}</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-muted-foreground">Status</label>
                                <select class="h-8 rounded-md border border-input bg-background px-2 text-xs outline-none" :value="(filters.status as string) ?? ''" @change="onChange({ ...filters, status: ($event.target as HTMLSelectElement).value || undefined, page: 1 })">
                                    <option value="">All</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </template>

                    <template #cell.name="{ row }">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary">
                                {{ initials(row.name) }}
                            </div>
                            <span class="font-medium text-foreground">{{ row.name }}</span>
                        </div>
                    </template>

                    <template #cell.roles="{ row }">
                        <div class="flex flex-wrap gap-1">
                            <span v-for="role in row.roles" :key="role.id" class="rounded-md bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-700 dark:bg-violet-500/10 dark:text-violet-400">
                                {{ role.name }}
                            </span>
                            <span v-if="!row.roles || row.roles.length === 0" class="text-xs text-muted-foreground">—</span>
                        </div>
                    </template>

                    <template #cell.tenant="{ row }">
                        <span class="text-sm">{{ row.tenant?.name ?? '—' }}</span>
                    </template>

                    <template #cell.is_active="{ row }">
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset" :class="row.is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-zinc-100 text-zinc-600 ring-zinc-600/20 dark:bg-zinc-500/10 dark:text-zinc-400'">
                            <span class="h-1.5 w-1.5 rounded-full bg-current" />
                            {{ row.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </template>

                    <template #cell.created_at="{ row }">
                        <span class="text-xs text-muted-foreground">{{ formatDate(row.created_at) }}</span>
                    </template>

                    <template #actions="{ row }">
                        <div class="flex items-center justify-end gap-1">
                            <Button variant="ghost" size="icon" as-child>
                                <Link :href="route('users.edit', row.id)" title="Edit">
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
            title="Delete user?"
            description="This will permanently remove the user's access to the platform."
            :loading="deleting"
            @confirm="confirm"
            @close="confirmDelete = false"
        />
    </AppLayout>
</template>
