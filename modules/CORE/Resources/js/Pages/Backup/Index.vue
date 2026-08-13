<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import { ref } from 'vue';
import { Database, Download, Loader2, Building2, RotateCcw, X, CheckCircle2, Upload } from 'lucide-vue-next';

const props = defineProps<{
    backups: Array<{ id: number; type: string; file_name: string; file_size: number; created_at: string; tenant: { id: number; name: string } | null }>;
    tenants: Array<{ id: number; name: string }>;
}>();

const backingAll = ref(false);
const form = useForm({ tenant_id: '' });

// Upload restore state
const uploadForm = useForm({ file: null as File | null, scope: 'tenant', tenant_id: '' });

function onUploadFile(e: Event) {
    const f = (e.target as HTMLInputElement).files?.[0];
    if (f) {
        uploadForm.file = f;
    }
}

function submitUploadRestore() {
    if (!uploadForm.file) return;
    uploadForm.post(route('backup.restore-upload'), {
        forceFormData: true,
        onSuccess: () => uploadForm.reset(),
    });
}

// Restore state
const restoreOpen = ref(false);
const restoreFile = ref<{ file_name: string; type: string; tenant: { id: number; name: string } | null } | null>(null);
const restoreTargetTenant = ref('');
const restoring = ref(false);

function openRestore(b: any) {
    restoreFile.value = b;
    restoreTargetTenant.value = b.type === 'tenant' ? (b.tenant?.id ? String(b.tenant.id) : '') : '';
    restoreOpen.value = true;
}

function confirmRestore() {
    if (!restoreFile.value) return;
    restoring.value = true;
    router.post(route('backup.restore'), {
        file_name: restoreFile.value.file_name,
        scope: restoreFile.value.type === 'all' ? 'all' : 'tenant',
        tenant_id: restoreTargetTenant.value || undefined,
    }, {
        preserveScroll: true,
        onSuccess: () => (restoreOpen.value = false),
        onFinish: () => (restoring.value = false),
    });
}

function backupAll() {
    backingAll.value = true;
    router.post(route('backup.all'), {}, {
        onFinish: () => (backingAll.value = false),
    });
}

function backupTenant() {
    if (!form.tenant_id) return;
    router.post(route('backup.tenant'), { tenant_id: form.tenant_id }, {
        onSuccess: () => (form.tenant_id = ''),
    });
}

