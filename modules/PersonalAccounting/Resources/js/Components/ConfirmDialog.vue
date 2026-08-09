<script setup lang="ts">
import { AlertTriangle } from 'lucide-vue-next';

defineProps<{
    open: boolean;
    title?: string;
    description?: string;
    confirmLabel?: string;
    loading?: boolean;
}>();

const emit = defineEmits<{ (e: 'close'): void; (e: 'confirm'): void }>();
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" leave-active-class="transition-opacity duration-150" leave-to-class="opacity-0">
            <div v-if="open" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm" @click="emit('close')" />
        </Transition>
        <Transition enter-active-class="transition scale duration-200" enter-from-class="opacity-0 scale-95" leave-active-class="transition scale duration-150" leave-to-class="opacity-0 scale-95">
            <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="w-full max-w-md rounded-2xl border border-border bg-card p-6 shadow-xl">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600 dark:bg-rose-500/10">
                            <AlertTriangle class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-foreground">{{ title ?? 'Are you sure?' }}</h2>
                            <p class="mt-1 text-sm text-muted-foreground">{{ description ?? 'This action cannot be undone.' }}</p>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted-foreground transition hover:bg-muted" :disabled="loading" @click="emit('close')">
                            Cancel
                        </button>
                        <button type="button" class="rounded-lg bg-destructive px-4 py-2 text-sm font-semibold text-destructive-foreground transition hover:bg-destructive/90" :disabled="loading" @click="emit('confirm')">
                            {{ loading ? 'Deleting…' : (confirmLabel ?? 'Delete') }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
