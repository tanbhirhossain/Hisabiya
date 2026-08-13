<script setup lang="ts">
import ModuleLayout from '../../Layouts/ModuleLayout.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Plus, Trash2, X, UserRound, ShieldCheck, Loader2 } from 'lucide-vue-next';

const props = defineProps<{
    members: Array<{
        id: number; module: string; role: string; is_active: boolean;
        user: { id: number; name: string; email: string; company_name: string | null; is_active: boolean };
    }>;
    roles: string[];
}>();

const addOpen = ref(false);
const form = useForm({ name: '', email: '', password: '', role: 'viewer' });
const confirmOpen = ref(false);
const deletingId = ref<number | null>(null);

function openAdd() {
    form.reset();
    form.defaults();
    form.role = 'viewer';
    addOpen.value = true;
}

function submit() {
    form.post(route('personal.settings.users.store'), {
        onSuccess: () => (addOpen.value = false),
    });
}

function setRole(member: any, role: string) {
    if (member.role === 'owner') return;
    router.put(route('personal.settings.users.update', member.id), { role }, { preserveScroll: true });
}

function toggleActive(member: any) {
    if (member.role === 'owner') return;
    router.put(route('personal.settings.users.update', member.id), { is_active: !member.is_active }, { preserveScroll: true });
}

function confirmDelete(id: number) {
    deletingId.value = id;
    confirmOpen.value = true;
}

function doDelete() {
    router.delete(route('personal.settings.users.destroy', deletingId.value), {
        preserveScroll: true,
        onSuccess: () => (confirmOpen.value = false),
    });
}

function initials(name: string): string {
    return name.split(' ').slice(0, 2).map((n) => n[0]).join('').toUpperCase();
}
</script>

<template>
    <ModuleLayout title="Users &amp; Access" :breadcrumbs="[{ title: 'Personal', href: '/personal/dashboard' }, { title: 'Users & Access', href: '/personal/settings/users' }]">
        <div class="space-y-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">Users &amp; access</h1>
                    <p class="text-sm text-muted-foreground">Manage who can access your Personal Accounting workspace and what they can do.</p>
                </div>
                <button type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90" @click="openAdd">
                    <Plus class="h-4 w-4" /> Add member
                </button>
            </div>

            <div v-if="members.length === 0" class="rounded-xl border border-dashed border-border p-12 text-center">
                <UserRound class="mx-auto h-10 w-10 text-muted-foreground" />
                <p class="mt-3 text-sm text-muted-foreground">No members yet. Add someone to share access.</p>
            </div>

            <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/40 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                <th class="px-5 py-3">Member</th>
                                <th class="px-5 py-3">Role</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="member in members" :key="member.id" class="border-b border-border transition last:border-0 hover:bg-muted/30">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary">
                                            {{ initials(member.user.name) }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-foreground">{{ member.user.name }}</p>
                                            <p class="text-xs text-muted-foreground">{{ member.user.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    <select
                                        :value="member.role"
                                        class="rounded-lg border border-input bg-background px-2 py-1.5 text-xs outline-none focus-visible:ring-2 focus-visible:ring-primary/30"
                                        :disabled="member.role === 'owner'"
                                        @change="setRole(member, ($event.target as HTMLSelectElement).value)"
                                    >
                                        <option v-for="role in roles" :key="role" :value="role" class="capitalize">{{ role }}</option>
                                    </select>
                                </td>
                                <td class="px-5 py-3">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset transition"
                                        :class="member.is_active
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400'
                                            : 'bg-zinc-100 text-zinc-600 ring-zinc-600/20 dark:bg-zinc-500/10 dark:text-zinc-400'"
                                        :disabled="member.role === 'owner'"
                                        @click="toggleActive(member)"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-current" />
                                        {{ member.is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <button
                                        v-if="member.role !== 'owner'"
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-rose-600 transition hover:bg-rose-500/10"
                                        @click="confirmDelete(member.id)"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" /> Remove
                                    </button>
                                    <span v-else class="inline-flex items-center gap-1 text-xs text-muted-foreground"><ShieldCheck class="h-3.5 w-3.5" /> Owner</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add member modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" leave-active-class="transition-opacity duration-150" leave-to-class="opacity-0">
                <div v-if="addOpen" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm" @click="addOpen = false" />
            </Transition>
            <Transition enter-active-class="transition scale duration-200" enter-from-class="opacity-0 scale-95" leave-active-class="transition scale duration-150" leave-to-class="opacity-0 scale-95">
                <div v-if="addOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <form class="w-full max-w-md rounded-2xl border border-border bg-card p-6 shadow-xl" @submit.prevent="submit">
                        <div class="mb-5 flex items-start justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-foreground">Add member</h2>
                                <p class="text-sm text-muted-foreground">Create their login and grant module access.</p>
                            </div>
                            <button type="button" class="rounded-md p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground" @click="addOpen = false"><X class="h-5 w-5" /></button>
                        </div>
                        <div class="space-y-4">
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Full name</label>
                                <input v-model="form.name" type="text" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                                <p v-if="form.errors.name" class="text-xs text-rose-500">{{ form.errors.name }}</p>
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
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Role</label>
                                <select v-model="form.role" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                                    <option value="viewer">Viewer (read-only)</option>
                                    <option value="manager">Manager (full use, no user mgmt)</option>
                                    <option value="owner">Owner (full control)</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-3 border-t border-border pt-4">
                                <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:opacity-50" :disabled="form.processing">
                                    <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                                    {{ form.processing ? 'Adding…' : 'Add member' }}
                                </button>
                                <button type="button" class="rounded-lg border border-border px-4 py-2.5 text-sm font-medium text-muted-foreground transition hover:bg-muted" @click="addOpen = false">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </Transition>
        </Teleport>

        <ConfirmDialog
            :open="confirmOpen"
            title="Remove this member?"
            description="They will lose access to this Personal Accounting workspace."
            @close="confirmOpen = false"
            @confirm="doDelete"
        />
    </ModuleLayout>
</template>
