<template>
    <Card class="w-full max-w-xl">
        <CardHeader>
            <CardTitle class="text-2xl">Create your admin account</CardTitle>
            <CardDescription>
                AdminUI has been successfully installed. Enter your details below to create an admin account.
            </CardDescription>
        </CardHeader>
        <form @submit.prevent="submit">
            <CardContent class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <InputGroup>
                        <InputGroupAddon><User /></InputGroupAddon>
                        <InputGroupInput
                            v-model="form.first_name"
                            placeholder="First name"
                            autocomplete="given-name"
                            :disabled="isLoading"
                            :aria-invalid="!!errors.first_name"
                        />
                    </InputGroup>
                    <p v-if="errors.first_name" class="px-1 text-sm text-destructive">
                        {{ errors.first_name }}
                    </p>
                </div>
                <div class="space-y-1.5">
                    <InputGroup>
                        <InputGroupAddon><User /></InputGroupAddon>
                        <InputGroupInput
                            v-model="form.last_name"
                            placeholder="Last name"
                            autocomplete="family-name"
                            :disabled="isLoading"
                            :aria-invalid="!!errors.last_name"
                        />
                    </InputGroup>
                    <p v-if="errors.last_name" class="px-1 text-sm text-destructive">
                        {{ errors.last_name }}
                    </p>
                </div>
                <div class="space-y-1.5 sm:col-span-2">
                    <InputGroup>
                        <InputGroupAddon><Building2 /></InputGroupAddon>
                        <InputGroupInput
                            v-model="form.company"
                            placeholder="Company name"
                            autocomplete="organization"
                            :disabled="isLoading"
                            :aria-invalid="!!errors.company"
                        />
                    </InputGroup>
                    <p v-if="errors.company" class="px-1 text-sm text-destructive">
                        {{ errors.company }}
                    </p>
                </div>
                <div class="space-y-1.5 sm:col-span-2">
                    <InputGroup>
                        <InputGroupAddon><Mail /></InputGroupAddon>
                        <InputGroupInput
                            v-model="form.email"
                            type="email"
                            placeholder="Email address"
                            autocomplete="email"
                            :disabled="isLoading"
                            :aria-invalid="!!errors.email"
                        />
                    </InputGroup>
                    <p v-if="errors.email" class="px-1 text-sm text-destructive">
                        {{ errors.email }}
                    </p>
                </div>
                <div class="space-y-1.5 sm:col-span-2">
                    <InputGroup>
                        <InputGroupAddon><Lock /></InputGroupAddon>
                        <InputGroupInput
                            v-model="form.password"
                            type="password"
                            placeholder="Password"
                            autocomplete="new-password"
                            :disabled="isLoading"
                            :aria-invalid="!!errors.password"
                        />
                    </InputGroup>
                    <p v-if="errors.password" class="px-1 text-sm text-destructive">
                        {{ errors.password }}
                    </p>
                </div>
                <div class="space-y-1.5 sm:col-span-2">
                    <InputGroup>
                        <InputGroupAddon><KeyRound /></InputGroupAddon>
                        <InputGroupInput
                            v-model="form.password_confirmation"
                            type="password"
                            placeholder="Confirm password"
                            autocomplete="new-password"
                            :disabled="isLoading"
                            :aria-invalid="!!errors.password_confirmation"
                        />
                    </InputGroup>
                    <p v-if="errors.password_confirmation" class="px-1 text-sm text-destructive">
                        {{ errors.password_confirmation }}
                    </p>
                </div>
            </CardContent>
            <CardFooter class="mt-6 items-center justify-between gap-4">
                <p class="text-sm" :class="hasError ? 'text-destructive' : 'text-muted-foreground'" role="status">
                    {{ message }}
                </p>
                <Button type="submit" size="lg" :disabled="isLoading || isRegistered">
                    <UserPlus />
                    Register
                </Button>
            </CardFooter>
        </form>
    </Card>
</template>

<script setup lang="ts">
import { Building2, KeyRound, Lock, Mail, User, UserPlus } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { InputGroup, InputGroupAddon, InputGroupInput } from "@/components/ui/input-group";
import { getInstallerConfig } from "@/composables/useInstallerConfig";
import { useRegistration } from "@/composables/useRegistration";

defineOptions({ title: "Register" });

const { registerUrl, adminUrl } = getInstallerConfig();
const { form, errors, isLoading, isRegistered, message, hasError, submit } = useRegistration({ registerUrl, adminUrl });
</script>
