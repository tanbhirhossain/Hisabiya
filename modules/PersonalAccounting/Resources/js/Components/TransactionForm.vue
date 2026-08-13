<script setup lang="ts">
import { computed, ref } from 'vue';
import { useTransactions } from '../Composables/useTransactions';
import SlideOver from './SlideOver.vue';
import { categoryIcon } from '../Lib/format';
import { ArrowDownToLine, ArrowUpFromLine, ArrowLeftRight, Calendar, Repeat, Paperclip, AlertTriangle } from 'lucide-vue-next';
import MoneyText from './MoneyText.vue';

const props = defineProps<{
    accounts: Array<{ id: number; name: string; type: string; color?: string; balance?: number; currency?: string }>;
    categories: Array<{ id: number; name: string; type: string; icon?: string; color?: string }>;
}>();

const {
    form, slideOpen, editing, close, submit,
} = useTransactions();

const attachment = ref<File | null>(null);
const attachmentName = ref<string>('');
const duplicateConfirmOpen = ref(false);

const duplicateWarning = computed(() => form.errors?.duplicate ?? '');

const types = [
    { id: 'expense', label: 'Expense', icon: ArrowDownToLine },
    { id: 'income', label: 'Income', icon: ArrowUpFromLine },
    { id: 'transfer', label: 'Transfer', icon: ArrowLeftRight },
];

const filteredCategories = computed(() => {
    if (form.type === 'transfer') return [];
    return props.categories.filter((c) => c.type === form.type);
});

const previewAmount = computed(() => {
    const num = Number(form.amount || 0);
    return num;
});

function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        attachment.value = file;
        attachmentName.value = file.name;
    }
}

function categoryIconFor(icon?: string) {
    return categoryIcon(icon);
}
</script>

