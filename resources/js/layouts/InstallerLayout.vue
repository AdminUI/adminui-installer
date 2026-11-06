<template>
    <!-- `dark` scopes the dark shadcn tokens to the installer -->
    <div class="dark relative isolate flex min-h-svh flex-col overflow-hidden bg-background text-foreground">
        <!-- Decorative background -->
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
            <div
                class="absolute -top-40 left-1/2 size-[48rem] -translate-x-1/2 rounded-full bg-primary/25 blur-[140px]"
            />
            <div class="absolute -right-40 bottom-0 size-[32rem] rounded-full bg-primary/10 blur-[120px]" />
            <div
                class="absolute inset-0 bg-[linear-gradient(to_right,rgb(255_255_255/0.04)_1px,transparent_1px),linear-gradient(to_bottom,rgb(255_255_255/0.04)_1px,transparent_1px)] [mask-image:radial-gradient(ellipse_at_top,black,transparent_70%)] bg-[size:48px_48px]"
            />
        </div>

        <header class="mx-auto flex w-full max-w-6xl items-center gap-3 px-6 py-6">
            <Logo class="h-9 w-auto text-primary" />
            <span class="text-lg font-semibold tracking-tight">AdminUI</span>
            <span class="ml-1 rounded-full border border-border px-2.5 py-0.5 text-xs text-muted-foreground">
                Installer
            </span>
        </header>

        <main class="mx-auto flex w-full max-w-6xl flex-1 items-center justify-center px-6 py-8">
            <slot />
        </main>

        <footer class="mx-auto w-full max-w-6xl px-6 py-6 text-sm text-muted-foreground">
            <a
                href="https://adminui.co.uk"
                target="_blank"
                rel="noopener"
                class="transition-colors hover:text-foreground"
            >
                adminui.co.uk
            </a>
        </footer>
    </div>
</template>

<script setup lang="ts">
import Logo from "@/components/Logo.vue";
</script>