function fmtSize(bytes: number): string {
    if (bytes > 1024 * 1024) return (bytes / 1024 / 1024).toFixed(2) + ' MB';
    if (bytes > 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return bytes + ' B';
}

function fmtDate(d: string): string {
    return new Date(d).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Backups', href: '/admin/backups' }]">
        <Head title="Backups" />

        <div class="flex flex-col gap-6 p-4 md:p-6">
            <PageHeader title="Data backups" description="Back up all tenants or a single tenant's data." />

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <!-- Full backup -->
                <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <Database class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-foreground">Full backup (all tenants)</h3>
                            <p class="text-xs text-muted-foreground">Exports every tenant and its data as JSON.</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow transition hover:bg-primary/90 disabled:opacity-60"
                        :disabled="backingAll"
                        @click="backupAll"
                    >
                        <Loader2 v-if="backingAll" class="h-4 w-4 animate-spin" />
                        <Database v-else class="h-4 w-4" />
                        {{ backingAll ? 'Backing up…' : 'Back up all tenants' }}
                    </button>
                </div>

                <!-- Single tenant backup -->
                <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                            <Building2 class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-foreground">Single tenant backup</h3>
                            <p class="text-xs text-muted-foreground">Export one tenant's data.</p>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <select v-model="form.tenant_id" class="flex-1 rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                            <option value="" disabled>Select tenant</option>
                            <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                        <button type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-50" :disabled="!form.tenant_id || form.processing" @click="backupTenant">
                            Backup
                        </button>
                    </div>
                </div>
            </div>

            <!-- Upload & Restore -->
            <div class="rounded-xl border border-amber-300/60 bg-amber-50/40 p-5 shadow-sm dark:border-amber-500/30 dark:bg-amber-500/5">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
                        <Upload class="h-5 w-5" />
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-foreground">Restore from a backup file</h3>
                        <p class="text-xs text-muted-foreground">Upload a <code class="text-[10px]">.json</code> backup file to restore its data.</p>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-foreground">Backup file</label>
                        <input type="file" accept=".json,application/json" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" @change="onUploadFile" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-foreground">Scope</label>
                        <select v-model="uploadForm.scope" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                            <option value="tenant">Single tenant</option>
                            <option value="all">All tenants (full backup)</option>
                        </select>
                    </div>
                    <div v-if="uploadForm.scope === 'tenant'" class="space-y-1.5">
                        <label class="text-sm font-medium text-foreground">Restore into tenant</label>
                        <select v-model="uploadForm.tenant_id" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                            <option value="" disabled>Select tenant</option>
                            <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-end gap-2">
                    <span v-if="uploadForm.errors.file" class="text-xs text-rose-500">{{ uploadForm.errors.file }}</span>
                    <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-700 disabled:opacity-50"
                        :disabled="!uploadForm.file || uploadForm.processing || (uploadForm.scope === 'tenant' && !uploadForm.tenant_id)" @click="submitUploadRestore">
                        <Loader2 v-if="uploadForm.processing" class="h-4 w-4 animate-spin" />
                        <RotateCcw v-else class="h-4 w-4" />
                        {{ uploadForm.processing ? 'Restoring…' : 'Restore from file' }}
                    </button>
                </div>
            </div>

            <!-- History -->
            <div class="rounded-xl border border-border bg-card shadow-sm">
                <div class="border-b border-border px-5 py-4">
                    <h2 class="text-sm font-semibold text-foreground">Backup history</h2>
                    <p class="text-xs text-muted-foreground">Recent backups and downloads.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/40 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                <th class="px-5 py-3">Type</th>
                                <th class="px-5 py-3">Tenant</th>
                                <th class="px-5 py-3">File</th>
                                <th class="px-5 py-3">Size</th>
                                <th class="px-5 py-3">Created</th>
                                <th class="px-5 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="b in backups" :key="b.id" class="border-b border-border transition last:border-0 hover:bg-muted/30">
                                <td class="px-5 py-3">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium capitalize" :class="b.type === 'all' ? 'bg-primary/10 text-primary' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400'">
                                        {{ b.type === 'all' ? 'All' : 'Tenant' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-muted-foreground">{{ b.tenant?.name ?? '—' }}</td>
                                <td class="px-5 py-3 font-mono text-xs text-foreground">{{ b.file_name }}</td>
                                <td class="px-5 py-3 text-muted-foreground">{{ fmtSize(b.file_size) }}</td>
                                <td class="px-5 py-3 text-muted-foreground">{{ fmtDate(b.created_at) }}</td>
                                <td class="px-5 py-3 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <button type="button" class="inline-flex items-center gap-1 text-xs font-medium text-amber-600 transition hover:underline dark:text-amber-400" @click="openRestore(b)">
                                            <RotateCcw class="h-3.5 w-3.5" /> Restore
                                        </button>
                                        <a :href="route('backup.download', b.file_name)" class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline">
                                            <Download class="h-3.5 w-3.5" /> Download
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="backups.length === 0">
                                <td colspan="6" class="px-5 py-10 text-center text-sm text-muted-foreground">No backups yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Restore modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" leave-active-class="transition-opacity duration-150" leave-to-class="opacity-0">
                <div v-if="restoreOpen" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm" @click="restoreOpen = false" />
            </Transition>
            <Transition enter-active-class="transition scale duration-200" enter-from-class="opacity-0 scale-95" leave-active-class="transition scale duration-150" leave-to-class="opacity-0 scale-95">
                <div v-if="restoreOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="w-full max-w-md rounded-2xl border border-border bg-card p-6 shadow-xl">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-500/10">
                                <RotateCcw class="h-5 w-5" />
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-foreground">Restore backup?</h2>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ restoreFile?.file_name }} — this will replace the existing data.
                                </p>
                            </div>
                        </div>

                        <div v-if="restoreFile?.type === 'tenant'" class="mt-4 space-y-1.5">
                            <label class="text-sm font-medium text-foreground">Restore into tenant</label>
                            <select v-model="restoreTargetTenant" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                                <option value="" disabled>Select tenant</option>
                                <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                        </div>

                        <div class="mt-4 rounded-lg border border-amber-300/60 bg-amber-50 p-3 text-xs text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-400">
                            <p class="flex items-center gap-1.5"><CheckCircle2 class="h-3.5 w-3.5" /> This is destructive. Existing data for the target will be overwritten.</p>
                        </div>

                        <div class="mt-6 flex justify-end gap-2">
                            <button type="button" class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted-foreground transition hover:bg-muted" @click="restoreOpen = false">Cancel</button>
                            <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-700 disabled:opacity-50" :disabled="restoring || (restoreFile?.type === 'tenant' && !restoreTargetTenant)" @click="confirmRestore">
                                <Loader2 v-if="restoring" class="h-4 w-4 animate-spin" />
                                <X v-else class="h-4 w-4" />
                                {{ restoring ? 'Restoring…' : 'Restore' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
