<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import { ref, computed } from 'vue';
import { ArrowLeft, Save, Check, Lock, Plus, X, Sparkles } from 'lucide-vue-next';

const props = defineProps<{
    plan: {
        id: number;
        name: string;
        slug: string;
        module: string;
        description: string | null;
        price_monthly: number;
        price_yearly: number;
        features: string[];
        permissions: string[];
        feature_flags: Record<string, boolean>;
        is_active: boolean;
    } | null;
    permission_groups: Array<{ module: string; permissions: string[] }>;
    modules: Array<{ key: string; label: string }>;
}>();

const isEdit = computed(() => props.plan !== null);

const form = useForm({
    name: props.plan?.name ?? '',
    slug: props.plan?.slug ?? '',
    module: props.plan?.module ?? 'personal_accounting',
    description: props.plan?.description ?? '',
    price_monthly: props.plan?.price_monthly ?? 0,
    price_yearly: props.plan?.price_yearly ?? 0,
    features: props.plan?.features ?? [''],
    permissions: props.plan?.permissions ?? [],
    feature_flags: props.plan?.feature_flags ?? {},
    is_active: props.plan?.is_active ?? true,
});

const newFeature = ref('');
const newFlagKey = ref('');
const search = ref('');

// feature_flags is a Record<key, boolean>. Convert to a list for the editor.
const flagKeys = computed(() => Object.keys(form.feature_flags));

function toggleFlag(key: string) {
    form.feature_flags[key] = !form.feature_flags[key];
}

function addFlagKey() {
    const key = newFlagKey.value.trim().toLowerCase().replace(/[^a-z0-9_]/g, '_');
    if (!key) return;
    if (!(key in form.feature_flags)) {
        form.feature_flags[key] = true;
    }
    newFlagKey.value = '';
}

function removeFlagKey(key: string) {
    delete form.feature_flags[key];
}

// Map a module key (underscores) to its permission prefix (hyphens), e.g.
// personal_accounting -> personal-accounting, so the permission groups can be
// filtered to just the selected module.
function modulePrefix(key: string): string {
    return key.replace(/_/g, '-');
}

// Only show the permission groups belonging to the selected module (plus any
// search filter). This is the "select module -> its permissions" flow.
const filteredGroups = computed(() => {
    const prefix = modulePrefix(form.module);
    const q = search.value.toLowerCase();

    return props.permission_groups
        .filter((g) => g.module === prefix)
        .map((g) => ({
            ...g,
            permissions: q ? g.permissions.filter((p) => p.toLowerCase().includes(q)) : g.permissions,
        }))
        .filter((g) => g.permissions.length > 0);
});

// When the module changes, drop any selected permissions that belong to a
// different module so the plan only ever grants the selected module's perms.
function onModuleChange() {
    const prefix = modulePrefix(form.module);
    form.permissions = form.permissions.filter((p) => p.startsWith(prefix + '.'));
    search.value = '';
}

function togglePermission(name: string) {
    const idx = form.permissions.indexOf(name);
    if (idx >= 0) form.permissions.splice(idx, 1);
    else form.permissions.push(name);
}

function addFeature() {
    if (newFeature.value.trim()) {
        form.features.push(newFeature.value.trim());
        newFeature.value = '';
    }
}

function removeFeature(idx: number) {
    form.features.splice(idx, 1);
    if (form.features.length === 0) form.features.push('');
}

