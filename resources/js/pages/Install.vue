<template>
    <div class="grid w-full gap-6 lg:grid-cols-5">
        <Card class="lg:col-span-3">
            <CardHeader>
                <CardTitle class="text-3xl">Welcome to AdminUI</CardTitle>
                <CardDescription class="text-base">
                    Your new Laravel-based CMS and e-commerce framework.
                </CardDescription>
            </CardHeader>
            <form @submit.prevent="onSubmit">
                <CardContent class="space-y-4">
                    <p class="text-muted-foreground">
                        To begin the one-click install, enter your licence key below and click install.
                    </p>
                    <div class="space-y-2">
                        <InputGroup>
                            <InputGroupAddon>
                                <KeyRound />
                            </InputGroupAddon>
                            <InputGroupInput
                                v-model="licenceKey"
                                placeholder="Your AdminUI licence key"
                                autocomplete="off"
                                :aria-invalid="!!error"
                            />
                        </InputGroup>
                        <p v-if="error" class="px-1 text-sm text-destructive">
                            {{ error }}
                        </p>
                    </div>
                </CardContent>
                <CardFooter class="mt-6 justify-end">
                    <Button type="submit" size="lg" :disabled="isInstalling || installComplete">
                        <Loader2 v-if="isInstalling" class="animate-spin" />
                        <Download v-else />
                        {{ status.saveKey ? "Continue installation" : "Install" }}
                    </Button>
                </CardFooter>
            </form>
        </Card>

        <Card class="lg:col-span-2">
            <CardHeader>
                <CardTitle>Installation progress</CardTitle>
                <CardDescription>Each step will update as it runs.</CardDescription>
            </CardHeader>
            <CardContent>
                <StepList :steps="steps" />
            </CardContent>
            <CardFooter v-if="installComplete" class="justify-end">
                <Button @click="navigate('register')">Register admin</Button>
            </CardFooter>
        </Card>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { Download, KeyRound, Loader2 } from "lucide-vue-next";
import StepList, { type Step } from "@/components/StepList.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { InputGroup, InputGroupAddon, InputGroupInput } from "@/components/ui/input-group";
import { navigate } from "@/composables/useNavigation";
import { runStep, type InstallStatus } from "@/composables/useInstaller";

defineOptions({ title: "Install" });

const MAX_LOG_LINES = 1000;

// INSTALL_TEST_KEY in .env pre-fills the key during development
const licenceKey = ref(document.getElementById("app")?.dataset.licenceKey ?? "");
const error = ref("");
const isInstalling = ref(false);
const running = ref<string | null>(null);
const failed = ref<string | null>(null);

// The controller renders its install status onto #app (data-status); each step is done once its key is truthy.
const app = document.getElementById("app");

const readStatus = (): InstallStatus => {
    try {
        return JSON.parse(app?.dataset.status ?? "{}") ?? {};
    } catch {
        return {};
    }
};

const baseUrl = (app?.dataset.baseUrl ?? window.location.pathname).replace(/\/$/, "");

const status = ref<InstallStatus>(readStatus());

interface StepDefinition {
    key: string;
    label: string;
    endpoint: string;
    /** Key in the status data holding the output of a previous run */
    logKey?: string;
    detail?: (s: InstallStatus) => string | undefined;
}

const stepDefinitions: StepDefinition[] = [
    { key: "saveKey", label: "Save licence key", endpoint: "save-key" },
    {
        key: "getLatestReleaseDetails",
        label: "Get latest release details",
        endpoint: "release-details",
        detail: (s) => (s.releaseDetails?.version ? `Found AdminUI ${s.releaseDetails.version}` : undefined),
    },
    { key: "downloadRelease", label: "Download release", endpoint: "download-release", detail: (s) => s.downloadStats },
    {
        key: "unpackRelease",
        label: "Unpack release",
        endpoint: "unpack-release",
        detail: (s) => (s.installSize ? `Unpacked size was ${s.installSize}` : undefined),
    },
    { key: "prepareDependencies", label: "Prepare dependencies", endpoint: "prepare-dependencies" },
    { key: "dependencies", label: "Update dependencies", endpoint: "dependencies", logKey: "composerLog" },
    {
        key: "publishResources",
        label: "Publish required resources",
        endpoint: "publish-resources",
        logKey: "publishResourcesLog",
    },
    { key: "runMigrations", label: "Run database migrations", endpoint: "run-migrations", logKey: "runMigrationsLog" },
    { key: "seedDatabase", label: "Seed database", endpoint: "seed-database", logKey: "seedDatabaseLog" },
];

// Live console output for steps run on this page; steps finished earlier fall back to the stored log.
const liveLogs = ref<Record<string, string[]>>({});

const logFor = (def: StepDefinition): string[] | undefined => {
    if (liveLogs.value[def.key]) return liveLogs.value[def.key];
    const stored = def.logKey ? status.value[def.logKey] : undefined;
    if (Array.isArray(stored)) return stored.map(String);
    return typeof stored === "string" && stored ? [stored] : undefined;
};

const steps = computed<Step[]>(() =>
    stepDefinitions.map((def) => ({
        key: def.key,
        label: def.label,
        status:
            running.value === def.key
                ? "loading"
                : failed.value === def.key
                  ? "error"
                  : status.value[def.key]
                    ? "done"
                    : "pending",
        detail: def.detail?.(status.value),
        log: logFor(def),
    })),
);

const installComplete = computed(() => status.value.installComplete === true);

const onSubmit = async () => {
    console.log("Here");
    if (isInstalling.value) return;

    if (!status.value.saveKey && !licenceKey.value.trim()) {
        error.value = "Enter your licence key to begin.";
        return;
    }

    error.value = "";
    failed.value = null;
    isInstalling.value = true;

    try {
        for (const def of stepDefinitions) {
            if (status.value[def.key]) continue;

            running.value = def.key;
            liveLogs.value[def.key] = [];

            status.value = await runStep(
                `${baseUrl}/${def.endpoint}`,
                def.key === "saveKey" ? { licence_key: licenceKey.value } : {},
                (line) => {
                    const lines = liveLogs.value[def.key];
                    lines.push(line);
                    if (lines.length > MAX_LOG_LINES) lines.splice(0, lines.length - MAX_LOG_LINES);
                },
            );
        }
    } catch (e) {
        failed.value = running.value;
        error.value = e instanceof Error ? e.message : "The installation failed.";
    } finally {
        running.value = null;
        isInstalling.value = false;
    }
};
</script>
