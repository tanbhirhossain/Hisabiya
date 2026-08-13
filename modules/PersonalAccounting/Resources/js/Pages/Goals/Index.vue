<script setup lang="ts">
import ModuleLayout from '../../Layouts/ModuleLayout.vue';
import MoneyText from '../../Components/MoneyText.vue';
import ProgressCircle from '../../Components/ProgressCircle.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Plus, Trash2, PiggyBank, X, Landmark, History } from 'lucide-vue-next';

const props = defineProps<{
    goals: Array<{
        id: number; name: string; target_amount: number; current_amount: number; deadline: string | null;
        color?: string; icon?: string; status: string; progress_percent: number;
        account_id?: number | null; account?: { id: number; name: string; type: string } | null;
        contribution_history?: Array<{ id: number; type: string; amount: number; date: string; note: string; account: { id: number; name: string } | null }>;
    }>;
    accounts: Array<{ id: number; name: string; type: string }>;
}>();

const addOpen = ref(false);
const addForm = useForm({ name: '', target_amount: '', deadline: '', color: '#10b981', account_id: '' });

// contribute / withdraw state
const activeContribute = ref<number | null>(null);
const activeWithdraw = ref<number | null>(null);
const amountForm = useForm({ amount: '' });
const confirmOpen = ref(false);
const deletingId = ref<number | null>(null);
const historyOpen = ref<number | null>(null);

const colors = ['#10b981', '#6366f1', '#0ea5e9', '#f59e0b', '#ec4899', '#8b5cf6'];

function toggleHistory(id: number) {
    historyOpen.value = historyOpen.value === id ? null : id;
}


function openAdd() {
    addForm.reset();
    addForm.defaults();
    addOpen.value = true;
}

function submitAdd() {
    addForm.post(route('personal.goals.store'), { onSuccess: () => (addOpen.value = false) });
}

function setContribute(id: number) {
    activeContribute.value = id;
    activeWithdraw.value = null;
    amountForm.reset();
    amountForm.clearErrors();
}

function setWithdraw(id: number) {
    activeWithdraw.value = id;
    activeContribute.value = null;
    amountForm.reset();
    amountForm.clearErrors();
}

function submitAmount(goalId: number, action: 'contribute' | 'withdraw') {
    amountForm.post(route(`personal.goals.${action}`, goalId), {
        preserveScroll: true,
        onSuccess: () => { activeContribute.value = null; activeWithdraw.value = null; },
    });
}

function confirmDelete(id: number) {
    deletingId.value = id;
    confirmOpen.value = true;
}

function doDelete() {
    router.delete(route('personal.goals.destroy', deletingId.value), {
        preserveScroll: true,
        onSuccess: () => (confirmOpen.value = false),
    });
}
</script>