<template>
    <SlideOver
        :open="slideOpen"
        :title="editing ? 'Edit transaction' : 'Add transaction'"
        description="Record an income, expense, or transfer"
        @close="close"
    >
        <form class="space-y-6" @submit.prevent="submit">
            <!-- Type toggle -->
            <div class="grid grid-cols-3 gap-2">
                <button
                    v-for="t in types"
                    :key="t.id"
                    type="button"
                    class="flex flex-col items-center gap-1.5 rounded-xl border-2 px-3 py-3 text-sm font-semibold transition"
                    :class="form.type === t.id
                        ? t.id === 'income'
                            ? 'border-emerald-500 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                            : t.id === 'expense'
                                ? 'border-rose-500 bg-rose-500/10 text-rose-600 dark:text-rose-400'
                                : 'border-sky-500 bg-sky-500/10 text-sky-600 dark:text-sky-400'
                        : 'border-border text-muted-foreground hover:bg-muted'"
                    @click="form.type = t.id"
                >
                    <component :is="t.icon" class="h-5 w-5" />
                    {{ t.label }}
                </button>
            </div>
            <p v-if="form.errors.type" class="text-sm text-rose-500">{{ form.errors.type }}</p>

            <!-- Amount -->
            <div>
                <label class="text-sm font-medium text-foreground">Amount (BDT)</label>
                <div class="mt-1.5 flex items-center gap-2 rounded-xl border-2 border-border bg-muted/40 px-4 py-3 focus-within:border-primary/60">
                    <span class="text-2xl font-semibold text-muted-foreground">৳</span>
                    <input
                        v-model="form.amount"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        class="w-full bg-transparent text-3xl font-bold tracking-tight text-foreground outline-none placeholder:text-muted-foreground/50"
                    />
                </div>
                <p v-if="form.errors.amount" class="mt-1 text-sm text-rose-500">{{ form.errors.amount }}</p>
                <p v-if="previewAmount > 0" class="mt-1 text-xs text-muted-foreground">
                    <MoneyText :value="previewAmount" :type="form.type" />
                </p>
            </div>

            <!-- From account -->
            <div class="space-y-1.5">
                <label class="text-sm font-medium text-foreground">{{ form.type === 'transfer' ? 'From account' : 'Account' }}</label>
                <select
                    v-model="form.account_id"
                    class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30"
                >
                    <option value="" disabled>Select an account</option>
                    <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                        {{ acc.name }} ({{ acc.type }})
                    </option>
                </select>
                <p v-if="form.errors.account_id" class="text-sm text-rose-500">{{ form.errors.account_id }}</p>
            </div>

            <!-- To account (transfer only) -->
            <div v-if="form.type === 'transfer'" class="space-y-1.5">
                <label class="text-sm font-medium text-foreground">To account</label>
                <select
                    v-model="form.to_account_id"
                    class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30"
                >
                    <option value="" disabled>Select destination account</option>
                    <option
                        v-for="acc in accounts.filter((a) => a.id !== form.account_id)"
                        :key="acc.id"
                        :value="acc.id"
                    >
                        {{ acc.name }} ({{ acc.type }})
                    </option>
                </select>
                <p v-if="form.errors.to_account_id" class="text-sm text-rose-500">{{ form.errors.to_account_id }}</p>
            </div>

            <!-- Category -->
            <div v-if="form.type !== 'transfer'" class="space-y-1.5">
                <label class="text-sm font-medium text-foreground">Category</label>
                <div class="grid grid-cols-2 gap-2">
                    <button
                        v-for="cat in filteredCategories"
                        :key="cat.id"
                        type="button"
                        class="flex items-center gap-2 rounded-lg border px-2.5 py-2 text-left text-sm transition"
                        :class="form.category_id === cat.id ? 'border-primary/50 bg-primary/5' : 'border-border hover:bg-muted'"
                        @click="form.category_id = cat.id"
                    >
                        <component :is="categoryIconFor(cat.icon)" class="h-4 w-4" :style="{ color: cat.color }" />
                        <span class="truncate">{{ cat.name }}</span>
                    </button>
                </div>
                <p v-if="form.errors.category_id" class="text-sm text-rose-500">{{ form.errors.category_id }}</p>
            </div>

            <!-- Date -->
            <div class="space-y-1.5">
                <label class="text-sm font-medium text-foreground">Date</label>
                <div class="relative">
                    <Calendar class="pointer-events-none absolute top-2.5 left-3 h-4 w-4 text-muted-foreground" />
                    <input
                        v-model="form.date"
                        type="date"
                        class="w-full rounded-lg border border-input bg-background py-2.5 pl-10 pr-3 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30"
                    />
                </div>
                <p v-if="form.errors.date" class="text-sm text-rose-500">{{ form.errors.date }}</p>
            </div>

            <!-- Note -->
            <div class="space-y-1.5">
                <label class="text-sm font-medium text-foreground">Note</label>
                <textarea
                    v-model="form.note"
                    rows="2"
                    placeholder="What was this for?"
                    class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30"
                />
            </div>

            <!-- Status toggle -->
            <div class="rounded-xl border border-border p-3">
                <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-foreground">
                    <input v-model="form.status" type="checkbox" class="h-4 w-4 rounded border-input accent-primary" true-value="pending" false-value="cleared" />
                    <span class="h-2.5 w-2.5 rounded-full" :class="form.status === 'pending' ? 'bg-amber-400' : 'bg-emerald-500'" />
                    {{ form.status === 'pending' ? 'Pending (not yet cleared)' : 'Cleared' }}
                </label>
            </div>

            <!-- Recurring -->
            <div class="rounded-xl border border-border p-3">
                <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-foreground">
                    <input v-model="form.is_recurring" type="checkbox" class="h-4 w-4 rounded border-input accent-primary" />
                    <Repeat class="h-4 w-4" />
                    Make this a recurring transaction
                </label>
                <div v-if="form.is_recurring" class="mt-3 grid grid-cols-2 gap-2">
                    <button
                        v-for="f in ['daily', 'weekly', 'monthly', 'yearly']"
                        :key="f"
                        type="button"
                        class="rounded-lg border px-2 py-2 text-xs font-medium capitalize transition"
                        :class="form.frequency === f ? 'border-primary/50 bg-primary/5 text-primary' : 'border-border text-muted-foreground hover:bg-muted'"
                        @click="form.frequency = f"
                    >
                        {{ f }}
                    </button>
                </div>

                <!-- End conditions -->
                <div v-if="form.is_recurring" class="mt-3 space-y-2 border-t border-border pt-3">
                    <p class="text-xs font-semibold text-foreground">Ends</p>
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-muted-foreground">
                        <input v-model="form.end_type" type="radio" value="never" class="accent-primary" /> Never
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-muted-foreground">
                        <input v-model="form.end_type" type="radio" value="on_date" class="accent-primary" /> On date
                        <input v-if="form.end_type === 'on_date'" v-model="form.end_date" type="date" class="ml-auto rounded-lg border border-input bg-background px-2 py-1 text-xs outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-muted-foreground">
                        <input v-model="form.end_type" type="radio" value="after_occurrences" class="accent-primary" /> After
                        <input v-if="form.end_type === 'after_occurrences'" v-model.number="form.max_occurrences" type="number" min="1" class="ml-auto w-20 rounded-lg border border-input bg-background px-2 py-1 text-xs outline-none focus-visible:ring-2 focus-visible:ring-primary/30" placeholder="times" />
                    </label>
                </div>
            </div>

            <!-- Attachment -->
            <div class="space-y-1.5">
                <label class="flex items-center gap-2 text-sm font-medium text-foreground">
                    <Paperclip class="h-4 w-4" /> Attachment
                </label>
                <label class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed border-border px-3 py-4 text-sm text-muted-foreground transition hover:bg-muted">
                    <input type="file" class="hidden" @change="onFileChange" />
                    <span v-if="!attachmentName">Click to upload a receipt or file</span>
                    <span v-else class="truncate font-medium text-foreground">{{ attachmentName }}</span>
                </label>
                <!-- Preview existing attachment when editing -->
                <a
                    v-if="editing?.attachment_path"
                    :href="'/storage/' + editing.attachment_path"
                    target="_blank"
                    rel="noopener"
                    class="mt-2 inline-flex items-center gap-1.5 text-xs font-medium text-primary hover:underline"
                >
                    <Paperclip class="h-3.5 w-3.5" /> View existing attachment
                </a>
            </div>

            <div class="flex items-center gap-3 border-t border-border pt-5">
                <button
                    type="submit"
                    class="flex-1 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90 disabled:opacity-50"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Saving…' : (editing ? 'Update transaction' : 'Add transaction') }}
                </button>
                <button type="button" class="rounded-lg border border-border px-4 py-2.5 text-sm font-medium text-muted-foreground transition hover:bg-muted" @click="close">
                    Cancel
                </button>
            </div>
        </form>

        <!-- Duplicate confirmation modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" leave-active-class="transition-opacity duration-150" leave-to-class="opacity-0">
                <div v-if="duplicateWarning" class="fixed inset-0 z-[60] bg-black/40 backdrop-blur-sm" @click="duplicateConfirmOpen = false" />
            </Transition>
            <Transition enter-active-class="transition scale duration-200" enter-from-class="opacity-0 scale-95" leave-active-class="transition scale duration-150" leave-to-class="opacity-0 scale-95">
                <div v-if="duplicateWarning" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                    <div class="w-full max-w-md rounded-2xl border border-border bg-card p-6 shadow-xl">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-500/10">
                                <AlertTriangle class="h-5 w-5" />
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-foreground">Possible duplicate</h2>
                                <p class="mt-1 text-sm text-muted-foreground">{{ duplicateWarning }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    Same account, amount and date as an existing transaction. Add it anyway?
                                </p>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end gap-2">
                            <button type="button" class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted-foreground transition hover:bg-muted" @click="form.clearErrors(); duplicateConfirmOpen = false">
                                Cancel
                            </button>
                            <button type="button" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-700" @click="submit(true)">
                                Add anyway
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </SlideOver>
</template>

