<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { ShieldCheck, LoaderCircle } from 'lucide-vue-next';

const form = useForm({ code: '' });

const submit = () => {
    form.post(route('two-factor.challenge.confirm'), {
        onFinish: () => form.reset('code'),
    });
};
</script>

<template>
    <AuthBase title="Two-factor verification" description="Enter the code from your authenticator app to continue.">
        <Head title="Two-factor verification" />

        <div class="mb-4 flex justify-center">
            <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-500">
                <ShieldCheck class="h-7 w-7" />
            </div>
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <div class="space-y-1.5">
                <Label for="code">Verification code</Label>
                <Input
                    id="code"
                    v-model="form.code"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    autofocus
                    placeholder="Enter your 6-digit code or a recovery code"
                    class="text-center text-lg tracking-widest"
                />
                <InputError :message="form.errors.code" />
            </div>

            <Button type="submit" class="w-full" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                Verify & sign in
            </Button>
        </form>
    </AuthBase>
</template>
