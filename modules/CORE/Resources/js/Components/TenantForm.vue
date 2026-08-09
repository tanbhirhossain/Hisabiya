<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import type { Tenant } from '@/types';
import { Loader2 } from 'lucide-vue-next';

const props = defineProps<{
    tenant?: Tenant;
}>();

const form = useForm({
    name: props.tenant?.name ?? '',
    slug: props.tenant?.slug ?? '',
    email: props.tenant?.email ?? '',
    phone: props.tenant?.phone ?? '',
    address: props.tenant?.address ?? '',
    currency: props.tenant?.currency ?? 'BDT',
    timezone: props.tenant?.timezone ?? 'Asia/Dhaka',
    status: props.tenant?.status ?? 'active',
    plan: props.tenant?.plan ?? 'free',
    trial_ends_at: props.tenant?.trial_ends_at ?? '',
});

const timezones = ['Asia/Dhaka', 'Asia/Kolkata', 'UTC', 'America/New_York', 'Europe/London', 'Asia/Dubai', 'Asia/Singapore', 'Asia/Tokyo'];

function submit(): void {
    if (props.tenant) {
        form.put(route('tenants.update', props.tenant.id), { preserveScroll: true });
    } else {
        form.post(route('tenants.store'), { preserveScroll: true });
    }
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="space-y-2">
                <Label for="name">Tenant name *</Label>
                <Input id="name" v-model="form.name" placeholder="e.g. Gulshan Mart" />
                <InputError :message="form.errors.name" />
            </div>
            <div class="space-y-2">
                <Label for="slug">Slug</Label>
                <Input id="slug" v-model="form.slug" placeholder="auto-generated if empty" />
                <InputError :message="form.errors.slug" />
            </div>
            <div class="space-y-2">
                <Label for="email">Email</Label>
                <Input id="email" type="email" v-model="form.email" placeholder="owner@example.com" />
                <InputError :message="form.errors.email" />
            </div>
            <div class="space-y-2">
                <Label for="phone">Phone</Label>
                <Input id="phone" v-model="form.phone" placeholder="+8801XXXXXXXXX" />
                <InputError :message="form.errors.phone" />
            </div>
            <div class="space-y-2 md:col-span-2">
                <Label for="address">Address</Label>
                <Input id="address" v-model="form.address" placeholder="Street, city, country" />
                <InputError :message="form.errors.address" />
            </div>
            <div class="space-y-2">
                <Label for="currency">Currency</Label>
                <Input id="currency" v-model="form.currency" placeholder="BDT" />
                <InputError :message="form.errors.currency" />
            </div>
            <div class="space-y-2">
                <Label for="timezone">Timezone</Label>
                <select id="timezone" v-model="form.timezone" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm outline-none transition focus-visible:ring-2 focus-visible:ring-ring/30">
                    <option v-for="tz in timezones" :key="tz" :value="tz">{{ tz }}</option>
                </select>
                <InputError :message="form.errors.timezone" />
            </div>
            <div class="space-y-2">
                <Label for="status">Status</Label>
                <select id="status" v-model="form.status" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm outline-none transition focus-visible:ring-2 focus-visible:ring-ring/30">
                    <option value="active">Active</option>
                    <option value="trial">Trial</option>
                    <option value="suspended">Suspended</option>
                </select>
                <InputError :message="form.errors.status" />
            </div>
            <div class="space-y-2">
                <Label for="plan">Plan</Label>
                <select id="plan" v-model="form.plan" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm outline-none transition focus-visible:ring-2 focus-visible:ring-ring/30">
                    <option value="free">Free</option>
                    <option value="starter">Starter</option>
                    <option value="pro">Pro</option>
                    <option value="enterprise">Enterprise</option>
                </select>
                <InputError :message="form.errors.plan" />
            </div>
        </div>

        <div class="flex items-center gap-3 border-t border-border pt-5">
            <Button type="submit" :disabled="form.processing">
                <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                {{ tenant ? 'Update Tenant' : 'Create Tenant' }}
            </Button>
            <Button type="button" variant="outline" as-child>
                <a :href="route('tenants.index')">Cancel</a>
            </Button>
        </div>
    </form>
</template>
