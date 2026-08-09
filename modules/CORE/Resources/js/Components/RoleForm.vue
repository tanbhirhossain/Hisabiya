<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import type { Role } from '@/types';
import { computed } from 'vue';
import { Loader2, ShieldCheck, CheckSquare, Square } from 'lucide-vue-next';

const props = defineProps<{
    role?: Role;
    allPermissions: Array<{ id: number; name: string }>;
    selected: number[];
}>();

const form = useForm({
    name: props.role?.name ?? '',
    permissions: [...props.selected],
});

const groups = computed(() => {
    const map: Record<string, Array<{ id: number; name: string }>> = {};
    for (const permission of props.allPermissions) {
        const group = permission.name.split('.')[0] || 'general';
        (map[group] ||= []).push(permission);
    }
    return Object.entries(map).map(([name, items]) => ({
        name,
        items: items.sort((a, b) => a.name.localeCompare(b.name)),
    }));
});

function groupAllSelected(group: { items: Array<{ id: number; name: string }> }): boolean {
    return group.items.every((p) => form.permissions.includes(p.id));
}

function toggleGroup(group: { items: Array<{ id: number; name: string }> }): void {
    const ids = group.items.map((p) => p.id);
    if (groupAllSelected(group)) {
        form.permissions = form.permissions.filter((id) => !ids.includes(id));
    } else {
        form.permissions = [...new Set([...form.permissions, ...ids])];
    }
}

function togglePermission(id: number): void {
    const index = form.permissions.indexOf(id);
    if (index === -1) {
        form.permissions.push(id);
    } else {
        form.permissions.splice(index, 1);
    }
}

function formatGroup(name: string): string {
    return name.charAt(0).toUpperCase() + name.slice(1);
}

function submit(): void {
    if (props.role) {
        form.put(route('roles.update', props.role.id), { preserveScroll: true });
    } else {
        form.post(route('roles.store'), { preserveScroll: true });
    }
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div class="max-w-md space-y-2">
            <Label for="name">Role name *</Label>
            <Input id="name" v-model="form.name" placeholder="e.g. manager" />
            <InputError :message="form.errors.name" />
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <Label class="flex items-center gap-2 text-sm font-semibold"><ShieldCheck class="h-4 w-4" /> Permissions</Label>
                <span class="text-xs text-muted-foreground">{{ form.permissions.length }} selected</span>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div v-for="group in groups" :key="group.name" class="rounded-xl border border-border p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-sm font-semibold text-foreground capitalize">{{ formatGroup(group.name) }}</span>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                            @click="toggleGroup(group)"
                        >
                            <component :is="groupAllSelected(group) ? CheckSquare : Square" class="h-3.5 w-3.5" />
                            {{ groupAllSelected(group) ? 'Clear' : 'All' }}
                        </button>
                    </div>
                    <div class="space-y-1.5">
                        <label
                            v-for="permission in group.items"
                            :key="permission.id"
                            class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm transition hover:bg-muted"
                            :class="form.permissions.includes(permission.id) ? 'bg-primary/5' : ''"
                        >
                            <input
                                :checked="form.permissions.includes(permission.id)"
                                type="checkbox"
                                class="h-4 w-4 rounded accent-primary"
                                @change="togglePermission(permission.id)"
                            />
                            <span class="text-foreground">{{ permission.name.replace(group.name + '.', '') }}</span>
                        </label>
                    </div>
                </div>
            </div>
            <InputError :message="form.errors.permissions" />
        </div>

        <div class="flex items-center gap-3 border-t border-border pt-5">
            <Button type="submit" :disabled="form.processing">
                <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                {{ role ? 'Update Role' : 'Create Role' }}
            </Button>
            <Button type="button" variant="outline" as-child>
                <a :href="route('roles.index')">Cancel</a>
            </Button>
        </div>
    </form>
</template>
