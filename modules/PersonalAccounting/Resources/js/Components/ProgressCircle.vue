<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        value: number; // 0-100
        size?: number;
        stroke?: number;
        color?: string;
        trackColor?: string;
    }>(),
    { size: 96, stroke: 10, color: '#10b981', trackColor: '#e5e7eb' },
);

const radius = computed(() => (props.size - props.stroke) / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);
const clamped = computed(() => Math.min(100, Math.max(0, props.value)));
const dashoffset = computed(() => circumference.value - (clamped.value / 100) * circumference.value);
</script>

<template>
    <div class="relative inline-flex items-center justify-center" :style="{ width: `${size}px`, height: `${size}px` }">
        <svg :width="size" :height="size" class="-rotate-90">
            <circle
                :cx="size / 2" :cy="size / 2" :r="radius"
                fill="none" :stroke="trackColor" :stroke-width="stroke"
            />
            <circle
                :cx="size / 2" :cy="size / 2" :r="radius"
                fill="none" :stroke="color" :stroke-width="stroke"
                stroke-linecap="round"
                :stroke-dasharray="circumference"
                :stroke-dashoffset="dashoffset"
                class="transition-all duration-700 ease-out"
            />
        </svg>
        <div class="absolute inset-0 flex items-center justify-center">
            <slot>
                <span class="text-sm font-bold" :style="{ color }">{{ Math.round(value) }}%</span>
            </slot>
        </div>
    </div>
</template>
