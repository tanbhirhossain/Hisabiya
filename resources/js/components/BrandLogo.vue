<script setup lang="ts">
import { Wallet } from 'lucide-vue-next';

interface Props {
    showName?: boolean;
    name?: string;
    light?: boolean; // for dark-on-image contexts (e.g. split auth panel)
}

withDefaults(defineProps<Props>(), {
    showName: true,
    name: 'Hisabiya',
    light: false,
});
</script>

<template>
    <div class="flex items-center gap-2.5">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm">
            <Wallet class="h-5 w-5" />
        </div>
        <span v-if="showName" class="text-lg font-bold tracking-tight" :class="light ? 'text-white' : 'text-foreground'">
            {{ name }}
        </span>
    </div>
</template>
