<script setup lang="ts">
import { computed } from 'vue';
import { formatMoney, formatNumber } from '../Lib/format';

const props = withDefaults(
    defineProps<{
        value: number | string | null | undefined;
        type?: 'income' | 'expense' | 'transfer' | '';
        compact?: boolean;
        signed?: boolean;
        className?: string;
    }>(),
    { type: '', compact: false, signed: false, className: '' },
);

const colorClass = computed(() => {
    switch (props.type) {
        case 'income':
            return 'text-emerald-600 dark:text-emerald-400';
        case 'expense':
            return 'text-rose-600 dark:text-rose-400';
        case 'transfer':
            return 'text-sky-600 dark:text-sky-400';
        default:
            return 'text-foreground';
    }
});

const sign = computed(() => {
    const num = Number(props.value ?? 0);
    if (!props.signed) return '';
    return num >= 0 ? '+' : '';
});
</script>

<template>
    <span class="tabular-nums" :class="[colorClass, className]">
        {{ sign }}{{ compact ? formatNumber(value) : formatMoney(value) }}
    </span>
</template>
