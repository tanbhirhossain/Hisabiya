<script setup lang="ts">
import { AlertTriangle } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

defineProps<{
    open: boolean;
    title?: string;
    description?: string;
    confirmLabel?: string;
    loading?: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'confirm'): void;
}>();
</script>

<template>
    <Dialog :open="open" @update:open="(v) => !v && emit('close')">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600 dark:bg-rose-500/10">
                        <AlertTriangle class="h-5 w-5" />
                    </div>
                    <div>
                        <DialogTitle class="text-base">{{ title ?? 'Are you sure?' }}</DialogTitle>
                        <DialogDescription class="mt-1 text-sm">
                            {{ description ?? 'This action cannot be undone.' }}
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>
            <DialogFooter class="sm:justify-end">
                <Button variant="outline" size="sm" :disabled="loading" @click="emit('close')">Cancel</Button>
                <Button variant="destructive" size="sm" :disabled="loading" @click="emit('confirm')">
                    {{ loading ? 'Deleting…' : (confirmLabel ?? 'Delete') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
