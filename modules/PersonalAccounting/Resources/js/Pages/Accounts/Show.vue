<script setup lang="ts">
import ModuleLayout from '../../Layouts/ModuleLayout.vue';
import MoneyText from '../../Components/MoneyText.vue';
import TypeBadge from '../../Components/TypeBadge.vue';
import CategoryIcon from '../../Components/CategoryIcon.vue';
import { Link } from '@inertiajs/vue3';
import { formatDate } from '../../Lib/format';
import { ArrowLeft, Wallet } from 'lucide-vue-next';

defineProps<{
    account: { id: number; name: string; type: string; currency: string; balance: number; is_default: boolean; color?: string };
    transactions: any;
}>();

const typeMeta: Record<string, string> = { cash: 'Cash', bank: 'Bank', mobile_banking: 'Mobile Banking' };
</script>

<template>
    <ModuleLayout :title="account.name" :breadcrumbs="[{ title: 'Personal', href: '/personal/dashboard' }, { title: 'Accounts', href: '/personal/accounts' }, { title: account.name, href: '#' }]">
        <div class="space-y-6">
            <Link :href="route('personal.accounts.index')" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition hover:text-foreground">
                <ArrowLeft class="h-4 w-4" /> All accounts
            </Link>

            <section class="relative overflow-hidden rounded-2xl border border-border bg-gradient-to-br from-slate-900 to-slate-800 p-6 text-white shadow-sm">
                <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/5 blur-2xl" />
                <div class="relative flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl" :style="{ backgroundColor: `${account.color ?? '#6366f1'}22`, color: account.color ?? '#fff' }">
                        <Wallet class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-sm text-slate-300">{{ typeMeta[account.type] ?? account.type }} · {{ account.currency }}</p>
                        <h1 class="text-lg font-semibold">{{ account.name }}</h1>
                    </div>
                    <p class="ml-auto text-3xl font-bold"><MoneyText :value="account.balance" class="text-white" /></p>
                </div>
            </section>

            <div class="rounded-xl border border-border bg-card shadow-sm">
                <div class="border-b border-border px-5 py-4">
                    <h2 class="text-sm font-semibold text-foreground">Transaction history</h2>
                </div>
                <ul class="divide-y divide-border">
                    <li v-for="txn in transactions.data" :key="txn.id" class="flex items-center gap-4 px-5 py-3">
                        <CategoryIcon :icon="txn.category?.icon" :color="txn.category?.color" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-foreground">{{ txn.note || txn.category?.name || txn.type }}</p>
                            <p class="text-xs text-muted-foreground">{{ formatDate(txn.date) }}</p>
                        </div>
                        <TypeBadge :type="txn.type" />
                        <MoneyText :value="txn.amount" :type="txn.type" signed class="font-semibold" />
                    </li>
                    <li v-if="transactions.data.length === 0" class="px-5 py-10 text-center text-sm text-muted-foreground">
                        No transactions for this account yet.
                    </li>
                </ul>
            </div>
        </div>
    </ModuleLayout>
</template>
