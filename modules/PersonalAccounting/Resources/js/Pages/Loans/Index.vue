<script setup lang="ts">
import ModuleLayout from '../../Layouts/ModuleLayout.vue';
import MoneyText from '../../Components/MoneyText.vue';
import ProgressBar from '../../Components/ProgressBar.vue';
import ProgressCircle from '../../Components/ProgressCircle.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import AddLoanModal from '../../Components/AddLoanModal.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Plus, HandCoins, HandHeart, Trash2, X, CalendarClock, User, FileDown } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    loans: Array<{
        id: number;
        name: string;
        direction: 'lent' | 'borrowed';
        contact: { id: number; name: string } | null;
        principal_amount: number;
        remaining_balance: number;
        total_paid: number;
        interest_rate: number;
        start_date: string;
        due_date: string | null;
        payment_frequency: string;
        payment_amount: number;
        status: string;
        progress_percent: number;
        is_overdue: boolean;
        projection: { months_remaining: number | null; estimated_clear: string | null };
        payments_count: number;
    }>;
    summary: { total_lent: number; total_borrowed: number; net: number };
    contacts: Array<{ id: number; name: string; type: string }>;
    accounts: Array<{ id: number; name: string; type: string; balance: number }>;
}>();

const addModal = ref<InstanceType<typeof AddLoanModal> | null>(null);
const payOpen = ref(false);
const payingLoan = ref<{ id: number; name: string; direction: string } | null>(null);
const payForm = useForm({ amount: '', account_id: '', paid_at: new Date().toISOString().slice(0, 10), note: '' });
const confirmOpen = ref(false);
const deletingId = ref<number | null>(null);

function openPay(loan: { id: number; name: string; direction: string }) {
    payingLoan.value = loan;
    payForm.reset();
    payForm.defaults();
    payOpen.value = true;
}

function submitPay() {
    if (!payingLoan.value) return;
    payForm.post(route('personal.loans.pay', payingLoan.value.id), {
        preserveScroll: true,
        onSuccess: () => (payOpen.value = false),
    });
}

function confirmDelete(id: number) {
    deletingId.value = id;
    confirmOpen.value = true;
}

function doDelete() {
    router.delete(route('personal.loans.destroy', deletingId.value), {
        preserveScroll: true,
        onSuccess: () => (confirmOpen.value = false),
    });
}

