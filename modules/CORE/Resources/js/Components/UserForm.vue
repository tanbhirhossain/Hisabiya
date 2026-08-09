<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import type { User } from '@/types';
import { Loader2, ShieldCheck } from 'lucide-vue-next';

const props = defineProps<{
    user?: User;
    options: { tenants: Array<{ id: number; name: string }>; roles: Array<{ id: number; name: string }> };
}>();

const form = useForm({
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    phone: props.user?.phone ?? '',
    tenant_id: props.user?.tenant?.id ?? '',
    is_active: props.user?.is_active ?? true,
    roles: (props.user?.roles ?? []).map((r) => r.id),
    password: '',
    password_confirmation: '',
});

function toggleRole(id: number): void {
    const index = form.roles.indexOf(id);
    if (index === -1) {
        form.roles.push(id);
    } else {
        form.roles.splice(index, 1);
    }
}

function submit(): void {
    if (props.user) {
        form.put(route('users.update', props.user.id), { preserveScroll: true });
    } else {
        form.post(route('users.store'), { preserveScroll: true });
    }
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="space-y-2">
                <Label for="name">Full name *</Label>
                <Input id="name" v-model="form.name" placeholder="e.g. Rahim Ahmed" />
                <InputError :message="form.errors.name" />
            </div>
            <div class="space-y-2">
                <Label for="email">Email *</Label>
                <Input id="email" type="email" v-model="form.email" placeholder="user@example.com" />
                <InputError :message="form.errors.email" />
            </div>
            <div class="space-y-2">
                <Label for="phone">Phone</Label>
                <Input id="phone" v-model="form.phone" placeholder="+8801XXXXXXXXX" />
                <InputError :message="form.errors.phone" />
            </div>
            <div class="space-y-2">
                <Label for="tenant_id">Tenant</Label>
                <select id="tenant_id" v-model="form.tenant_id" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm outline-none transition focus-visible:ring-2 focus-visible:ring-ring/30">
                    <option value="">— No tenant —</option>
                    <option v-for="tenant in options.tenants" :key="tenant.id" :value="tenant.id">{{ tenant.name }}</option>
                </select>
                <InputError :message="form.errors.tenant_id" />
            </div>
            <div class="space-y-2">
                <Label for="password">{{ user ? 'New password' : 'Password *' }}</Label>
                <Input id="password" type="password" v-model="form.password" placeholder="Min. 8 characters" autocomplete="new-password" />
                <InputError :message="form.errors.password" />
            </div>
            <div class="space-y-2">
                <Label for="password_confirmation">Confirm password</Label>
                <Input id="password_confirmation" type="password" v-model="form.password_confirmation" placeholder="Repeat password" autocomplete="new-password" />
                <InputError :message="form.errors.password_confirmation" />
            </div>
        </div>

        <div class="space-y-3">
            <div class="flex items-center gap-2">
                <input id="is_active" v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-input accent-primary" />
                <Label for="is_active" class="cursor-pointer">Active account</Label>
            </div>
            <InputError :message="form.errors.is_active" />
        </div>

        <div class="space-y-3">
            <Label class="flex items-center gap-2"><ShieldCheck class="h-4 w-4" /> Roles</Label>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                <label v-for="role in options.roles" :key="role.id" class="flex cursor-pointer items-center gap-2 rounded-lg border border-border px-3 py-2.5 text-sm transition hover:bg-muted" :class="form.roles.includes(role.id) ? 'border-primary/40 bg-primary/5' : ''">
                    <input :checked="form.roles.includes(role.id)" type="checkbox" class="h-4 w-4 rounded accent-primary" @change="toggleRole(role.id)" />
                    <span class="font-medium">{{ role.name }}</span>
                </label>
            </div>
            <InputError :message="form.errors.roles" />
        </div>

        <div class="flex items-center gap-3 border-t border-border pt-5">
            <Button type="submit" :disabled="form.processing">
                <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                {{ user ? 'Update User' : 'Create User' }}
            </Button>
            <Button type="button" variant="outline" as-child>
                <a :href="route('users.index')">Cancel</a>
            </Button>
        </div>
    </form>
</template>
