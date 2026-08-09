<script setup lang="ts">
import { computed } from 'vue';

interface Slice {
    label: string;
    value: number;
}

const props = defineProps<{
    data: Slice[];
    size?: number;
    thickness?: number;
    colors?: string[];
    centerLabel?: string;
    centerValue?: string;
}>();

const palette = props.colors ?? ['#6366f1', '#f59e0b', '#ef4444', '#10b981', '#0ea5e9', '#8b5cf6'];

const total = computed(() => props.data.reduce((sum, d) => sum + d.value, 0));

const segments = computed(() => {
    let cumulative = 0;
    const r = props.size ? props.size / 2 : 120;
    const c = props.thickness ?? 28;

    return props.data.map((slice, i) => {
        const start = cumulative;
        cumulative += slice.value;
        const end = cumulative;

        const largeArc = (end - start) > total.value / 2 ? 1 : 0;
        const startAngle = (start / total.value) * 360 - 90;
        const endAngle = (end / total.value) * 360 - 90;

        const rad = (a: number) => (a * Math.PI) / 180;
        const outerR = r;
        const innerR = r - c;

        const x1 = r + outerR * Math.cos(rad(startAngle));
        const y1 = r + outerR * Math.sin(rad(startAngle));
        const x2 = r + outerR * Math.cos(rad(endAngle));
        const y2 = r + outerR * Math.sin(rad(endAngle));
        const x3 = r + innerR * Math.cos(rad(endAngle));
        const y3 = r + innerR * Math.sin(rad(endAngle));
        const x4 = r + innerR * Math.cos(rad(startAngle));
        const y4 = r + innerR * Math.sin(rad(startAngle));

        return {
            path: `M ${x1} ${y1} A ${outerR} ${outerR} 0 ${largeArc} 1 ${x2} ${y2} L ${x3} ${y3} A ${innerR} ${innerR} 0 ${largeArc} 0 ${x4} ${y4} Z`,
            color: palette[i % palette.length],
        };
    });
});
</script>

<template>
    <div class="flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
        <div class="relative" :style="{ width: `${size ?? 240}px`, height: `${size ?? 240}px` }">
            <svg :viewBox="`0 0 ${size ?? 240} ${size ?? 240}`" class="h-full w-full -rotate-0">
                <circle
                    v-if="total === 0"
                    :cx="(size ?? 240) / 2"
                    :cy="(size ?? 240) / 2"
                    :r="(size ?? 240) / 2 - (thickness ?? 28) / 2"
                    :stroke-width="thickness ?? 28"
                    class="fill-none stroke-muted"
                />
                <path v-for="(seg, i) in segments" :key="i" :d="seg.path" :fill="seg.color" class="transition hover:opacity-80" />
            </svg>
            <div v-if="centerLabel || centerValue" class="absolute inset-0 flex flex-col items-center justify-center">
                <span v-if="centerValue" class="text-2xl font-bold text-foreground">{{ centerValue }}</span>
                <span v-if="centerLabel" class="text-xs text-muted-foreground">{{ centerLabel }}</span>
            </div>
        </div>

        <div class="w-full max-w-[200px] space-y-2">
            <div v-for="(slice, i) in data" :key="i" class="flex items-center justify-between gap-3 text-sm">
                <span class="inline-flex items-center gap-2 capitalize text-muted-foreground">
                    <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: palette[i % palette.length] }" />
                    {{ slice.label }}
                </span>
                <span class="font-semibold text-foreground">{{ slice.value }}</span>
            </div>
            <div v-if="total === 0" class="text-center text-xs text-muted-foreground">No data yet</div>
        </div>
    </div>
</template>
