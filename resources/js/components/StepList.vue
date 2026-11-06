<template>
    <ol class="space-y-1">
        <li
            v-for="step in steps"
            :key="step.key"
            class="flex items-start gap-3 rounded-md px-2 py-2"
            :class="{ 'bg-muted/50': step.status === 'loading' }"
        >
            <Loader2 v-if="step.status === 'loading'" class="mt-0.5 size-5 shrink-0 animate-spin text-primary" />
            <CheckCircle2 v-else-if="step.status === 'done'" class="mt-0.5 size-5 shrink-0 text-emerald-500" />
            <XCircle v-else-if="step.status === 'error'" class="mt-0.5 size-5 shrink-0 text-destructive" />
            <Circle v-else class="mt-0.5 size-5 shrink-0 text-muted-foreground/50" />

            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium" :class="{ 'text-muted-foreground': step.status === 'pending' }">
                    {{ step.label }}
                </p>
                <p v-if="step.detail" class="text-xs text-muted-foreground">
                    {{ step.detail }}
                </p>
                <pre
                    v-if="step.log?.length"
                    v-autoscroll
                    class="mt-2 max-h-40 overflow-auto rounded-md bg-muted p-2 font-mono text-xs whitespace-pre-wrap"
                    >{{ step.log.join("\n") }}</pre>
            </div>
        </li>
    </ol>
</template>

<script setup lang="ts">
import { CheckCircle2, Circle, Loader2, XCircle } from "lucide-vue-next";

export type StepStatus = "pending" | "loading" | "done" | "error";

export interface Step {
    key: string;
    label: string;
    status: StepStatus;
    detail?: string;
    log?: string[];
}

defineProps<{ steps: Step[] }>();

// Keep the newest console output in view while a step is streaming
const vAutoscroll = {
    mounted: (el: HTMLElement) => (el.scrollTop = el.scrollHeight),
    updated: (el: HTMLElement) => (el.scrollTop = el.scrollHeight),
};
</script>
