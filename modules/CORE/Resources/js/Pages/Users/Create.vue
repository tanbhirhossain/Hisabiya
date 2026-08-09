<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import UserForm from '../../Components/UserForm.vue';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    options: { tenants: Array<{ id: number; name: string }>; roles: Array<{ id: number; name: string }> };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: '/admin/users' },
    { title: 'Create', href: '/admin/users/create' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create User" />
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <PageHeader title="Create user" description="Add a new user to the platform." />
            <div class="max-w-3xl rounded-xl border border-border bg-card p-6 shadow-sm">
                <UserForm :options="options" />
            </div>
        </div>
    </AppLayout>
</template>
