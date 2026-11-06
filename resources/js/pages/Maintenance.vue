<template>
    <StatusCard title="Down for maintenance" :icon="Wrench">
        <template #description> This website is currently undergoing maintenance. </template>
        <p>Please check back in a few minutes.</p>
    </StatusCard>
</template>

<script setup lang="ts">
import { Wrench } from "lucide-vue-next";
import StatusCard from "@/components/StatusCard.vue";

defineOptions({ title: "Site is not currently available" });
</script>
