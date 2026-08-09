<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import PermissionForm from '../../Components/PermissionForm.vue';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Permissions', href: '/admin/permissions' },
    { title: 'Create', href: '/admin/permissions/create' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create Permission" />
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <PageHeader title="Create permission" description="Add a new capability to the system." />
            <div class="max-w-3xl rounded-xl border border-border bg-card p-6 shadow-sm">
                <PermissionForm />
            </div>
        </div>
    </AppLayout>
</template>
