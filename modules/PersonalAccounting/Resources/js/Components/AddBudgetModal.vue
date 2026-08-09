<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { X } from 'lucide-vue-next';
import { categoryIcon } from '../Lib/format';

const props = defineProps<{
    categories: Array<{ id: number; name: string; icon?: string; color?: string }>;
}>();

const open = ref(false);
const form = useForm({
    category_id: '',
    amount: '',
    period: 'monthly',
    start_date: new Date().toISOString().slice(0, 10),
    end_date: '',
});

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
    form.post(route('personal.budgets.store'), { onSuccess: close });
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
                            <h2 class="text-lg font-semibold text-foreground">Create budget</h2>
                            <p class="text-sm text-muted-foreground">Set a spending limit for a category.</p>
                        </div>
                        <button type="button" class="rounded-md p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground" @click="close"><X class="h-5 w-5" /></button>
                    </div>

                    <div class="space-y-5">
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-foreground">Category</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button v-for="cat in categories" :key="cat.id" type="button"
                                    class="flex items-center gap-2 rounded-lg border px-2.5 py-2 text-left text-sm transition"
                                    :class="form.category_id === cat.id ? 'border-primary/50 bg-primary/5' : 'border-border hover:bg-muted'"
                                    @click="form.category_id = cat.id">
                                    <component :is="categoryIcon(cat.icon)" class="h-4 w-4" :style="{ color: cat.color }" />
                                    <span class="truncate">{{ cat.name }}</span>
                                </button>
                            </div>
                            <p v-if="form.errors.category_id" class="text-sm text-rose-500">{{ form.errors.category_id }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-foreground">Budget amount (BDT)</label>
                            <input v-model="form.amount" type="number" step="0.01" placeholder="e.g. 15000" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                            <p v-if="form.errors.amount" class="text-sm text-rose-500">{{ form.errors.amount }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Period</label>
                                <select v-model="form.period" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Start date</label>
                                <input v-model="form.start_date" type="date" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                            </div>
                        </div>

                        <div class="flex items-center gap-3 border-t border-border pt-4">
                            <button type="submit" class="flex-1 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:opacity-50" :disabled="form.processing">
                                {{ form.processing ? 'Saving…' : 'Create budget' }}
                            </button>
                            <button type="button" class="rounded-lg border border-border px-4 py-2.5 text-sm font-medium text-muted-foreground transition hover:bg-muted" @click="close">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
        </Transition>
    </Teleport>
</template>
