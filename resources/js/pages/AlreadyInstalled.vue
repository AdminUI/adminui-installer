<template>
    <StatusCard title="AdminUI is already installed" :icon="PackageCheck">
        <template #description> An AdminUI installation already exists on your system. </template>
        <p>
            If you haven't registered an admin account, click the button below. Otherwise, please use the System Update
            function in your AdminUI software to perform an update.
        </p>
        <template #actions>
            <Button size="lg" @click="navigate('register')">
                <UserPlus />
                Go to registration page
            </Button>
        </template>
    </StatusCard>
</template>

<script setup lang="ts">
import { PackageCheck, UserPlus } from "lucide-vue-next";
import StatusCard from "@/components/StatusCard.vue";
import { Button } from "@/components/ui/button";
import { navigate } from "@/composables/useNavigation";

defineOptions({ title: "Already installed" });
</script>
