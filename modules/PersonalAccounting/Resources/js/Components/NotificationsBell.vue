<script setup lang="ts">
import { onMounted, ref } from 'vue';
import axios from 'axios';
import { Link } from '@inertiajs/vue3';
import { Bell, CheckCheck, Clock, AlertTriangle, AlertCircle, CalendarClock, Trophy, Target, RefreshCcw, BarChart3 } from 'lucide-vue-next';

const open = ref(false);
const unreadCount = ref(0);
const notifications = ref<any[]>([]);
const loading = ref(false);

function typeMeta(type: string): { icon: any; color: string } {
    const map: Record<string, { icon: any; color: string }> = {
        BudgetExceededNotification: { icon: AlertTriangle, color: 'text-rose-600 bg-rose-500/10' },
        BudgetWarningNotification: { icon: AlertCircle, color: 'text-amber-600 bg-amber-500/10' },
        LoanOverdueNotification: { icon: AlertTriangle, color: 'text-rose-600 bg-rose-500/10' },
        LoanPaymentDueNotification: { icon: CalendarClock, color: 'text-indigo-600 bg-indigo-500/10' },
        SavingsGoalReachedNotification: { icon: Trophy, color: 'text-emerald-600 bg-emerald-500/10' },
        SavingsGoalMilestoneNotification: { icon: Target, color: 'text-sky-600 bg-sky-500/10' },
        RecurringTransactionFailedNotification: { icon: RefreshCcw, color: 'text-orange-600 bg-orange-500/10' },
        MonthlyReportNotification: { icon: BarChart3, color: 'text-violet-600 bg-violet-500/10' },
    };
    return map[type] ?? { icon: Clock, color: 'text-muted-foreground bg-muted' };
}

function timeAgo(date: string): string {
    const seconds = Math.floor((Date.now() - new Date(date).getTime()) / 1000);
    if (seconds < 60) return 'just now';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h`;
    const days = Math.floor(hours / 24);
    return `${days}d`;
}

async function load() {
    loading.value = true;
    try {
        const res = await axios.get(route('personal.notifications.index'), { params: { per_page: 8 } });
        notifications.value = res.data.notifications.data;
        unreadCount.value = res.data.unread_count;
    } catch (e) {
        /* ignore */
    } finally {
        loading.value = false;
    }
}

async function markRead(notification: any) {
    if (notification.read_at) return;
    try {
        await axios.post(route('personal.notifications.read', notification.id));
        notification.read_at = new Date().toISOString();
        unreadCount.value = Math.max(0, unreadCount.value - 1);
    } catch (e) {
        /* ignore */
    }
}

async function markAllRead() {
    try {
        await axios.post(route('personal.notifications.read-all'));
        notifications.value.forEach((n) => (n.read_at = new Date().toISOString()));
        unreadCount.value = 0;
    } catch (e) {
        /* ignore */
    }
}

function toggle() {
    open.value = !open.value;
    if (open.value && notifications.value.length === 0) load();
}

onMounted(load);
</script>

<template>
    <div class="relative">
        <button
            type="button"
            class="relative rounded-lg p-2 text-muted-foreground transition hover:bg-muted hover:text-foreground"
            :title="`${unreadCount} unread`"
            @click="toggle"
        >
            <Bell class="h-5 w-5" />
            <span
                v-if="unreadCount > 0"
                class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <Transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 translate-y-1 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="open" class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-xl border border-border bg-card shadow-xl">
                <div class="flex items-center justify-between border-b border-border px-4 py-3">
                    <p class="text-sm font-semibold text-foreground">Notifications</p>
                    <button v-if="unreadCount > 0" type="button" class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline" @click="markAllRead">
                        <CheckCheck class="h-3.5 w-3.5" /> Mark all read
                    </button>
                </div>

                <div class="max-h-80 overflow-y-auto">
                    <p v-if="loading" class="px-4 py-8 text-center text-sm text-muted-foreground">Loading…</p>
                    <p v-else-if="notifications.length === 0" class="px-4 py-8 text-center text-sm text-muted-foreground">No notifications.</p>
                    <template v-else>
                        <div
                            v-for="notification in notifications"
                            :key="notification.id"
                            class="flex cursor-pointer items-start gap-3 px-4 py-3 transition hover:bg-muted/40"
                            :class="notification.read_at ? '' : 'bg-primary/[0.03]'"
                            @click="markRead(notification)"
                        >
                            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg" :class="typeMeta(notification.type).color">
                                <component :is="typeMeta(notification.type).icon" class="h-4 w-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="line-clamp-2 text-sm text-foreground">{{ notification.data?.message }}</p>
                                <p class="mt-0.5 flex items-center gap-1 text-[11px] text-muted-foreground">
                                    <Clock class="h-3 w-3" /> {{ timeAgo(notification.created_at) }}
                                </p>
                            </div>
                        </div>
                    </template>
                </div>

                <Link
                    :href="route('personal.notifications.index')"
                    class="block border-t border-border px-4 py-2.5 text-center text-sm font-medium text-primary transition hover:bg-muted"
                    @click="open = false"
                >
                    View all notifications
                </Link>
            </div>
        </Transition>
    </div>
</template>
