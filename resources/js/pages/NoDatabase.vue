<template>
    <StatusCard title="Can't connect to the database" :icon="DatabaseZap">
        <template #description> To install AdminUI you will need to connect a database. </template>
        <p>
            Please update your <code class="rounded bg-muted px-1.5 py-0.5">.env</code>
            file with the correct database credentials, then reload this page.
        </p>
        <p>
            If you are struggling with your setup, please contact us via the
            <a
                class="font-semibold text-primary hover:underline"
                href="https://adminui.co.uk"
                target="_blank"
                rel="noopener"
            >
                AdminUI website
            </a>
            . We offer basic installation from only &pound;49.99.
        </p>
    </StatusCard>
</template>

<script setup lang="ts">
import { DatabaseZap } from "lucide-vue-next";
import StatusCard from "@/components/StatusCard.vue";

defineOptions({ title: "Error connecting to database" });
</script>
