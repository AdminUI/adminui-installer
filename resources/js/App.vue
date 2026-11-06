<template>
    <InstallerLayout>
        <component :is="component" />
    </InstallerLayout>
</template>

<script setup lang="ts">
import { watchEffect } from "vue";
import InstallerLayout from "@/layouts/InstallerLayout.vue";
import { useNavigation } from "@/composables/useNavigation";

const { component } = useNavigation();

watchEffect(() => {
    const title = (component.value as { title?: string }).title;
    document.title = title ? `${title} | AdminUI Installer` : "AdminUI Installer";
});
</script>
