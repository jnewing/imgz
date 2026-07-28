<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ExternalLink, ShieldCheck, Trash2 } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { dismiss, remove } from '@/routes/admin/moderation';

interface ReportedImage {
    public_id: string;
    url: string;
    thumbnail_url: string;
    direct_url: string;
    human_size: string;
    created_at: string | null;
    reports_count: number;
    reasons: Record<string, number>;
}

defineProps<{
    images: { data: ReportedImage[] };
}>();

function removeImage(image: ReportedImage): void {
    router.delete(remove.url(image.public_id), { preserveScroll: true });
}

function dismissReports(image: ReportedImage): void {
    router.post(dismiss.url(image.public_id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Moderation" />

    <div class="px-4 py-6">
        <Heading
            title="Moderation"
            description="Review reported images. Removing an image also blocks it from being re-uploaded."
        />

        <div
            v-if="images.data.length === 0"
            class="rounded-xl border border-border bg-card p-10 text-center text-sm text-muted-foreground"
        >
            No reported images. All clear.
        </div>

        <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <Card v-for="image in images.data" :key="image.public_id">
                <CardContent class="flex flex-col gap-3 p-4">
                    <a
                        :href="image.url"
                        target="_blank"
                        class="group relative block aspect-video overflow-hidden rounded-lg bg-muted"
                    >
                        <img
                            :src="image.thumbnail_url"
                            :alt="image.public_id"
                            class="size-full object-cover"
                        />
                        <span
                            class="absolute top-2 right-2 rounded bg-destructive px-1.5 py-0.5 text-xs font-medium text-white"
                        >
                            {{ image.reports_count }}
                            {{ image.reports_count === 1 ? 'report' : 'reports' }}
                        </span>
                        <span
                            class="absolute inset-0 flex items-center justify-center bg-background/60 opacity-0 transition-opacity group-hover:opacity-100"
                        >
                            <ExternalLink class="size-5" />
                        </span>
                    </a>

                    <div class="flex flex-wrap gap-1">
                        <Badge
                            v-for="(count, reason) in image.reasons"
                            :key="reason"
                            variant="secondary"
                            class="text-[10px]"
                        >
                            {{ reason }} ×{{ count }}
                        </Badge>
                    </div>

                    <p class="text-xs text-muted-foreground">
                        {{ image.human_size }} · uploaded {{ image.created_at }}
                    </p>

                    <div class="flex gap-2">
                        <Button
                            variant="destructive"
                            size="sm"
                            class="flex-1"
                            @click="removeImage(image)"
                        >
                            <Trash2 class="size-4" />
                            Remove &amp; block
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            class="flex-1"
                            @click="dismissReports(image)"
                        >
                            <ShieldCheck class="size-4" />
                            Dismiss
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
