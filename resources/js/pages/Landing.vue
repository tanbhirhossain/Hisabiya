<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    ArrowRight,
    Check,
    Wallet,
    TrendingUp,
    PiggyBank,
    Repeat,
    BarChart3,
    Target,
    HandCoins,
    ShieldCheck,
    Lock,
    Sparkles,
    Menu,
    X,
    Star,
    ChevronDown,
    LayoutDashboard,
    ArrowLeftRight,
    Clock,
    Users,
    Database,
} from 'lucide-vue-next';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);

const props = defineProps<{
    modules?: Array<{
        key: string;
        label: string;
        tagline: string;
        plans: Array<{
            id: number;
            name: string;
            description: string;
            price_monthly: number;
            price_yearly: number;
            features: string[];
        }>;
    }>;
}>();

const mobileOpen = ref(false);
const activeModule = ref(0);

// Active module's plans for the pricing section (falls back to any plan).
const pricingModules = computed(() => (props.modules ?? []).length ? props.modules : []);
const currentModule = computed(() => pricingModules.value[activeModule.value] ?? null);

// FAQ accordion
const openFaq = ref<number | null>(0);

const features = [
    {
        icon: LayoutDashboard,
        title: 'A clear money dashboard',
        text: 'See your total balance, income, expenses and net worth at a glance — updated in real time.',
    },
    {
        icon: ArrowLeftRight,
        title: 'Income & expense tracking',
        text: 'Log every transaction across cash, bank and card accounts in seconds, with transfers between accounts.',
    },
    {
        icon: Target,
        title: 'Budgets that keep you on track',
        text: 'Set monthly budgets per category and get live alerts before you overspend.',
    },
    {
        icon: PiggyBank,
        title: 'Savings goals that motivate',
        text: 'Set a target, link an account and watch your progress grow toward the things you care about.',
    },
    {
        icon: Repeat,
        title: 'Recurring transactions, automated',
        text: 'Rent, salary, subscriptions — set them once and they apply automatically on schedule.',
    },
    {
        icon: BarChart3,
        title: 'Reports that make sense',
        text: 'Understand your spending with clear charts and downloadable reports, so you always know where money goes.',
    },
];

const steps = [
    {
        num: '01',
        title: 'Create your account',
        text: 'Sign up in under a minute. No credit card required to start on the free plan.',
    },
    {
        num: '02',
        title: 'Add your money in seconds',
        text: 'Connect your accounts, load sample data, or just start logging transactions.',
    },
    {
        num: '03',
        title: 'Grow with insights',
        text: 'Set budgets and savings goals, then watch your finances transform with clear, actionable data.',
    },
];

const testimonials = [
    {
        quote: 'Finally a money app that understands how we actually spend. The budgets and reports alone are worth it.',
        name: 'Rafiul Islam',
        role: 'Small business owner',
        initials: 'RI',
    },
    {
        quote: 'I went from zero visibility to knowing exactly where every taka goes. Setup took five minutes.',
        name: 'Nusrat Jahan',
        role: 'Freelancer',
        initials: 'NJ',
    },
    {
        quote: 'The loan and savings tracking changed how I manage my finances. It feels like having an accountant in my pocket.',
        name: 'Tanbir Hossain',
        role: 'Product manager',
        initials: 'TH',
    },
];

const faqs = [
    {
        q: 'Is Hisabiya really free to start?',
        a: 'Yes. The free plan lets you track income and expenses, manage budgets and set savings goals with no credit card. Upgrade to a paid plan only when you need advanced features like loans, reports and backups.',
    },
    {
        q: 'How do I pay?',
        a: 'We accept bKash, bank transfer and online card payments through SSLCommerz. Manual bKash/bank payments are confirmed by our team quickly.',
    },
    {
        q: 'Is my financial data safe and private?',
        a: 'Absolutely. Your data is stored in a fully isolated tenant on our platform and is never shared with other users. We use encryption and strict access controls.',
    },
    {
        q: 'Can I cancel anytime?',
        a: 'Yes. You can cancel or downgrade your subscription at any time, no questions asked. We also offer a clear refund policy.',
    },
    {
        q: 'Do I need to be an accountant to use it?',
        a: 'Not at all. Hisabiya is built for regular people and small business owners. It is simple to use, with sample data you can load to explore everything.',
    },
];

