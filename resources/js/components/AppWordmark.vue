<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

// The imgz wordmark with a consistent treatment: the first letter in the brand
// teal, the rest in the foreground, and an optional dimmed ".net" domain.
const props = defineProps<{ domain?: boolean }>();

const name = computed(() => String(usePage().props.name ?? 'imgz'));
</script>

<template>
    <span class="lowercase">
        <span class="text-primary">{{ name.charAt(0) }}</span
        ><span class="text-foreground">{{ name.slice(1) }}</span
        ><span v-if="props.domain" class="text-muted-foreground">.net</span>
    </span>
</template>
