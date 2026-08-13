<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PublicLayout from '../../Layouts/PublicLayout.vue';
import { ShieldCheck, CheckCircle2, XCircle, Loader2 } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    tranId: string;
    amount: number;
    plan_name: string;
    complete_url: string;
}>();

const completing = ref(false);

function complete() {
    completing.value = true;
    window.location.href = props.complete_url;
}
</script>

<template>
    <PublicLayout>
        <Head title="Payment" />
        <div class="mx-auto max-w-md px-4 py-16">
            <div class="rounded-2xl border border-border bg-card p-8 shadow-sm">
                <div class="flex items-center justify-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                        <ShieldCheck class="h-7 w-7" />
                    </div>
                </div>
                <h1 class="mt-4 text-center text-xl font-bold text-foreground">Secure payment</h1>
                <p class="mt-2 text-center text-sm text-muted-foreground">
                    You're paying <strong class="text-foreground">৳{{ Number(amount).toLocaleString('en-IN') }}</strong> for
                    <strong class="text-foreground">{{ plan_name }}</strong>.
                </p>

                <!-- Simulation notice -->
                <div class="mt-6 rounded-xl border border-amber-300/60 bg-amber-50 p-4 text-sm text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-400">
                    <p class="font-medium">Development mode</p>
                    <p class="mt-1 text-xs">
                        No payment gateway is configured. Click "Complete payment" to simulate a successful SSLCommerz payment
                        (as if the user paid and returned from the gateway). This is for testing only.
                    </p>
                </div>

                <div class="mt-6 flex items-center justify-center gap-3 text-xs text-muted-foreground">
                    <span class="inline-flex items-center gap-1"><CheckCircle2 class="h-4 w-4 text-emerald-500" /> 256-bit SSL</span>
                    <span class="inline-flex items-center gap-1"><XCircle class="h-4 w-4 text-muted-foreground" /> Powered by SSLCommerz</span>
                </div>

                <button
                    type="button"
                    class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground shadow transition hover:bg-primary/90 disabled:opacity-60"
                    :disabled="completing"
                    @click="complete"
                >
                    <Loader2 v-if="completing" class="h-4 w-4 animate-spin" />
                    {{ completing ? 'Processing…' : 'Complete payment' }}
                </button>
                <p class="mt-2 text-center text-xs text-muted-foreground">TRX: {{ tranId }}</p>
            </div>
        </div>
    </PublicLayout>
</template>