<template>
    <ModuleLayout title="Savings Goals" :breadcrumbs="[{ title: 'Personal', href: '/personal/dashboard' }, { title: 'Goals', href: '/personal/goals' }]">
        <div class="space-y-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">Savings goals</h1>
                    <p class="text-sm text-muted-foreground">Save towards the things that matter.</p>
                </div>
                <button type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90" @click="openAdd">
                    <Plus class="h-4 w-4" /> New goal
                </button>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div v-for="goal in goals" :key="goal.id" class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl" :style="{ backgroundColor: `${goal.color ?? '#10b981'}1a`, color: goal.color ?? '#10b981' }">
                                <PiggyBank class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-foreground">{{ goal.name }}</h3>
                                <p class="text-xs text-muted-foreground capitalize">{{ goal.status }}</p>
                                <p v-if="goal.account" class="mt-0.5 inline-flex items-center gap-1 text-[11px] font-medium text-muted-foreground">
                                    <Landmark class="h-3 w-3" /> {{ goal.account.name }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" class="rounded-md p-1.5 text-muted-foreground transition hover:bg-muted hover:text-foreground" title="Contribution history" @click="toggleHistory(goal.id)">
                                <History class="h-4 w-4" />
                            </button>
                            <button type="button" class="rounded-md p-1.5 text-muted-foreground transition hover:bg-muted hover:text-destructive" @click="confirmDelete(goal.id)">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center gap-4">
                        <ProgressCircle :value="goal.progress_percent" :color="goal.color ?? '#10b981'" :size="88" :stroke="9" />
                        <div>
                            <p class="text-xs text-muted-foreground">Saved</p>
                            <p class="text-lg font-bold text-foreground"><MoneyText :value="goal.current_amount" compact /></p>
                            <p class="text-xs text-muted-foreground">of <MoneyText :value="goal.target_amount" compact /></p>
                            <p v-if="goal.deadline" class="mt-1 text-xs text-muted-foreground">Due {{ goal.deadline }}</p>
                        </div>
                    </div>

                    <!-- Contribute / Withdraw -->
                    <div v-if="activeContribute === goal.id || activeWithdraw === goal.id" class="mt-4 rounded-lg border border-border p-3">
                        <p class="mb-2 text-xs font-medium text-foreground capitalize">{{ activeContribute === goal.id ? 'Contribute' : 'Withdraw' }} amount</p>
                        <div class="flex gap-2">
                            <input v-model="amountForm.amount" type="number" step="0.01" placeholder="Amount" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                            <button type="button" class="rounded-lg bg-primary px-3 py-2 text-sm font-semibold text-primary-foreground disabled:opacity-50" :disabled="amountForm.processing" @click="submitAmount(goal.id, activeContribute === goal.id ? 'contribute' : 'withdraw')">
                                Save
                            </button>
                        </div>
                        <p v-if="amountForm.errors.amount" class="mt-1 text-xs text-rose-500">{{ amountForm.errors.amount }}</p>
                    </div>

                    <div v-else class="mt-4 grid grid-cols-2 gap-2">
                        <button type="button" class="rounded-lg bg-emerald-500/10 px-3 py-2 text-sm font-semibold text-emerald-600 transition hover:bg-emerald-500/20 dark:text-emerald-400" @click="setContribute(goal.id)">
                            Contribute
                        </button>
                        <button type="button" class="rounded-lg border border-border px-3 py-2 text-sm font-semibold text-muted-foreground transition hover:bg-muted" @click="setWithdraw(goal.id)">
                            Withdraw
                        </button>
                    </div>

                    <!-- Contribution history -->
                    <div v-if="historyOpen === goal.id" class="mt-4 rounded-lg border border-border bg-muted/30 p-3">
                        <p class="mb-2 text-xs font-semibold text-foreground">Contribution history</p>
                        <ul v-if="goal.contribution_history?.length" class="space-y-1.5">
                            <li v-for="h in goal.contribution_history" :key="h.id" class="flex items-center justify-between text-xs">
                                <span class="inline-flex items-center gap-1.5 text-muted-foreground">
                                    <span class="h-1.5 w-1.5 rounded-full" :class="h.type === 'income' ? 'bg-emerald-500' : 'bg-rose-500'" />
                                    {{ h.date }}
                                </span>
                                <MoneyText :value="h.amount" :type="h.type" compact />
                            </li>
                        </ul>
                        <p v-else class="text-xs text-muted-foreground">No contributions recorded yet.</p>
                    </div>
                </div>
            </div>

            <!-- Add goal modal -->
            <Teleport to="body">
                <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" leave-active-class="transition-opacity duration-150" leave-to-class="opacity-0">
                    <div v-if="addOpen" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm" @click="addOpen = false" />
                </Transition>
                <Transition enter-active-class="transition scale duration-200" enter-from-class="opacity-0 scale-95" leave-active-class="transition scale duration-150" leave-to-class="opacity-0 scale-95">
                    <div v-if="addOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                        <form class="w-full max-w-md rounded-2xl border border-border bg-card p-6 shadow-xl" @submit.prevent="submitAdd">
                            <div class="mb-5 flex items-start justify-between">
                                <div>
                                    <h2 class="text-lg font-semibold text-foreground">New savings goal</h2>
                                    <p class="text-sm text-muted-foreground">What are you saving for?</p>
                                </div>
                                <button type="button" class="rounded-md p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground" @click="addOpen = false"><X class="h-5 w-5" /></button>
                            </div>
                            <div class="space-y-5">
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium text-foreground">Goal name</label>
                                    <input v-model="addForm.name" type="text" placeholder="e.g. New laptop" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                                    <p v-if="addForm.errors.name" class="text-sm text-rose-500">{{ addForm.errors.name }}</p>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium text-foreground">Target amount (BDT)</label>
                                    <input v-model="addForm.target_amount" type="number" step="0.01" placeholder="e.g. 85000" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                                    <p v-if="addForm.errors.target_amount" class="text-sm text-rose-500">{{ addForm.errors.target_amount }}</p>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium text-foreground">Deadline (optional)</label>
                                    <input v-model="addForm.deadline" type="date" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium text-foreground">Link to account (optional)</label>
                                    <select v-model="addForm.account_id" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                                        <option value="">— Don't link an account —</option>
                                        <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }} ({{ a.type }})</option>
                                    </select>
                                    <p class="text-xs text-muted-foreground">Contributions will create real transactions from this account.</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-foreground">Colour</label>
                                    <div class="mt-2 flex gap-2">
                                        <button v-for="c in colors" :key="c" type="button" class="h-8 w-8 rounded-full transition" :class="addForm.color === c ? 'ring-2 ring-offset-2 ring-primary' : ''" :style="{ backgroundColor: c }" @click="addForm.color = c" />
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 border-t border-border pt-4">
                                    <button type="submit" class="flex-1 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:opacity-50" :disabled="addForm.processing">
                                        {{ addForm.processing ? 'Saving…' : 'Create goal' }}
                                    </button>
                                    <button type="button" class="rounded-lg border border-border px-4 py-2.5 text-sm font-medium text-muted-foreground transition hover:bg-muted" @click="addOpen = false">Cancel</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </Transition>
            </Teleport>
        </div>

        <ConfirmDialog
            :open="confirmOpen"
            title="Delete this goal?"
            description="This will permanently remove the savings goal."
            @close="confirmOpen = false"
            @confirm="doDelete"
        />
    </ModuleLayout>
</template>
