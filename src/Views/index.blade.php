@extends('adminui-installer::layout')

@section('title')
    AdminUI Installer
@stop

@section('content')
    <div class="flex max-w-xl gap-8">
        <div>
            <x-adminui-installer::logo></x-adminui-installer::logo>
        </div>
        <div class="text-white/90">
            <h1 class="mb-4 text-3xl font-bold">Welcome to AdminUI</h1>
            <p class="mb-2 text-white/70">Your new Laravel-based CMS and Ecommerce framework</p>
            <p class="text-white/70">To begin our one-click install, simply enter your licence key in the input below
                and then click the install button.</p>
        </div>
    </div>
    <div class="mt-8">
        <form id="installation-form" @submit.prevent="onSubmit">
            <div class="mb-3">
                <x-adminui-installer::input-text model="key" placeholder="Your AdminUI Licence Key">
                    <x-slot:icon>
                        <svg class="mr-2 h-6 w-6" viewBox="0 0 24 24">
                            <path fill="currentColor"
                                d="M7 14C5.9 14 5 13.1 5 12S5.9 10 7 10 9 10.9 9 12 8.1 14 7 14M12.6 10C11.8 7.7 9.6 6 7 6C3.7 6 1 8.7 1 12S3.7 18 7 18C9.6 18 11.8 16.3 12.6 14H16V18H20V14H23V10H12.6Z" />
                        </svg>
                    </x-slot:icon>
                </x-adminui-installer::input-text>
                <div class="px-2 text-sm text-red-300 opacity-0 transition-opacity" :class="{ 'opacity-100': error }">
                    ${ error }&nbsp;
                </div>
            </div>
            <div class="flex items-center justify-end">

                <x-adminui-installer::button loading="isInstalling" type="submit" disabled="status.installComplete">
                    <x-slot:icon>
                        <svg class="-ml-1 mr-2 h-6 w-6" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M5,20H19V18H5M19,9H15V3H9V9H5L12,16L19,9Z" />
                        </svg>
                    </x-slot:icon>
                    <span v-if="!status.saveKey">Install</span>
                    <span v-else>Continue Installation</span>
                </x-adminui-installer::button>
            </div>
        </form>
    </div>
@stop

@section('sidebar')
    <div class="flex min-h-full w-full justify-center py-4">
        <div class="w-3/4">
            <h1 class="mb-8 text-center text-lg font-black uppercase">Installation Progress</h1>
            <ul>
                <x-adminui-installer::step-status key="saveKey" loading-text="Saving licence key"
                    done-text="Licence key saved" />

                <x-adminui-installer::step-status key="getLatestReleaseDetails"
                    loading-text="Getting latest release details"
                    done-text="Found AdminUI ${status.releaseDetails?.version}" />

                <x-adminui-installer::step-status key="downloadRelease"
                    loading-text="Dowloading version ${status.releaseDetails?.version}"
                    done-text="${status.downloadStats}" />

                <x-adminui-installer::step-status key="unpackRelease"
                    loading-text="Unpacking version ${status.releaseDetails?.version}"
                    done-text="Unpacked size was ${status.installSize}" />

                <x-adminui-installer::step-status key="prepareDependencies" loading-text="Preparing dependencies"
                    done-text="Ready to update dependencies" />

                <x-adminui-installer::step-status key="dependencies" loading-text="Updating dependenices"
                    done-text="Dependencies updated">
                    <x-slot:append>
                        <button class="rounded bg-blue-600 px-2 uppercase text-white"
                            v-on:click="showComposerLog = !showComposerLog">
                            <span v-if="showComposerLog">Hide Log</span>
                            <span v-else>Show Log</span>
                        </button>
                    </x-slot>
                    <x-slot:footer>
                        <div class="grid w-full transition-all duration-500 ease-in-out"
                            v-bind:style="{
                    'grid-template-rows': showComposerLog ? '1fr' : '0fr'
                }">
                            <div class="overflow-hidden">
                                <code class="bg-panel relative block rounded px-2 py-1 text-xs text-white">
                                    <pre class="max-w-full overflow-hidden whitespace-pre-wrap">${ status.composerLog }</pre>
                                </code>
                            </div>
                        </div>
                    </x-slot>
                </x-adminui-installer::step-status>

                <x-adminui-installer::step-status key="publishResources" loading-text="Publishing required resources"
                    done-text="Published">
                    <x-slot:append>
                        <button class="rounded bg-blue-600 px-2 uppercase text-white"
                            v-on:click="showPublishResourcesLog = !showPublishResourcesLog">
                            <span v-if="showPublishResourcesLog">Hide Log</span>
                            <span v-else>Show Log</span>
                        </button>
                    </x-slot>
                    <x-slot:footer>
                        <div class="grid w-full transition-all duration-500 ease-in-out"
                            v-bind:style="{
                    'grid-template-rows': showPublishResourcesLog ? '1fr' : '0fr'
                }">
                            <div class="overflow-hidden">
                                <code class="bg-panel relative block rounded px-2 py-1 text-xs text-white">
                                    <pre class="max-w-full overflow-hidden whitespace-pre-wrap">${ status.publishResourcesLog }</pre>
                                </code>
                            </div>
                        </div>
                    </x-slot>
                </x-adminui-installer::step-status>

                <x-adminui-installer::step-status key="runMigrations" loading-text="Running database migrations"
                    done-text="Migrations run">
                    <x-slot:append>
                        <button class="rounded bg-blue-600 px-2 uppercase text-white"
                            v-on:click="showRunMigrationsLog = !showRunMigrationsLog">
                            <span v-if="showRunMigrationsLog">Hide Log</span>
                            <span v-else>Show Log</span>
                        </button>
                    </x-slot>
                    <x-slot:footer>
                        <div class="grid w-full transition-all duration-500 ease-in-out"
                            v-bind:style="{
                    'grid-template-rows': showRunMigrationsLog ? '1fr' : '0fr'
                }">
                            <div class="overflow-hidden">
                                <code class="bg-panel relative block rounded px-2 py-1 text-xs text-white">
                                    <pre class="max-w-full overflow-hidden whitespace-pre-wrap">${ status.runMigrationsLog }</pre>
                                </code>
                            </div>
                        </div>
                    </x-slot>
                </x-adminui-installer::step-status>

                <x-adminui-installer::step-status key="seedDatabase" loading-text="Seeding database"
                    done-text="Database seeded" />

                <div v-if="status.installComplete === true" class="flex justify-end pt-12">
                    <x-adminui-installer::button tag="a" href="{{ route('adminui.installer.register') }}">Register
                        Admin</x-adminui-installer::button>
                </div>

            </ul>
        </div>
    </div>
