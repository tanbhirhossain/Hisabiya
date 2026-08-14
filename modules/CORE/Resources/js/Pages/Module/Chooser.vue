<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import {
    ArrowRight,
    Wallet,
    Building2,
    Sparkles,
    Check,
    ShieldCheck,
    LayoutDashboard,
    Plus,
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
        href: string | null;
        features?: string[];
    }>;
    canAdmin: boolean;
    hasSubscription: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'My Modules', href: '/modules' }];

function greeting(): string {
    const h = new Date().getHours();
    if (h < 12) return 'Good morning';
    if (h < 17) return 'Good afternoon';
    return 'Good evening';
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
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="My Modules" />

        <div class="relative flex flex-col gap-6 p-4 md:p-6">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-64 overflow-hidden">
                <div class="absolute -top-24 right-0 h-72 w-72 rounded-full bg-indigo-500/20 blur-3xl" />
                <div class="absolute -top-16 left-1/3 h-60 w-60 rounded-full bg-sky-500/15 blur-3xl" />
            </div>

            <!-- Header -->
            <header class="relative flex flex-col gap-2">
                <div class="inline-flex w-fit items-center gap-2 rounded-full border border-border/60 bg-background/60 px-3 py-1 text-xs font-medium text-muted-foreground backdrop-blur">
                    <Sparkles class="h-3.5 w-3.5 text-indigo-500" />
                    Your subscribed modules
                </div>
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">{{ greeting() }} 👋</h1>
                <p class="max-w-2xl text-sm text-muted-foreground md:text-base">
                    Pick where you'd like to work. Each module is a fully separate workspace under your account.
                </p>
                <Link
                    href="/billing/browse"
                    class="mt-3 inline-flex w-fit items-center gap-1.5 rounded-lg border border-border bg-card px-3.5 py-2 text-sm font-semibold text-foreground shadow-sm transition hover:border-indigo-400/50 hover:bg-muted"
                >
                    <Plus class="h-4 w-4" />
                    Add a module
                </Link>
            </header>

            <!-- Module grid -->
            <section class="relative grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="module in modules"
                    :key="module.key"
                    :href="module.href ?? route('dashboard')"
                    class="group relative overflow-hidden rounded-2xl border border-border/70 bg-card p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-400/50 hover:shadow-lg hover:shadow-indigo-500/10"
                >
                    <div
                        class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full opacity-20 blur-2xl transition group-hover:opacity-40"
                        :style="{ backgroundColor: module.color }"
                    />
                    <div class="flex items-start justify-between">
                        <div
                            class="inline-flex h-12 w-12 items-center justify-center rounded-xl text-white shadow-md"
                            :style="{ backgroundColor: module.color }"
                        >
                            <component :is="iconFor(module.icon)" class="h-6 w-6" />
                        </div>
                        <ArrowRight class="h-4 w-4 text-muted-foreground transition group-hover:translate-x-0.5 group-hover:text-indigo-500" />
                    </div>
                    <h2 class="mt-5 text-lg font-semibold">{{ module.label }}</h2>
                    <p class="mt-0.5 text-xs font-medium" :style="{ color: module.color }">{{ module.tagline }}</p>
                    <p class="mt-3 text-sm text-muted-foreground">{{ module.description }}</p>

                    <ul v-if="module.features?.length" class="mt-4 space-y-1.5">
                        <li v-for="feature in module.features" :key="feature" class="flex items-center gap-2 text-xs text-muted-foreground">
                            <Check class="h-3.5 w-3.5 shrink-0 text-emerald-500" />
                            {{ feature }}
                        </li>
                    </ul>

                    <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold" :style="{ color: module.color }">
                        Open workspace
                        <ArrowRight class="h-4 w-4 transition group-hover:translate-x-0.5" />
                    </span>
                </Link>

                <!-- Platform admin card -->
                <Link
                    v-if="canAdmin"
                    href="/admin/tenants"
                    class="group relative overflow-hidden rounded-2xl border border-border/70 bg-card p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-400/50 hover:shadow-lg hover:shadow-indigo-500/10"
                >
                    <div class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-zinc-500/20 blur-2xl transition group-hover:opacity-40" />
                    <div class="flex items-start justify-between">
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-zinc-800 text-white shadow-md dark:bg-zinc-700">
                            <ShieldCheck class="h-6 w-6" />
                        </div>
                        <ArrowRight class="h-4 w-4 text-muted-foreground transition group-hover:translate-x-0.5 group-hover:text-zinc-500" />
                    </div>
                    <h2 class="mt-5 text-lg font-semibold">Platform Administration</h2>
                    <p class="mt-0.5 text-xs font-medium text-muted-foreground">Manage the whole SaaS</p>
                    <p class="mt-3 text-sm text-muted-foreground">Tenants, users, roles, permissions, subscriptions, payments and platform backups.</p>
                    <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-muted-foreground transition group-hover:text-zinc-500">
                        Open admin console
                        <ArrowRight class="h-4 w-4 transition group-hover:translate-x-0.5" />
                    </span>
                </Link>
            </section>

            <!-- Empty / subscribe state -->
            <section
                v-if="!hasSubscription && !canAdmin"
                class="relative flex flex-col items-center justify-center gap-4 rounded-2xl border border-dashed border-border bg-card/40 px-6 py-16 text-center"
            >
                <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-500">
                    <Wallet class="h-7 w-7" />
                </div>
                <div>
                    <h2 class="text-lg font-semibold">You don't have any modules yet</h2>
                    <p class="mx-auto mt-1 max-w-md text-sm text-muted-foreground">
                        Subscribe to a module to unlock your workspace. Choose a plan that fits and you'll be up and running in minutes.
                    </p>
                </div>
                <Link
                    href="/pricing"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-600/20 transition hover:bg-indigo-700"
                >
                    <Sparkles class="h-4 w-4" />
                    View plans & pricing
                </Link>
            </section>
        </div>
    </AppLayout>
</template>
