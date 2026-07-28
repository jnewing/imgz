<script setup lang="ts">
import { Head, InfiniteScroll, Link, router } from '@inertiajs/vue3';
import { Copy, ExternalLink, ImagePlus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { useClipboard } from '@/composables/useClipboard';
import { dashboard, home } from '@/routes';
import { destroy } from '@/routes/images';
import type { ImageResource } from '@/types/image';

const props = defineProps<{
    images: { data: ImageResource[] };
    stats: { count: number; size: number };
    filters: { sort: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const { copy } = useClipboard();

const gridEl = ref();
const pendingDelete = ref<ImageResource | null>(null);
const deleting = ref(false);
const localCount = ref(props.stats.count);
const localSize = ref(props.stats.size);

// Sorting. Changing the sort is a fresh visit that resets the merged scroll
// data, so the component re-initializes from the new props.
const sort = ref(props.filters.sort);

/**
 * Reload the grid in the chosen order. `reset` clears the accumulated
 * infinite-scroll pages so the re-sorted results replace, rather than append
 * to, the existing ones.
 */
function changeSort(value: string): void {
    sort.value = value;

    router.get(
        dashboard.url(),
        value === 'newest' ? {} : { sort: value },
        {
            only: ['images', 'stats', 'filters'],
            reset: ['images'],
        },
    );
}

// Public IDs removed this session. Filtering (rather than mutating the merged
// prop) keeps deletions in place as infinite scroll appends more pages.
const deletedIds = ref(new Set<string>());

const visibleImages = computed(() =>
    props.images.data.filter((image) => !deletedIds.value.has(image.public_id)),
);

const humanTotal = computed(() => formatBytes(localSize.value));

function formatBytes(bytes: number): string {
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let value = bytes;
    let unit = 0;

    while (value > 1024 && unit < units.length - 1) {
        value /= 1024;
        unit++;
    }

    return `${Math.round(value * 100) / 100} ${units[unit]}`;
}

/**
 * Reads Laravel's XSRF-TOKEN cookie so the manual delete XHR passes CSRF checks.
 */
function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

/**
 * Deletes via the JSON endpoint (no X-Inertia header) so the image is removed
 * in place without reloading and resetting the infinite-scroll grid.
 */
async function confirmDelete(): Promise<void> {
    const image = pendingDelete.value;

    if (!image || deleting.value) {
        return;
    }

    deleting.value = true;

    try {
        const response = await fetch(destroy.url(image.public_id), {
            method: 'DELETE',
            headers: {
                'X-XSRF-TOKEN': xsrfToken(),
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('Delete failed');
        }

        if (!deletedIds.value.has(image.public_id)) {
            deletedIds.value = new Set(deletedIds.value).add(image.public_id);
            localCount.value = Math.max(0, localCount.value - 1);
            localSize.value = Math.max(0, localSize.value - image.size);
        }

        pendingDelete.value = null;
    } finally {
        deleting.value = false;
    }
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="px-4 py-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                title="Your uploads"
                :description="`${localCount} ${localCount === 1 ? 'image' : 'images'} · ${humanTotal}`"
            />

            <Button as-child>
                <Link :href="home()">
                    <ImagePlus class="size-4" />
                    Upload
                </Link>
            </Button>
        </div>

        <!-- Sort -->
        <div v-if="localCount > 0" class="mt-6 flex flex-col gap-1.5">
            <Label class="text-xs text-muted-foreground">Sort</Label>
            <Select
                :model-value="sort"
                @update:model-value="(value) => changeSort(String(value))"
            >
                <SelectTrigger class="w-40">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="newest">Newest first</SelectItem>
                    <SelectItem value="oldest">Oldest first</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <!-- Empty account -->
        <div
            v-if="localCount === 0"
            class="mt-6 flex flex-col items-center gap-3 rounded-xl border border-dashed border-border bg-card/40 px-6 py-16 text-center"
        >
            <ImagePlus class="size-8 text-muted-foreground" />
            <div>
                <p class="font-medium">No uploads yet</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Images you upload while signed in show up here.
                </p>
            </div>
            <Button as-child variant="outline" class="mt-2">
                <Link :href="home()">Upload your first image</Link>
            </Button>
        </div>

        <!-- Masonry grid -->
        <InfiniteScroll
            v-else
            data="images"
            :items-element="() => gridEl"
            class="mt-6"
        >
            <div
                ref="gridEl"
                class="columns-2 gap-4 sm:columns-3 xl:columns-4 [&>*]:mb-4"
            >
                <figure
                    v-for="image in visibleImages"
                    :key="image.public_id"
                    class="group relative break-inside-avoid overflow-hidden rounded-xl border border-border bg-muted/30"
                >
                    <a :href="image.url" target="_blank">
                        <img
                            :src="image.thumbnail_url"
                            :alt="image.title ?? image.public_id"
                            loading="lazy"
                            class="w-full object-cover transition-opacity group-hover:opacity-60"
                        />
                    </a>

                    <!-- Hover actions -->
                    <div
                        class="pointer-events-none absolute inset-x-0 top-0 flex items-start justify-end gap-1.5 p-2 opacity-0 transition-opacity group-hover:opacity-100"
                    >
                        <Button
                            type="button"
                            size="icon-sm"
                            variant="secondary"
                            class="pointer-events-auto shadow-sm"
                            aria-label="Copy direct link"
                            @click="copy(image.direct_url, 'Direct link copied')"
                        >
                            <Copy class="size-3.5" />
                        </Button>
                        <Button
                            as-child
                            size="icon-sm"
                            variant="secondary"
                            class="pointer-events-auto shadow-sm"
                            aria-label="Open image"
                        >
                            <a :href="image.url" target="_blank">
                                <ExternalLink class="size-3.5" />
                            </a>
                        </Button>
                        <Button
                            type="button"
                            size="icon-sm"
                            variant="destructive"
                            class="pointer-events-auto shadow-sm"
                            aria-label="Delete image"
                            @click="pendingDelete = image"
                        >
                            <Trash2 class="size-3.5" />
                        </Button>
                    </div>

                    <figcaption
                        class="pointer-events-none absolute inset-x-0 bottom-0 flex items-center justify-between gap-2 bg-gradient-to-t from-black/70 to-transparent px-3 pt-6 pb-2 text-xs text-white opacity-0 transition-opacity group-hover:opacity-100"
                    >
                        <span v-if="image.width && image.height" class="font-mono">
                            {{ image.width }}×{{ image.height }}
                        </span>
                        <span class="font-mono">{{ image.human_size }}</span>
                    </figcaption>
                </figure>
            </div>

            <template #loading>
                <div class="flex justify-center py-8">
                    <Spinner class="size-5 text-muted-foreground" />
                </div>
            </template>
        </InfiniteScroll>
    </div>

    <Dialog
        :open="pendingDelete !== null"
        @update:open="(open) => !open && (pendingDelete = null)"
    >
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete this image?</DialogTitle>
                <DialogDescription>
                    This permanently removes the image and its link. This can't
                    be undone.
                </DialogDescription>
            </DialogHeader>

            <DialogFooter>
                <Button variant="outline" @click="pendingDelete = null">
                    Cancel
                </Button>
                <Button
                    variant="destructive"
                    :disabled="deleting"
                    @click="confirmDelete"
                >
                    {{ deleting ? 'Deleting…' : 'Delete' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