function submit() {
    if (isEdit.value && props.plan) {
        form.put(route('subscriptions.plans.update', props.plan.id));
    } else {
        form.post(route('subscriptions.plans.store'));
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Subscriptions', href: '/admin/subscriptions' }, { title: isEdit ? 'Edit Plan' : 'New Plan', href: '#' }]">
        <Head :title="isEdit ? 'Edit Plan' : 'New Plan'" />

        <div class="flex flex-col gap-6 p-4 md:p-6">
            <div class="flex items-center justify-between">
                <PageHeader :title="isEdit ? 'Edit subscription plan' : 'Create subscription plan'" :description="'Define the package and which permissions it grants.'" />
                <Link :href="route('subscriptions.index')" class="inline-flex items-center gap-1.5 rounded-lg border border-border px-3.5 py-2 text-sm font-semibold text-muted-foreground transition hover:bg-muted hover:text-foreground">
                    <ArrowLeft class="h-4 w-4" /> Back
                </Link>
            </div>

            <form class="grid grid-cols-1 gap-6 lg:grid-cols-5" @submit.prevent="submit">
                <!-- Package details -->
                <div class="space-y-5 lg:col-span-2">
                    <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
                        <h2 class="text-sm font-semibold text-foreground">Package details</h2>

                        <div class="mt-5 space-y-4">
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Name</label>
                                <input v-model="form.name" type="text" placeholder="Personal Accounting Pro" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                                <p v-if="form.errors.name" class="text-xs text-rose-500">{{ form.errors.name }}</p>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium text-foreground">Slug</label>
                                    <input v-model="form.slug" type="text" placeholder="personal-accounting-pro" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                                    <p v-if="form.errors.slug" class="text-xs text-rose-500">{{ form.errors.slug }}</p>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium text-foreground">Module</label>
                                    <select v-model="form.module" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" @change="onModuleChange">
                                        <option v-for="m in modules" :key="m.key" :value="m.key">{{ m.label }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Description</label>
                                <textarea v-model="form.description" rows="2" placeholder="What this package offers" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium text-foreground">Monthly price (৳)</label>
                                    <input v-model.number="form.price_monthly" type="number" min="0" step="0.01" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                                    <p v-if="form.errors.price_monthly" class="text-xs text-rose-500">{{ form.errors.price_monthly }}</p>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium text-foreground">Yearly price (৳)</label>
                                    <input v-model.number="form.price_yearly" type="number" min="0" step="0.01" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                                </div>
                            </div>

                            <label class="flex items-center justify-between gap-3 rounded-lg border border-border px-4 py-3">
                                <span class="text-sm font-medium text-foreground">Active (available for purchase)</span>
                                <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded accent-primary" />
                            </label>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
                        <h2 class="text-sm font-semibold text-foreground">Feature bullets</h2>
                        <p class="mt-1 text-xs text-muted-foreground">Shown on the pricing page.</p>
                        <div class="mt-4 space-y-2">
                            <div v-for="(f, i) in form.features" :key="i" class="flex items-center gap-2">
                                <input v-model="form.features[i]" type="text" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                                <button type="button" class="text-muted-foreground transition hover:text-rose-500" @click="removeFeature(i)">
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                            <div class="flex items-center gap-2">
                                <input v-model="newFeature" type="text" placeholder="Add a feature…" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" @keydown.enter.prevent="addFeature" />
                                <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-border px-3 py-2 text-sm font-semibold text-muted-foreground transition hover:bg-muted" @click="addFeature">
                                    <Plus class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Permission picker -->
                <div class="lg:col-span-3">
                    <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="flex items-center gap-2 text-sm font-semibold text-foreground">
                                    <Lock class="h-4 w-4" />
                                    Permissions for {{ form.module.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()) }}
                                </h2>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    Tick which {{ form.module.replace(/_/g, '-') }}.* permissions this package unlocks. Selected: {{ form.permissions.length }}
                                </p>
                            </div>
                            <input v-model="search" type="text" placeholder="Search permissions…" class="rounded-lg border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                        </div>

                        <div class="mt-5 space-y-5 max-h-[60vh] overflow-y-auto pr-1">
                            <div v-for="group in filteredGroups" :key="group.module">
                                <p class="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase">{{ group.module }}</p>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <button
                                        v-for="perm in group.permissions"
                                        :key="perm"
                                        type="button"
                                        class="flex items-center justify-between gap-2 rounded-lg border px-3 py-2 text-left text-xs transition"
                                        :class="form.permissions.includes(perm) ? 'border-primary bg-primary/5 text-foreground' : 'border-border text-muted-foreground hover:bg-muted'"
                                        @click="togglePermission(perm)"
                                    >
                                        <span class="break-all font-mono">{{ perm }}</span>
                                        <Check v-if="form.permissions.includes(perm)" class="h-3.5 w-3.5 shrink-0 text-primary" />
                                    </button>
                                </div>
                            </div>
                            <p v-if="filteredGroups.length === 0" class="py-10 text-center text-sm text-muted-foreground">No permissions match your search.</p>
                        </div>
                    </div>

                    <!-- Feature flags (functional gates) -->
                    <div class="mt-6 rounded-xl border border-border bg-card p-6 shadow-sm">
                        <h2 class="flex items-center gap-2 text-sm font-semibold text-foreground">
                            <Sparkles class="h-4 w-4" />
                            Feature flags
                        </h2>
                        <p class="mt-1 text-xs text-muted-foreground">Functional capabilities unlocked by this package. Toggle each flag on/off.</p>

                        <div class="mt-4 space-y-2">
                            <div v-for="key in flagKeys" :key="key" class="flex items-center justify-between gap-3 rounded-lg border border-border px-4 py-3">
                                <span class="font-mono text-xs text-foreground">{{ key }}</span>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition"
                                        :class="form.feature_flags[key] ? 'bg-primary' : 'bg-muted'"
                                        @click="toggleFlag(key)"
                                    >
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition" :class="form.feature_flags[key] ? 'translate-x-4' : 'translate-x-0.5'" />
                                    </button>
                                    <button type="button" class="text-muted-foreground transition hover:text-rose-500" @click="removeFlagKey(key)">
                                        <X class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                            <p v-if="flagKeys.length === 0" class="py-6 text-center text-sm text-muted-foreground">No feature flags set.</p>
                            <div class="flex items-center gap-2 pt-1">
                                <input v-model="newFlagKey" type="text" placeholder="add flag key e.g. import_csv" class="w-full rounded-lg border border-input bg-background px-3 py-2 font-mono text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" @keydown.enter.prevent="addFlagKey" />
                                <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-border px-3 py-2 text-sm font-semibold text-muted-foreground transition hover:bg-muted" @click="addFlagKey">
                                    <Plus class="h-4 w-4" /> Add
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground shadow transition hover:bg-primary/90 disabled:opacity-60" :disabled="form.processing">
                        <Save class="h-4 w-4" />
                        {{ form.processing ? 'Saving…' : isEdit ? 'Update plan' : 'Create plan' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
