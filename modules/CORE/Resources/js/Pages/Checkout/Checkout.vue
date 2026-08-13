<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import PublicLayout from '../../Layouts/PublicLayout.vue';
import { ref, computed } from 'vue';
import { CreditCard, Smartphone, Landmark, ArrowLeft, Loader2, Check } from 'lucide-vue-next';

const props = defineProps<{
    plan: {
        id: number; name: string; description: string; price_monthly: number; price_yearly: number;
        features: string[]; permissions: string[];
    };
    payment_methods: Array<{ id: string; label: string; icon: string }>;
}>();

const form = useForm({
    plan_id: props.plan.id,
    email: '',
    password: '',
    name: '',
    company_name: '',
    provider: 'sslcommerz',
});

const methodIcons: Record<string, any> = {
    'credit-card': CreditCard,
    smartphone: Smartphone,
    landmark: Landmark,
};

const selectedMethod = computed(() => props.payment_methods.find((m) => m.id === form.provider));

function submit() {
    form.post(route('checkout.process'), {
        onFinish: () => form.clearErrors(),
    });
}
</script>

<template>
    <PublicLayout>
        <Head title="Checkout" />
        <div class="mx-auto max-w-4xl px-4 py-12">
            <Link :href="route('pricing')" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition hover:text-foreground">
                <ArrowLeft class="h-4 w-4" /> Back to pricing
            </Link>

            <div class="mt-6 grid grid-cols-1 gap-8 lg:grid-cols-5">
                <!-- Order summary -->
                <div class="lg:col-span-2">
                    <div class="sticky top-24 rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <h2 class="text-sm font-semibold text-muted-foreground uppercase">Order summary</h2>
                        <h3 class="mt-2 text-xl font-bold text-foreground">{{ plan.name }}</h3>
                        <p class="mt-1 text-sm text-muted-foreground">{{ plan.description }}</p>
                        <div class="mt-6 flex items-end justify-between border-t border-border pt-4">
                            <span class="text-3xl font-bold text-foreground">৳{{ Number(plan.price_monthly).toLocaleString('en-IN') }}</span>
                            <span class="text-sm text-muted-foreground">/month</span>
                        </div>
                        <ul class="mt-6 space-y-2">
                            <li v-for="f in plan.features" :key="f" class="flex items-start gap-2 text-sm text-muted-foreground">
                                <Check class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" /> {{ f }}
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Account + payment -->
                <form class="space-y-6 lg:col-span-3" @submit.prevent="submit">
                    <div class="rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-foreground">Create your account</h2>
                        <p class="mt-1 text-sm text-muted-foreground">Your account is created now and activated once payment is confirmed.</p>
                        <div class="mt-5 grid grid-cols-1 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Full name</label>
                                <input v-model="form.name" type="text" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                                <p v-if="form.errors.name" class="text-xs text-rose-500">{{ form.errors.name }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Company / business name <span class="text-muted-foreground">(optional)</span></label>
                                <input v-model="form.company_name" type="text" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Email</label>
                                <input v-model="form.email" type="email" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                                <p v-if="form.errors.email" class="text-xs text-rose-500">{{ form.errors.email }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Password</label>
                                <input v-model="form.password" type="password" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                                <p v-if="form.errors.password" class="text-xs text-rose-500">{{ form.errors.password }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-foreground">Payment method</h2>
                        <div class="mt-4 space-y-2">
                            <button
                                v-for="m in payment_methods"
                                :key="m.id"
                                type="button"
                                class="flex w-full items-center gap-3 rounded-xl border-2 px-4 py-3 text-left text-sm font-medium transition"
                                :class="form.provider === m.id ? 'border-primary bg-primary/5 text-foreground' : 'border-border text-muted-foreground hover:bg-muted'"
                                @click="form.provider = m.id"
                            >
                                <component :is="methodIcons[m.icon] ?? CreditCard" class="h-5 w-5" />
                                {{ m.label }}
                            </button>
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3.5 text-sm font-semibold text-primary-foreground shadow transition hover:bg-primary/90 disabled:opacity-60"
                        :disabled="form.processing"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        {{ form.processing ? 'Processing…' : `Pay ৳${Number(plan.price_monthly).toLocaleString('en-IN')} via ${selectedMethod?.label ?? 'payment'}` }}
                    </button>
                    <p class="text-center text-xs text-muted-foreground">
                        By subscribing you agree to our terms. You'll be taken to {{ selectedMethod?.label ?? 'the payment method' }} to complete payment.
                    </p>
                </form>
            </div>
        </div>
    </PublicLayout>
</template>
