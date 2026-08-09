<script setup lang="ts">
import { computed } from 'vue';

interface Point {
    label: string;
    value: number;
}

const props = withDefaults(
    defineProps<{
        data: Point[];
        height?: number;
        color?: string;
        secondaryColor?: string;
        formatter?: (value: number) => string;
        showGrid?: boolean;
        showTooltips?: boolean;
        showAreas?: boolean;
    }>(),
    {
        height: 240,
        color: '#6366f1',
        secondaryColor: '#818cf8',
        showGrid: true,
        showTooltips: true,
        showAreas: true,
        formatter: (value: number) => String(value),
    },
);

const width = 1000; // viewBox units
const padX = 12;
const padY = 24;

const points = computed(() => props.data.map((d) => ({ label: d.label, value: d.value })));
const maxVal = computed(() => Math.max(...points.value.map((p) => p.value), 1));
const minVal = computed(() => Math.min(...points.value.map((p) => p.value), 0));
const range = computed(() => maxVal.value - minVal.value || 1);

const plotW = computed(() => width - padX * 2);
const plotH = computed(() => props.height - padY * 2);

function x(index: number): number {
    if (points.value.length <= 1) return padX;
    return padX + (index / (points.value.length - 1)) * plotW.value;
}

function y(value: number): number {
    return padY + plotH.value - ((value - minVal.value) / range.value) * plotH.value;
}

const linePath = computed(() =>
    points.value.map((p, i) => `${i === 0 ? 'M' : 'L'} ${x(i).toFixed(2)} ${y(p.value).toFixed(2)}`).join(' '),
);

const areaPath = computed(() => `${linePath.value} L ${x(points.value.length - 1).toFixed(2)} ${padY + plotH.value} L ${x(0).toFixed(2)} ${padY + plotH.value} Z`);

const gridLines = computed(() => {
    if (!props.showGrid) return [];
    const lines = [];
    for (let i = 0; i <= 4; i++) {
        const yy = padY + (i / 4) * plotH.value;
        lines.push({ y: yy });
    }
    return lines;
});

function labelFor(i: number): number {
    return padY + plotH.value - i * (plotH.value / 4);
}

function hoverState(): void {
    // handled via CSS group hover on circles
}
</script>

<template>
    <svg
        :viewBox="`0 0 ${width} ${height}`"
        class="w-full select-none"
        :style="{ height: `${height}px` }"
        preserveAspectRatio="none"
    >
        <defs>
            <linearGradient :id="`area-${color.replace('#', '')}`" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" :stop-color="color" stop-opacity="0.35" />
                <stop offset="100%" :stop-color="color" stop-opacity="0.02" />
            </linearGradient>
        </defs>

        <!-- grid -->
        <g v-if="showGrid">
            <line
                v-for="(line, i) in gridLines"
                :key="i"
                :x1="padX"
                :x2="width - padX"
                :y1="line.y"
                :y2="line.y"
                stroke="currentColor"
                class="stroke-border/60"
                stroke-dasharray="4 4"
            />
        </g>

        <!-- area fill -->
        <path v-if="showAreas" :d="areaPath" :fill="`url(#area-${color.replace('#', '')})`" />

        <!-- line -->
        <path
            :d="linePath"
            fill="none"
            :stroke="color"
            stroke-width="3"
            stroke-linecap="round"
            stroke-linejoin="round"
            vector-effect="non-scaling-stroke"
        />

        <!-- points -->
        <g v-if="showTooltips">
            <circle
                v-for="(p, i) in points"
                :key="i"
                :cx="x(i)"
                :cy="y(p.value)"
                r="3.5"
                :fill="color"
                class="opacity-0 transition group-hover:opacity-100"
            >
                <title>{{ p.label }}: {{ formatter(p.value) }}</title>
            </circle>
        </g>
    </svg>

    <!-- x labels -->
    <div class="mt-1 flex justify-between px-1 text-[10px] text-muted-foreground">
        <span v-for="(p, i) in points" :key="i">{{ p.label }}</span>
    </div>
</template>
