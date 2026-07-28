<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { aup, privacy, terms } from '@/routes';

defineProps<{
    title: string;
    updated: string;
}>();
</script>

<template>
    <Head :title="title" />

    <PublicLayout>
        <article class="mx-auto w-full max-w-3xl px-6 pt-16 pb-20 sm:pt-20">
            <header class="mb-10">
                <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">
                    {{ title }}
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Last updated: {{ updated }}
                </p>
            </header>

            <div
                class="space-y-6 text-muted-foreground [&_a]:text-primary [&_a]:underline [&_a]:underline-offset-2 [&_h2]:mt-10 [&_h2]:text-xl [&_h2]:font-semibold [&_h2]:tracking-tight [&_h2]:text-foreground [&_li]:leading-relaxed [&_p]:leading-relaxed [&_strong]:font-medium [&_strong]:text-foreground [&_ul]:list-disc [&_ul]:space-y-1.5 [&_ul]:pl-6"
            >
                <slot />
            </div>

            <footer
                class="mt-14 flex flex-wrap gap-x-4 gap-y-1 border-t border-border/60 pt-6 text-sm"
            >
                <Link :href="terms()" class="text-primary underline-offset-2 hover:underline">
                    Terms of Service
                </Link>
                <Link :href="aup()" class="text-primary underline-offset-2 hover:underline">
                    Acceptable Use Policy
                </Link>
                <Link :href="privacy()" class="text-primary underline-offset-2 hover:underline">
                    Privacy Policy
                </Link>
            </footer>
        </article>
    </PublicLayout>
</template>
