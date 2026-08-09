<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import UserForm from '../../Components/UserForm.vue';
import type { BreadcrumbItem, User } from '@/types';

defineProps<{
    user: User;
    options: { tenants: Array<{ id: number; name: string }>; roles: Array<{ id: number; name: string }> };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: '/admin/users' },
    { title: 'Edit', href: '#' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Edit ${user.name}`" />
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <PageHeader :title="`Edit ${user.name}`" description="Update the user's profile and roles." />
            <div class="max-w-3xl rounded-xl border border-border bg-card p-6 shadow-sm">
                <UserForm :user="user" :options="options" />
            </div>
        </div>
    </AppLayout>
</template>