const stats = [
    { value: '10+', label: 'Core modules' },
    { value: '50k+', label: 'Transactions trackable' },
    { value: 'BDT', label: 'Made for Bangladesh' },
    { value: '24/7', label: 'Your data, secure' },
];
</script>

<template>
    <Head>
        <title>Hisabiya — Simple accounting &amp; personal finance for Bangladesh</title>
        <meta name="description" content="Hisabiya helps individuals and small businesses track income, expenses, budgets, savings and loans — all in one clean, secure workspace. Start free." />
        <meta property="og:title" content="Hisabiya — Simple accounting & personal finance" />
        <meta property="og:description" content="Track income, expenses, budgets, savings and loans in one clean, secure workspace. Start free." />
        <meta property="og:type" content="website" />
    </Head>

    <div class="min-h-screen bg-background text-foreground antialiased">
        <!-- Announcement bar -->
        <div class="bg-primary px-4 py-2 text-center text-xs font-medium text-primary-foreground">
            <span class="inline-flex items-center gap-1.5">
                <Sparkles class="h-3.5 w-3.5" />
                Launch offer — take control of your money free, upgrade only when you're ready.
            </span>
        </div>

        <!-- Nav -->
        <header class="sticky top-0 z-50 border-b border-border/60 bg-background/85 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6">
                <Link :href="route('home')" class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm">
                        <Wallet class="h-5 w-5" />
                    </div>
                    <span class="text-lg font-bold tracking-tight">Hisabiya</span>
                </Link>

                <nav class="hidden items-center gap-7 text-sm font-medium text-muted-foreground md:flex">
                    <a href="#features" class="transition hover:text-foreground">Features</a>
                    <a href="#how-it-works" class="transition hover:text-foreground">How it works</a>
                    <Link :href="route('pricing')" class="transition hover:text-foreground">Pricing</Link>
                    <a href="#faq" class="transition hover:text-foreground">FAQ</a>
                </nav>

                <div class="flex items-center gap-3">
                    <Link
                        v-if="!user"
                        :href="route('login')"
                        class="hidden text-sm font-semibold text-muted-foreground transition hover:text-foreground sm:inline-flex"
                    >
                        Log in
                    </Link>
                    <Link v-if="!user" :href="route('pricing')">
                        <Button class="gap-1.5">
                            Get started
                            <ArrowRight class="h-4 w-4" />
                        </Button>
                    </Link>
                    <Link v-else :href="route('dashboard')">
                        <Button class="gap-1.5">
                            Go to dashboard
                            <ArrowRight class="h-4 w-4" />
                        </Button>
                    </Link>
                    <button class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border md:hidden" @click="mobileOpen = !mobileOpen">
                        <Menu v-if="!mobileOpen" class="h-5 w-5" />
                        <X v-else class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <!-- Mobile menu -->
            <div v-if="mobileOpen" class="border-t border-border bg-background px-4 py-4 md:hidden">
                <div class="flex flex-col gap-4">
                    <a href="#features" class="text-sm font-medium text-muted-foreground" @click="mobileOpen = false">Features</a>
                    <a href="#how-it-works" class="text-sm font-medium text-muted-foreground" @click="mobileOpen = false">How it works</a>
                    <Link :href="route('pricing')" class="text-sm font-medium text-muted-foreground" @click="mobileOpen = false">Pricing</Link>
                    <a href="#faq" class="text-sm font-medium text-muted-foreground" @click="mobileOpen = false">FAQ</a>
                    <Link v-if="!user" :href="route('login')" class="text-sm font-medium text-muted-foreground" @click="mobileOpen = false">Log in</Link>
                </div>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0">
                <div class="absolute -top-32 left-1/4 h-96 w-96 rounded-full bg-indigo-500/20 blur-3xl" />
                <div class="absolute right-0 top-20 h-72 w-72 rounded-full bg-sky-500/10 blur-3xl" />
            </div>

            <div class="relative mx-auto max-w-7xl px-4 pb-16 pt-16 text-center sm:px-6 md:pb-24 md:pt-24">
                <div class="mx-auto mb-6 inline-flex items-center gap-2 rounded-full border border-border bg-background/60 px-3 py-1 text-xs font-medium text-muted-foreground">
                    <ShieldCheck class="h-3.5 w-3.5 text-primary" />
                    Made for Bangladesh · bKash &amp; bank friendly
                </div>

                <h1 class="mx-auto max-w-4xl text-4xl font-bold leading-[1.1] tracking-tight sm:text-6xl">
                    Your money, finally<br class="hidden sm:block" />
                    <span class="bg-gradient-to-r from-indigo-600 to-sky-500 bg-clip-text text-transparent">under control</span>
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-lg text-muted-foreground">
                    Hisabiya helps individuals and small businesses track income, expenses, budgets, savings and loans —
                    all in one clean, secure workspace. Simple enough for anyone.
                </p>

                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <Link :href="route('pricing')">
                        <Button size="lg" class="w-full gap-2 px-8 text-base sm:w-auto">
                            Start free today
                            <ArrowRight class="h-5 w-5" />
                        </Button>
                    </Link>
                    <a href="#features">
                        <Button size="lg" variant="outline" class="w-full gap-2 px-8 text-base sm:w-auto">
                            Explore features
                        </Button>
                    </a>
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-muted-foreground">
                    <span class="inline-flex items-center gap-1.5"><Check class="h-4 w-4 text-emerald-500" /> No credit card required</span>
                    <span class="inline-flex items-center gap-1.5"><Check class="h-4 w-4 text-emerald-500" /> Free plan</span>
                    <span class="inline-flex items-center gap-1.5"><Lock class="h-4 w-4 text-emerald-500" /> Your data stays private</span>
                </div>

                <!-- Fake product preview -->
                <div class="relative mx-auto mt-16 max-w-5xl">
                    <div class="rounded-2xl border border-border bg-card p-2 shadow-2xl shadow-indigo-500/10">
                        <div class="flex items-center gap-1.5 border-b border-border px-4 py-3">
                            <span class="h-3 w-3 rounded-full bg-rose-400" />
                            <span class="h-3 w-3 rounded-full bg-amber-400" />
                            <span class="h-3 w-3 rounded-full bg-emerald-400" />
                            <span class="ml-3 text-xs font-medium text-muted-foreground">hisabiya — Dashboard</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-4">
                            <div class="col-span-2 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 p-5 text-white">
                                <p class="text-xs font-medium text-emerald-100">Total balance</p>
                                <p class="mt-1 text-2xl font-bold sm:text-3xl">৳ 1,24,500</p>
                                <p class="mt-2 text-xs text-emerald-100">Across 3 accounts</p>
                            </div>
                            <div class="rounded-xl border border-border bg-background p-5">
                                <p class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground"><TrendingUp class="h-3.5 w-3.5 text-emerald-500" /> Income</p>
                                <p class="mt-1 text-2xl font-bold">৳ 62,400</p>
                            </div>
                            <div class="rounded-xl border border-border bg-background p-5">
                                <p class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground"><ArrowRight class="h-3.5 w-3.5 text-rose-500" /> Expenses</p>
                                <p class="mt-1 text-2xl font-bold">৳ 38,900</p>
                            </div>
                            <div class="col-span-2 rounded-xl border border-border bg-background p-5">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-medium text-muted-foreground">Budget health</p>
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400">On track</span>
                                </div>
                                <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-muted">
                                    <div class="h-full w-[68%] rounded-full bg-emerald-500" />
                                </div>
                                <p class="mt-2 text-xs text-muted-foreground">68% of monthly budget used</p>
                            </div>
                            <div class="col-span-2 rounded-xl border border-border bg-background p-5">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-medium text-muted-foreground">Savings goal</p>
                                    <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-semibold text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-400">Emergency fund</span>
                                </div>
                                <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-muted">
                                    <div class="h-full w-[45%] rounded-full bg-indigo-500" />
                                </div>
                                <p class="mt-2 text-xs text-muted-foreground">৳ 45,000 of ৳ 100,000</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats -->
        <section class="border-y border-border bg-muted/40">
            <div class="mx-auto grid max-w-7xl grid-cols-2 gap-8 px-4 py-12 sm:px-6 md:grid-cols-4">
                <div v-for="s in stats" :key="s.label" class="text-center">
                    <p class="text-3xl font-bold text-foreground">{{ s.value }}</p>
                    <p class="mt-1 text-sm text-muted-foreground">{{ s.label }}</p>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section id="features" class="mx-auto max-w-7xl scroll-mt-20 px-4 py-20 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                    <Sparkles class="h-3.5 w-3.5" /> Everything you need
                </span>
                <h2 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">One place for all your money</h2>
                <p class="mt-4 text-lg text-muted-foreground">Powerful tools that work the way you do — without the accountant-speak.</p>
            </div>

            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="f in features"
                    :key="f.title"
                    class="group rounded-2xl border border-border bg-card p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-400/40 hover:shadow-lg"
                >
                    <div class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary transition group-hover:bg-primary group-hover:text-primary-foreground">
                        <component :is="f.icon" class="h-5 w-5" />
                    </div>
                    <h3 class="mt-4 text-lg font-semibold">{{ f.title }}</h3>
                    <p class="mt-2 text-sm text-muted-foreground">{{ f.text }}</p>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section id="how-it-works" class="scroll-mt-20 border-y border-border bg-muted/40">
            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6">
                <div class="mx-auto max-w-2xl text-center">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                        <Clock class="h-3.5 w-3.5" /> Get started in minutes
                    </span>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">How it works</h2>
                </div>

                <div class="mt-12 grid gap-8 md:grid-cols-3">
                    <div v-for="step in steps" :key="step.num" class="relative text-center md:text-left">
                        <p class="text-5xl font-bold text-primary/15">{{ step.num }}</p>
                        <h3 class="mt-3 text-lg font-semibold">{{ step.title }}</h3>
                        <p class="mt-2 text-sm text-muted-foreground">{{ step.text }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials -->
        <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                    <Star class="h-3.5 w-3.5" /> Loved by people like you
                </span>
                <h2 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">Trusted by mindful spenders</h2>
            </div>

            <div class="mt-12 grid gap-6 md:grid-cols-3">
                <div v-for="t in testimonials" :key="t.name" class="flex flex-col justify-between rounded-2xl border border-border bg-card p-6 shadow-sm">
                    <div>
                        <div class="flex gap-0.5">
                            <Star v-for="i in 5" :key="i" class="h-4 w-4 fill-amber-400 text-amber-400" />
                        </div>
                        <p class="mt-4 text-sm leading-relaxed text-foreground">“{{ t.quote }}”</p>
                    </div>
                    <div class="mt-6 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">
                            {{ t.initials }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold">{{ t.name }}</p>
                            <p class="text-xs text-muted-foreground">{{ t.role }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing teaser -->
        <section class="scroll-mt-20 border-y border-border bg-muted/40">
            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Simple, transparent pricing</h2>
                    <p class="mt-4 text-lg text-muted-foreground">Start free, upgrade when you're ready. Cancel anytime.</p>
                </div>

                <!-- Module tabs (multi-module pricing) -->
                <div v-if="pricingModules.length > 1" class="mt-8 flex flex-wrap items-center justify-center gap-2">
                    <button
                        v-for="(m, i) in pricingModules"
                        :key="m.key"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-sm font-semibold transition"
                        :class="activeModule === i ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-muted/70'"
                        @click="activeModule = i"
                    >
                        {{ m.label }}
                    </button>
                </div>

                <div v-if="currentModule" class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="(plan, pi) in currentModule.plans"
                        :key="plan.id"
                        class="relative flex flex-col rounded-2xl border p-6 shadow-sm transition hover:shadow-lg"
                        :class="pi === 1
                            ? 'border-2 border-primary bg-card shadow-lg'
                            : 'border-border bg-card'"
                    >
                        <span v-if="pi === 1" class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-primary px-3 py-0.5 text-xs font-semibold text-primary-foreground">Most popular</span>

                        <h3 class="text-lg font-semibold">{{ plan.name }}</h3>
                        <p class="mt-1 text-sm text-muted-foreground">{{ plan.description }}</p>
                        <p class="mt-4 text-3xl font-bold">৳{{ Number(plan.price_monthly).toLocaleString('en-IN') }}<span class="text-base font-normal text-muted-foreground">/mo</span></p>
                        <p v-if="plan.price_yearly" class="mt-1 text-xs text-muted-foreground">or ৳{{ Number(plan.price_yearly).toLocaleString('en-IN') }}/yr</p>

                        <ul class="mt-6 flex-1 space-y-2.5 text-sm">
                            <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-2">
                                <Check class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" /> {{ feature }}
                            </li>
                        </ul>

                        <Link :href="route('checkout', plan.id)" class="mt-6">
                            <Button :variant="pi === 1 ? 'default' : 'outline'" class="w-full">Choose {{ plan.name }}</Button>
                        </Link>
                    </div>
                </div>

                <div v-else class="mt-10 text-center text-sm text-muted-foreground">
                    No active plans right now.
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section id="faq" class="mx-auto max-w-3xl scroll-mt-20 px-4 py-20 sm:px-6">
            <div class="text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Frequently asked questions</h2>
            </div>

            <div class="mt-10 divide-y divide-border rounded-2xl border border-border bg-card">
                <div v-for="(faq, i) in faqs" :key="i">
                    <button class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left" @click="openFaq = openFaq === i ? null : i">
                        <span class="text-sm font-semibold sm:text-base">{{ faq.q }}</span>
                        <ChevronDown class="h-5 w-5 shrink-0 text-muted-foreground transition" :class="openFaq === i ? 'rotate-180' : ''" />
                    </button>
                    <div v-if="openFaq === i" class="px-6 pb-5">
                        <p class="text-sm leading-relaxed text-muted-foreground">{{ faq.a }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final CTA -->
        <section class="relative overflow-hidden border-t border-border">
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-indigo-600 via-indigo-600 to-violet-700" />
            <div class="relative mx-auto max-w-3xl px-4 py-20 text-center sm:px-6">
                <h2 class="text-3xl font-bold tracking-tight text-white sm:text-5xl">Take control of your money today</h2>
                <p class="mx-auto mt-4 max-w-xl text-lg text-indigo-100">
                    Join people across Bangladesh who finally know where their money goes. Start free in under a minute.
                </p>
                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <Link :href="route('pricing')">
                        <Button size="lg" class="w-full gap-2 bg-white px-8 text-base text-indigo-700 shadow-lg hover:bg-indigo-50 sm:w-auto">
                            Start free now
                            <ArrowRight class="h-5 w-5" />
                        </Button>
                    </Link>
                    <Link v-if="!user" :href="route('login')">
                        <Button size="lg" variant="ghost" class="w-full gap-2 px-8 text-base text-white hover:bg-white/10 sm:w-auto">
                            Log in
                        </Button>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-border bg-background py-12">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-8 px-4 sm:px-6 md:flex-row">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                        <Wallet class="h-4 w-4" />
                    </div>
                    <span class="font-bold">Hisabiya</span>
                </div>
                <div class="flex flex-wrap items-center justify-center gap-6 text-sm text-muted-foreground">
                    <Link :href="route('legal.terms')" class="transition hover:text-foreground">Terms</Link>
                    <Link :href="route('legal.privacy')" class="transition hover:text-foreground">Privacy</Link>
                    <Link :href="route('legal.refund')" class="transition hover:text-foreground">Refund</Link>
                    <Link :href="route('pricing')" class="transition hover:text-foreground">Pricing</Link>
                </div>
                <p class="text-sm text-muted-foreground">© {{ new Date().getFullYear() }} Hisabiya. All rights reserved.</p>
            </div>
        </footer>
    </div>
</template>
