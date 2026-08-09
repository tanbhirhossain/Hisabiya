<script setup lang="ts">
import ModuleLayout from '../../Layouts/ModuleLayout.vue';
import MoneyText from '../../Components/MoneyText.vue';
import AddAccountModal from '../../Components/AddAccountModal.vue';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Plus, Wallet, Landmark, Smartphone, ArrowRight, MoreHorizontal } from 'lucide-vue-next';

const props = defineProps<{
    accounts: Array<{ id: number; name: string; type: string; currency: string; balance: number; is_default: boolean; color?: string; transactions_count: number }>;
    balance: { total: number; count: number };
}>();

const modal = ref<InstanceType<typeof AddAccountModal> | null>(null);

const typeMeta: Record<string, { label: string; icon: any }> = {
    cash: { label: 'Cash', icon: Wallet },
    bank: { label: 'Bank', icon: Landmark },
    mobile_banking: { label: 'Mobile Banking', icon: Smartphone },
};
</script>

<template>
    <ModuleLayout title="Accounts" :breadcrumbs="[{ title: 'Personal', href: '/personal/dashboard' }, { title: 'Accounts', href: '/personal/accounts' }]">
        <div class="space-y-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">Accounts</h1>
                    <p class="text-sm text-muted-foreground">Manage your wallets and track balances.</p>
                </div>
                <button type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90" @click="modal?.openModal()">
                    <Plus class="h-4 w-4" /> Add account
                </button>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-border bg-gradient-to-br from-slate-900 to-slate-800 p-5 text-white shadow-sm">
                    <p class="text-sm text-slate-300">Total balance</p>
                    <p class="mt-1 text-2xl font-bold"><MoneyText :value="balance.total" class="text-white" /></p>
                    <p class="mt-1 text-xs text-slate-400">{{ balance.count }} account(s)</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Link
                    v-for="account in accounts"
                    :key="account.id"
                    :href="route('personal.accounts.show', account.id)"
                    class="group rounded-xl border border-border bg-card p-5 shadow-sm transition hover:shadow-md"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl" :style="{ backgroundColor: `${account.color ?? '#6366f1'}1a`, color: account.color ?? '#6366f1' }">
                            <component :is="typeMeta[account.type]?.icon ?? Wallet" class="h-5 w-5" />
                        </div>
                        <ArrowRight class="h-4 w-4 text-muted-foreground transition group-hover:translate-x-0.5 group-hover:text-foreground" />
                    </div>
                    <div class="mt-4 flex items-center gap-2">
                        <h3 class="text-sm font-semibold text-foreground">{{ account.name }}</h3>
                        <span v-if="account.is_default" class="rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-medium text-primary">Default</span>
                    </div>
                    <p class="text-xs text-muted-foreground">{{ typeMeta[account.type]?.label ?? account.type }} · {{ account.transactions_count }} txns</p>
                    <p class="mt-2 text-xl font-bold text-foreground"><MoneyText :value="account.balance" /></p>
                </Link>
            </div>
        </div>

        <AddAccountModal ref="modal" />
    </ModuleLayout>
</template>
