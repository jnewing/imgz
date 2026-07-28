<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { destroy } from '@/routes/images';
import type { ImageResource } from '@/types/image';

const props = defineProps<{ image: ImageResource; token: string }>();

const deleting = ref(false);

function confirmDelete(): void {
    router.delete(destroy.url(props.image.public_id), {
        data: { token: props.token },
        onStart: () => (deleting.value = true),
        onFinish: () => (deleting.value = false),
    });
}
</script>

<template>
    <Head title="Delete image" />

    <PublicLayout>
        <div class="mx-auto flex w-full max-w-md flex-col px-6 py-16">
            <Card>
                <CardContent
                    class="flex flex-col items-center gap-5 p-6 text-center"
                >
                    <img
                        :src="image.thumbnail_url"
                        :alt="image.public_id"
                        class="max-h-56 rounded-lg object-contain"
                    />
                    <div>
                        <h1 class="text-lg font-semibold">
                            Delete this image?
                        </h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            This permanently removes the image and its link.
                            This can't be undone.
                        </p>
                    </div>
                    <div class="flex w-full items-center gap-3">
                        <Button as-child variant="outline" class="flex-1">
                            <Link :href="image.url">Cancel</Link>
                        </Button>
                        <Button
                            variant="destructive"
                            class="flex-1"
                            :disabled="deleting"
                            @click="confirmDelete"
                        >
                            {{ deleting ? 'Deleting…' : 'Delete' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </PublicLayout>
</template>
