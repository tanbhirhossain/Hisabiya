<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PublicLayout from '../../Layouts/PublicLayout.vue';
import { ShieldCheck } from 'lucide-vue-next';

const props = defineProps<{
    title: string;
    updated: string;
    sections: Array<{ heading: string; body: string }>;
}>();
</script>

<template>
    <PublicLayout>
        <Head :title="title" />

        <div class="mx-auto max-w-3xl px-4 py-14">
            <div class="mb-8 flex items-center gap-3">
                <div class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <ShieldCheck class="h-5 w-5" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground md:text-3xl">{{ title }}</h1>
                    <p class="text-sm text-muted-foreground">Last updated: {{ updated }}</p>
                </div>
            </div>

            <div class="space-y-6">
                <section v-for="section in sections" :key="section.heading">
                    <h2 class="text-base font-semibold text-foreground">{{ section.heading }}</h2>
                    <p class="mt-1.5 text-sm leading-relaxed text-muted-foreground">{{ section.body }}</p>
                </section>
            </div>
        </div>
    </PublicLayout>
</template>