function formatDate(d: string | null): string {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>

<template>
    <ModuleLayout title="Loans" :breadcrumbs="[{ title: 'Personal', href: '/personal/dashboard' }, { title: 'Loans', href: '/personal/loans' }]">
        <div class="space-y-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">Loans</h1>
                    <p class="text-sm text-muted-foreground">Track money you've lent out or borrowed.</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="route('personal.contacts.index')" class="inline-flex items-center gap-1.5 rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted-foreground transition hover:bg-muted">
                        <User class="h-4 w-4" /> Contacts
                    </Link>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90" @click="addModal?.openModal()">
                        <Plus class="h-4 w-4" /> New loan
                    </button>
                </div>
            </div>

            <!-- Summary -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <p class="flex items-center gap-2 text-sm text-muted-foreground"><HandCoins class="h-4 w-4 text-emerald-500" /> To receive (lent)</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400"><MoneyText :value="summary.total_lent" compact /></p>
                </div>
                <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <p class="flex items-center gap-2 text-sm text-muted-foreground"><HandHeart class="h-4 w-4 text-rose-500" /> To repay (borrowed)</p>
                    <p class="mt-1 text-2xl font-bold text-rose-600 dark:text-rose-400"><MoneyText :value="summary.total_borrowed" compact /></p>
                </div>
                <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <p class="text-sm text-muted-foreground">Net receivable</p>
                    <p class="mt-1 text-2xl font-bold text-foreground"><MoneyText :value="summary.net" compact /></p>
                </div>
            </div>

            <!-- Loan cards -->
            <div v-if="loans.length === 0" class="rounded-xl border border-dashed border-border p-12 text-center">
                <HandCoins class="mx-auto h-10 w-10 text-muted-foreground" />
                <p class="mt-3 text-sm text-muted-foreground">No loans yet. Create your first one to track lending or borrowing.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div v-for="loan in loans" :key="loan.id" class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl" :class="loan.direction === 'lent' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400'">
                                <component :is="loan.direction === 'lent' ? HandCoins : HandHeart" class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-foreground">{{ loan.name }}</h3>
                                <p class="flex items-center gap-1 text-xs text-muted-foreground"><User class="h-3 w-3" /> {{ loan.contact?.name ?? '—' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-medium capitalize" :class="loan.direction === 'lent' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400'">
                                {{ loan.direction }}
                            </span>
                            <span v-if="loan.is_overdue" class="inline-flex animate-pulse items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-400" title="Loan overdue">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500" /> {{ loan.days_overdue ?? 'overdue' }}
                            </span>
                            <button type="button" class="rounded-md p-1 text-muted-foreground transition hover:bg-muted hover:text-destructive" @click="confirmDelete(loan.id)"><Trash2 class="h-4 w-4" /></button>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center gap-4">
                        <ProgressCircle :value="loan.progress_percent" :color="loan.direction === 'lent' ? '#10b981' : '#f43f5e'" :size="76" :stroke="8" />
                        <div>
                            <p class="text-xs text-muted-foreground">Remaining</p>
                            <p class="text-lg font-bold text-foreground"><MoneyText :value="loan.remaining_balance" compact /></p>
                            <p class="text-xs text-muted-foreground">of <MoneyText :value="loan.principal_amount" compact /> principal</p>
                        </div>
                    </div>

                    <div class="mt-3 space-y-1 text-xs text-muted-foreground">
                        <p class="flex items-center gap-1.5"><CalendarClock class="h-3.5 w-3.5" /> Started {{ formatDate(loan.start_date) }} · Due {{ formatDate(loan.due_date) }}</p>
                        <p>Paid <MoneyText :value="loan.total_paid" compact /> · {{ loan.payments_count }} payment(s)</p>
                        <p v-if="loan.projection.months_remaining">Clears ~{{ loan.projection.months_remaining }}mo ({{ loan.projection.estimated_clear }})</p>
                        <p v-if="loan.interest_rate > 0">{{ loan.interest_rate }}% interest · {{ loan.payment_frequency }}</p>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <button type="button" class="rounded-lg bg-primary/10 px-3 py-2 text-sm font-semibold text-primary transition hover:bg-primary/20" @click="openPay({ id: loan.id, name: loan.name, direction: loan.direction })">
                            Record payment
                        </button>
                        <a
                            :href="route('personal.loans.statement', loan.id)"
                            target="_blank"
                            class="inline-flex items-center justify-center gap-1 rounded-lg border border-border px-3 py-2 text-sm font-semibold text-muted-foreground transition hover:bg-muted"
                        >
                            <FileDown class="h-4 w-4" /> Statement
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <AddLoanModal ref="addModal" :contacts="contacts" :accounts="accounts" />

        <!-- Record payment modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" leave-active-class="transition-opacity duration-150" leave-to-class="opacity-0">
                <div v-if="payOpen" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm" @click="payOpen = false" />
            </Transition>
            <Transition enter-active-class="transition scale duration-200" enter-from-class="opacity-0 scale-95" leave-active-class="transition scale duration-150" leave-to-class="opacity-0 scale-95">
                <div v-if="payOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <form class="w-full max-w-md rounded-2xl border border-border bg-card p-6 shadow-xl" @submit.prevent="submitPay">
                        <div class="mb-5 flex items-start justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-foreground">Record payment</h2>
                                <p class="text-sm text-muted-foreground capitalize">{{ payingLoan?.name }} · {{ payingLoan?.direction }}</p>
                            </div>
                            <button type="button" class="rounded-md p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground" @click="payOpen = false"><X class="h-5 w-5" /></button>
                        </div>
                        <div class="space-y-4">
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Amount (BDT)</label>
                                <input v-model="payForm.amount" type="number" step="0.01" placeholder="e.g. 5000" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                                <p v-if="payForm.errors.amount" class="text-sm text-rose-500">{{ payForm.errors.amount }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Payment date</label>
                                <input v-model="payForm.paid_at" type="date" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Move money via account (optional)</label>
                                <select v-model="payForm.account_id" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                                    <option value="">— Don't create a transaction —</option>
                                    <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }} ({{ a.type }})</option>
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Note</label>
                                <input v-model="payForm.note" type="text" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                            </div>
                            <div class="flex items-center gap-3 border-t border-border pt-4">
                                <button type="submit" class="flex-1 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:opacity-50" :disabled="payForm.processing">
                                    {{ payForm.processing ? 'Saving…' : 'Record payment' }}
                                </button>
                                <button type="button" class="rounded-lg border border-border px-4 py-2.5 text-sm font-medium text-muted-foreground transition hover:bg-muted" @click="payOpen = false">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </Transition>
        </Teleport>

        <ConfirmDialog
            :open="confirmOpen"
            title="Delete this loan?"
            description="This will permanently remove the loan and its payments."
            @close="confirmOpen = false"
            @confirm="doDelete"
        />
    </ModuleLayout>
</template>
