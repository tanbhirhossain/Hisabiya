<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import TenantForm from '../../Components/TenantForm.vue';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tenants', href: '/admin/tenants' },
    { title: 'Create', href: '/admin/tenants/create' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create Tenant" />
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <PageHeader title="Create tenant" description="Add a new organisation to the platform." />
            <div class="max-w-3xl rounded-xl border border-border bg-card p-6 shadow-sm">
                <TenantForm />
            </div>
        </div>
    </AppLayout>
</template>
