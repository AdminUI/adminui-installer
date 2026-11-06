<template>
    <StatusCard title="Admin account already created" :icon="ShieldCheck">
        <template #description> An admin account has already been set up for AdminUI. </template>
        <p>Please use it to log in to AdminUI and then create additional admin accounts from there.</p>
        <template v-if="loginUrl" #actions>
            <Button as="a" size="lg" :href="loginUrl">
                <LogIn />
                Go to admin login
            </Button>
        </template>
    </StatusCard>
</template>

<script setup lang="ts">
import { LogIn, ShieldCheck } from "lucide-vue-next";
import StatusCard from "@/components/StatusCard.vue";
import { Button } from "@/components/ui/button";
import { getInstallerConfig } from "@/composables/useInstallerConfig";

defineOptions({ title: "Already registered" });

const { loginUrl } = getInstallerConfig();
</script>
