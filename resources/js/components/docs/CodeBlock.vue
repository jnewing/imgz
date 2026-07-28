<script setup lang="ts">
import { Check, Copy } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { useClipboard } from '@/composables/useClipboard';

const props = defineProps<{
    code: string;
    label?: string;
}>();

const { copy } = useClipboard();
const copied = ref(false);

function copyCode(): void {
    copy(props.code, 'Copied to clipboard');
    copied.value = true;
    window.setTimeout(() => (copied.value = false), 1500);
}
</script>

<template>
    <div class="group relative">
        <div
            v-if="label"
            class="rounded-t-lg border border-b-0 border-border bg-muted/60 px-4 py-1.5 font-mono text-xs text-muted-foreground"
        >
            {{ label }}
        </div>
        <pre
            class="overflow-x-auto border border-border bg-card p-4 text-sm leading-relaxed text-foreground"
            :class="label ? 'rounded-b-lg' : 'rounded-lg'"
        ><code>{{ code }}</code></pre>
        <Button
            type="button"
            size="icon-sm"
            variant="secondary"
            class="absolute top-2 right-2 opacity-0 shadow-sm transition-opacity group-hover:opacity-100"
            :class="{ 'opacity-100': copied }"
            aria-label="Copy code"
            @click="copyCode"
        >
            <Check v-if="copied" class="size-3.5" />
            <Copy v-else class="size-3.5" />
        </Button>
    </div>
</template>
