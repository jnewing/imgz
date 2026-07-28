<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ImageOff, Loader2, UploadCloud } from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { toast } from 'vue-sonner';
import EmbedCodes from '@/components/imgz/EmbedCodes.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useUploader } from '@/composables/useUploader';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { login, register } from '@/routes';

interface Limits {
    max_files: number;
    max_size_kb: number;
    accept: string[];
    guest_uploads: boolean;
    is_authenticated: boolean;
}

const props = defineProps<{
    limits: Limits;
    retentionOptions: { value: number; label: string }[];
}>();

const canUpload = computed(
    () => props.limits.guest_uploads || props.limits.is_authenticated,
);

const expiry = ref('0');
const isDragging = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const { items, upload, remove } = useUploader(() => ({
    expires_in: Number(expiry.value) || null,
}));

const acceptAttr = computed(() => props.limits.accept.join(','));
const maxSizeLabel = computed(
    () => `${Math.round(props.limits.max_size_kb / 1024)} MB`,
);
const extensions = computed(() =>
    props.limits.accept.map((mime) => mime.replace('image/', '')).join(' · '),
);

function validate(files: File[]): File[] {
    const maxBytes = props.limits.max_size_kb * 1024;

    return files.filter((file) => {
        if (!props.limits.accept.includes(file.type)) {
            toast.error(`${file.name} isn't a supported image type.`);

            return false;
        }

        if (file.size > maxBytes) {
            toast.error(`${file.name} is larger than ${maxSizeLabel.value}.`);

            return false;
        }

        return true;
    });
}

function handleFiles(fileList: FileList | null): void {
    if (!fileList || fileList.length === 0) {
        return;
    }

    const valid = validate(Array.from(fileList));

    if (valid.length > 0) {
        upload(valid);
    }
}

function onDrop(event: DragEvent): void {
    isDragging.value = false;
    handleFiles(event.dataTransfer?.files ?? null);
}

function onInputChange(event: Event): void {
    handleFiles((event.target as HTMLInputElement).files);
    (event.target as HTMLInputElement).value = '';
}

function onPaste(event: ClipboardEvent): void {
    const files = Array.from(event.clipboardData?.items ?? [])
        .filter((item) => item.kind === 'file')
        .map((item) => item.getAsFile())
        .filter((file): file is File => file !== null);

    if (files.length > 0) {
        handleFiles({
            ...files,
            length: files.length,
            item: (i: number) => files[i],
        } as unknown as FileList);
    }
}

onMounted(() => window.addEventListener('paste', onPaste));
onUnmounted(() => window.removeEventListener('paste', onPaste));
</script>

