<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        value: number; // 0-100
        color?: string;
        height?: string;
        animated?: boolean;
    }>(),
    { height: 'h-2', animated: true },
);

const clamped = computed(() => Math.min(100, Math.max(0, props.value)));
</script>

<template>
    <div class="w-full overflow-hidden rounded-full bg-muted" :class="height">
        <div
            class="rounded-full transition-all duration-500"
            :class="animated ? 'transition-all duration-700 ease-out' : ''"
            :style="{ width: `${clamped}%`, backgroundColor: color ?? '#6366f1' }"
        />
    </div>
</template>
