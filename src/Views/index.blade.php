<x-adminui-installer::layout title="AdminUI Installer: Install">
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
    <div class="mt-8" v-scope>
        <form id="installation-form" @submit.prevent="onSubmit">
            <div class="mb-3">
                <x-adminui-installer::input-text model="key" disabled="isInstalling"
                    placeholder="Your AdminUI Licence Key">
                    <x-slot:icon>
                        <svg class="mr-2 h-6 w-6" viewBox="0 0 24 24">
                            <path fill="currentColor"
                                d="M7 14C5.9 14 5 13.1 5 12S5.9 10 7 10 9 10.9 9 12 8.1 14 7 14M12.6 10C11.8 7.7 9.6 6 7 6C3.7 6 1 8.7 1 12S3.7 18 7 18C9.6 18 11.8 16.3 12.6 14H16V18H20V14H23V10H12.6Z" />
                        </svg>
                    </x-slot:icon>
                </x-adminui-installer::input-text>
                <div class="px-2 text-sm text-red-300 opacity-0 transition-opacity" :class="{ 'opacity-100': error }">
                    @{{ error }}&nbsp;
                </div>
            </div>
            <div class="flex items-center justify-between">
                <div v-if="installError" v-text="installError" class="max-w-sm text-red-300"></div>
                <div v-else v-text="installMessage" class="max-w-sm animate-pulse text-white/80"></div>
                <x-adminui-installer::button loading="isInstalling" type="submit">
                    <x-slot:icon>
                        <svg class="-ml-1 mr-2 h-6 w-6" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M5,20H19V18H5M19,9H15V3H9V9H5L12,16L19,9Z" />
                        </svg>
                    </x-slot:icon>
                    @if (false === $isMigrated)
                        Continue Install
                    @else
                        Install
                    @endif
                </x-adminui-installer::button>
            </div>
            @if (false === $isMigrated)
                <div class="mt-8 flex items-center rounded border-l-8 border-l-blue-500 bg-blue-500/40 p-4">
                    <div class="mr-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-500/80">
                            <svg class="h-6 w-6" viewBox="0 0 24 24">
                                <path fill="currentColor"
                                    d="M11,9H13V7H11M12,20C7.59,20 4,16.41 4,12C4,7.59 7.59,4 12,4C16.41,4 20,7.59 20,12C20,16.41 16.41,20 12,20M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M11,17H13V11H11V17Z" />
                            </svg>
                        </div>
                    </div>
                    <div>It seems that a previously attempted install failed. Use the above form to reattempt.</div>
                </div>
            @endif
        </form>
    </div>

    <x-slot:scripts>
        <script type="module">
            const jsonHeaders = {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
            const genericError = { status: 'error', error: 'Server error'};

            const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));

            import {
                createApp
            } from 'https://unpkg.com/petite-vue?module'
            createApp({
                key: "{{ config('adminui-installer.test_key') ?? '' }}",
                partialInstall: !!{{ false === $isMigrated }},
                version: "",
                error: "",
                log: [],
                installMessage: "",
                installError: "",
                isInstalling: false,
                onError(errorMessage = null) {
                    console.log(this.log);
                    this.isInstalling = false;
                    this.installError = errorMessage ?? "An error occurred!";
                    return false;
                },
                async onSubmit() {
                    this.error = this.installError = this.version = "";
                    if (this.partialInstall) {
                        this.version = window.localStorage.getItem('aui-installing-version') || "";
                    }
                    else {
                        window.localStorage.removeItem("aui-installing-version");
                    }
                    this.isInstalling = true;
                    this.log = [];

                    if (this.partialInstall !== true) {
                        /* ******************************************
                        * STEP ONE
                        ****************************************** */
                        this.installMessage = "Downloading install package...";
                        const stepOneResult = await fetch("{{ route('adminui.installer.download') }}", {
                            method: "POST",
                            headers: jsonHeaders,
                            body: JSON.stringify({
                                key: this.key
                            })
                        });
                        const stepOneJson = await stepOneResult.json().catch(err => {
                            return genericError;
                        });;
                        if (stepOneJson?.log) this.log.push(...stepOneJson.log);
                        if (stepOneJson?.data?.version) this.version = stepOneJson.data.version;
                        if (stepOneJson.status !== "success") {
                            return this.onError(stepOneJson.error);
                        }


                        /* ******************************************
                        * STEP TWO
                        ****************************************** */
                        this.installMessage = `Extracting AdminUI ${this.version} package...`;
                        const stepTwoResult = await fetch("{{ route('adminui.installer.extract') }}", {
                            method: "POST",
                            headers: jsonHeaders,
                            body: JSON.stringify({
                                key: this.key
                            })
                        });
                        const stepTwoJson = await stepTwoResult.json().catch(err => {
                            return genericError;
                        });
                        if (stepTwoJson?.log) this.log.push(...stepTwoJson.log);
                        if (stepTwoJson.status !== "success") {
                            return this.onError(stepTwoJson.error);
                        }

                        /* ******************************************
                        * STEP THREE
                        ****************************************** */
                        this.installMessage = "Downloading system dependencies...";
                        const stepThreeResult = await fetch("{{ route('adminui.installer.dependencies') }}", {
                            method: "POST",
                            headers: jsonHeaders,
                            body: JSON.stringify({
                                key: this.key,
                            })
                        });
                        const stepThreeJson = await stepThreeResult.json().catch(err => {
                            return genericError;
                        });
                        if (stepThreeJson?.log) this.log.push(...stepThreeJson.log);
                        if (stepThreeJson.status !== "success") {
                            return this.onError(stepThreeJson.error);
                        }
                    }

                    /* ******************************************
                     * STEP THREE POINT FIVE
                     ****************************************** */
                     this.installMessage = "Flushing Cache...";
                    const stepCacheResult = await fetch("{{ route('adminui.installer.clear-cache') }}", {
                        method: "POST",
                        headers: jsonHeaders,
                        body: JSON.stringify({
                            key: this.key,
                        })
                    });
                    const stepCacheJson = await stepCacheResult.json().catch(err => {
                        return genericError;
                    });
                    if (stepCacheJson?.log) this.log.push(...stepCacheJson.log);
                    if (stepCacheJson.status !== "success") {
                        return this.onError(stepCacheJson.error);
                    }

                    /* ******************************************
                     * STEP FOUR
                     ****************************************** */
                    this.installMessage = "Preparing database update...";
                    const stepFourResult = await fetch("{{ route('adminui.installer.base-publish') }}", {
                        method: "POST",
                        headers: jsonHeaders,
                    });
                    const stepFourJson = await stepFourResult.json().catch(err => {
                        return genericError;
                    });
                    if (stepFourJson?.log) this.log.push(...stepFourJson.log);
                    if (stepFourJson.status !== "success") {
                        return this.onError(stepFourJson.error);
                    }

                    /* ******************************************
                     * STEP FIVE
                     ****************************************** */
                    this.installMessage = "Updating database...";
                    const stepFiveResult = await fetch("{{ route('adminui.installer.base-migrations') }}", {
                        method: "POST",
                        headers: jsonHeaders,
                    });
                    const stepFiveJson = await stepFiveResult.json().catch(err => {
                        return genericError;
                    });
                    if (stepFiveJson?.log) this.log.push(...stepFiveJson.log);
                    if (stepFiveJson.status !== "success") {
                        return this.onError(stepFiveJson.error);
                    }

                    /* ******************************************
                     * STEP SIX
                     ****************************************** */
                    this.installMessage = "Installing AdminUI resources";
                    const stepSixResult = await fetch("{{ route('adminui.installer.publish') }}", {
                        method: "POST",
                        headers: jsonHeaders,
                        body: JSON.stringify({
                            key: this.key,
                            version: this.version
                        })
                    });
                    const stepSixJson = await stepSixResult.json().catch(err => {
                        return genericError;
                    });
                    if (stepSixJson?.log) this.log.push(...stepSixJson.log);
                    if (stepSixJson.status !== "success") {
                        return this.onError(stepSixJson.error);
                    }


                    /* ******************************************
                     * STEP SEVEN
                     ****************************************** */
                    this.installMessage = "Finishing installation";
                    await sleep(2000);
                    const stepSevenResult = await fetch("{{ route('adminui.installer.finish') }}", {
                        method: "POST",
                        headers: jsonHeaders,
                        body: JSON.stringify({
                            key: this.key,
                            version: this.version
                        })
                    });
                    const stepSevenJson = await stepSevenResult.json().catch(err => {
                        return genericError;
                    });
                    if (stepSevenJson?.log) this.log.push(...stepSevenJson.log);
                    if (stepSevenJson.status !== "success") {
                        return this.onError(stepSevenJson.error);
                    }

                    let count = 3,
                        setCountdown;
                    (setCountdown = () => {
                        this.installMessage =
                            `Complete. Redirecting to admin registration page in ${count}`;
                    })();

                    this.isInstalling = false;

                    let interval = setInterval(() => {
                        if (count <= 0) {
                            clearInterval(interval);
                            window.location.href = "{{ route('adminui.installer.register') }}";
                        }
                        setCountdown();
                        count--;
                    }, 1000)
                }
            }).mount()
        </script>
    </x-slot:scripts>

</x-adminui-installer::layout>
