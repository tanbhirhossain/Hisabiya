<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { ShieldCheck, ShieldOff, Smartphone, Copy, Check, KeyRound } from 'lucide-vue-next';
import QRCode from 'qrcode';

const props = defineProps<{
    enabled: boolean;
    setup: boolean;
    qr_uri: string | null;
    secret: string | null;
    recovery_codes: string[];
}>();

const page = usePage<{ flash: { recovery_codes?: string[]; two_factor_enabled?: boolean } }>();
const flashRecoveryCodes = ref(page.props.flash?.recovery_codes ?? []);
const justEnabled = ref(page.props.flash?.two_factor_enabled ?? false);

const setupForm = useForm({});
const confirmForm = useForm({ code: '' });
const disableForm = useForm({});

const qrDataUrl = ref<string | null>(null);
const copied = ref(false);

function beginSetup() {
    setupForm.post(route('two-factor.setup'));
}

function confirm() {
    confirmForm.post(route('two-factor.confirm'), {
        onSuccess: () => confirmForm.reset('code'),
    });
}

function disable() {
    disableForm.post(route('two-factor.disable'));
}

async function renderQr() {
    if (props.qr_uri) {
        qrDataUrl.value = await QRCode.toDataURL(props.qr_uri, { width: 220, margin: 1, color: { dark: '#1e1b4b' } });
    }
}

async function copySecret() {
    if (props.secret) {
        await navigator.clipboard.writeText(props.secret);
        copied.value = true;
        setTimeout(() => (copied.value = false), 1500);
    }
}

onMounted(renderQr);
watch(() => props.qr_uri, renderQr);
</script>

<template>
    <AuthBase :title="'Two-factor authentication'" :description="'Protect your account with an extra verification step.'">
        <Head title="Two-factor authentication" />

        <!-- Enabled state -->
        <div v-if="enabled" class="space-y-4 text-center">
            <div class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-500">
                <ShieldCheck class="h-7 w-7" />
            </div>
            <div>
                <h2 class="text-lg font-semibold text-foreground">Two-factor authentication is on</h2>
                <p class="mt-1 text-sm text-muted-foreground">Your account requires a one-time code from your authenticator app to sign in.</p>
            </div>

            <Button variant="destructive" class="w-full" :disabled="disableForm.processing" @click="disable">
                <ShieldOff class="h-4 w-4" /> Disable two-factor authentication
            </Button>
        </div>

        <!-- Not enabled -->
        <div v-else-if="!setup" class="space-y-4 text-center">
            <div class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-500">
                <Smartphone class="h-7 w-7" />
            </div>
            <div>
                <h2 class="text-lg font-semibold text-foreground">Add an extra layer of security</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Use an authenticator app like Google Authenticator, Authy or 1Password to generate one-time login codes.
                </p>
            </div>

            <Button class="w-full" :disabled="setupForm.processing" @click="beginSetup">
                <ShieldCheck class="h-4 w-4" /> Set up two-factor authentication
            </Button>
        </div>

        <!-- Setup in progress -->
        <div v-else class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-500">
                    <Smartphone class="h-5 w-5" />
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-foreground">Scan the QR code</h2>
                    <p class="text-xs text-muted-foreground">Open your authenticator app and scan this code.</p>
                </div>
            </div>

            <div class="flex justify-center rounded-xl border border-border bg-white p-4">
                <img v-if="qrDataUrl" :src="qrDataUrl" alt="QR code" class="h-40 w-40" />
            </div>

            <div class="flex items-center gap-2 rounded-lg border border-border bg-muted/40 px-3 py-2">
                <code class="flex-1 break-all text-xs text-muted-foreground">{{ secret }}</code>
                <button type="button" class="text-muted-foreground transition hover:text-foreground" @click="copySecret">
                    <Check v-if="copied" class="h-4 w-4 text-emerald-500" />
                    <Copy v-else class="h-4 w-4" />
                </button>
            </div>
            <p class="text-center text-xs text-muted-foreground">Can't scan? Enter this secret key manually.</p>

            <form class="space-y-3" @submit.prevent="confirm">
                <div class="space-y-1.5">
                    <Label for="code">Verification code</Label>
                    <Input id="code" v-model="confirmForm.code" inputmode="numeric" autocomplete="one-time-code" placeholder="000000" />
                    <InputError :message="confirmForm.errors.code" />
                </div>
                <Button type="submit" class="w-full" :disabled="confirmForm.processing">
                    <KeyRound class="h-4 w-4" /> Confirm & enable
                </Button>
            </form>
        </div>

        <!-- Recovery codes (shown once after enabling) -->
        <div v-if="justEnabled && flashRecoveryCodes.length" class="rounded-xl border border-amber-300/60 bg-amber-50/40 p-4 dark:border-amber-500/30 dark:bg-amber-500/5">
            <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">Save these recovery codes</p>
            <p class="mt-1 text-xs text-amber-600/80 dark:text-amber-400/80">Each can be used once if you lose your authenticator. Store them somewhere safe — they won't be shown again.</p>
            <div class="mt-3 grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                <code v-for="code in flashRecoveryCodes" :key="code" class="rounded bg-background px-2 py-1 text-center font-mono text-xs">{{ code }}</code>
            </div>
        </div>
    </AuthBase>
</template>
