<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref, watch } from 'vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const props = defineProps<{
    type: 'line' | 'bar' | 'doughnut' | 'pie';
    data: any;
    options?: any;
    height?: number;
}>();

const canvas = ref<HTMLCanvasElement | null>(null);
let chart: Chart | null = null;

onMounted(() => {
    if (!canvas.value) return;
    chart = new Chart(canvas.value, {
        type: props.type,
        data: props.data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            ...props.options,
        },
    });
});

watch(
    () => props.data,
    (data) => {
        if (!chart) return;
        chart.data = data;
        chart.update();
    },
    { deep: true },
);

watch(
    () => props.options,
    (options) => {
        if (!chart) return;
        chart.options = options;
        chart.update();
    },
    { deep: true },
);

onBeforeUnmount(() => {
    chart?.destroy();
    chart = null;
});
</script>

<template>
    <div :style="{ height: `${height ?? 260}px` }" class="relative w-full">
        <canvas ref="canvas" />
    </div>
</template>
