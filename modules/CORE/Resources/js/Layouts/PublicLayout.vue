<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Wallet, ArrowRight } from 'lucide-vue-next';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background">
        <!-- Top nav -->
        <header class="sticky top-0 z-40 border-b border-border/60 bg-background/80 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
                <Link :href="route('home')" class="flex items-center gap-2 text-lg font-bold text-foreground">
                    <div class="flex h-8 w-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                        <Wallet class="h-4 w-4" />
                    </div>
                    {{ page.props.name }}
                </Link>
                <nav class="hidden items-center gap-6 text-sm font-medium text-muted-foreground md:flex">
                    <Link :href="route('pricing')" class="transition hover:text-foreground">Pricing</Link>
                    <a href="#features" class="transition hover:text-foreground">Features</a>
                </nav>
                <div class="flex items-center gap-3">
                    <Link
                        v-if="!user"
                        :href="route('login')"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-border px-4 py-2 text-sm font-semibold text-muted-foreground transition hover:bg-muted hover:text-foreground"
                    >
                        Login
                    </Link>
                    <Link
                        v-if="!user"
                        :href="route('pricing')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90"
                    >
                        Get started <ArrowRight class="h-4 w-4" />
                    </Link>
                    <Link
                        v-else
                        :href="route('dashboard')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90"
                    >
                        Dashboard <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t border-border/60 py-8">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 text-sm text-muted-foreground md:flex-row">
                <p>© {{ new Date().getFullYear() }} {{ page.props.name }}. All rights reserved.</p>
                <p>Built for businesses &amp; individuals.</p>
            </div>
        </footer>
    </div>
</template>
