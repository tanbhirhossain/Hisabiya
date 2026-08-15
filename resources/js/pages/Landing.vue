<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
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
    PlayCircle,
    Quote,
    BadgeCheck,
    Bell,
    Zap,
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
const openFaq = ref<number | null>(0);
const scrolled = ref(false);

// Scroll state for sticky nav elevation.
function onScroll() {
    scrolled.value = window.scrollY > 8;
}
onMounted(() => window.addEventListener('scroll', onScroll, { passive: true }));
onBeforeUnmount(() => window.removeEventListener('scroll', onScroll));

const pricingModules = computed(() => (props.modules ?? []).length ? props.modules : []);
const currentModule = computed(() => pricingModules.value[activeModule.value] ?? null);

const features = [
    {
        icon: LayoutDashboard,
        title: 'A clear money dashboard',
        text: 'See your total balance, income, expenses and net worth at a glance — updated in real time.',
    },
    {
        icon: ArrowLeftRight,
        title: 'Income & expense tracking',
        text: 'Log every transaction across cash, bank and card accounts in seconds, with instant transfers between accounts.',
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
    { num: '01', icon: Wallet, title: 'Create your account', text: 'Sign up in under a minute. No credit card required to start on the free plan.' },
    { num: '02', icon: Zap, title: 'Add your money in seconds', text: 'Connect your accounts, load sample data, or just start logging transactions.' },
    { num: '03', icon: TrendingUp, title: 'Grow with insights', text: 'Set budgets and savings goals, then watch your finances transform with clear, actionable data.' },
];

const testimonials = [
    { quote: 'Finally a money app that understands how we actually spend. The budgets and reports alone are worth it.', name: 'Rafiul Islam', role: 'Small business owner', initials: 'RI', color: 'from-indigo-500 to-violet-600' },
    { quote: 'I went from zero visibility to knowing exactly where every taka goes. Setup took five minutes.', name: 'Nusrat Jahan', role: 'Freelancer', initials: 'NJ', color: 'from-emerald-500 to-teal-600' },
    { quote: 'The loan and savings tracking changed how I manage my finances. It feels like having an accountant in my pocket.', name: 'Tanbir Hossain', role: 'Product manager', initials: 'TH', color: 'from-sky-500 to-indigo-600' },
];

const faqs = [
    { q: 'Is Hisabiya really free to start?', a: 'Yes. The free plan lets you track income and expenses, manage budgets and set savings goals with no credit card. Upgrade to a paid plan only when you need advanced features like loans, reports and backups.' },
    { q: 'How do I pay?', a: 'We accept bKash, bank transfer and online card payments through SSLCommerz. Manual bKash/bank payments are confirmed by our team quickly.' },
    { q: 'Is my financial data safe and private?', a: 'Absolutely. Your data is stored in a fully isolated tenant on our platform and is never shared with other users. We use encryption and strict access controls.' },
    { q: 'Can I cancel anytime?', a: 'Yes. You can cancel or downgrade your subscription at any time, no questions asked. We also offer a clear refund policy.' },
    { q: 'Do I need to be an accountant to use it?', a: 'Not at all. Hisabiya is built for regular people and small business owners. It is simple to use, with sample data you can load to explore everything.' },
];

const stats = [
    { value: '10+', label: 'Core modules', suffix: '+' },
    { value: '50k', label: 'Transactions tracked', suffix: 'k+' },
    { value: '৳', label: 'Managed in BDT', suffix: '' },
    { value: '99.9%', label: 'Data security uptime', suffix: '%' },
];

const socialAvatars = [
    { initials: 'RI', color: 'from-indigo-500 to-violet-600' },
    { initials: 'NJ', color: 'from-emerald-500 to-teal-600' },
    { initials: 'TH', color: 'from-sky-500 to-indigo-600' },
    { initials: 'SA', color: 'from-rose-500 to-pink-600' },
    { initials: 'MK', color: 'from-amber-500 to-orange-600' },
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

    <div class="min-h-screen overflow-x-hidden bg-background text-foreground antialiased">
        <!-- Announcement bar -->
        <div class="relative bg-gradient-to-r from-indigo-600 via-violet-600 to-indigo-700 px-4 py-2.5 text-center text-xs font-medium text-white">
            <span class="inline-flex items-center gap-2">
                <Sparkles class="h-3.5 w-3.5" />
                <span class="hidden sm:inline">Launch offer — take control of your money free, upgrade only when you're ready.</span>
                <span class="sm:hidden">Launch offer — start free today.</span>
            </span>
        </div>

        <!-- Nav -->
        <header class="sticky top-0 z-50 transition-all duration-300" :class="scrolled ? 'border-b border-border/70 bg-background/80 shadow-sm backdrop-blur-xl' : 'bg-background/50 backdrop-blur'">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6">
                <Link :href="route('home')" class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-lg shadow-indigo-500/25">
                        <Wallet class="h-5 w-5" />
                    </div>
                    <span class="text-lg font-bold tracking-tight">Hisabiya</span>
                </Link>

                <nav class="hidden items-center gap-8 text-sm font-medium text-muted-foreground md:flex">
                    <a href="#features" class="transition hover:text-foreground">Features</a>
                    <a href="#how-it-works" class="transition hover:text-foreground">How it works</a>
                    <Link :href="route('pricing')" class="transition hover:text-foreground">Pricing</Link>
                    <a href="#faq" class="transition hover:text-foreground">FAQ</a>
                </nav>

                <div class="flex items-center gap-3">
                    <Link v-if="!user" :href="route('login')" class="hidden text-sm font-semibold text-muted-foreground transition hover:text-foreground sm:inline-flex">
                        Log in
                    </Link>
                    <Link v-if="!user" :href="route('pricing')">
                        <Button class="gap-1.5 rounded-lg shadow-lg shadow-indigo-500/20">
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
            <!-- Layered premium background -->
            <div class="pointer-events-none absolute inset-0">
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(99,102,241,0.12),transparent_60%)]" />
                <div class="absolute -top-40 left-1/4 h-[30rem] w-[30rem] rounded-full bg-indigo-500/25 blur-3xl" />
                <div class="absolute right-0 top-20 h-80 w-80 rounded-full bg-sky-500/20 blur-3xl" />
                <div class="absolute bottom-0 left-0 h-72 w-72 rounded-full bg-violet-500/15 blur-3xl" />
                <!-- subtle grid -->
                <div class="absolute inset-0 opacity-[0.04]" style="background-image:linear-gradient(to right,#6366f1 1px,transparent 1px),linear-gradient(to bottom,#6366f1 1px,transparent 1px);background-size:48px 48px" />
            </div>

            <div class="relative mx-auto max-w-7xl px-4 pb-16 pt-14 text-center sm:px-6 md:pb-24 md:pt-20">
                <!-- Trust badge -->
                <div class="mx-auto mb-7 inline-flex items-center gap-2 rounded-full border border-border/70 bg-background/70 px-4 py-1.5 text-xs font-medium text-muted-foreground shadow-sm backdrop-blur">
                    <span class="flex h-2 w-2">
                        <span class="absolute inline-flex h-2 w-2 animate-ping rounded-full bg-emerald-400 opacity-75" />
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500" />
                    </span>
                    Made for Bangladesh · bKash &amp; bank friendly
                </div>

                <h1 class="mx-auto max-w-4xl text-[2.6rem] font-bold leading-[1.05] tracking-tight sm:text-6xl md:text-7xl">
                    Your money, finally
                    <span class="relative mt-2 block bg-gradient-to-r from-indigo-600 via-violet-600 to-sky-500 bg-clip-text pb-2 text-transparent">
                        under control
                        <svg class="absolute -bottom-1 left-1/2 w-[92%] -translate-x-1/2 text-indigo-500/30" viewBox="0 0 400 12" fill="none"><path d="M2 9c70-5 220-8 396-4" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                    </span>
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-lg text-muted-foreground md:text-xl">
                    Hisabiya helps individuals and small businesses track income, expenses, budgets, savings and loans —
                    all in one clean, secure workspace. Simple enough for anyone.
                </p>

                <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <Link :href="route('pricing')">
                        <Button size="lg" class="group w-full gap-2 px-8 py-3 text-base shadow-xl shadow-indigo-500/25 sm:w-auto">
                            Start free today
                            <ArrowRight class="h-5 w-5 transition group-hover:translate-x-0.5" />
                        </Button>
                    </Link>
                    <a href="#how-it-works">
                        <Button size="lg" variant="outline" class="w-full gap-2 px-8 py-3 text-base sm:w-auto">
                            <PlayCircle class="h-5 w-5" />
                            See how it works
                        </Button>
                    </a>
                </div>

                <!-- Social proof row -->
                <div class="mt-7 flex flex-col items-center justify-center gap-3 sm:flex-row sm:gap-5">
                    <div class="flex -space-x-2.5">
                        <div v-for="(a, i) in socialAvatars" :key="i" class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br text-[10px] font-bold text-white ring-2 ring-background" :class="a.color">
                            {{ a.initials }}
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 text-sm text-muted-foreground">
                        <div class="flex gap-0.5">
                            <Star v-for="i in 5" :key="i" class="h-4 w-4 fill-amber-400 text-amber-400" />
                        </div>
                        <span><strong class="text-foreground">4.9/5</strong> from 2,000+ users</span>
                    </div>
                </div>

                <!-- Premium product mockup -->
                <div class="relative mx-auto mt-14 max-w-5xl">
                    <!-- Glow behind mockup -->
                    <div class="absolute -inset-x-8 -top-10 h-40 bg-gradient-to-r from-indigo-500/20 via-violet-500/20 to-sky-500/20 blur-3xl" />

                    <div class="relative rounded-2xl border border-border/70 bg-card/80 p-2 shadow-2xl shadow-indigo-500/10 backdrop-blur">
                        <div class="flex items-center gap-1.5 border-b border-border px-4 py-3">
                            <span class="h-3 w-3 rounded-full bg-rose-400" />
                            <span class="h-3 w-3 rounded-full bg-amber-400" />
                            <span class="h-3 w-3 rounded-full bg-emerald-400" />
                            <span class="ml-3 flex items-center gap-1.5 rounded-md bg-muted px-2 py-0.5 text-[11px] font-medium text-muted-foreground">
                                <Lock class="h-3 w-3" /> hisabiya.app/dashboard
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-4">
                            <div class="col-span-2 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 p-5 text-white shadow-lg">
                                <p class="flex items-center gap-1.5 text-xs font-medium text-emerald-100"><Wallet class="h-3.5 w-3.5" /> Total balance</p>
                                <p class="mt-1 text-3xl font-bold sm:text-4xl">৳ 1,24,500</p>
                                <p class="mt-2 text-xs text-emerald-100">Across 3 accounts</p>
                                <span class="mt-3 inline-flex items-center gap-1 rounded-full bg-white/15 px-2 py-0.5 text-[10px] font-semibold text-white"><TrendingUp class="h-3 w-3" /> +12% this month</span>
                            </div>
                            <div class="rounded-xl border border-border bg-background p-5">
                                <p class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground"><TrendingUp class="h-3.5 w-3.5 text-emerald-500" /> Income</p>
                                <p class="mt-1 text-2xl font-bold">৳ 62,400</p>
                                <span class="mt-2 inline-block rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400">+8%</span>
                            </div>
                            <div class="rounded-xl border border-border bg-background p-5">
                                <p class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground"><ArrowRight class="h-3.5 w-3.5 text-rose-500" /> Expenses</p>
                                <p class="mt-1 text-2xl font-bold">৳ 38,900</p>
                                <span class="mt-2 inline-block rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold text-rose-700 dark:bg-rose-500/15 dark:text-rose-400">-5%</span>
                            </div>
                            <div class="col-span-2 rounded-xl border border-border bg-background p-5">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-medium text-muted-foreground">Budget health</p>
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400">On track</span>
                                </div>
                                <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-muted">
                                    <div class="h-full w-[68%] rounded-full bg-gradient-to-r from-emerald-500 to-teal-500" />
                                </div>
                                <p class="mt-2 text-xs text-muted-foreground">68% of monthly budget used</p>
                            </div>
                            <div class="col-span-2 rounded-xl border border-border bg-background p-5">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-medium text-muted-foreground">Savings goal</p>
                                    <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-semibold text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-400">Emergency fund</span>
                                </div>
                                <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-muted">
                                    <div class="h-full w-[45%] rounded-full bg-gradient-to-r from-indigo-500 to-violet-500" />
                                </div>
                                <p class="mt-2 text-xs text-muted-foreground">৳ 45,000 of ৳ 100,000</p>
                            </div>
                        </div>

                        <!-- Floating notification card -->
                        <div class="absolute -right-3 top-16 hidden rounded-xl border border-border bg-card p-3 shadow-xl backdrop-blur sm:block lg:-right-6">
                            <div class="flex items-center gap-2">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-500"><Bell class="h-4 w-4" /></div>
                                <div>
                                    <p class="text-xs font-semibold">Budget alert</p>
                                    <p class="text-[10px] text-muted-foreground">Food: 80% used</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats -->
        <section class="relative border-y border-border/60 bg-muted/30">
            <div class="mx-auto grid max-w-7xl grid-cols-2 gap-8 px-4 py-14 sm:px-6 md:grid-cols-4">
                <div v-for="s in stats" :key="s.label" class="text-center">
                    <p class="bg-gradient-to-r from-indigo-600 to-violet-600 bg-clip-text text-4xl font-bold text-transparent">{{ s.value }}<span v-if="s.suffix" class="text-2xl">{{ s.suffix }}</span></p>
                    <p class="mt-1.5 text-sm text-muted-foreground">{{ s.label }}</p>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section id="features" class="mx-auto max-w-7xl scroll-mt-20 px-4 py-24 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-primary/20 bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                    <Sparkles class="h-3.5 w-3.5" /> Everything you need
                </span>
                <h2 class="mt-5 text-3xl font-bold tracking-tight sm:text-5xl">One place for all your money</h2>
                <p class="mt-4 text-lg text-muted-foreground">Powerful tools that work the way you do — without the accountant-speak.</p>
            </div>

            <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="f in features"
                    :key="f.title"
                    class="group relative overflow-hidden rounded-2xl border border-border/70 bg-card p-7 shadow-sm transition-all duration-300 hover:-translate-y-1.5 hover:border-indigo-400/40 hover:shadow-xl hover:shadow-indigo-500/10"
                >
                    <div class="pointer-events-none absolute -right-10 -top-10 h-28 w-28 rounded-full bg-indigo-500/10 blur-2xl opacity-0 transition group-hover:opacity-100" />
                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-lg shadow-indigo-500/20 transition group-hover:scale-110">
                        <component :is="f.icon" class="h-6 w-6" />
                    </div>
                    <h3 class="mt-5 text-lg font-semibold">{{ f.title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-muted-foreground">{{ f.text }}</p>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section id="how-it-works" class="scroll-mt-20 border-y border-border/60 bg-muted/30">
            <div class="mx-auto max-w-7xl px-4 py-24 sm:px-6">
                <div class="mx-auto max-w-2xl text-center">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-primary/20 bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                        <Clock class="h-3.5 w-3.5" /> Get started in minutes
                    </span>
                    <h2 class="mt-5 text-3xl font-bold tracking-tight sm:text-5xl">How it works</h2>
                </div>

                <div class="relative mt-16 grid gap-8 md:grid-cols-3">
                    <div v-for="step in steps" :key="step.num" class="group relative text-center md:text-left">
                        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-lg shadow-indigo-500/20 transition group-hover:scale-110 md:mx-0">
                            <component :is="step.icon" class="h-7 w-7" />
                        </div>
                        <p class="absolute -top-4 right-0 text-6xl font-bold text-primary/10 md:right-4">{{ step.num }}</p>
                        <h3 class="text-lg font-semibold">{{ step.title }}</h3>
                        <p class="mt-2 text-sm text-muted-foreground">{{ step.text }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials -->
        <section class="mx-auto max-w-7xl px-4 py-24 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-primary/20 bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                    <Star class="h-3.5 w-3.5" /> Loved by people like you
                </span>
                <h2 class="mt-5 text-3xl font-bold tracking-tight sm:text-5xl">Trusted by mindful spenders</h2>
            </div>

            <div class="mt-16 grid gap-6 md:grid-cols-3">
                <div v-for="t in testimonials" :key="t.name" class="flex flex-col justify-between rounded-2xl border border-border/70 bg-card p-7 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                    <div>
                        <Quote class="h-7 w-7 text-primary/30" />
                        <div class="mt-3 flex gap-0.5">
                            <Star v-for="i in 5" :key="i" class="h-4 w-4 fill-amber-400 text-amber-400" />
                        </div>
                        <p class="mt-4 text-sm leading-relaxed text-foreground">“{{ t.quote }}”</p>
                    </div>
                    <div class="mt-6 flex items-center gap-3 border-t border-border/60 pt-5">
                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br text-sm font-bold text-white" :class="t.color">
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
        <section class="scroll-mt-20 border-y border-border/60 bg-muted/30">
            <div class="mx-auto max-w-7xl px-4 py-24 sm:px-6">
                <div class="mx-auto max-w-2xl text-center">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-primary/20 bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                        <BadgeCheck class="h-3.5 w-3.5" /> Simple, transparent pricing
                    </span>
                    <h2 class="mt-5 text-3xl font-bold tracking-tight sm:text-5xl">Start free, upgrade when you're ready</h2>
                    <p class="mt-4 text-lg text-muted-foreground">Cancel anytime. No hidden fees.</p>
                </div>

                <!-- Module tabs -->
                <div v-if="pricingModules.length > 1" class="mt-8 flex flex-wrap items-center justify-center gap-2">
                    <button
                        v-for="(m, i) in pricingModules"
                        :key="m.key"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-sm font-semibold transition"
                        :class="activeModule === i ? 'bg-primary text-primary-foreground shadow-lg shadow-indigo-500/20' : 'bg-muted text-muted-foreground hover:bg-muted/70'"
                        @click="activeModule = i"
                    >
                        {{ m.label }}
                    </button>
                </div>

                <div v-if="currentModule" class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="(plan, pi) in currentModule.plans"
                        :key="plan.id"
                        class="relative flex flex-col rounded-2xl p-7 transition-all duration-300 hover:-translate-y-1"
                        :class="pi === 1
                            ? 'border-2 border-primary bg-gradient-to-b from-primary/5 to-card shadow-xl shadow-indigo-500/10'
                            : 'border border-border/70 bg-card shadow-sm hover:shadow-lg'"
                    >
                        <span v-if="pi === 1" class="absolute -top-3 left-1/2 -translate-x-1/2 inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-indigo-600 to-violet-600 px-3 py-1 text-xs font-semibold text-white shadow-lg">
                            <Sparkles class="h-3 w-3" /> Most popular
                        </span>

                        <h3 class="text-lg font-semibold">{{ plan.name }}</h3>
                        <p class="mt-1 text-sm text-muted-foreground">{{ plan.description }}</p>
                        <p class="mt-5 flex items-baseline gap-1">
                            <span class="text-4xl font-bold tracking-tight">৳{{ Number(plan.price_monthly).toLocaleString('en-IN') }}</span>
                            <span class="text-sm text-muted-foreground">/mo</span>
                        </p>
                        <p v-if="plan.price_yearly" class="mt-1 text-xs text-muted-foreground">or ৳{{ Number(plan.price_yearly).toLocaleString('en-IN') }}/yr</p>

                        <ul class="mt-7 flex-1 space-y-3">
                            <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-2.5 text-sm">
                                <span class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-emerald-500/15">
                                    <Check class="h-3 w-3 text-emerald-500" />
                                </span>
                                {{ feature }}
                            </li>
                        </ul>

                        <Link :href="route('checkout', plan.id)" class="mt-8">
                            <Button :variant="pi === 1 ? 'default' : 'outline'" class="w-full gap-1.5">
                                Choose {{ plan.name }}
                                <ArrowRight class="h-4 w-4" />
                            </Button>
                        </Link>
                    </div>
                </div>

                <div v-else class="mt-10 text-center text-sm text-muted-foreground">
                    No active plans right now.
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section id="faq" class="mx-auto max-w-3xl scroll-mt-20 px-4 py-24 sm:px-6">
            <div class="text-center">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-primary/20 bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                    <ShieldCheck class="h-3.5 w-3.5" /> We've got you covered
                </span>
                <h2 class="mt-5 text-3xl font-bold tracking-tight sm:text-4xl">Frequently asked questions</h2>
            </div>

            <div class="mt-12 divide-y divide-border rounded-2xl border border-border/70 bg-card shadow-sm">
                <div v-for="(faq, i) in faqs" :key="i">
                    <button class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left transition hover:bg-muted/40" @click="openFaq = openFaq === i ? null : i">
                        <span class="text-sm font-semibold sm:text-base">{{ faq.q }}</span>
                        <ChevronDown class="h-5 w-5 shrink-0 text-muted-foreground transition-transform" :class="openFaq === i ? 'rotate-180' : ''" />
                    </button>
                    <div v-if="openFaq === i" class="px-6 pb-5">
                        <p class="text-sm leading-relaxed text-muted-foreground">{{ faq.a }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final CTA -->
        <section class="relative overflow-hidden border-t border-border">
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-indigo-700 via-violet-700 to-indigo-800" />
            <div class="pointer-events-none absolute -top-20 left-1/2 h-72 w-[40rem] -translate-x-1/2 rounded-full bg-white/10 blur-3xl" />
            <div class="relative mx-auto max-w-3xl px-4 py-24 text-center sm:px-6">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white backdrop-blur">
                    <Sparkles class="h-3.5 w-3.5" /> Join 2,000+ users across Bangladesh
                </span>
                <h2 class="mt-6 text-4xl font-bold tracking-tight text-white sm:text-5xl">Take control of your money today</h2>
                <p class="mx-auto mt-4 max-w-xl text-lg text-indigo-100">
                    Start free in under a minute. Upgrade only when you're ready.
                </p>
                <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <Link :href="route('pricing')">
                        <Button size="lg" class="w-full gap-2 bg-white px-8 py-3 text-base text-indigo-700 shadow-xl shadow-black/10 hover:bg-indigo-50 sm:w-auto">
                            Start free now
                            <ArrowRight class="h-5 w-5" />
                        </Button>
                    </Link>
                    <Link v-if="!user" :href="route('login')">
                        <Button size="lg" variant="ghost" class="w-full gap-2 px-8 py-3 text-base text-white hover:bg-white/10 sm:w-auto">
                            Log in
                        </Button>
                    </Link>
                </div>
                <div class="mt-8 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-indigo-100">
                    <span class="inline-flex items-center gap-1.5"><Lock class="h-4 w-4" /> No credit card required</span>
                    <span class="inline-flex items-center gap-1.5"><ShieldCheck class="h-4 w-4" /> Your data stays private</span>
                    <span class="inline-flex items-center gap-1.5"><BadgeCheck class="h-4 w-4" /> Cancel anytime</span>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-border bg-background py-14">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-8 px-4 sm:px-6 md:flex-row">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-lg shadow-indigo-500/20">
                        <Wallet class="h-5 w-5" />
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
