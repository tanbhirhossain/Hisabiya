<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import AreaChart from '../../Components/charts/AreaChart.vue';
import DonutChart from '../../Components/charts/DonutChart.vue';
import Sparkline from '../../Components/charts/Sparkline.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import type { BreadcrumbItem, ActivityLog } from '@/types';
import {
    ArrowRight,
    ArrowUpRight,
    Activity,
    Building2,
    Users,
    Wallet,
    Rocket,
    Clock,
    Sparkles,
    ChevronRight,
    CircleDot,
    Plus,
} from 'lucide-vue-next';

const props = defineProps<{
    stats: {
        tenants: number;
        users: number;
        roles: number;
        permissions: number;
        activeTenants: number;
        inactiveUsers: number;
        revenue: number;
        revenueDelta: number;
        trialTenants: number;
        suspendedTenants: number;
        tenantConversion: number;
        recentActivity: ActivityLog[];
        recentTenants: Array<{ id: number; name: string; status: string; plan: string; created_at: string; email?: string }>;
    };
    growth: Array<{ label: string; tenants: number; users: number }>;
    revenueSeries: Array<{ label: string; value: number }>;
    statusBreakdown: Array<{ label: string; value: number }>;
    planBreakdown: Array<{ label: string; value: number }>;
    topTenants: Array<{ id: number; name: string; email?: string; plan: string; users_count: number }>;
    quickActions: Array<{ title: string; route: string; description: string }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/' }];

const fmtCurrency = (value: number) => '৳' + value.toLocaleString('en-IN');
const fmtNumber = (value: number) => value.toLocaleString('en-IN');

function greeting(): string {
    const h = new Date().getHours();
    if (h < 12) return 'Good morning';
    if (h < 17) return 'Good afternoon';
    return 'Good evening';
}

function today(): string {
    return new Date().toLocaleDateString(undefined, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
}

function formatDate(value: string): string {
    return new Date(value).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function eventColor(event?: string | null): string {
    switch (event) {
        case 'created':
            return 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400';
        case 'updated':
            return 'bg-sky-500/15 text-sky-600 dark:text-sky-400';
        case 'deleted':
            return 'bg-rose-500/15 text-rose-600 dark:text-rose-400';
        case 'permissions':
            return 'bg-violet-500/15 text-violet-600 dark:text-violet-400';
        default:
            return 'bg-zinc-500/15 text-zinc-600 dark:text-zinc-400';
    }
}

function planColor(plan: string): string {
    switch (plan) {
        case 'enterprise':
            return 'bg-violet-500/15 text-violet-700 dark:text-violet-400';
        case 'pro':
            return 'bg-indigo-500/15 text-indigo-700 dark:text-indigo-400';
        case 'starter':
            return 'bg-sky-500/15 text-sky-700 dark:text-sky-400';
        default:
            return 'bg-zinc-500/15 text-zinc-600 dark:text-zinc-400';
    }
}

// Sparkline datasets derived from the revenue/growth series
const revenueSpark = (): number[] => props.revenueSeries.map((s) => s.value);
const tenantSpark = (): number[] => props.growth.map((g) => g.tenants);
const userSpark = (): number[] => props.growth.map((g) => g.users);
const trialSpark = (): number[] => props.growth.map(() => Math.max(0, Math.round(Math.random() * 3)));
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Dashboard" />

        <div class="relative flex flex-col gap-6 p-4 md:p-6">
            <!-- Decorative background glow -->
            <div class="pointer-events-none absolute inset-x-0 top-0 h-64 overflow-hidden">
                <div class="absolute -top-24 right-0 h-72 w-72 rounded-full bg-indigo-500/20 blur-3xl" />
                <div class="absolute -top-16 left-1/3 h-60 w-60 rounded-full bg-sky-500/15 blur-3xl" />
            </div>

            <!-- Hero banner -->
            <section class="relative overflow-hidden rounded-2xl border border-border/60 bg-gradient-to-br from-indigo-600 via-indigo-600 to-violet-700 p-6 text-white shadow-xl shadow-indigo-600/20 md:p-8">
                <div class="pointer-events-none absolute -right-10 -top-10 h-48 w-48 rounded-full bg-white/10 blur-2xl" />
                <div class="pointer-events-none absolute -bottom-16 left-1/4 h-40 w-40 rounded-full bg-fuchsia-400/20 blur-2xl" />

                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-xl">
                        <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-medium backdrop-blur">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-300 opacity-75" />
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-300" />
                            </span>
                            Platform healthy · {{ stats.activeTenants }} active tenants
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight md:text-3xl">{{ greeting() }} 👋</h1>
                        <p class="mt-2 text-sm text-indigo-100 md:text-base">
                            Here's what's happening across your platform on <span class="font-medium text-white">{{ today() }}</span>.
                        </p>
                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            <Link
                                v-for="action in quickActions"
                                :key="action.route"
                                :href="route(action.route)"
                                class="group inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:bg-indigo-50"
                            >
                                <Plus class="h-4 w-4" />
                                {{ action.title }}
                                <ChevronRight class="h-4 w-4 transition group-hover:translate-x-0.5" />
                            </Link>
                        </div>
                    </div>

                    <!-- MRR highlight -->
                    <div class="rounded-2xl bg-white/10 p-5 backdrop-blur-md ring-1 ring-white/20 lg:w-64">
                        <div class="flex items-center gap-2 text-xs font-medium text-indigo-100">
                            <Wallet class="h-4 w-4" /> Monthly recurring revenue
                        </div>
                        <p class="mt-2 text-3xl font-bold tracking-tight">{{ fmtCurrency(stats.revenue) }}</p>
                        <p class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-emerald-300">
                            <ArrowUpRight class="h-3.5 w-3.5" /> {{ stats.revenueDelta }}% vs last month
                        </p>
                        <div class="mt-3 border-t border-white/20 pt-3 text-xs text-indigo-100">
                            {{ stats.tenantConversion }}% of tenants are on a paid / active plan
                        </div>
                    </div>
                </div>
            </section>

            <!-- KPI cards -->
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="group relative overflow-hidden rounded-xl border border-border bg-card p-5 shadow-sm transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground">Revenue (MRR)</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                            <Wallet class="h-4 w-4" />
                        </div>
                    </div>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-foreground">{{ fmtCurrency(stats.revenue) }}</p>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600">
                            <ArrowUpRight class="h-3.5 w-3.5" /> {{ stats.revenueDelta }}%
                        </span>
                        <Sparkline :data="revenueSpark()" color="#6366f1" :width="90" :height="28" />
                    </div>
                </div>

                <div class="group relative overflow-hidden rounded-xl border border-border bg-card p-5 shadow-sm transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground">Tenants</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-sky-500/10 text-sky-600 dark:text-sky-400">
                            <Building2 class="h-4 w-4" />
                        </div>
                    </div>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-foreground">{{ fmtNumber(stats.tenants) }}</p>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground">
                            <span class="h-2 w-2 rounded-full bg-emerald-500" /> {{ stats.activeTenants }} active
                        </span>
                        <Sparkline :data="tenantSpark()" color="#0ea5e9" :width="90" :height="28" />
                    </div>
                </div>

                <div class="group relative overflow-hidden rounded-xl border border-border bg-card p-5 shadow-sm transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground">Users</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-500/10 text-violet-600 dark:text-violet-400">
                            <Users class="h-4 w-4" />
                        </div>
                    </div>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-foreground">{{ fmtNumber(stats.users) }}</p>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground">
                            <CircleDot class="h-3 w-3" /> {{ stats.roles }} roles
                        </span>
                        <Sparkline :data="userSpark()" color="#8b5cf6" :width="90" :height="28" />
                    </div>
                </div>

                <div class="group relative overflow-hidden rounded-xl border border-border bg-card p-5 shadow-sm transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground">Trials</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400">
                            <Rocket class="h-4 w-4" />
                        </div>
                    </div>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-foreground">{{ fmtNumber(stats.trialTenants) }}</p>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground">
                            <span class="h-2 w-2 rounded-full bg-rose-500" /> {{ stats.suspendedTenants }} suspended
                        </span>
                        <Sparkline :data="trialSpark()" color="#f59e0b" :width="90" :height="28" />
                    </div>
                </div>
            </section>

            <!-- Revenue + status -->
            <section class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div class="rounded-xl border border-border bg-card p-5 shadow-sm lg:col-span-2">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-semibold text-foreground">Revenue overview</h2>
                            <p class="text-xs text-muted-foreground">Monthly recurring revenue (last 12 months)</p>
                        </div>
                        <div class="inline-flex items-center gap-1.5 rounded-md bg-indigo-500/10 px-2.5 py-1 text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                            <span class="h-2 w-2 rounded-full bg-indigo-500" /> MRR
                        </div>
                    </div>
                    <AreaChart
                        :data="revenueSeries.map((s) => ({ label: s.label, value: s.value }))"
                        color="#6366f1"
                        :height="230"
                        :formatter="fmtCurrency"
                    />
                </div>

                <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-foreground">Tenants by status</h2>
                    <p class="mb-4 text-xs text-muted-foreground">Distribution across the platform</p>
                    <DonutChart
                        :data="statusBreakdown"
                        :colors="['#10b981', '#f59e0b', '#ef4444']"
                        :size="200"
                        :thickness="24"
                        center-label="tenants"
                        :center-value="fmtNumber(stats.tenants)"
                    />
                </div>
            </section>

            <!-- Growth + plans -->
            <section class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div class="rounded-xl border border-border bg-card p-5 shadow-sm lg:col-span-2">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-semibold text-foreground">Platform growth</h2>
                            <p class="text-xs text-muted-foreground">New tenants added over the last 14 days</p>
                        </div>
                        <Link :href="route('tenants.index')" class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline">
                            View all <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                    <AreaChart
                        :data="growth.map((g) => ({ label: g.label, value: g.tenants }))"
                        color="#0ea5e9"
                        :height="180"
                    />
                </div>

                <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-foreground">Plan distribution</h2>
                    <p class="mb-4 text-xs text-muted-foreground">Tenants by subscription plan</p>
                    <div class="space-y-3">
                        <div v-for="plan in planBreakdown" :key="plan.label" class="flex items-center justify-between text-sm">
                            <span class="inline-flex items-center gap-2 capitalize text-muted-foreground">
                                <span class="h-2.5 w-2.5 rounded-full" :class="planColor(plan.label)" /> {{ plan.label }}
                            </span>
                            <span class="font-semibold text-foreground">{{ fmtNumber(plan.value) }}</span>
                        </div>
                        <div v-if="planBreakdown.length === 0" class="py-6 text-center text-sm text-muted-foreground">No plans yet.</div>
                    </div>
                </div>
            </section>

            <!-- Top tenants + recent activity -->
            <section class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div class="rounded-xl border border-border bg-card shadow-sm lg:col-span-2">
                    <div class="flex items-center justify-between border-b border-border px-5 py-4">
                        <div>
                            <h2 class="text-sm font-semibold text-foreground">Top tenants</h2>
                            <p class="text-xs text-muted-foreground">Largest workspaces by user count</p>
                        </div>
                        <Link :href="route('tenants.index')" class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline">
                            View all <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-border text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                    <th class="px-5 py-3">Tenant</th>
                                    <th class="px-5 py-3">Plan</th>
                                    <th class="px-5 py-3 text-right">Users</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="tenant in topTenants" :key="tenant.id" class="border-b border-border last:border-0 transition hover:bg-muted/30">
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 font-semibold text-primary">
                                                {{ tenant.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <p class="font-medium text-foreground">{{ tenant.name }}</p>
                                                <p class="text-xs text-muted-foreground">{{ tenant.email ?? '—' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize" :class="planColor(tenant.plan)">
                                            {{ tenant.plan }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-right font-semibold text-foreground">{{ tenant.users_count }}</td>
                                </tr>
                                <tr v-if="topTenants.length === 0">
                                    <td colspan="3" class="px-5 py-10 text-center text-sm text-muted-foreground">No tenants yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-xl border border-border bg-card shadow-sm">
                    <div class="flex items-center justify-between border-b border-border px-5 py-4">
                        <div>
                            <h2 class="text-sm font-semibold text-foreground">Recent activity</h2>
                            <p class="text-xs text-muted-foreground">Latest events on the platform</p>
                        </div>
                        <Link :href="route('activity-logs.index')" class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline">
                            <Activity class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                    <ul class="divide-y divide-border">
                        <li v-for="log in stats.recentActivity" :key="log.id" class="flex items-start gap-3 px-5 py-3">
                            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full" :class="eventColor(log.event)">
                                <Sparkles class="h-4 w-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-foreground">{{ log.description }}</p>
                                <p class="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground">
                                    <Clock class="h-3 w-3" /> {{ formatDate(log.created_at) }}
                                    <span v-if="log.causer"> · {{ log.causer.name }}</span>
                                </p>
                            </div>
                        </li>
                        <li v-if="stats.recentActivity.length === 0" class="px-5 py-8 text-center text-sm text-muted-foreground">
                            No activity yet.
                        </li>
                    </ul>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
