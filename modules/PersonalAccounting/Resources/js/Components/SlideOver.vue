<script setup lang="ts">
import { X } from 'lucide-vue-next';

defineProps<{
    open: boolean;
    title?: string;
    description?: string;
}>();

const emit = defineEmits<{ (e: 'close'): void }>();
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150"
            leave-to-class="opacity-0"
        >
            <div v-if="open" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm" @click="emit('close')" />
        </Transition>

        <Transition
            enter-active-class="transition-transform duration-300 ease-out"
            enter-from-class="translate-x-full"
            leave-active-class="transition-transform duration-200 ease-in"
            leave-to-class="translate-x-full"
        >
            <div
                v-if="open"
                class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col bg-card shadow-2xl"
            >
                <div class="flex items-start justify-between border-b border-border px-5 py-4">
                    <div>
                        <h2 class="text-lg font-semibold text-foreground">{{ title }}</h2>
                        <p v-if="description" class="mt-0.5 text-sm text-muted-foreground">{{ description }}</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-md p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                        @click="emit('close')"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-5 py-5">
                    <slot />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
