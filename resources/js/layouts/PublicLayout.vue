<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import AppWordmark from '@/components/AppWordmark.vue';
import { Toaster } from '@/components/ui/sonner';
import { aup, dashboard, docs, login, privacy, register, terms } from '@/routes';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header class="border-b border-border/60">
            <div
                class="mx-auto flex h-16 w-full max-w-5xl items-center justify-between px-6"
            >
                <Link href="/" class="flex items-center gap-2">
                    <span
                        class="flex size-7 items-center justify-center rounded bg-primary text-primary-foreground"
                    >
                        <AppLogoIcon class="size-5 fill-current" />
                    </span>
                    <AppWordmark
                        domain
                        class="text-xl font-semibold tracking-tight"
                    />
                </Link>

                <nav class="flex items-center gap-1 text-sm font-medium">
                    <Link
                        v-if="user"
                        :href="dashboard()"
                        class="rounded-md px-3 py-1.5 text-muted-foreground transition-colors hover:text-foreground"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-md px-3 py-1.5 text-muted-foreground transition-colors hover:text-foreground"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="register()"
                            class="rounded-md bg-foreground px-3 py-1.5 text-background transition-opacity hover:opacity-90"
                        >
                            Sign up
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t border-border/60">
            <div
                class="mx-auto flex w-full max-w-5xl flex-col items-center justify-between gap-3 px-6 py-6 text-sm text-muted-foreground sm:flex-row"
            >
                <p>Free image hosting. Share anywhere.</p>
                <nav class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1">
                    <Link :href="docs()" class="transition-colors hover:text-foreground">
                        API
                    </Link>
                    <Link :href="terms()" class="transition-colors hover:text-foreground">
                        Terms
                    </Link>
                    <Link :href="aup()" class="transition-colors hover:text-foreground">
                        Acceptable Use
                    </Link>
                    <Link :href="privacy()" class="transition-colors hover:text-foreground">
                        Privacy
                    </Link>
                    <AppWordmark domain class="font-medium" />
                </nav>
            </div>
        </footer>

        <Toaster />
    </div>
</template>
