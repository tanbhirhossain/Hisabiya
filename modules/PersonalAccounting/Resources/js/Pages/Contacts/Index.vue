<script setup lang="ts">
import ModuleLayout from '../../Layouts/ModuleLayout.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Plus, Trash2, X, User, Building2, Phone, Mail } from 'lucide-vue-next';

const props = defineProps<{
    contacts: Array<{ id: number; name: string; type: string; phone: string | null; email: string | null; address: string | null; loans_count: number }>;
}>();

const addOpen = ref(false);
const form = useForm({ name: '', type: 'person', phone: '', email: '', address: '', notes: '' });
const confirmOpen = ref(false);
const deletingId = ref<number | null>(null);

function openAdd() {
    form.reset();
    form.defaults();
    addOpen.value = true;
}

function submit() {
    form.post(route('personal.contacts.store'), { onSuccess: () => (addOpen.value = false) });
}

function confirmDelete(id: number) {
    deletingId.value = id;
    confirmOpen.value = true;
}

function doDelete() {
    router.delete(route('personal.contacts.destroy', deletingId.value), {
        preserveScroll: true,
        onSuccess: () => (confirmOpen.value = false),
    });
}
</script>

<template>
    <ModuleLayout title="Contacts" :breadcrumbs="[{ title: 'Personal', href: '/personal/dashboard' }, { title: 'Contacts', href: '/personal/contacts' }]">
        <div class="space-y-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">Contacts</h1>
                    <p class="text-sm text-muted-foreground">People and businesses you lend to or borrow from.</p>
                </div>
                <button type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90" @click="openAdd">
                    <Plus class="h-4 w-4" /> New contact
                </button>
            </div>

            <div v-if="contacts.length === 0" class="rounded-xl border border-dashed border-border p-12 text-center">
                <User class="mx-auto h-10 w-10 text-muted-foreground" />
                <p class="mt-3 text-sm text-muted-foreground">No contacts yet. Add people or businesses to manage loans with them.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div v-for="contact in contacts" :key="contact.id" class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <component :is="contact.type === 'person' ? User : Building2" class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-foreground">{{ contact.name }}</h3>
                                <span class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-medium capitalize text-muted-foreground">{{ contact.type }}</span>
                            </div>
                        </div>
                        <button type="button" class="rounded-md p-1.5 text-muted-foreground transition hover:bg-muted hover:text-destructive" @click="confirmDelete(contact.id)"><Trash2 class="h-4 w-4" /></button>
                    </div>
                    <div class="mt-4 space-y-1 text-xs text-muted-foreground">
                        <p v-if="contact.phone" class="flex items-center gap-1.5"><Phone class="h-3.5 w-3.5" /> {{ contact.phone }}</p>
                        <p v-if="contact.email" class="flex items-center gap-1.5"><Mail class="h-3.5 w-3.5" /> {{ contact.email }}</p>
                        <p v-if="contact.address" class="truncate">{{ contact.address }}</p>
                    </div>
                    <p class="mt-3 text-xs font-medium text-muted-foreground">{{ contact.loans_count }} loan(s)</p>
                </div>
            </div>
        </div>

        <!-- Add contact modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" leave-active-class="transition-opacity duration-150" leave-to-class="opacity-0">
                <div v-if="addOpen" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm" @click="addOpen = false" />
            </Transition>
            <Transition enter-active-class="transition scale duration-200" enter-from-class="opacity-0 scale-95" leave-active-class="transition scale duration-150" leave-to-class="opacity-0 scale-95">
                <div v-if="addOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <form class="w-full max-w-md rounded-2xl border border-border bg-card p-6 shadow-xl" @submit.prevent="submit">
                        <div class="mb-5 flex items-start justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-foreground">New contact</h2>
                                <p class="text-sm text-muted-foreground">Add a person or business.</p>
                            </div>
                            <button type="button" class="rounded-md p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground" @click="addOpen = false"><X class="h-5 w-5" /></button>
                        </div>
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" class="flex items-center justify-center gap-2 rounded-xl border-2 px-3 py-2.5 text-sm font-semibold transition" :class="form.type === 'person' ? 'border-primary bg-primary/5 text-primary' : 'border-border text-muted-foreground hover:bg-muted'" @click="form.type = 'person'"><User class="h-4 w-4" /> Person</button>
                                <button type="button" class="flex items-center justify-center gap-2 rounded-xl border-2 px-3 py-2.5 text-sm font-semibold transition" :class="form.type === 'business' ? 'border-primary bg-primary/5 text-primary' : 'border-border text-muted-foreground hover:bg-muted'" @click="form.type = 'business'"><Building2 class="h-4 w-4" /> Business</button>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Name</label>
                                <input v-model="form.name" type="text" placeholder="Full name or business name" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                                <p v-if="form.errors.name" class="text-sm text-rose-500">{{ form.errors.name }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium text-foreground">Phone</label>
                                    <input v-model="form.phone" type="text" placeholder="+8801XXXXXXXXX" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium text-foreground">Email</label>
                                    <input v-model="form.email" type="email" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">Address</label>
                                <input v-model="form.address" type="text" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30" />
                            </div>
                            <div class="flex items-center gap-3 border-t border-border pt-4">
                                <button type="submit" class="flex-1 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:opacity-50" :disabled="form.processing">
                                    {{ form.processing ? 'Saving…' : 'Add contact' }}
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
            title="Delete this contact?"
            description="This will remove the contact. Existing loans linked to it will be kept."
            @close="confirmOpen = false"
            @confirm="doDelete"
        />
    </ModuleLayout>
</template>
