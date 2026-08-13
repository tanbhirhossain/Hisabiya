<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import PublicLayout from '../../Layouts/PublicLayout.vue';
import { ref, computed } from 'vue';
import { Loader2, Phone, Landmark, Upload, ArrowLeft } from 'lucide-vue-next';

const props = defineProps<{
    provider: string;
    ref: string;
    details: { name: string; number: string; instructions: string };
}>();

const page = usePage();
const paymentId = computed(() => {
    // We find the user's latest pending manual payment for this reference.
    const payments = (page.props as any).pending_payment_id;
    return payments ?? null;
});

const file = ref<File | null>(null);
const form = useForm({
    payment_id: paymentId.value ?? '',
    trx_id: '',
    proof: null as File | null,
});

const isBkash = computed(() => props.provider === 'manual_bkash');

function onFile(e: Event) {
    const f = (e.target as HTMLInputElement).files?.[0];
    if (f) {
        file.value = f;
        form.proof = f;
    }
}

function submit() {
    form.post(route('checkout.manual.submit'), {
        forceFormData: true,
        onFinish: () => form.clearErrors(),
    });
}
</script>

<template>
    <PublicLayout>
        <Head title="Manual Payment" />
        <div class="mx-auto max-w-2xl px-4 py-12">
            <a :href="route('pricing')" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition hover:text-foreground">
                <ArrowLeft class="h-4 w-4" /> Back
            </a>

            <div class="mt-6 rounded-2xl border border-border bg-card p-8 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <component :is="isBkash ? Phone : Landmark" class="h-6 w-6" />
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-foreground">{{ details.name }}</h1>
                        <p class="text-sm text-muted-foreground">{{ details.instructions }}</p>
                    </div>
                </div>

                <div class="mt-6 rounded-xl bg-muted/60 p-5 text-center">
                    <p class="text-xs font-medium text-muted-foreground uppercase">Send the amount to</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-foreground">{{ details.number }}</p>
                </div>

                <p class="mt-4 text-sm text-muted-foreground">
                    After sending, enter your transaction ID below. A CORE admin will verify and activate your subscription.
                </p>

                <form class="mt-6 space-y-4" @submit.prevent="submit">
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-foreground">Transaction ID (TRX ID)</label>
                        <input v-model="form.trx_id" type="text" placeholder="e.g. 9HK7X3F2LZ" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                        <p v-if="form.errors.trx_id" class="text-xs text-rose-500">{{ form.errors.trx_id }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-foreground">Payment screenshot <span class="text-muted-foreground">(optional)</span></label>
                        <label class="flex cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground transition hover:bg-muted/40">
                            <input type="file" accept="image/*" class="hidden" @change="onFile" />
                            <Upload class="mb-2 h-6 w-6" />
                            <span v-if="!file">Click to upload a screenshot</span>
                            <span v-else class="font-medium text-foreground">{{ file.name }}</span>
                        </label>
                    </div>

                    <button
                        type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground shadow transition hover:bg-primary/90 disabled:opacity-60"
                        :disabled="form.processing"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        {{ form.processing ? 'Submitting…' : 'Submit payment proof' }}
                    </button>
                </form>
            </div>
        </div>
    </PublicLayout>
</template>