@stop

@push('scripts')
    <script type="module" defer>
        import {
            createApp
        } from 'https://unpkg.com/petite-vue@0.4.1/dist/petite-vue.es.js?module';

        const request = async (url, data = {}, config = {}) => {
            const result = await fetch(url, {
                method: config.method ?? "POST",
                body: JSON.stringify(data),
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
            if (result.ok === false) {
                throw new Error(result.statusText);
            }
            return await result.json();
        }

        const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));

        createApp({
            $delimiters: ['${', '}'],
            key: "{{ config('adminui-installer.test_key') ?? '' }}",
            error: "",
            status: @json($status),
            showComposerLog: false,
            showPublishResourcesLog: false,
            showRunMigrationsLog: false,
            showSeedDatabaseLog: false,
            installError: "",
            isInstalling: false,
            get installStarted() {
                return Object.values(this.status).some(line => !!line);
            },
            onError(errorMessage = null) {
                this.isInstalling = false;
                this.installError = errorMessage ?? "An error occurred!";
                return false;
            },
            async onSubmit() {
                this.isInstalling = true;
                try {
                    if (!this.status.saveKey) {
                        this.status.saveKey = "loading";
                        const stepOne = await request("{{ route('adminui.installer.save-key') }}", {
                            licence_key: this.key
                        });
                        this.status = stepOne.status;
                    }

                    if (!this.status.getLatestReleaseDetails) {
                        this.status.getLatestReleaseDetails = "loading";
                        const result = await request("{{ route('adminui.installer.release-details') }}");
                        this.status = result.status;
                    }

                    if (!this.status.downloadRelease) {
                        this.status.downloadRelease = "loading";
                        const result = await request("{{ route('adminui.installer.download-release') }}");
                        this.status = result.status;
                    }

                    if (!this.status.unpackRelease) {
                        this.status.unpackRelease = "loading";
                        const result = await request("{{ route('adminui.installer.unpack-release') }}");
                        this.status = result.status;
                    }

                    if (!this.status.prepareDependencies) {
                        this.status.prepareDependencies = "loading";
                        const result = await request("{{ route('adminui.installer.prepare-dependencies') }}");
                        this.status = result.status;
                    }

                    if (!this.status.dependencies) {
                        this.status.dependencies = "loading";
                        const result = await request("{{ route('adminui.installer.dependencies') }}");
                        this.status = result.status;
                    }

                    if (!this.status.publishResources) {
                        this.status.publishResources = "loading";
                        const result = await request("{{ route('adminui.installer.publish-resources') }}");
                        this.status = result.status;
                    }

                    if (!this.status.runMigrations) {
                        this.status.runMigrations = "loading";
                        const result = await request("{{ route('adminui.installer.run-migrations') }}");
                        this.status = result.status;
                    }

                    if (!this.status.seedDatabase) {
                        this.status.seedDatabase = "loading";
                        const result = await request("{{ route('adminui.installer.seed-database') }}");
                        this.status = result.status;
                    }
                } catch (err) {
                    this.error = err;
                }

                setTimeout(() => {
                    this.isInstalling = false;
                }, 2000)
            },
        }).mount();
    </script>
@endpush
