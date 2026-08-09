<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import PermissionForm from '../../Components/PermissionForm.vue';
import type { BreadcrumbItem, Permission } from '@/types';

defineProps<{
    permission: Permission;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Permissions', href: '/admin/permissions' },
    { title: 'Edit', href: '#' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Edit ${permission.name}`" />
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <PageHeader :title="`Edit ${permission.name}`" description="Update the permission." />
            <div class="max-w-3xl rounded-xl border border-border bg-card p-6 shadow-sm">
                <PermissionForm :permission="permission" />
            </div>
        </div>
    </AppLayout>
</template>
