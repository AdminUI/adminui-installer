<template>
    <p data-slot="card-description" :class="cn('text-sm text-muted-foreground', props.class)">
        <slot />
    </p>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from "vue";
import { cn } from "@/helpers/cn";

const props = defineProps<{
    class?: HTMLAttributes["class"];
}>();
</script>
