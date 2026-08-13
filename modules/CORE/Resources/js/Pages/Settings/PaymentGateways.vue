<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import { CreditCard, Smartphone, Landmark, Loader2, Save } from 'lucide-vue-next';

const props = defineProps<{
    gateways: {
        sslcommerz: { enabled: boolean; sandbox: boolean; store_id: string; store_pass: string };
        manual_bkash: { enabled: boolean; number: string; instructions: string };
        manual_bank: { enabled: boolean; account_name: string; account_number: string; bank_name: string; instructions: string };
    };
}>();

const form = useForm({
    sslcommerz: { ...props.gateways.sslcommerz },
    manual_bkash: { ...props.gateways.manual_bkash },
    manual_bank: { ...props.gateways.manual_bank },
});

function submit() {
    form.post(route('settings.payment-gateways.update'));
}

const gateways = [
    { key: 'sslcommerz', label: 'SSLCommerz', desc: 'Online payments — bKash, Nagad, cards, banks', icon: CreditCard },
    { key: 'manual_bkash', label: 'Manual bKash', desc: 'Offline bKash — admin approves after payment', icon: Smartphone },
    { key: 'manual_bank', label: 'Manual Bank', desc: 'Offline bank transfer — admin approves after payment', icon: Landmark },
];
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Payment Gateways', href: '/admin/settings/payment-gateways' }]">
        <Head title="Payment Gateways" />

        <div class="flex flex-col gap-6 p-4 md:p-6">
            <PageHeader title="Payment gateway settings" description="Configure how customers pay for subscriptions." />

            <form class="space-y-6" @submit.prevent="submit">
                <div v-for="gw in gateways" :key="gw.key" class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <component :is="gw.icon" class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-foreground">{{ gw.label }}</h3>
                                <p class="text-xs text-muted-foreground">{{ gw.desc }}</p>
                            </div>
                        </div>
                        <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-foreground">
                            <input v-model="form[gw.key].enabled" type="checkbox" class="h-4 w-4 rounded accent-primary" />
                            Enabled
                        </label>
                    </div>

                    <div v-if="form[gw.key].enabled" class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <!-- SSLCommerz -->
                        <template v-if="gw.key === 'sslcommerz'">
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Store ID</label>
                                <input v-model="form.sslcommerz.store_id" type="text" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Store Password</label>
                                <input v-model="form.sslcommerz.store_pass" type="password" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                            </div>
                            <label class="flex items-center gap-2 text-sm font-medium text-foreground md:col-span-2">
                                <input v-model="form.sslcommerz.sandbox" type="checkbox" class="h-4 w-4 rounded accent-primary" />
                                Sandbox mode (test payments)
                            </label>
                        </template>

                        <!-- Manual bKash -->
                        <template v-else-if="gw.key === 'manual_bkash'">
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">bKash number</label>
                                <input v-model="form.manual_bkash.number" type="text" placeholder="01700-000000" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                            </div>
                            <div class="space-y-1.5 md:col-span-2">
                                <label class="text-sm font-medium text-foreground">Instructions shown to customer</label>
                                <textarea v-model="form.manual_bkash.instructions" rows="2" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                            </div>
                        </template>

                        <!-- Manual Bank -->
                        <template v-else>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Bank name</label>
                                <input v-model="form.manual_bank.bank_name" type="text" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Account name</label>
                                <input v-model="form.manual_bank.account_name" type="text" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Account number</label>
                                <input v-model="form.manual_bank.account_number" type="text" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                            </div>
                            <div class="space-y-1.5 md:col-span-2">
                                <label class="text-sm font-medium text-foreground">Instructions</label>
                                <textarea v-model="form.manual_bank.instructions" rows="2" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-sm font-semibold text-primary-foreground shadow transition hover:bg-primary/90 disabled:opacity-60" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Save v-else class="h-4 w-4" />
                        {{ form.processing ? 'Saving…' : 'Save settings' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
