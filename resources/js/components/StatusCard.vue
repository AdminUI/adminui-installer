<template>
    <Card class="w-full max-w-lg">
        <CardHeader class="items-center text-center">
            <div
                v-if="icon"
                class="mb-2 flex size-14 items-center justify-center rounded-full bg-primary/10 text-primary"
            >
                <component :is="icon" class="size-7" />
            </div>
            <CardTitle class="text-2xl">{{ title }}</CardTitle>
            <CardDescription v-if="$slots.description" class="text-base">
                <slot name="description" />
            </CardDescription>
        </CardHeader>
        <CardContent v-if="$slots.default" class="space-y-3 text-muted-foreground">
            <slot />
        </CardContent>
        <CardFooter v-if="$slots.actions" class="justify-center gap-2">
            <slot name="actions" />
        </CardFooter>
    </Card>
</template>

<script setup lang="ts">
import type { Component } from "vue";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";

defineProps<{
    title: string;
    icon?: Component;
}>();
</script>
