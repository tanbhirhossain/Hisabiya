<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    label: string;
    value: string | number;
    icon?: Component;
    trend?: string;
    trendPositive?: boolean;
    iconClass?: string;
}>();
</script>

<template>
    <div class="group relative overflow-hidden rounded-xl border border-border bg-card p-5 shadow-sm transition hover:shadow-md">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-muted-foreground">{{ label }}</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-foreground">{{ value }}</p>
                <p
                    v-if="trend"
                    class="mt-2 inline-flex items-center gap-1 text-xs font-medium"
                    :class="trendPositive ? 'text-emerald-600' : 'text-rose-600'"
                >
                    {{ trend }}
                </p>
            </div>
            <div
                v-if="icon"
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg transition group-hover:scale-105"
                :class="iconClass ?? 'bg-primary/10 text-primary'"
            >
                <component :is="icon" class="h-5 w-5" />
            </div>
        </div>
    </div>
</template>
