<script setup lang="ts">
import ModuleLayout from '../../Layouts/ModuleLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import {
    AlertTriangle,
    AlertCircle,
    CalendarClock,
    Trophy,
    Target,
    RefreshCcw,
    BarChart3,
    CheckCheck,
    Clock,
    Trash2,
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    notifications: any;
    unreadCount: number;
}>();

const currentPath = computed(() => window.location.pathname);

function typeMeta(type: string): { icon: any; color: string } {
    const map: Record<string, { icon: any; color: string }> = {
        BudgetExceededNotification: { icon: AlertTriangle, color: 'text-rose-600 bg-rose-500/10 dark:text-rose-400' },
        BudgetWarningNotification: { icon: AlertCircle, color: 'text-amber-600 bg-amber-500/10 dark:text-amber-400' },
        LoanOverdueNotification: { icon: AlertTriangle, color: 'text-rose-600 bg-rose-500/10 dark:text-rose-400' },
        LoanPaymentDueNotification: { icon: CalendarClock, color: 'text-indigo-600 bg-indigo-500/10 dark:text-indigo-400' },
        SavingsGoalReachedNotification: { icon: Trophy, color: 'text-emerald-600 bg-emerald-500/10 dark:text-emerald-400' },
        SavingsGoalMilestoneNotification: { icon: Target, color: 'text-sky-600 bg-sky-500/10 dark:text-sky-400' },
        RecurringTransactionFailedNotification: { icon: RefreshCcw, color: 'text-orange-600 bg-orange-500/10 dark:text-orange-400' },
        MonthlyReportNotification: { icon: BarChart3, color: 'text-violet-600 bg-violet-500/10 dark:text-violet-400' },
    };
    return map[type] ?? { icon: Clock, color: 'text-muted-foreground bg-muted' };
}

function timeAgo(date: string): string {
    const seconds = Math.floor((Date.now() - new Date(date).getTime()) / 1000);
    if (seconds < 60) return 'just now';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    if (days < 7) return `${days}d ago`;
    const weeks = Math.floor(days / 7);
    if (weeks < 5) return `${weeks}w ago`;
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

async function markRead(notification: any) {
    if (notification.read_at) return;
    try {
        await axios.post(route('personal.notifications.read', notification.id));
        notification.read_at = new Date().toISOString();
        props.unreadCount = Math.max(0, props.unreadCount - 1);
    } catch (e) {
        /* ignore */
    }
}

async function markAllRead() {
    try {
        await axios.post(route('personal.notifications.read-all'));
        props.notifications.data.forEach((n: any) => (n.read_at = new Date().toISOString()));
        props.unreadCount = 0;
    } catch (e) {
        /* ignore */
    }
}

async function destroyNotification(notification: any) {
    if (!confirm('Delete this notification?')) return;
    try {
        await axios.delete(route('personal.notifications.destroy', notification.id));
        props.notifications.data = props.notifications.data.filter((n: any) => n.id !== notification.id);
        if (!notification.read_at) props.unreadCount = Math.max(0, props.unreadCount - 1);
    } catch (e) {
        /* ignore */
    }
}

function notificationUrl(n: any): string | null {
    return n.data?.url ?? null;
}

function gotoPage(url: string | null, n: any) {
    markRead(n);
    if (url) window.location.href = url;
}
</script>

<template>
    <ModuleLayout title="Notifications" :breadcrumbs="[{ title: 'Personal', href: '/personal/dashboard' }, { title: 'Notifications', href: '/personal/notifications' }]">
        <div class="space-y-5">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">Notifications</h1>
                    <p class="text-sm text-muted-foreground">Stay on top of budgets, loans, goals and reports.</p>
                </div>
                <button
                    v-if="unreadCount > 0"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary/10 px-4 py-2 text-sm font-semibold text-primary transition hover:bg-primary/20"
                    @click="markAllRead"
                >
                    <CheckCheck class="h-4 w-4" /> Mark all read
                </button>
            </div>

            <div v-if="notifications.data.length === 0" class="rounded-xl border border-dashed border-border p-14 text-center">
                <CheckCheck class="mx-auto h-10 w-10 text-muted-foreground" />
                <p class="mt-3 text-sm text-muted-foreground">You're all caught up — no notifications yet.</p>
            </div>

            <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                <ul class="divide-y divide-border">
                    <li
                        v-for="notification in notifications.data"
                        :key="notification.id"
                        class="flex cursor-pointer items-start gap-4 px-5 py-4 transition hover:bg-muted/30"
                        :class="notification.read_at ? '' : 'bg-primary/[0.02]'"
                        @click="gotoPage(notificationUrl(notification), notification)"
                    >
                        <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" :class="typeMeta(notification.type).color">
                            <component :is="typeMeta(notification.type).icon" class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-foreground">{{ notification.data?.message ?? 'Notification' }}</p>
                            <p class="mt-0.5 flex items-center gap-2 text-xs text-muted-foreground">
                                <span class="inline-flex items-center gap-1"><Clock class="h-3 w-3" /> {{ timeAgo(notification.created_at) }}</span>
                                <span v-if="!notification.read_at" class="rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-medium text-primary">New</span>
                            </p>
                        </div>
                        <button
                            type="button"
                            class="shrink-0 rounded-md p-1.5 text-muted-foreground transition hover:bg-muted hover:text-destructive"
                            @click.stop="destroyNotification(notification)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </li>
                </ul>

                <!-- Pagination -->
                <div v-if="notifications.links?.length" class="flex items-center justify-between border-t border-border px-5 py-3">
                    <p class="text-xs text-muted-foreground">Showing {{ notifications.from ?? 0 }}–{{ notifications.to ?? 0 }} of {{ notifications.total ?? 0 }}</p>
                    <div class="flex items-center gap-1">
                        <button
                            v-for="(link, i) in notifications.links"
                            :key="i"
                            type="button"
                            class="inline-flex h-8 items-center rounded-md px-2 text-xs transition"
                            :class="link.active ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted'"
                            :disabled="!link.url"
                            @click="router.get(link.url)"
                        >
                            <span v-if="i === 0">‹</span>
                            <span v-else-if="i === notifications.links.length - 1">›</span>
                            <span v-else>{{ link.label }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </ModuleLayout>
</template>
