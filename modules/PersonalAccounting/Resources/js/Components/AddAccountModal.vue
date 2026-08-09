<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Wallet, Landmark, Smartphone, X } from 'lucide-vue-next';

const open = ref(false);
const form = useForm({
    name: '',
    type: 'cash',
    currency: 'BDT',
    balance: '',
    is_default: false,
    color: '#6366f1',
});

const types = [
    { id: 'cash', label: 'Cash', icon: Wallet },
    { id: 'bank', label: 'Bank', icon: Landmark },
    { id: 'mobile_banking', label: 'Mobile Banking', icon: Smartphone },
];

const colors = ['#6366f1', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];

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
    form.post(route('personal.accounts.store'), { onSuccess: close });
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
                <form class="w-full max-w-lg rounded-2xl border border-border bg-card p-6 shadow-xl" @submit.prevent="submit">
                    <div class="mb-5 flex items-start justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-foreground">Add account</h2>
                            <p class="text-sm text-muted-foreground">Create a new wallet or account.</p>
                        </div>
                        <button type="button" class="rounded-md p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground" @click="close"><X class="h-5 w-5" /></button>
                    </div>

                    <div class="space-y-5">
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-foreground">Account name</label>
                            <input v-model="form.name" type="text" placeholder="e.g. My Savings" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                            <p v-if="form.errors.name" class="text-sm text-rose-500">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-foreground">Type</label>
                            <div class="mt-1.5 grid grid-cols-3 gap-2">
                                <button v-for="t in types" :key="t.id" type="button"
                                    class="flex flex-col items-center gap-1.5 rounded-xl border-2 px-3 py-3 text-sm font-medium transition"
                                    :class="form.type === t.id ? 'border-primary bg-primary/5 text-primary' : 'border-border text-muted-foreground hover:bg-muted'"
                                    @click="form.type = t.id">
                                    <component :is="t.icon" class="h-5 w-5" /> {{ t.label }}
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Opening balance</label>
                                <input v-model="form.balance" type="number" step="0.01" placeholder="0" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Currency</label>
                                <input v-model="form.currency" type="text" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                            </div>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-foreground">Colour</label>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <button v-for="c in colors" :key="c" type="button"
                                    class="h-8 w-8 rounded-full transition"
                                    :class="form.color === c ? 'ring-2 ring-offset-2 ring-primary' : ''"
                                    :style="{ backgroundColor: c }"
                                    @click="form.color = c" />
                            </div>
                        </div>

                        <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-foreground">
                            <input v-model="form.is_default" type="checkbox" class="h-4 w-4 rounded accent-primary" />
                            Set as default account
                        </label>

                        <div class="flex items-center gap-3 border-t border-border pt-4">
                            <button type="submit" class="flex-1 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:opacity-50" :disabled="form.processing">
                                {{ form.processing ? 'Saving…' : 'Add account' }}
                            </button>
                            <button type="button" class="rounded-lg border border-border px-4 py-2.5 text-sm font-medium text-muted-foreground transition hover:bg-muted" @click="close">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
        </Transition>
    </Teleport>
</template>
