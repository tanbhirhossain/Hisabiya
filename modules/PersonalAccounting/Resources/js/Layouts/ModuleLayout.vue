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
    HandCoins,
    UserRound,
    UsersRound,
    Repeat,
    Database,
    CircleUser,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import NotificationsBell from '../Components/NotificationsBell.vue';
import { PanelLeft, PanelLeftClose, Menu, X } from 'lucide-vue-next';

interface Props {
    title: string;
    breadcrumbs?: BreadcrumbItem[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();

// Sidebar state: `collapsed` toggles desktop icon-only mode; `mobileOpen`
// controls the off-canvas drawer on small screens.
const collapsed = ref(false);
const mobileOpen = ref(false);

function toggleCollapse() {
    collapsed.value = !collapsed.value;
}

function goToMobileLink() {
    mobileOpen.value = false;
}

const navItems = [
    { title: 'Dashboard', href: '/personal/dashboard', icon: LayoutDashboard, route: 'personal.dashboard' },
    { title: 'Transactions', href: '/personal/transactions', icon: ArrowLeftRight, route: 'personal.transactions.index' },
    { title: 'Recurring', href: '/personal/recurring', icon: Repeat, route: 'personal.recurring.index' },
    { title: 'Accounts', href: '/personal/accounts', icon: Wallet, route: 'personal.accounts.index' },
    { title: 'Budgets', href: '/personal/budgets', icon: Target, route: 'personal.budgets.index' },
    { title: 'Savings Goals', href: '/personal/goals', icon: PiggyBank, route: 'personal.goals.index' },
    { title: 'Loans', href: '/personal/loans', icon: HandCoins, route: 'personal.loans.index' },
    { title: 'Contacts', href: '/personal/contacts', icon: UserRound, route: 'personal.contacts.index' },
    { title: 'Reports', href: '/personal/reports', icon: BarChart3, route: 'personal.reports.index' },
    { title: 'Users & Access', href: '/personal/settings/users', icon: UsersRound, route: 'personal.settings.users.index', permission: 'personal-accounting.acl' },
    { title: 'Backup', href: '/personal/settings/backup', icon: Database, route: 'personal.settings.backup.index', permission: 'personal-accounting.backup' },
];

const currentPath = computed(() => page.url.split('?')[0]);
const userInitial = computed(() => (page.props.auth?.user?.name?.[0] ?? 'U').toUpperCase());
const userName = computed(() => page.props.auth?.user?.name ?? '');
const userPermissions = computed(() => (page.props.auth?.user?.permissions ?? []) as string[]);
const isSuperAdmin = computed(() => (page.props.auth?.user?.roles ?? []).includes('super-admin'));

// Filter nav items by the user's permissions.
const visibleNavItems = computed(() =>
    navItems.filter((item: any) => {
        if (!item.permission) return true;
        return isSuperAdmin.value || userPermissions.value.includes(item.permission);
    }),
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="title" />

        <div class="flex min-h-[calc(100svh-4rem)]">
            <!-- In-module sidebar (desktop, collapsible) -->
            <aside
                v-show="!mobileOpen"
                class="sticky top-0 z-20 hidden h-[calc(100svh-4rem)] shrink-0 flex-col border-r border-border bg-card/60 backdrop-blur transition-all duration-300 md:flex"
                :class="collapsed ? 'w-16' : 'w-60'"
            >
                <div class="flex items-center justify-between border-b border-border px-4 py-4" :class="collapsed ? 'justify-center px-2' : ''">
                    <template v-if="!collapsed">
                        <div>
                            <p class="text-xs font-semibold tracking-wider text-muted-foreground uppercase">Personal Finance</p>
                            <p class="mt-0.5 text-sm font-semibold text-foreground">My Money</p>
                        </div>
                        <button
                            type="button"
                            class="rounded-md p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                            @click="toggleCollapse"
                        >
                            <PanelLeftClose class="h-4 w-4" />
                        </button>
                    </template>
                    <button
                        v-else
                        type="button"
                        class="rounded-md p-1.5 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                        @click="toggleCollapse"
                    >
                        <PanelLeft class="h-4 w-4" />
                    </button>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto p-3" :class="collapsed ? 'flex flex-col items-center space-y-2' : ''">
                    <Link
                        v-for="item in visibleNavItems"
                        :key="item.route"
                        :href="item.href"
                        class="flex items-center gap-3 rounded-lg py-2.5 text-sm font-medium transition"
                        :class="[
                            currentPath === item.href
                                ? 'bg-primary/10 text-primary'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                            collapsed ? 'w-10 justify-center px-0' : 'px-3',
                        ]"
                        :title="collapsed ? item.title : undefined"
                    >
                        <component :is="item.icon" class="h-4 w-4 shrink-0" />
                        <span v-if="!collapsed" class="truncate">{{ item.title }}</span>
                    </Link>
                </nav>

                <div class="border-t border-border p-3">
                    <div
                        class="flex items-center gap-3 rounded-lg bg-muted/60 py-2"
                        :class="collapsed ? 'justify-center px-0' : 'px-3'"
                    >
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/15 text-sm font-semibold text-primary">
                            {{ userInitial }}
                        </div>
                        <div v-if="!collapsed" class="min-w-0">
                            <p class="truncate text-sm font-medium text-foreground">{{ userName }}</p>
                            <p class="text-xs text-muted-foreground">Personal account</p>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Mobile drawer -->
            <transition
                enter-active-class="transition-opacity duration-200"
                enter-from-class="opacity-0"
                leave-active-class="transition-opacity duration-200"
                leave-to-class="opacity-0"
            >
                <div v-if="mobileOpen" class="fixed inset-0 z-40 bg-black/40 md:hidden" @click="mobileOpen = false" />
            </transition>
            <transition
                enter-active-class="transition-transform duration-200"
                enter-from-class="-translate-x-full"
                leave-active-class="transition-transform duration-200"
                leave-to-class="-translate-x-full"
            >
                <aside v-if="mobileOpen" class="fixed left-0 top-0 z-50 flex h-svh w-64 flex-col border-r border-border bg-card shadow-xl md:hidden">
                    <div class="flex items-center justify-between border-b border-border px-4 py-4">
                        <div>
                            <p class="text-xs font-semibold tracking-wider text-muted-foreground uppercase">Personal Finance</p>
                            <p class="mt-0.5 text-sm font-semibold text-foreground">My Money</p>
                        </div>
                        <button type="button" class="rounded-md p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground" @click="mobileOpen = false">
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <nav class="flex-1 space-y-1 overflow-y-auto p-3">
                        <Link
                            v-for="item in visibleNavItems"
                            :key="item.route"
                            :href="item.href"
                            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition"
                            :class="currentPath === item.href
                                ? 'bg-primary/10 text-primary'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground'"
                            @click="goToMobileLink"
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
            </transition>

            <!-- Content -->
            <main class="flex-1 overflow-x-hidden">
                <div class="sticky top-0 z-30 flex items-center justify-between border-b border-border bg-background/80 px-4 py-2 backdrop-blur md:justify-end md:px-6">
                    <button
                        type="button"
                        class="rounded-lg p-1.5 text-muted-foreground transition hover:bg-muted hover:text-foreground md:hidden"
                        @click="mobileOpen = true"
                    >
                        <Menu class="h-5 w-5" />
                    </button>
                    <NotificationsBell />
                </div>
                <div class="p-4 md:p-6">
                    <slot />
                </div>
            </main>
        </div>

        <FlashMessage />
    </AppLayout>
</template>
