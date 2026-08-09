<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import TenantForm from '../../Components/TenantForm.vue';
import type { BreadcrumbItem, Tenant } from '@/types';

defineProps<{
    tenant: Tenant;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tenants', href: '/admin/tenants' },
    { title: 'Edit', href: '#' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Edit ${tenant.name}`" />
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <PageHeader :title="`Edit ${tenant.name}`" description="Update the tenant details." />
            <div class="max-w-3xl rounded-xl border border-border bg-card p-6 shadow-sm">
                <TenantForm :tenant="tenant" />
            </div>
        </div>
    </AppLayout>
</template>
