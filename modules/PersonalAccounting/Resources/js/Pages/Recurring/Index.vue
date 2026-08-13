<script setup lang="ts">
import ModuleLayout from '../../Layouts/ModuleLayout.vue';
import MoneyText from '../../Components/MoneyText.vue';
import CategoryIcon from '../../Components/CategoryIcon.vue';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import { Repeat, History, Power, ArrowLeft } from 'lucide-vue-next';

const props = defineProps<{
    recurring: Array<{
        id: number; name: string; type: string; amount: number; frequency: string;
        next_run_at: string | null; last_run_at: string | null; is_active: boolean;
        end_type: string; end_date: string | null; max_occurrences: number | null; occurrences_count: number;
        account: { id: number; name: string; color: string } | null;
        category: { id: number; name: string; icon: string; color: string } | null;
    }>;
}>();

const logsOpen = ref<number | null>(null);
const logs = ref<any[]>([]);
const logsLoading = ref(false);

function formatDateTime(value: string | null): string {
    if (!value) return '—';
    return new Date(value).toLocaleString('en-GB', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
}

function daysUntil(value: string | null): string {
    if (!value) return '—';
    const diff = Math.ceil((new Date(value).getTime() - Date.now()) / (1000 * 60 * 60 * 24));
    if (diff <= 0) return 'due now';
    return `in ${diff}d`;
}

async function toggleLogs(id: number) {
    if (logsOpen.value === id) {
        logsOpen.value = null;
        return;
    }
    logsOpen.value = id;
    logsLoading.value = true;
    logs.value = [];
    try {
        const res = await axios.get(route('personal.recurring.logs', id), { params: { per_page: 20 } });
        logs.value = res.data.data;
    } catch (e) {
        /* ignore */
    } finally {
        logsLoading.value = false;
    }
}

async function toggleActive(recurring: any) {
    try {
        await axios.post(route('personal.recurring.toggle', recurring.id));
        recurring.is_active = !recurring.is_active;
    } catch (e) {
        /* ignore */
    }
}

function endLabel(r: any): string {
    if (r.end_type === 'on_date') return `Until ${r.end_date ?? '—'}`;
    if (r.end_type === 'after_occurrences') return `${r.occurrences_count}/${r.max_occurrences} times`;
    return 'Never ends';
}
</script>

<template>
    <ModuleLayout title="Recurring Transactions" :breadcrumbs="[{ title: 'Personal', href: '/personal/dashboard' }, { title: 'Recurring', href: '/personal/recurring' }]">
        <div class="space-y-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">Recurring transactions</h1>
                    <p class="text-sm text-muted-foreground">Automated transactions on a schedule.</p>
                </div>
                <Link :href="route('personal.transactions.index')" class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline">
                    <ArrowLeft class="h-4 w-4" /> Back to transactions
                </Link>
            </div>

            <div v-if="recurring.length === 0" class="rounded-xl border border-dashed border-border p-12 text-center">
                <Repeat class="mx-auto h-10 w-10 text-muted-foreground" />
                <p class="mt-3 text-sm text-muted-foreground">No recurring transactions yet. Create one from the transaction form by toggling "Make this a recurring transaction".</p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div v-for="r in recurring" :key="r.id" class="rounded-xl border border-border bg-card p-5 shadow-sm" :class="!r.is_active ? 'opacity-60' : ''">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl" :class="r.type === 'income' ? 'bg-emerald-500/10 text-emerald-600' : r.type === 'transfer' ? 'bg-sky-500/10 text-sky-600' : 'bg-rose-500/10 text-rose-600'">
                                <Repeat class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-foreground">{{ r.name }}</h3>
                                <p class="text-xs text-muted-foreground capitalize">{{ r.frequency }} · {{ r.account?.name ?? '—' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <span v-if="!r.is_active" class="rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-medium text-zinc-500 dark:bg-zinc-500/10 dark:text-zinc-400">paused</span>
                            <button type="button" class="rounded-md p-1.5 text-muted-foreground transition hover:bg-muted" :title="r.is_active ? 'Pause' : 'Resume'" @click="toggleActive(r)">
                                <Power class="h-4 w-4" />
                            </button>
                            <button type="button" class="rounded-md p-1.5 text-muted-foreground transition hover:bg-muted" title="Run history" @click="toggleLogs(r.id)">
                                <History class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between">
                        <MoneyText :value="r.amount" :type="r.type" signed class="text-lg font-bold" />
                        <span class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-medium text-muted-foreground">{{ endLabel(r) }}</span>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-2 border-t border-border pt-3 text-xs text-muted-foreground">
                        <div>
                            <p>Next run</p>
                            <p class="font-medium text-foreground">{{ formatDateTime(r.next_run_at) }} <span class="text-primary">({{ daysUntil(r.next_run_at) }})</span></p>
                        </div>
                        <div>
                            <p>Last run</p>
                            <p class="font-medium text-foreground">{{ formatDateTime(r.last_run_at) }}</p>
                        </div>
                    </div>

                    <!-- Run history -->
                    <div v-if="logsOpen === r.id" class="mt-3 rounded-lg border border-border bg-muted/30 p-3">
                        <p class="mb-2 text-xs font-semibold text-foreground">Run history</p>
                        <p v-if="logsLoading" class="text-xs text-muted-foreground">Loading…</p>
                        <ul v-else-if="logs.length" class="space-y-1.5">
                            <li v-for="log in logs" :key="log.id" class="flex items-center justify-between text-xs">
                                <span class="inline-flex items-center gap-1.5 text-muted-foreground">
                                    <span class="h-1.5 w-1.5 rounded-full" :class="log.status === 'success' ? 'bg-emerald-500' : 'bg-rose-500'" />
                                    {{ new Date(log.ran_at).toLocaleString('en-GB', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) }}
                                </span>
                                <span :class="log.status === 'success' ? 'font-medium text-emerald-600 dark:text-emerald-400' : 'font-medium text-rose-600 dark:text-rose-400'">
                                    {{ log.status }}
                                </span>
                            </li>
                        </ul>
                        <p v-else class="text-xs text-muted-foreground">No runs yet.</p>
                    </div>
                </div>
            </div>
        </div>
    </ModuleLayout>
</template>
