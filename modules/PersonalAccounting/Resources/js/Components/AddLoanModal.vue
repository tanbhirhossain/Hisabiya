<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { X, HandCoins, HandHeart } from 'lucide-vue-next';

const props = defineProps<{
    contacts: Array<{ id: number; name: string; type: string }>;
    accounts: Array<{ id: number; name: string; type: string; balance: number }>;
}>();

const open = ref(false);
const form = useForm({
    name: '',
    direction: 'lent',
    contact_id: '',
    principal_amount: '',
    interest_rate: '0',
    start_date: new Date().toISOString().slice(0, 10),
    due_date: '',
    payment_frequency: 'monthly',
    payment_amount: '',
    account_id: '',
    notes: '',
});

const frequencies = ['weekly', 'biweekly', 'monthly', 'quarterly', 'yearly'];

function openModal() {
    form.reset();
    form.defaults();
    open.value = true;
}

function close() {
    open.value = false;
    form.reset();
}

function submit() {
    form.post(route('personal.loans.store'), { onSuccess: close });
}

defineExpose({ openModal });
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" leave-active-class="transition-opacity duration-150" leave-to-class="opacity-0">
            <div v-if="open" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm" @click="close" />
        </Transition>
        <Transition enter-active-class="transition scale duration-200" enter-from-class="opacity-0 scale-95" leave-active-class="transition scale duration-150" leave-to-class="opacity-0 scale-95">
            <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <form class="max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-border bg-card p-6 shadow-xl" @submit.prevent="submit">
                    <div class="mb-5 flex items-start justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-foreground">New loan</h2>
                            <p class="text-sm text-muted-foreground">Record money you lent or borrowed.</p>
                        </div>
                        <button type="button" class="rounded-md p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground" @click="close"><X class="h-5 w-5" /></button>
                    </div>

                    <div class="space-y-5">
                        <!-- Direction toggle -->
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button"
                                class="flex items-center justify-center gap-2 rounded-xl border-2 px-3 py-3 text-sm font-semibold transition"
                                :class="form.direction === 'lent' ? 'border-emerald-500 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'border-border text-muted-foreground hover:bg-muted'"
                                @click="form.direction = 'lent'">
                                <HandCoins class="h-5 w-5" /> I lent money
                            </button>
                            <button type="button"
                                class="flex items-center justify-center gap-2 rounded-xl border-2 px-3 py-3 text-sm font-semibold transition"
                                :class="form.direction === 'borrowed' ? 'border-rose-500 bg-rose-500/10 text-rose-600 dark:text-rose-400' : 'border-border text-muted-foreground hover:bg-muted'"
                                @click="form.direction = 'borrowed'">
                                <HandHeart class="h-5 w-5" /> I borrowed money
                            </button>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-foreground">Loan name / purpose</label>
                            <input v-model="form.name" type="text" placeholder="e.g. Car loan, Emergency loan" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                            <p v-if="form.errors.name" class="text-sm text-rose-500">{{ form.errors.name }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-foreground">Contact (who you lent to / borrowed from)</label>
                            <select v-model="form.contact_id" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                                <option value="" disabled>Select a contact</option>
                                <option v-for="c in contacts" :key="c.id" :value="c.id">{{ c.name }} ({{ c.type }})</option>
                            </select>
                            <p class="text-xs text-muted-foreground">Create the contact from the Contacts page first if it's not listed.</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Principal (BDT)</label>
                                <input v-model="form.principal_amount" type="number" step="0.01" placeholder="e.g. 50000" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                                <p v-if="form.errors.principal_amount" class="text-sm text-rose-500">{{ form.errors.principal_amount }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Interest rate (%)</label>
                                <input v-model="form.interest_rate" type="number" step="0.01" min="0" max="100" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Start date</label>
                                <input v-model="form.start_date" type="date" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Due date (optional)</label>
                                <input v-model="form.due_date" type="date" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Payment frequency</label>
                                <select v-model="form.payment_frequency" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                                    <option v-for="f in frequencies" :key="f" :value="f" class="capitalize">{{ f }}</option>
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Scheduled payment (BDT)</label>
                                <input v-model="form.payment_amount" type="number" step="0.01" placeholder="0" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-foreground">Move principal via account (optional)</label>
                            <select v-model="form.account_id" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                                <option value="">— Don't create a transaction —</option>
                                <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }} ({{ a.type }})</option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-foreground">Notes</label>
                            <textarea v-model="form.notes" rows="2" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                        </div>

                        <div class="flex items-center gap-3 border-t border-border pt-4">
                            <button type="submit" class="flex-1 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:opacity-50" :disabled="form.processing">
                                {{ form.processing ? 'Saving…' : 'Create loan' }}
                            </button>
                            <button type="button" class="rounded-lg border border-border px-4 py-2.5 text-sm font-medium text-muted-foreground transition hover:bg-muted" @click="close">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
        </Transition>
    </Teleport>
</template>
