<template>
    <StatusCard title="AdminUI is not installed" :icon="PackageX">
        <template #description>
            An AdminUI installation was not detected. You must install AdminUI before registering your admin account.
        </template>
        <template #actions>
            <Button size="lg" @click="navigate('install')">
                <Download />
                Go to install page
            </Button>
        </template>
    </StatusCard>
</template>

<script setup lang="ts">
import { Download, PackageX } from "lucide-vue-next";
import StatusCard from "@/components/StatusCard.vue";
import { Button } from "@/components/ui/button";
import { navigate } from "@/composables/useNavigation";

defineOptions({ title: "AdminUI not installed" });
</script>
