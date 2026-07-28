<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <!-- imgz mark: a "z" monogram. -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 32 32"
        :class="className"
        v-bind="$attrs"
    >
        <path
            d="M10 10.5 H22 L10 21.5 H22"
            fill="none"
            stroke="currentColor"
            stroke-width="3.4"
            stroke-linejoin="round"
            stroke-linecap="round"
        />
    </svg>
</template>
