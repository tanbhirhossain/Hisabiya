<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import RoleForm from '../../Components/RoleForm.vue';
import type { BreadcrumbItem, Role } from '@/types';

defineProps<{
    role: Role;
    permissions: { all: Array<{ id: number; name: string }>; selected: number[] };
    options: { permissions: Array<{ id: number; name: string }> };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Roles', href: '/admin/roles' },
    { title: 'Edit', href: '#' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Edit ${role.name}`" />
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <PageHeader :title="`Edit ${role.name}`" description="Update the role's name and permissions." />
            <div class="max-w-4xl rounded-xl border border-border bg-card p-6 shadow-sm">
                <RoleForm :role="role" :all-permissions="permissions.all" :selected="permissions.selected" />
            </div>
        </div>
    </AppLayout>
</template>
