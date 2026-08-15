<script setup lang="ts">
import BrandLogo from '@/components/BrandLogo.vue';
import { usePage } from '@inertiajs/vue3';
import type { SharedData } from '@/types';

interface Props {
    class?: string;
}

defineProps<Props>();

const page = usePage<SharedData>();
</script>

<template>
    <div class="flex items-center gap-2">
        <BrandLogo :show-name="false" />
        <div class="grid text-left text-sm">
            <span class="truncate font-semibold leading-none">{{ page.props.name }}</span>
        </div>
    </div>
</template>

