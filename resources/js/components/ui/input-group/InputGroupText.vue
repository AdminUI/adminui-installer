<template>
    <span
        :class="
            cn(
                `flex items-center gap-2 text-sm text-muted-foreground [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4`,
                props.class,
            )
        "
    >
        <slot />
    </span>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from "vue";
import { cn } from "@/helpers/cn";

const props = defineProps<{
    class?: HTMLAttributes["class"];
}>();
</script>
