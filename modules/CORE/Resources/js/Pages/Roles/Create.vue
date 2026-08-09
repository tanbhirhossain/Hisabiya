<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import RoleForm from '../../Components/RoleForm.vue';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    options: { permissions: Array<{ id: number; name: string }> };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Roles', href: '/admin/roles' },
    { title: 'Create', href: '/admin/roles/create' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create Role" />
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <PageHeader title="Create role" description="Create a role and choose its permissions." />
            <div class="max-w-4xl rounded-xl border border-border bg-card p-6 shadow-sm">
                <RoleForm :all-permissions="options.permissions" :selected="[]" />
            </div>
        </div>
    </AppLayout>
</template>
