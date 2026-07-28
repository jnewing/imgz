<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Download, Eye, Flag, Maximize2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmbedCodes from '@/components/imgz/EmbedCodes.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { report } from '@/routes/images';
import type { ImageResource } from '@/types/image';

const props = defineProps<{
    image: ImageResource;
    reportReasons: { value: string; label: string }[];
}>();

const title = computed(() => props.image.title ?? props.image.public_id);
const dimensions = computed(() =>
    props.image.width && props.image.height
        ? `${props.image.width} × ${props.image.height}`
        : null,
);

const reportOpen = ref(false);
const reason = ref('');
const reporting = ref(false);

function submitReport(): void {
    if (!reason.value) {
return;
}

    router.post(
        report.url(props.image.public_id),
        { reason: reason.value },
        {
            preserveScroll: true,
            onStart: () => (reporting.value = true),
            onFinish: () => (reporting.value = false),
            onSuccess: () => {
                reportOpen.value = false;
                reason.value = '';
            },
        },
    );
}
</script>

<template>
    <Head :title="title" />

    <PublicLayout>
        <div class="mx-auto w-full max-w-5xl px-6 py-8">
            <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
                <!-- Image -->
                <div
                    class="flex items-center justify-center overflow-hidden rounded-xl border border-border bg-muted/30 p-3"
                >
                    <img
                        :src="image.direct_url"
                        :alt="title"
                        class="max-h-[75vh] w-auto max-w-full rounded-md object-contain"
                    />
                </div>

                <!-- Details -->
                <aside class="flex flex-col gap-5">
                    <div>
                        <h1 class="truncate text-lg font-semibold">
                            {{ title }}
                        </h1>
                        <dl
                            class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-sm text-muted-foreground"
                        >
                            <div
                                v-if="dimensions"
                                class="flex items-center gap-1.5"
                            >
                                <Maximize2 class="size-3.5" />
                                <dd>{{ dimensions }}</dd>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="font-mono">{{
                                    image.human_size
                                }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <Eye class="size-3.5" />
                                <dd>{{ image.views }}</dd>
                            </div>
                        </dl>
                    </div>

                    <Button as-child>
                        <a :href="image.direct_url" :download="image.public_id">
                            <Download class="size-4" />
                            Download
                        </a>
                    </Button>

                    <Card>
                        <CardContent class="p-4">
                            <h2
                                class="mb-3 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                            >
                                Share
                            </h2>
                            <EmbedCodes :image="image" />
                        </CardContent>
                    </Card>

                    <Button
                        variant="ghost"
                        size="sm"
                        class="self-start text-muted-foreground hover:text-destructive"
                        @click="reportOpen = true"
                    >
                        <Flag class="size-3.5" />
                        Report
                    </Button>
                </aside>
            </div>
        </div>

        <Dialog v-model:open="reportOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Report image</DialogTitle>
                    <DialogDescription>
                        Tell us why this image should be reviewed. Reports are
                        anonymous.
                    </DialogDescription>
                </DialogHeader>

                <Select v-model="reason">
                    <SelectTrigger class="w-full">
                        <SelectValue placeholder="Select a reason" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in reportReasons"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <DialogFooter>
                    <Button
                        variant="destructive"
                        :disabled="!reason || reporting"
                        @click="submitReport"
                    >
                        {{ reporting ? 'Reporting…' : 'Submit report' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </PublicLayout>
</template>