<template>
    <Head title="Free image hosting" />

    <PublicLayout>
        <div class="mx-auto w-full max-w-3xl px-6 py-14 sm:py-20">
            <div class="mb-8 text-center">
                <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">
                    Drop an image. Get a link.
                </h1>
                <p class="mt-3 text-base text-muted-foreground">
                    Free image hosting, no account needed. Share it anywhere.
                </p>
            </div>

            <!-- Upload gate for account-only instances. -->
            <div
                v-if="!canUpload"
                class="rounded-xl border border-border bg-card p-10 text-center"
            >
                <p class="text-foreground">
                    This instance requires an account to upload.
                </p>
                <div class="mt-4 flex items-center justify-center gap-3">
                    <Button as-child>
                        <Link :href="login()">Log in</Link>
                    </Button>
                    <Button as-child variant="outline">
                        <Link :href="register()">Create account</Link>
                    </Button>
                </div>
            </div>

            <template v-else>
                <div
                    class="imgz-dropzone group cursor-pointer rounded-2xl bg-card p-10 text-center transition-shadow sm:p-14"
                    :class="{ 'imgz-dropzone--active': isDragging }"
                    role="button"
                    tabindex="0"
                    @click="fileInput?.click()"
                    @keydown.enter.prevent="fileInput?.click()"
                    @keydown.space.prevent="fileInput?.click()"
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="onDrop"
                >
                    <UploadCloud
                        class="mx-auto size-10 text-muted-foreground transition-colors group-hover:text-foreground"
                    />
                    <p class="mt-4 text-lg font-medium">
                        Drag &amp; drop, paste, or click to upload
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ extensions }}, up to {{ maxSizeLabel }},
                        {{ limits.max_files }} at a time
                    </p>

                    <input
                        ref="fileInput"
                        type="file"
                        class="hidden"
                        multiple
                        :accept="acceptAttr"
                        @change="onInputChange"
                    />
                </div>

                <!-- Upload options -->
                <div
                    class="mt-4 flex flex-wrap items-center justify-center gap-x-6 gap-y-3 text-sm"
                >
                    <label
                        class="flex items-center gap-2 text-muted-foreground"
                    >
                        <span>Auto-delete</span>
                        <Select v-model="expiry">
                            <SelectTrigger class="h-8 w-36">
                                <SelectValue placeholder="Keep forever" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="0">Keep forever</SelectItem>
                                <SelectItem
                                    v-for="option in retentionOptions"
                                    :key="option.value"
                                    :value="String(option.value)"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </label>
                </div>
            </template>

            <!-- Results -->
            <div v-if="items.length" class="mt-10 flex flex-col gap-4">
                <Card v-for="item in items" :key="item.id" class="py-0">
                    <CardContent class="flex gap-4 p-4">
                        <div
                            class="relative size-20 shrink-0 overflow-hidden rounded-lg bg-muted"
                        >
                            <img
                                :src="item.previewUrl"
                                :alt="item.name"
                                class="size-full object-cover"
                                :class="{
                                    'opacity-40': item.status !== 'done',
                                }"
                            />
                            <div
                                v-if="item.status === 'uploading'"
                                class="absolute inset-0 flex items-center justify-center"
                            >
                                <Loader2
                                    class="size-6 animate-spin text-foreground"
                                />
                            </div>
                            <div
                                v-else-if="item.status === 'error'"
                                class="absolute inset-0 flex items-center justify-center"
                            >
                                <ImageOff class="size-6 text-destructive" />
                            </div>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <p class="truncate text-sm font-medium">
                                    {{ item.name }}
                                </p>
                                <Button
                                    v-if="item.result"
                                    as-child
                                    variant="link"
                                    size="sm"
                                    class="h-auto shrink-0 p-0 text-xs"
                                >
                                    <a :href="item.result.url" target="_blank"
                                        >Open</a
                                    >
                                </Button>
                            </div>

                            <!-- Uploading progress -->
                            <div
                                v-if="item.status === 'uploading'"
                                class="mt-3"
                            >
                                <Progress
                                    :model-value="item.progress"
                                    class="h-1.5"
                                />
                                <p class="mt-1 text-xs text-muted-foreground">
                                    Uploading… {{ item.progress }}%
                                </p>
                            </div>

                            <!-- Error -->
                            <div
                                v-else-if="item.status === 'error'"
                                class="mt-2 flex items-center justify-between gap-2"
                            >
                                <p class="text-sm text-destructive">
                                    {{ item.error }}
                                </p>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-auto p-1 text-xs"
                                    @click="remove(item.id)"
                                >
                                    Dismiss
                                </Button>
                            </div>

                            <!-- Done: embed codes -->
                            <div
                                v-else-if="item.result"
                                class="mt-3 flex flex-col gap-3"
                            >
                                <EmbedCodes :image="item.result" />
                                <div class="flex items-center gap-3 text-xs">
                                    <Button
                                        as-child
                                        variant="link"
                                        size="sm"
                                        class="h-auto p-0 text-xs text-destructive/80 hover:text-destructive"
                                    >
                                        <a :href="item.result.delete_url"
                                            >Delete</a
                                        >
                                    </Button>
                                    <span class="text-muted-foreground">{{
                                        item.result.human_size
                                    }}</span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </PublicLayout>
</template>

<style>
@property --imgz-angle {
    syntax: '<angle>';
    initial-value: 0deg;
    inherits: false;
}

.imgz-dropzone {
    position: relative;
    border: 2px dashed var(--border);
}

.imgz-dropzone.imgz-dropzone--active {
    border-color: transparent;
    box-shadow: 0 0 45px -12px rgb(126 221 211 / 0.55);
}

.imgz-dropzone.imgz-dropzone--active::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    padding: 2px;
    background: conic-gradient(
        from var(--imgz-angle),
        #7eddd3,
        #b6efe7,
        #4fb9ad,
        #7eddd3
    );
    -webkit-mask:
        linear-gradient(#000 0 0) content-box,
        linear-gradient(#000 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    animation: imgz-angle-spin 3s linear infinite;
}

@keyframes imgz-angle-spin {
    to {
        --imgz-angle: 360deg;
    }
}

@media (prefers-reduced-motion: reduce) {
    .imgz-dropzone.imgz-dropzone--active::before {
        animation: none;
    }
}
</style>
