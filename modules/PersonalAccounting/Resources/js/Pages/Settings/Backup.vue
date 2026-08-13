<script setup lang="ts">
import ModuleLayout from '../../Layouts/ModuleLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Database, Download, Loader2, ShieldCheck, RotateCcw, X, Upload } from 'lucide-vue-next';

const props = defineProps<{
    backups: Array<{ id: number; type: string; file_name: string; file_size: number; created_at: string }>;
}>();

const backing = ref(false);
const uploadFile = ref<File | null>(null);
const uploadRestoring = ref(false);

function onUploadFile(e: Event) {
    const f = (e.target as HTMLInputElement).files?.[0];
    if (f) uploadFile.value = f;
}

function submitUploadRestore() {
    if (!uploadFile.value) return;
    uploadRestoring.value = true;
    const fd = new FormData();
    fd.append('file', uploadFile.value);
    // Use axios-style via Inertia router with forceFormData.
    router.post(route('personal.settings.backup.restore-upload'), { file: uploadFile.value }, {
        forceFormData: true,
        onSuccess: () => {
            uploadFile.value = null;
            uploadRestoring.value = false;
        },
        onFinish: () => (uploadRestoring.value = false),
    });
}

const restoreOpen = ref(false);
const restoreFile = ref<string | null>(null);
const restoring = ref(false);

function createBackup() {
    backing.value = true;
    router.post(route('personal.settings.backup.create'), {}, {
        onFinish: () => (backing.value = false),
    });
}

function openRestore(fileName: string) {
    restoreFile.value = fileName;
    restoreOpen.value = true;
}

function confirmRestore() {
    if (!restoreFile.value) return;
    restoring.value = true;
    router.post(route('personal.settings.backup.restore'), {
        file_name: restoreFile.value,
    }, {
        preserveScroll: true,
        onSuccess: () => (restoreOpen.value = false),
        onFinish: () => (restoring.value = false),
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
    <ModuleLayout title="Backup" :breadcrumbs="[{ title: 'Personal', href: '/personal/dashboard' }, { title: 'Backup', href: '/personal/settings/backup' }]">
        <div class="space-y-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">Data backup</h1>
                    <p class="text-sm text-muted-foreground">Back up your Personal Accounting data anytime.</p>
                </div>
                <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow transition hover:bg-primary/90 disabled:opacity-60" :disabled="backing" @click="createBackup">
                    <Loader2 v-if="backing" class="h-4 w-4 animate-spin" />
                    <Database v-else class="h-4 w-4" />
                    {{ backing ? 'Backing up…' : 'Create backup' }}
                </button>
            </div>

            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-400">
                <p class="flex items-center gap-2 font-medium"><ShieldCheck class="h-4 w-4" /> PRO feature</p>
                <p class="mt-1 text-xs">You can back up your own workspace data. The download includes your accounts, transactions, budgets, goals, loans, contacts, and reports. Only your own tenant's data is ever shown or restored.</p>
            </div>

            <!-- Upload & Restore (own tenant only) -->
            <div class="rounded-xl border border-amber-300/60 bg-amber-50/40 p-5 shadow-sm dark:border-amber-500/30 dark:bg-amber-500/5">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
                        <Upload class="h-5 w-5" />
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-foreground">Restore from a backup file</h3>
                        <p class="text-xs text-muted-foreground">Upload a <code class="text-[10px]">.json</code> backup of your own workspace. It will restore only into your tenant.</p>
                    </div>
                </div>
                <div class="mt-4 flex flex-col gap-3 md:flex-row md:items-end">
                    <div class="flex-1 space-y-1.5">
                        <label class="text-sm font-medium text-foreground">Backup file</label>
                        <input type="file" accept=".json,application/json" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" @change="onUploadFile" />
                    </div>
                    <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-700 disabled:opacity-50"
                        :disabled="!uploadFile || uploadRestoring" @click="submitUploadRestore">
                        <Loader2 v-if="uploadRestoring" class="h-4 w-4 animate-spin" />
                        <RotateCcw v-else class="h-4 w-4" />
                        {{ uploadRestoring ? 'Restoring…' : 'Restore my data' }}
                    </button>
                </div>
            </div>

            <div class="rounded-xl border border-border bg-card shadow-sm">
                <div class="border-b border-border px-5 py-4">
                    <h2 class="text-sm font-semibold text-foreground">Your backups</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/40 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                <th class="px-5 py-3">File</th>
                                <th class="px-5 py-3">Size</th>
                                <th class="px-5 py-3">Created</th>
                                <th class="px-5 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="b in backups" :key="b.id" class="border-b border-border transition last:border-0 hover:bg-muted/30">
                                <td class="px-5 py-3 font-mono text-xs text-foreground">{{ b.file_name }}</td>
                                <td class="px-5 py-3 text-muted-foreground">{{ fmtSize(b.file_size) }}</td>
                                <td class="px-5 py-3 text-muted-foreground">{{ fmtDate(b.created_at) }}</td>
                                <td class="px-5 py-3 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <button type="button" class="inline-flex items-center gap-1 text-xs font-medium text-amber-600 transition hover:underline dark:text-amber-400" @click="openRestore(b.file_name)">
                                            <RotateCcw class="h-3.5 w-3.5" /> Restore
                                        </button>
                                        <a :href="route('personal.settings.backup.download', b.file_name)" class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline">
                                            <Download class="h-3.5 w-3.5" /> Download
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="backups.length === 0">
                                <td colspan="4" class="px-5 py-10 text-center text-sm text-muted-foreground">No backups yet. Create your first backup above.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Restore confirm modal -->
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
                                <h2 class="text-base font-semibold text-foreground">Restore this backup?</h2>
                                <p class="mt-1 text-sm text-muted-foreground">{{ restoreFile }}</p>
                            </div>
                        </div>
                        <div class="mt-4 rounded-lg border border-amber-300/60 bg-amber-50 p-3 text-xs text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-400">
                            <p>This will replace your current workspace data with the backup.</p>
                        </div>
                        <div class="mt-6 flex justify-end gap-2">
                            <button type="button" class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted-foreground transition hover:bg-muted" @click="restoreOpen = false">Cancel</button>
                            <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-700 disabled:opacity-50" :disabled="restoring" @click="confirmRestore">
                                <Loader2 v-if="restoring" class="h-4 w-4 animate-spin" />
                                <X v-else class="h-4 w-4" />
                                {{ restoring ? 'Restoring…' : 'Restore' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </ModuleLayout>
</template>
