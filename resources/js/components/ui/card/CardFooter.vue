<template>
    <div data-slot="card-footer" :class="cn('flex items-center px-6 [.border-t]:pt-6', props.class)">
        <slot />
    </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from "vue";
import { cn } from "@/helpers/cn";

const props = defineProps<{
    class?: HTMLAttributes["class"];
}>();
</script>
