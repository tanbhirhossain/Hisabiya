<script setup lang="ts">
import { computed } from 'vue';
import { categoryIcon } from '../Lib/format';

const props = defineProps<{
    icon?: string | null;
    color?: string | null;
    size?: 'sm' | 'md' | 'lg';
}>();

const comp = computed(() => categoryIcon(props.icon));
const sizes = { sm: 'h-7 w-7 rounded-lg', md: 'h-9 w-9 rounded-xl', lg: 'h-11 w-11 rounded-xl' };
const iconSizes = { sm: 'h-3.5 w-3.5', md: 'h-4 w-4', lg: 'h-5 w-5' };
</script>

<template>
    <div
        class="flex items-center justify-center"
        :class="sizes[size ?? 'md']"
        :style="{ backgroundColor: `${color ?? '#6366f1'}1a`, color: color ?? '#6366f1' }"
    >
        <component :is="comp" :class="iconSizes[size ?? 'md']" />
    </div>
</template>
