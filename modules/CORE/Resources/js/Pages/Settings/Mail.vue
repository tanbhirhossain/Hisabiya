<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import { Mail, CheckCircle2, XCircle, Send } from 'lucide-vue-next';

const props = defineProps<{
    settings: {
        enabled: boolean;
        driver: string;
        host: string;
        port: number;
        username: string;
        encryption: string;
        from_address: string;
        from_name: string;
    };
    configured: boolean;
}>();

const form = useForm({
    enabled: props.settings.enabled,
    driver: props.settings.driver,
    host: props.settings.host,
    port: props.settings.port,
    username: props.settings.username,
    password: '',
    encryption: props.settings.encryption || 'tls',
    from_address: props.settings.from_address,
    from_name: props.settings.from_name,
});

const testForm = useForm({ email: '' });

function save() {
    form.post(route('settings.mail.update'));
}

function sendTest() {
    testForm.post(route('settings.mail.test'));
}
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Mail Settings', href: '/admin/settings/mail' }]">
        <Head title="Mail Settings" />
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <PageHeader title="Mail settings" description="Configure outbound transactional email (receipts, invoices, alerts) via SMTP." />

            <!-- Status banner -->
            <div
                class="flex items-center gap-3 rounded-xl border px-5 py-4"
                :class="configured ? 'border-emerald-300/60 bg-emerald-50/40 dark:border-emerald-500/30 dark:bg-emerald-500/5' : 'border-amber-300/60 bg-amber-50/40 dark:border-amber-500/30 dark:bg-amber-500/5'"
            >
                <CheckCircle2 v-if="configured" class="h-5 w-5 shrink-0 text-emerald-500" />
                <XCircle v-else class="h-5 w-5 shrink-0 text-amber-500" />
                <div>
                    <p class="text-sm font-semibold text-foreground">{{ configured ? 'SMTP is configured and enabled.' : 'Email delivery is not configured.' }}</p>
                    <p class="text-xs text-muted-foreground">{{ configured ? 'Transactional emails will be sent through your SMTP server.' : 'Emails currently go to the log. Configure SMTP to actually deliver them.' }}</p>
                </div>
            </div>

            <form class="grid grid-cols-1 gap-6 lg:grid-cols-2" @submit.prevent="save">
                <div class="space-y-5 rounded-xl border border-border bg-card p-6 shadow-sm">
                    <h2 class="text-sm font-semibold text-foreground">SMTP server</h2>

                    <label class="flex items-center justify-between gap-3 rounded-lg border border-border px-4 py-3">
                        <span class="text-sm font-medium text-foreground">Enable SMTP delivery</span>
                        <input v-model="form.enabled" type="checkbox" class="h-4 w-4 rounded accent-primary" />
                    </label>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-foreground">Mailer</label>
                        <select v-model="form.driver" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                            <option value="smtp">SMTP</option>
                            <option value="log">Log (development)</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-foreground">Host</label>
                            <input v-model="form.host" type="text" placeholder="smtp.example.com" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-foreground">Port</label>
                            <input v-model.number="form.port" type="number" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-foreground">Encryption</label>
                        <select v-model="form.encryption" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                            <option value="tls">TLS</option>
                            <option value="ssl">SSL</option>
                            <option value="none">None</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-foreground">Username</label>
                            <input v-model="form.username" type="text" autocomplete="off" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-foreground">Password</label>
                            <input v-model="form.password" type="password" autocomplete="new-password" placeholder="••••••••" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                        </div>
                    </div>
                </div>

                <div class="space-y-5 rounded-xl border border-border bg-card p-6 shadow-sm">
                    <h2 class="text-sm font-semibold text-foreground">From address</h2>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-foreground">From email</label>
                        <input v-model="form.from_address" type="email" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-foreground">From name</label>
                        <input v-model="form.from_name" type="text" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                    </div>

                    <button
                        type="submit"
                        class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow transition hover:opacity-90 disabled:opacity-60"
                        :disabled="form.processing"
                    >
                        <Mail class="h-4 w-4" />
                        Save settings
                    </button>

                    <p v-if="form.recentlySuccessful" class="text-xs text-emerald-600 dark:text-emerald-400">Saved.</p>
                </div>
            </form>

            <!-- Test send -->
            <form class="rounded-xl border border-border bg-card p-6 shadow-sm" @submit.prevent="sendTest">
                <h2 class="text-sm font-semibold text-foreground">Send a test email</h2>
                <p class="mt-1 text-xs text-muted-foreground">Verify your SMTP configuration by sending a test message to an address.</p>
                <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                    <input v-model="testForm.email" type="email" required placeholder="you@example.com" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30 sm:max-w-xs" />
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow transition hover:bg-emerald-700 disabled:opacity-60"
                        :disabled="testForm.processing"
                    >
                        <Send class="h-4 w-4" />
                        Send test
                    </button>
                </div>
                <p v-if="testForm.recentlySuccessful" class="mt-2 text-xs text-emerald-600 dark:text-emerald-400">Test email sent.</p>
            </form>
        </div>
    </AppLayout>
</template>
