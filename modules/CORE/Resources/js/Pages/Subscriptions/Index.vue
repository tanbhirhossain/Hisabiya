<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import { usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { CreditCard, Check, X, Crown, Sparkles, Plus, Pencil, Trash2 } from 'lucide-vue-next';

const props = defineProps<{
    plans: Array<{ id: number; module: string; name: string; slug: string; description: string; price_monthly: number; price_yearly: number; permissions: string[]; features: string[]; is_active: boolean; subscriptions_count: number }>;
    subscriptions: Array<{ id: number; status: string; module: string; ends_at: string | null; tenant: { id: number; name: string; email: string } | null; plan: { id: number; name: string; slug: string; module: string } | null }>;
    tenants: Array<{ id: number; name: string }>;
    pendingPayments: Array<{ id: number; provider: string; amount: number; status: string; trx_id: string | null; tenant: { id: number; name: string; email: string } | null; subscription: { id: number; module: string } | null }>;
    paidPayments: Array<{ id: number; provider: string; amount: number; status: string; trx_id: string | null; created_at: string; tenant: { id: number; name: string; email: string } | null; subscription: { id: number; module: string } | null }>;
}>();

const page = usePage();
const assignOpen = ref(false);
const downgradeOpen = ref(false);
const downgradeSub = ref<any>(null);
const downgradePlanId = ref('');
const form = useForm({ tenant_id: '', plan_id: '', module: 'personal_accounting' });

function openAssign() {
    form.reset();
    form.defaults();
    assignOpen.value = true;
}

function submit() {
    form.post(route('subscriptions.assign'), { onSuccess: () => (assignOpen.value = false) });
}

function cancel(id: number) {
    router.post(route('subscriptions.cancel', id), { preserveScroll: true });
}

function openDowngrade(sub: any) {
    downgradeSub.value = sub;
    downgradePlanId.value = '';
    downgradeOpen.value = true;
}

function submitDowngrade() {
    if (!downgradeSub.value || !downgradePlanId.value) return;
    router.post(route('subscriptions.downgrade', downgradeSub.value.id), { plan_id: downgradePlanId.value }, {
        preserveScroll: true,
        onSuccess: () => (downgradeOpen.value = false),
    });
}

function deletePlan(id: number) {
    if (!window.confirm('Delete this plan? This cannot be undone.')) return;
    router.delete(route('subscriptions.plans.destroy', id), { preserveScroll: true });
}

function approvePayment(id: number) {
    router.post(route('subscriptions.payments.approve', id), { preserveScroll: true });
}

function rejectPayment(id: number) {
    router.post(route('subscriptions.payments.reject', id), { preserveScroll: true });
}

function refundPayment(id: number) {
    const reason = window.prompt('Refund reason (optional):') ?? '';
    if (reason === null) return; // cancelled
    router.post(route('subscriptions.payments.refund', id), { reason }, { preserveScroll: true });
}

function moduleLabel(module: string): string {
    return module.replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function fmtPrice(v: number): string {
    return '৳' + Number(v).toLocaleString('en-IN');
}
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Subscriptions', href: '/admin/subscriptions' }]">
        <Head title="Subscriptions" />

        <div class="flex flex-col gap-6 p-4 md:p-6">
            <PageHeader title="Subscriptions" description="Manage module plans and which tenants are subscribed to them.">
                <template #actions>
                    <Link :href="route('subscriptions.plans.create')" class="inline-flex items-center gap-1.5 rounded-lg border border-border px-4 py-2 text-sm font-semibold text-muted-foreground transition hover:bg-muted hover:text-foreground">
                        <Plus class="h-4 w-4" /> New plan
                    </Link>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90"
                        @click="openAssign"
                    >
                        <CreditCard class="h-4 w-4" /> Assign plan
                    </button>
                </template>
            </PageHeader>

            <!-- Plans -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div v-for="plan in plans" :key="plan.id" class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl" :class="plan.slug.includes('pro') ? 'bg-violet-500/10 text-violet-600 dark:text-violet-400' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'">
                                <component :is="plan.slug.includes('pro') ? Crown : Sparkles" class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-foreground">{{ plan.name }}</h3>
                                <p class="text-xs text-muted-foreground">{{ moduleLabel(plan.module) }}</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">{{ plan.subscriptions_count }} sub(s)</span>
                    </div>
                    <p class="mt-3 text-sm text-muted-foreground">{{ plan.description }}</p>
                    <div class="mt-3 flex items-center gap-4 text-sm">
                        <span class="text-lg font-bold text-foreground">{{ fmtPrice(plan.price_monthly) }}<span class="text-xs font-normal text-muted-foreground">/mo</span></span>
                        <span class="text-muted-foreground">or {{ fmtPrice(plan.price_yearly) }}/yr</span>
                    </div>
                    <div class="mt-3 space-y-1.5">
                        <p v-for="feature in plan.features" :key="feature" class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Check class="h-4 w-4 text-emerald-500" /> {{ feature }}
                        </p>
                    </div>
                    <p class="mt-3 text-xs font-medium text-muted-foreground">{{ plan.permissions?.length }} permissions granted</p>
                    <div class="mt-4 flex items-center gap-2 border-t border-border pt-4">
                        <Link :href="route('subscriptions.plans.edit', plan.id)" class="inline-flex items-center gap-1.5 rounded-lg border border-border px-3 py-1.5 text-xs font-semibold text-muted-foreground transition hover:bg-muted hover:text-foreground">
                            <Pencil class="h-3.5 w-3.5" /> Edit plan
                        </Link>
                        <button
                            v-if="plan.subscriptions_count === 0"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-border px-3 py-1.5 text-xs font-semibold text-rose-500 transition hover:bg-rose-500/10"
                            @click="deletePlan(plan.id)"
                        >
                            <Trash2 class="h-3.5 w-3.5" /> Delete
                        </button>
                        <span v-else class="text-xs text-muted-foreground">In use</span>
                    </div>
                </div>
            </div>

            <!-- Pending payment approvals -->
            <div v-if="pendingPayments.length" class="rounded-xl border border-amber-300/60 bg-amber-50/40 shadow-sm dark:border-amber-500/30 dark:bg-amber-500/5">
                <div class="flex items-center justify-between border-b border-border px-5 py-4">
                    <div>
                        <h2 class="text-sm font-semibold text-foreground">Pending payment approvals</h2>
                        <p class="text-xs text-muted-foreground">Manual bKash/Bank payments awaiting verification.</p>
                    </div>
                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">{{ pendingPayments.length }} pending</span>
                </div>
                <div class="divide-y divide-border">
                    <div v-for="payment in pendingPayments" :key="payment.id" class="flex flex-col gap-3 px-5 py-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-sm font-medium text-foreground">{{ payment.tenant?.name }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ payment.provider.replace('_', ' ') }} · ৳{{ Number(payment.amount).toLocaleString('en-IN') }}
                                <span v-if="payment.trx_id"> · TRX: {{ payment.trx_id }}</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-700" @click="approvePayment(payment.id)">
                                <Check class="h-3.5 w-3.5" /> Approve
                            </button>
                            <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-border px-3 py-1.5 text-xs font-semibold text-muted-foreground transition hover:bg-muted" @click="rejectPayment(payment.id)">
                                <X class="h-3.5 w-3.5" /> Reject
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paid / refundable payments -->
            <div v-if="paidPayments.length" class="rounded-xl border border-border bg-card shadow-sm">
                <div class="flex items-center justify-between border-b border-border px-5 py-4">
                    <div>
                        <h2 class="text-sm font-semibold text-foreground">Recent payments</h2>
                        <p class="text-xs text-muted-foreground">Paid transactions. Refund to revoke access and return funds.</p>
                    </div>
                </div>
                <div class="divide-y divide-border">
                    <div v-for="payment in paidPayments" :key="payment.id" class="flex flex-col gap-3 px-5 py-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-sm font-medium text-foreground">{{ payment.tenant?.name }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ payment.provider.replace('_', ' ') }} · ৳{{ Number(payment.amount).toLocaleString('en-IN') }}
                                <span v-if="payment.trx_id"> · TRX: {{ payment.trx_id }}</span>
                            </p>
                            <p class="text-xs capitalize" :class="payment.status === 'refunded' ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400'">
                                {{ payment.status }}
                            </p>
                        </div>
                        <button
                            v-if="payment.status !== 'refunded'"
                            type="button"
                            class="inline-flex items-center gap-1 rounded-lg border border-border px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-500/10"
                            @click="refundPayment(payment.id)"
                        >
                            <X class="h-3.5 w-3.5" /> Refund
                        </button>
                    </div>
                </div>
            </div>

            <!-- Active subscriptions -->
            <div class="rounded-xl border border-border bg-card shadow-sm">
                <div class="border-b border-border px-5 py-4">
                    <h2 class="text-sm font-semibold text-foreground">Tenant subscriptions</h2>
                    <p class="text-xs text-muted-foreground">Which module plans tenants are on.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/40 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                <th class="px-5 py-3">Tenant</th>
                                <th class="px-5 py-3">Module</th>
                                <th class="px-5 py-3">Plan</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="sub in subscriptions" :key="sub.id" class="border-b border-border transition last:border-0 hover:bg-muted/30">
                                <td class="px-5 py-3">
                                    <p class="font-medium text-foreground">{{ sub.tenant?.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ sub.tenant?.email }}</p>
                                </td>
                                <td class="px-5 py-3 text-muted-foreground">{{ moduleLabel(sub.module) }}</td>
                                <td class="px-5 py-3 font-medium text-foreground">{{ sub.plan?.name }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium capitalize" :class="sub.status === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-500/10 dark:text-zinc-400'">
                                        {{ sub.status }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button v-if="sub.status === 'active'" type="button" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-sky-600 transition hover:bg-sky-500/10" @click="openDowngrade(sub)">
                                            Downgrade
                                        </button>
                                        <button v-if="sub.status === 'active'" type="button" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-rose-600 transition hover:bg-rose-500/10" @click="cancel(sub.id)">
                                            <X class="h-3.5 w-3.5" /> Cancel
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="subscriptions.length === 0">
                                <td colspan="5" class="px-5 py-10 text-center text-sm text-muted-foreground">No subscriptions yet. Assign a plan to a tenant.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Assign modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" leave-active-class="transition-opacity duration-150" leave-to-class="opacity-0">
                <div v-if="assignOpen" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm" @click="assignOpen = false" />
            </Transition>
            <Transition enter-active-class="transition scale duration-200" enter-from-class="opacity-0 scale-95" leave-active-class="transition scale duration-150" leave-to-class="opacity-0 scale-95">
                <div v-if="assignOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <form class="w-full max-w-md rounded-2xl border border-border bg-card p-6 shadow-xl" @submit.prevent="submit">
                        <div class="mb-5">
                            <h2 class="text-lg font-semibold text-foreground">Assign subscription</h2>
                            <p class="text-sm text-muted-foreground">Subscribe a tenant to a module plan.</p>
                        </div>
                        <div class="space-y-4">
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Tenant</label>
                                <select v-model="form.tenant_id" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                                    <option value="" disabled>Select tenant</option>
                                    <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">{{ tenant.name }}</option>
                                </select>
                                <p v-if="form.errors.tenant_id" class="text-sm text-rose-500">{{ form.errors.tenant_id }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Plan</label>
                                <select v-model="form.plan_id" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                                    <option value="" disabled>Select plan</option>
                                    <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }} — {{ fmtPrice(plan.price_monthly) }}/mo</option>
                                </select>
                                <p v-if="form.errors.plan_id" class="text-sm text-rose-500">{{ form.errors.plan_id }}</p>
                            </div>
                            <div class="flex items-center gap-3 border-t border-border pt-4">
                                <button type="submit" class="flex-1 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:opacity-50" :disabled="form.processing">
                                    {{ form.processing ? 'Assigning…' : 'Assign plan' }}
                                </button>
                                <button type="button" class="rounded-lg border border-border px-4 py-2.5 text-sm font-medium text-muted-foreground transition hover:bg-muted" @click="assignOpen = false">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </Transition>
        </Teleport>

        <!-- Downgrade modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" leave-active-class="transition-opacity duration-150" leave-to-class="opacity-0">
                <div v-if="downgradeOpen" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm" @click="downgradeOpen = false" />
            </Transition>
            <Transition enter-active-class="transition scale duration-200" enter-from-class="opacity-0 scale-95" leave-active-class="transition scale duration-150" leave-to-class="opacity-0 scale-95">
                <div v-if="downgradeOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="w-full max-w-md rounded-2xl border border-border bg-card p-6 shadow-xl">
                        <h2 class="text-lg font-semibold text-foreground">Downgrade subscription</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ downgradeSub?.tenant?.name }} · {{ downgradeSub?.plan?.name }}
                        </p>
                        <div class="mt-4 space-y-1.5">
                            <label class="text-sm font-medium text-foreground">Downgrade to</label>
                            <select v-model="downgradePlanId" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                                <option value="" disabled>Select plan</option>
                                <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }} — {{ fmtPrice(plan.price_monthly) }}/mo</option>
                            </select>
                        </div>
                        <div class="mt-6 flex justify-end gap-2">
                            <button type="button" class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted-foreground transition hover:bg-muted" @click="downgradeOpen = false">Cancel</button>
                            <button type="button" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:opacity-50" :disabled="!downgradePlanId" @click="submitDowngrade">Downgrade</button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
