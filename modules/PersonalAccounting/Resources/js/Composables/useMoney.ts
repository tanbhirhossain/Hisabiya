import { computed } from 'vue';
import { formatMoney, formatNumber } from '../Lib/format';

/**
 * Shared money helpers bound to a reactive amount.
 */
export function useMoney(amount: () => number | string | null | undefined) {
    const money = computed(() => formatMoney(amount()));
    const number = computed(() => formatNumber(amount(), 2));

    return { money, number };
}

export function currencySymbol(): string {
    return '৳';
}
