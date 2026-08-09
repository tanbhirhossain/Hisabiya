<script setup lang="ts">
import { computed } from 'vue';
import { TYPE_STYLES } from '../Lib/format';

const props = defineProps<{ type: 'income' | 'expense' | 'transfer' | string }>();

const styles = computed(() => TYPE_STYLES[props.type] ?? TYPE_STYLES.expense);
</script>

<template>
    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium capitalize" :class="styles.bg">
        <span class="h-1.5 w-1.5 rounded-full" :class="styles.dot" />
        {{ type }}
    </span>
</template>
