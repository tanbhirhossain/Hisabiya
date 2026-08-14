<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import {
    ArrowRight,
    Wallet,
    Building2,
    Check,
    Sparkles,
    LayoutDashboard,
    CheckCircle2,
} from 'lucide-vue-next';

const props = defineProps<{
    modules: Array<{
        key: string;
        label: string;
        tagline: string;
        description: string;
        icon: string;
        color: string;
        route: string;
        features?: string[];
        plans: Array<{
            id: number;
            name: string;
            description: string;
            price_monthly: number;
            features: string[];
        }>;
        has_subscription: boolean;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Add Module', href: '/billing/browse' }];

function price(v: number): string {
    return '৳' + Number(v).toLocaleString('en-IN');
}

function iconFor(name: string) {
    switch (name) {
        case 'Wallet':
            return Wallet;
        case 'Building2':
            return Building2;
        case 'LayoutDashboard':
            return LayoutDashboard;
        default:
            return Wallet;
    }
}

function checkoutUrl(planId: number): string {
    return route('checkout', { plan: planId, add: 1 });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Add a Module" />

        <div class="relative flex flex-col gap-6 p-4 md:p-6">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-64 overflow-hidden">
                <div class="absolute -top-24 right-0 h-72 w-72 rounded-full bg-indigo-500/20 blur-3xl" />
                <div class="absolute -top-16 left-1/3 h-60 w-60 rounded-full bg-sky-500/15 blur-3xl" />
            </div>

            <header class="relative flex flex-col gap-2">
                <div class="inline-flex w-fit items-center gap-2 rounded-full border border-border/60 bg-background/60 px-3 py-1 text-xs font-medium text-muted-foreground backdrop-blur">
                    <Sparkles class="h-3.5 w-3.5 text-indigo-500" />
                    Grow your workspace
                </div>
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Add another module</h1>
                <p class="max-w-2xl text-sm text-muted-foreground md:text-base">
                    Subscribe to a new module and it appears in your workspace alongside the ones you already have. One account, many tools.
                </p>
            </header>

            <!-- Empty state -->
            <section
                v-if="modules.length === 0"
                class="relative flex flex-col items-center justify-center gap-4 rounded-2xl border border-dashed border-border bg-card/40 px-6 py-16 text-center"
            >
                <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-500">
                    <CheckCircle2 class="h-7 w-7" />
                </div>
                <div>
                    <h2 class="text-lg font-semibold">You're subscribed to every available module</h2>
                    <p class="mx-auto mt-1 max-w-md text-sm text-muted-foreground">
                        There are no more modules to add right now. Check back soon as we roll out new tools.
                    </p>
                </div>
            </section>

            <!-- Module cards -->
            <section v-else class="relative grid gap-6 lg:grid-cols-2">
                <div
                    v-for="module in modules"
                    :key="module.key"
                    class="flex flex-col overflow-hidden rounded-2xl border border-border/70 bg-card shadow-sm"
                >
                    <!-- Module header -->
                    <div class="relative overflow-hidden border-b border-border/60 p-6">
                        <div
                            class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full opacity-20 blur-2xl"
                            :style="{ backgroundColor: module.color }"
                        />
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="inline-flex h-11 w-11 items-center justify-center rounded-xl text-white shadow-md" :style="{ backgroundColor: module.color }">
                                    <component :is="iconFor(module.icon)" class="h-5 w-5" />
                                </div>
                                <div>
                                    <h2 class="text-lg font-semibold">{{ module.label }}</h2>
                                    <p class="text-xs font-medium" :style="{ color: module.color }">{{ module.tagline }}</p>
                                </div>
                            </div>
                            <span
                                v-if="module.has_subscription"
                                class="inline-flex items-center gap-1 rounded-full bg-amber-500/10 px-2.5 py-1 text-[11px] font-semibold text-amber-600 dark:text-amber-400"
                            >
                                Pending / subscribed
                            </span>
                        </div>
                        <p class="mt-3 text-sm text-muted-foreground">{{ module.description }}</p>
                    </div>

                    <!-- Plans -->
                    <div class="flex flex-1 flex-col gap-3 p-6">
                        <p v-if="module.plans.length === 0" class="text-sm text-muted-foreground">No plans available yet.</p>
                        <div
                            v-for="plan in module.plans"
                            :key="plan.id"
                            class="flex items-center justify-between gap-3 rounded-xl border border-border/70 bg-background/40 p-4"
                        >
                            <div>
                                <h3 class="text-sm font-semibold">{{ plan.name }}</h3>
                                <p class="text-xs text-muted-foreground">{{ plan.description }}</p>
                                <div class="mt-1 flex items-baseline gap-1">
                                    <span class="text-xl font-bold">{{ price(plan.price_monthly) }}</span>
                                    <span class="text-xs text-muted-foreground">/month</span>
                                </div>
                            </div>
                            <Link
                                :href="checkoutUrl(plan.id)"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-md transition hover:opacity-90"
                                :style="{ backgroundColor: module.color }"
                            >
                                Add
                                <ArrowRight class="h-4 w-4" />
                            </Link>
                        </div>

                        <div class="mt-auto space-y-1.5 pt-2">
                            <p v-if="module.features?.length" class="text-xs font-semibold text-muted-foreground uppercase">Includes</p>
                            <li v-for="feature in module.features" :key="feature" class="flex items-center gap-2 text-xs text-muted-foreground">
                                <Check class="h-3.5 w-3.5 shrink-0 text-emerald-500" />
                                {{ feature }}
                            </li>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
