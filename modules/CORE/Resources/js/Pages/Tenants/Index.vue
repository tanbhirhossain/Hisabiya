<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import DataTable, { type DataTableColumn, type DataTableFilters } from '../../Components/DataTable.vue';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { type BreadcrumbItem, type Tenant, type Paginated } from '@/types';
import { Building2, Pencil, Plus, Trash2, Mail, Phone } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    tenants: Paginated<Tenant>;
    filters: Record<string, unknown>;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Tenants', href: '/admin/tenants' }];

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Tenant', sortable: true },
    { key: 'email', label: 'Contact', sortable: false },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'currency', label: 'Currency', sortable: true },
    { key: 'users_count', label: 'Users', sortable: false },
    { key: 'created_at', label: 'Created', sortable: true },
];

const confirmDelete = ref(false);
const deletingId = ref<number | null>(null);
const deleting = ref(false);

function onChange(filters: DataTableFilters): void {
    router.get(route('tenants.index'), filters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['tenants', 'filters'],
    });
}

function openDelete(tenant: Tenant): void {
    deletingId.value = tenant.id;
    confirmDelete.value = true;
}

function confirm(): void {
    deleting.value = true;
    router.delete(route('tenants.destroy', deletingId.value), {
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
        <Head title="Tenants" />

        <div class="flex flex-col gap-6 p-4 md:p-6">
            <PageHeader title="Tenants" description="Manage the organisations and workspaces that use your platform.">
                <template #actions>
                    <Button as-child>
                        <Link :href="route('tenants.create')">
                            <Plus class="mr-1.5 h-4 w-4" /> New Tenant
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                <DataTable
                    :columns="columns"
                    :rows="tenants.data"
                    :links="tenants.links"
                    :meta="tenants.meta"
                    :filters="filters"
                    exportable
                    export-filename="tenants"
                    search-placeholder="Search tenants…"
                    @change="onChange"
                >
                    <template #cell.name="{ row }">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <Building2 class="h-4 w-4" />
                            </div>
                            <div>
                                <p class="font-medium text-foreground">{{ row.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ row.slug }}</p>
                            </div>
                        </div>
                    </template>

                    <template #cell.email="{ row }">
                        <div class="text-sm">
                            <span v-if="row.email" class="inline-flex items-center gap-1 text-foreground"><Mail class="h-3.5 w-3.5 text-muted-foreground" />{{ row.email }}</span>
                            <span v-else class="text-muted-foreground">—</span>
                            <span v-if="row.phone" class="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground"><Phone class="h-3 w-3" />{{ row.phone }}</span>
                        </div>
                    </template>

                    <template #cell.status="{ row }">
                        <StatusBadge :status="row.status" />
                    </template>

                    <template #cell.currency="{ row }">
                        <span class="rounded-md bg-muted px-2 py-0.5 text-xs font-medium">{{ row.currency }}</span>
                    </template>

                    <template #cell.users_count="{ row }">
                        <span class="text-sm">{{ row.users_count ?? 0 }}</span>
                    </template>

                    <template #cell.created_at="{ row }">
                        <span class="text-xs text-muted-foreground">{{ formatDate(row.created_at) }}</span>
                    </template>

                    <template #actions="{ row }">
                        <div class="flex items-center justify-end gap-1">
                            <Button variant="ghost" size="icon" as-child>
                                <Link :href="route('tenants.edit', row.id)" title="Edit">
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
            title="Delete tenant?"
            description="This will permanently remove this tenant. Users linked to it will be unassigned."
            :loading="deleting"
            @confirm="confirm"
            @close="confirmDelete = false"
        />
    </AppLayout>
</template>
