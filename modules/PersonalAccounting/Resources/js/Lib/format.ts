import type { Component } from 'vue';
import {
    Banknote,
    Briefcase,
    Car,
    Gift,
    HeartPulse,
    Home,
    Laptop,
    MoreHorizontal,
    PiggyBank,
    ShoppingBag,
    TrendingUp,
    Tv,
    Utensils,
    Zap,
    Book,
    Wallet,
    Landmark,
    Smartphone,
} from 'lucide-vue-next';

const ICON_MAP: Record<string, Component> = {
    utensils: Utensils,
    car: Car,
    home: Home,
    'shopping-bag': ShoppingBag,
    zap: Zap,
    tv: Tv,
    'heart-pulse': HeartPulse,
    book: Book,
    'piggy-bank': PiggyBank,
    more: MoreHorizontal,
    banknote: Banknote,
    briefcase: Briefcase,
    laptop: Laptop,
    'trending-up': TrendingUp,
    gift: Gift,
    wallet: Wallet,
    bank: Landmark,
    'mobile-banking': Smartphone,
};

export function categoryIcon(name?: string | null): Component {
    if (!name) return MoreHorizontal;
    return ICON_MAP[name] ?? MoreHorizontal;
}

export function formatMoney(value: number | string | null | undefined, compact = false): string {
    const num = Number(value ?? 0);
    const formatter = new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: compact ? 0 : 2,
        maximumFractionDigits: compact ? 0 : 2,
    });
    return formatter.format(num);
}

/** Format without the currency symbol (for amounts in inputs / compact cards). */
export function formatNumber(value: number | string | null | undefined, digits = 0): string {
    const num = Number(value ?? 0);
    return num.toLocaleString('en-IN', { minimumFractionDigits: digits, maximumFractionDigits: digits });
}

export function formatDate(value: string | null | undefined): string {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

export function formatDateTime(value: string | null | undefined): string {
    if (!value) return '—';
    return new Date(value).toLocaleString('en-GB', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
}

export function monthLabel(date: Date): string {
    return date.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' });
}

export const TYPE_STYLES: Record<string, { text: string; bg: string; dot: string }> = {
    income: { text: 'text-emerald-600 dark:text-emerald-400', bg: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400', dot: 'bg-emerald-500' },
    expense: { text: 'text-rose-600 dark:text-rose-400', bg: 'bg-rose-500/10 text-rose-600 dark:text-rose-400', dot: 'bg-rose-500' },
    transfer: { text: 'text-sky-600 dark:text-sky-400', bg: 'bg-sky-500/10 text-sky-600 dark:text-sky-400', dot: 'bg-sky-500' },
};
