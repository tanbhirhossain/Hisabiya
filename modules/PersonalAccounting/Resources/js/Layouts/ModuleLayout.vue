<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import FlashMessage from '../Components/FlashMessage.vue';
import {
    LayoutDashboard,
    ArrowLeftRight,
    Wallet,
    Target,
    PiggyBank,
    BarChart3,
    CircleUser,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

interface Props {
    title: string;
    breadcrumbs?: BreadcrumbItem[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();

const navItems = [
    { title: 'Dashboard', href: '/personal/dashboard', icon: LayoutDashboard, route: 'personal.dashboard' },
    { title: 'Transactions', href: '/personal/transactions', icon: ArrowLeftRight, route: 'personal.transactions.index' },
    { title: 'Accounts', href: '/personal/accounts', icon: Wallet, route: 'personal.accounts.index' },
    { title: 'Budgets', href: '/personal/budgets', icon: Target, route: 'personal.budgets.index' },
    { title: 'Savings Goals', href: '/personal/goals', icon: PiggyBank, route: 'personal.goals.index' },
    { title: 'Reports', href: '/personal/reports', icon: BarChart3, route: 'personal.reports.index' },
];

const currentPath = computed(() => page.url.split('?')[0]);
const userInitial = computed(() => (page.props.auth?.user?.name?.[0] ?? 'U').toUpperCase());
const userName = computed(() => page.props.auth?.user?.name ?? '');
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="title" />

        <div class="flex min-h-[calc(100svh-4rem)]">
            <!-- In-module sidebar -->
            <aside class="sticky top-0 flex h-[calc(100svh-4rem)] w-60 shrink-0 flex-col border-r border-border bg-card/60 backdrop-blur">
                <div class="border-b border-border px-4 py-4">
                    <p class="text-xs font-semibold tracking-wider text-muted-foreground uppercase">Personal Finance</p>
                    <p class="mt-0.5 text-sm font-semibold text-foreground">My Money</p>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto p-3">
                    <Link
                        v-for="item in navItems"
                        :key="item.route"
                        :href="item.href"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition"
                        :class="currentPath === item.href
                            ? 'bg-primary/10 text-primary'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground'"
                    >
                        <component :is="item.icon" class="h-4 w-4" />
                        {{ item.title }}
                    </Link>
                </nav>

                <div class="border-t border-border p-3">
                    <div class="flex items-center gap-3 rounded-lg bg-muted/60 px-3 py-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/15 text-sm font-semibold text-primary">
                            {{ userInitial }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-foreground">{{ userName }}</p>
                            <p class="text-xs text-muted-foreground">Personal account</p>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Content -->
            <main class="flex-1 overflow-x-hidden">
                <div class="p-4 md:p-6">
                    <slot />
                </div>
            </main>
        </div>

        <FlashMessage />
    </AppLayout>
</template>
