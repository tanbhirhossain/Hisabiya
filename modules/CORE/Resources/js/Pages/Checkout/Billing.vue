<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import { FileDown, CheckCircle2, Clock, XCircle } from 'lucide-vue-next';

defineProps<{
    payments: any;
}>();

function statusMeta(status: string): { icon: any; label: string; cls: string } {
    const map: Record<string, any> = {
        paid: { icon: CheckCircle2, label: 'Paid', cls: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' },
        approved: { icon: CheckCircle2, label: 'Approved', cls: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' },
        pending: { icon: Clock, label: 'Pending', cls: 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' },
        failed: { icon: XCircle, label: 'Failed', cls: 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400' },
    };
    return map[status] ?? { icon: Clock, label: status, cls: 'bg-muted text-muted-foreground' };
}

function fmtMoney(v: number): string {
    return '৳' + Number(v).toLocaleString('en-IN');
}
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Billing', href: '/billing' }]">
        <Head title="Billing" />

        <div class="flex flex-col gap-6 p-4 md:p-6">
            <PageHeader title="Billing &amp; invoices" description="Your payment history and downloadable invoices." />

            <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/40 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                <th class="px-5 py-3">Date</th>
                                <th class="px-5 py-3">Provider</th>
                                <th class="px-5 py-3">Plan</th>
                                <th class="px-5 py-3">TRX</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Amount</th>
                                <th class="px-5 py-3 text-right">Invoice</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in payments.data" :key="p.id" class="border-b border-border transition last:border-0 hover:bg-muted/30">
                                <td class="px-5 py-3 text-muted-foreground">{{ new Date(p.paid_at ?? p.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) }}</td>
                                <td class="px-5 py-3 capitalize text-muted-foreground">{{ p.provider.replace('_', ' ') }}</td>
                                <td class="px-5 py-3 font-medium text-foreground">{{ p.subscription?.plan?.name ?? '—' }}</td>
                                <td class="px-5 py-3 font-mono text-xs text-muted-foreground">{{ p.trx_id ?? p.provider_ref }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium capitalize" :class="statusMeta(p.status).cls">
                                        <component :is="statusMeta(p.status).icon" class="h-3.5 w-3.5" />
                                        {{ statusMeta(p.status).label }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right font-semibold text-foreground">{{ fmtMoney(p.amount) }}</td>
                                <td class="px-5 py-3 text-right">
                                    <Link
                                        v-if="p.status === 'paid' || p.status === 'approved'"
                                        :href="route('billing.download', p.id)"
                                        class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                                    >
                                        <FileDown class="h-3.5 w-3.5" /> PDF
                                    </Link>
                                    <span v-else class="text-xs text-muted-foreground">—</span>
                                </td>
                            </tr>
                            <tr v-if="payments.data.length === 0">
                                <td colspan="7" class="px-5 py-12 text-center text-sm text-muted-foreground">No payments yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
