<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import type { Permission } from '@/types';
import { Loader2 } from 'lucide-vue-next';

const props = defineProps<{
    permission?: Permission;
}>();

const form = useForm({
    name: props.permission?.name ?? '',
    guard_name: props.permission?.guard_name ?? 'web',
});

function submit(): void {
    if (props.permission) {
        form.put(route('permissions.update', props.permission.id), { preserveScroll: true });
    } else {
        form.post(route('permissions.store'), { preserveScroll: true });
    }
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="space-y-2">
                <Label for="name">Permission name *</Label>
                <Input id="name" v-model="form.name" placeholder="e.g. tenant.delete" />
                <p class="text-xs text-muted-foreground">Use the <code>resource.action</code> convention, e.g. <code>user.view</code>.</p>
                <InputError :message="form.errors.name" />
            </div>
            <div class="space-y-2">
                <Label for="guard_name">Guard *</Label>
                <Input id="guard_name" v-model="form.guard_name" placeholder="web" />
                <InputError :message="form.errors.guard_name" />
            </div>
        </div>

        <div class="flex items-center gap-3 border-t border-border pt-5">
            <Button type="submit" :disabled="form.processing">
                <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                {{ permission ? 'Update Permission' : 'Create Permission' }}
            </Button>
            <Button type="button" variant="outline" as-child>
                <a :href="route('permissions.index')">Cancel</a>
            </Button>
        </div>
    </form>
</template>
