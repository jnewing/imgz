<script setup lang="ts">
import { Check, Copy } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useClipboard } from '@/composables/useClipboard';
import type { ImageResource } from '@/types/image';

const props = defineProps<{ image: ImageResource }>();

const { copy } = useClipboard();
const copiedLabel = ref<string | null>(null);

const alt = computed(() => props.image.title ?? props.image.public_id);

const fields = computed(() => [
    { label: 'Viewer link', value: props.image.url },
    { label: 'Direct link', value: props.image.direct_url },
    {
        label: 'HTML',
        value: `<img src="${props.image.direct_url}" alt="${alt.value}">`,
    },
    { label: 'BBCode', value: `[img]${props.image.direct_url}[/img]` },
    { label: 'Markdown', value: `![${alt.value}](${props.image.direct_url})` },
]);

async function copyField(label: string, value: string): Promise<void> {
    await copy(value, `${label} copied`);
    copiedLabel.value = label;
    window.setTimeout(() => {
        if (copiedLabel.value === label) {
            copiedLabel.value = null;
        }
    }, 1500);
}
</script>

<template>
    <div class="flex flex-col gap-2.5">
        <div
            v-for="field in fields"
            :key="field.label"
            class="flex items-center gap-2"
        >
            <span
                class="w-24 shrink-0 text-xs font-medium text-muted-foreground"
                >{{ field.label }}</span
            >
            <div class="relative flex-1">
                <Input
                    :model-value="field.value"
                    readonly
                    spellcheck="false"
                    class="h-8 bg-muted/40 pr-9 font-mono text-xs"
                    @focus="
                        (e: FocusEvent) =>
                            (e.target as HTMLInputElement).select()
                    "
                />
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    class="absolute top-1/2 right-0.5 -translate-y-1/2"
                    :aria-label="`Copy ${field.label}`"
                    @click="copyField(field.label, field.value)"
                >
                    <Check
                        v-if="copiedLabel === field.label"
                        class="size-3.5 text-primary"
                    />
                    <Copy v-else class="size-3.5" />
                </Button>
            </div>
        </div>
    </div>
</template>
