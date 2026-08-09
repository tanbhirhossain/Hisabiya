<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        data: number[];
        color?: string;
        width?: number;
        height?: number;
    }>(),
    {
        color: '#6366f1',
        width: 96,
        height: 32,
    },
);

const values = computed(() => props.data);
const max = computed(() => Math.max(...values.value, 1));
const min = computed(() => Math.min(...values.value, 0));
const range = computed(() => max.value - min.value || 1);

const path = computed(() =>
    values.value
        .map((v, i) => {
            const x = (i / Math.max(values.value.length - 1, 1)) * props.width;
            const y = props.height - ((v - min.value) / range.value) * props.height;
            return `${i === 0 ? 'M' : 'L'} ${x.toFixed(1)} ${y.toFixed(1)}`;
        })
        .join(' '),
);
</script>

<template>
    <svg :width="width" :height="height" :viewBox="`0 0 ${width} ${height}`" class="overflow-visible">
        <path :d="path" fill="none" :stroke="color" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
</template>
