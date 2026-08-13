<script setup lang="ts">
import ModuleLayout from '../../Layouts/ModuleLayout.vue';
import MoneyText from '../../Components/MoneyText.vue';
import AddAccountModal from '../../Components/AddAccountModal.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import { Plus, Wallet, Landmark, Smartphone, ArrowRight, Archive, EyeOff, Eye, ChevronDown } from 'lucide-vue-next';

const props = defineProps<{
    accounts: Array<{ id: number; name: string; type: string; currency: string; balance: number; is_default: boolean; is_archived?: boolean; color?: string; transactions_count: number }>;
    balance: { total: number; count: number };
}>();

const modal = ref<InstanceType<typeof AddAccountModal> | null>(null);
const showArchived = ref(false);
const confirmOpen = ref(false);
const archivingId = ref<number | null>(null);
const expandedId = ref<number | null>(null);
const balanceHistory = ref<Array<{ date: string; balance: number }>>([]);
const historyLoading = ref(false);

const typeMeta: Record<string, { label: string; icon: any }> = {
    cash: { label: 'Cash', icon: Wallet },
    bank: { label: 'Bank', icon: Landmark },
    mobile_banking: { label: 'Mobile Banking', icon: Smartphone },
};

function toggleArchived() {
    showArchived.value = !showArchived.value;
    router.get(route('personal.accounts.index'), { archived: showArchived.value ? 1 : undefined }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['accounts', 'balance'],
    });
}

function confirmArchive(account: { id: number; name: string }) {
    archivingId.value = account.id;
    confirmOpen.value = true;
}

function doArchive() {
    router.post(route('personal.accounts.archive', archivingId.value), {
        preserveScroll: true,
        onSuccess: () => (confirmOpen.value = false),
    });
}

async function toggleExpand(account: { id: number; name: string }) {
    if (expandedId.value === account.id) {
        expandedId.value = null;
        return;
    }
    expandedId.value = account.id;
    historyLoading.value = true;
    balanceHistory.value = [];
    try {
        const res = await axios.get(route('personal.accounts.balance-history', account.id), { params: { period: 'month' } });
        balanceHistory.value = res.data.data;
    } catch (e) {
        /* ignore */
    } finally {
        historyLoading.value = false;
    }
}

// Simple sparkline path from balance history.
function sparklinePath(points: Array<{ balance: number }>, w = 120, h = 32): string {
    if (points.length < 2) return '';
    const values = points.map((p) => p.balance);
    const min = Math.min(...values);
    const max = Math.max(...values);
    const range = max - min || 1;
    return values
        .map((v, i) => {
            const x = (i / (values.length - 1)) * w;
            const y = h - ((v - min) / range) * h;
            return `${i === 0 ? 'M' : 'L'} ${x.toFixed(1)} ${y.toFixed(1)}`;
        })
        .join(' ');
}
</script>

<template>
    <ModuleLayout title="Accounts" :breadcrumbs="[{ title: 'Personal', href: '/personal/dashboard' }, { title: 'Accounts', href: '/personal/accounts' }]">
        <div class="space-y-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">Accounts</h1>
                    <p class="text-sm text-muted-foreground">Manage your wallets and track balances.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-border px-3 py-2 text-sm font-medium text-muted-foreground transition hover:bg-muted"
                        @click="toggleArchived"
                    >
                        <Eye v-if="showArchived" class="h-4 w-4" />
                        <EyeOff v-else class="h-4 w-4" />
                        {{ showArchived ? 'Hide archived' : 'Show archived' }}
                    </button>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90" @click="modal?.openModal()">
                        <Plus class="h-4 w-4" /> Add account
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-border bg-gradient-to-br from-slate-900 to-slate-800 p-5 text-white shadow-sm">
                    <p class="text-sm text-slate-300">Total balance</p>
                    <p class="mt-1 text-2xl font-bold"><MoneyText :value="balance.total" class="text-white" /></p>
                    <p class="mt-1 text-xs text-slate-400">{{ balance.count }} active account(s)</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="account in accounts"
                    :key="account.id"
                    class="rounded-xl border bg-card p-5 shadow-sm transition hover:shadow-md"
                    :class="account.is_archived ? 'border-dashed opacity-70' : 'border-border'"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl" :style="{ backgroundColor: `${account.color ?? '#6366f1'}1a`, color: account.color ?? '#6366f1' }">
                            <component :is="typeMeta[account.type]?.icon ?? Wallet" class="h-5 w-5" />
                        </div>
                        <div class="flex items-center gap-1">
                            <button
                                v-if="!account.is_archived"
                                type="button"
                                class="rounded-md p-1.5 text-muted-foreground transition hover:bg-muted hover:text-destructive"
                                title="Archive account"
                                @click="confirmArchive(account)"
                            >
                                <Archive class="h-4 w-4" />
                            </button>
                            <Link :href="route('personal.accounts.show', account.id)" title="View details">
                                <ArrowRight class="h-4 w-4 text-muted-foreground transition hover:text-foreground" />
                            </Link>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-2">
                        <h3 class="text-sm font-semibold text-foreground">{{ account.name }}</h3>
                        <span v-if="account.is_default" class="rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-medium text-primary">Default</span>
                        <span v-if="account.is_archived" class="rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-medium text-zinc-500 dark:bg-zinc-500/10 dark:text-zinc-400">Archived</span>
                    </div>
                    <p class="text-xs text-muted-foreground">{{ typeMeta[account.type]?.label ?? account.type }} · {{ account.transactions_count }} txns</p>
                    <p class="mt-2 text-xl font-bold text-foreground"><MoneyText :value="account.balance" /></p>

                    <!-- Expand for balance history sparkline -->
                    <button type="button" class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-muted-foreground transition hover:text-foreground" @click="toggleExpand(account)">
                        <ChevronDown class="h-3.5 w-3.5" :class="expandedId === account.id ? 'rotate-180' : ''" />
                        {{ expandedId === account.id ? 'Hide balance history' : 'Balance history' }}
                    </button>
                    <div v-if="expandedId === account.id" class="mt-2">
                        <p v-if="historyLoading" class="text-xs text-muted-foreground">Loading…</p>
                        <svg v-else-if="balanceHistory.length >= 2" :viewBox="`0 0 120 32`" class="h-8 w-full">
                            <path :d="sparklinePath(balanceHistory)" fill="none" stroke="#6366f1" stroke-width="2" stroke-linecap="round" />
                        </svg>
                        <p v-else class="text-xs text-muted-foreground">Not enough data this month.</p>
                    </div>
                </div>
            </div>
        </div>

        <AddAccountModal ref="modal" />
        <ConfirmDialog
            :open="confirmOpen"
            title="Archive this account?"
            description="The account will be hidden from lists but its data is kept. You can't archive your only active account."
            @close="confirmOpen = false"
            @confirm="doArchive"
        />
    </ModuleLayout>
</template>
