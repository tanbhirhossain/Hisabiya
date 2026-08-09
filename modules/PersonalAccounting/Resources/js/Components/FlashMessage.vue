<script setup lang="ts">
import { CheckCircle2, XCircle, X } from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage();
const visible = ref(false);
const type = ref<'success' | 'error'>('success');
const message = ref('');
let timer: ReturnType<typeof setTimeout> | undefined;

const flash = computed(() => (page.props as any).flash);

watch(
    flash,
    (value) => {
        const msg = value?.success ?? value?.error;
        if (!msg) return;
        type.value = value.success ? 'success' : 'error';
        message.value = msg;
        visible.value = true;
        clearTimeout(timer);
        timer = setTimeout(() => (visible.value = false), 4000);
    },
    { immediate: true, deep: true },
);
</script>

<template>
    <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-2">
        <div
            v-if="visible"
            class="fixed top-4 right-4 z-[100] flex w-full max-w-sm items-start gap-3 rounded-xl border p-4 shadow-lg"
            :class="type === 'success' ? 'border-emerald-200 bg-white text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-950 dark:text-emerald-300' : 'border-rose-200 bg-white text-rose-800 dark:border-rose-500/30 dark:bg-rose-950 dark:text-rose-300'"
            role="status"
        >
            <CheckCircle2 v-if="type === 'success'" class="mt-0.5 h-5 w-5 shrink-0" />
            <XCircle v-else class="mt-0.5 h-5 w-5 shrink-0" />
            <p class="flex-1 text-sm font-medium">{{ message }}</p>
            <button type="button" class="shrink-0 opacity-60 hover:opacity-100" @click="visible = false"><X class="h-4 w-4" /></button>
        </div>
    </Transition>
</template>
