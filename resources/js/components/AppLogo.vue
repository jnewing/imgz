<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import AppWordmark from '@/components/AppWordmark.vue';
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded bg-sidebar-primary text-sidebar-primary-foreground"
    >
        <AppLogoIcon class="size-6 fill-current" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <AppWordmark class="mb-0.5 truncate leading-tight font-semibold" />
    </div>
</template>
