<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '../../Layouts/PublicLayout.vue';
import { Check, Sparkles, Crown, ArrowRight } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    module: string;
    modules: Array<{
        key: string;
        label: string;
        tagline: string;
        description: string;
        icon: string;
        color: string;
        plans: Array<{
            id: number; name: string; description: string; price_monthly: number; price_yearly: number;
            features: string[]; permissions: string[];
        }>;
    }>;
}>();

// Which module is shown (tab).
const activeModule = ref(props.module);

const currentModule = computed(() => props.modules.find((m) => m.key === activeModule.value) ?? props.modules[0]);

const plans = computed(() => currentModule.value?.plans ?? []);
const moduleLabel = computed(() => currentModule.value?.label ?? '');

const featured = computed(() => plans.value.find((p) => p.name.toLowerCase().includes('pro'))?.id ?? plans.value[1]?.id ?? plans.value[0]?.id);

function price(v: number): string {
    return '৳' + Number(v).toLocaleString('en-IN');
}
</script>

<template>
    <PublicLayout>
        <Head title="Pricing" />
        <div class="mx-auto max-w-6xl px-4 py-16">
            <div class="mx-auto max-w-2xl text-center">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                    <Sparkles class="h-3.5 w-3.5" /> {{ moduleLabel }}
                </span>
                <h1 class="mt-4 text-4xl font-bold tracking-tight text-foreground md:text-5xl">
                    Simple, transparent pricing
                </h1>
                <p class="mt-4 text-lg text-muted-foreground">
                    Start free, upgrade when you're ready. Cancel anytime.
                </p>
            </div>

            <!-- Module switcher -->
            <div v-if="modules.length > 1" class="mt-8 flex flex-wrap items-center justify-center gap-2">
                <button
                    v-for="m in modules"
                    :key="m.key"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-sm font-semibold transition"
                    :class="activeModule === m.key ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-muted/70'"
                    @click="activeModule = m.key"
                >
                    {{ m.label }}
                </button>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-2">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    class="relative flex flex-col rounded-2xl border p-8 shadow-sm transition hover:shadow-lg"
                    :class="plan.id === featured
                        ? 'border-primary/50 bg-gradient-to-b from-primary/5 to-transparent'
                        : 'border-border bg-card'"
                >
                    <div v-if="plan.id === featured" class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <span class="inline-flex items-center gap-1 rounded-full bg-primary px-3 py-1 text-xs font-semibold text-primary-foreground shadow">
                            <Crown class="h-3.5 w-3.5" /> Most popular
                        </span>
                    </div>

                    <h2 class="text-xl font-bold text-foreground">{{ plan.name }}</h2>
                    <p class="mt-2 text-sm text-muted-foreground">{{ plan.description }}</p>

                    <div class="mt-6">
                        <span class="text-4xl font-bold tracking-tight text-foreground">{{ price(plan.price_monthly) }}</span>
                        <span class="text-muted-foreground">/month</span>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        or {{ price(plan.price_yearly) }} billed yearly
                    </p>

                    <ul class="mt-8 flex-1 space-y-3">
                        <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-2 text-sm text-muted-foreground">
                            <Check class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" /> {{ feature }}
                        </li>
                    </ul>

                    <Link
                        :href="route('checkout', plan.id)"
                        class="mt-8 inline-flex items-center justify-center gap-2 rounded-xl px-5 py-3 text-sm font-semibold transition"
                        :class="plan.id === featured
                            ? 'bg-primary text-primary-foreground shadow hover:bg-primary/90'
                            : 'border border-border text-foreground hover:bg-muted'"
                    >
                        Subscribe <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>
            </div>

            <p class="mt-10 text-center text-sm text-muted-foreground">
                Already have an account? <Link :href="route('login')" class="font-medium text-primary hover:underline">Log in</Link>
            </p>
        </div>
    </PublicLayout>
</template>
