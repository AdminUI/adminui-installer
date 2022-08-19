<x-adminui-installer::layout title="AdminUI Installer: Install">
    <div class="flex gap-8 max-w-xl">
        <div>
            <x-adminui-installer::logo></x-adminui-installer::logo>
        </div>
        <div class="text-white/90">
            <h1 class="text-3xl font-bold mb-4">Welcome to AdminUI</h1>
            <p class="text-white/70 mb-2">Your new Laravel-based CMS and Ecommerce framework</p>
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
                        <svg class="w-6 h-6 mr-2" viewBox="0 0 24 24">
                            <path fill="currentColor"
                                d="M7 14C5.9 14 5 13.1 5 12S5.9 10 7 10 9 10.9 9 12 8.1 14 7 14M12.6 10C11.8 7.7 9.6 6 7 6C3.7 6 1 8.7 1 12S3.7 18 7 18C9.6 18 11.8 16.3 12.6 14H16V18H20V14H23V10H12.6Z" />
                        </svg>
                    </x-slot:icon>
                </x-adminui-installer::input-text>
                <div class="text-red-300 text-sm opacity-0 transition-opacity px-2" :class="{ 'opacity-100': error }">
                    @{{ error }}&nbsp;
                </div>
            </div>
            <div class="flex justify-between items-center">
                <div v-if="installError" v-text="installError" class="text-red-300 max-w-sm"></div>
                <div v-else v-text="installMessage" class="text-white/80 max-w-sm animate-pulse"></div>
                <x-adminui-installer::button loading="isInstalling" type="submit">
                    <x-slot:icon>
                        <svg class="w-6 h-6 -ml-1 mr-2" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M5,20H19V18H5M19,9H15V3H9V9H5L12,16L19,9Z" />
                        </svg>
                    </x-slot:icon>
                    Install
                </x-adminui-installer::button>
            </div>
        </form>
    </div>

    <x-slot:scripts>
        <script type="module">
            const jsonHeaders = {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }

            import {
                createApp
            } from 'https://unpkg.com/petite-vue?module'
            createApp({
                key: "{{ config('adminui-installer.test_key') ?? '' }}",
                version: "",
                error: "",
                log: [],
                installMessage: "",
                installError: "",
                isInstalling: false,
                onError() {
                    this.isInstalling = false;
                    this.installError = this.log[this.log.length - 1] ?? "An error occurred!";
                    return false;
                },
                async onSubmit() {
                    this.error = this.installError = this.version = "";
                    this.isInstalling = true;
                    this.log = [];

                    /* ******************************************
                     * STEP ONE
                     ****************************************** */
                    this.installMessage = "Downloading install package...";
                    const stepOneResult = await fetch("{{ route('adminui.installer.one') }}", {
                        method: "POST",
                        headers: jsonHeaders,
                        body: JSON.stringify({
                            key: this.key
                        })
                    });
                    const stepOneJson = await stepOneResult.json();
                    if (stepOneJson?.log) this.log.push(...stepOneJson.log);
                    if (stepOneJson?.data?.version) this.version = stepOneJson.data.version;
                    if (stepOneJson.status !== "success") {
                        return this.onError();
                    }


                    /* ******************************************
                     * STEP TWO
                     ****************************************** */
                    this.installMessage = "Extracting AdminUI " + this.version " + package...";
                    const stepTwoResult = await fetch("{{ route('adminui.installer.two') }}", {
                        method: "POST",
                        headers: jsonHeaders,
                        body: JSON.stringify({
                            key: this.key
                        })
                    });
                    const stepTwoJson = await stepTwoResult.json();
                    if (stepTwoJson?.log) this.log.push(...stepTwoJson.log);
                    if (stepTwoJson.status !== "success") {
                        this.isInstalling = false;
                        return false;
                    }

                    /* ******************************************
                     * STEP THREE
                     ****************************************** */
                    this.installMessage = "Downloading system dependencies...";
                    const stepThreeResult = await fetch("{{ route('adminui.installer.three') }}", {
                        method: "POST",
                        headers: jsonHeaders,
                        body: JSON.stringify({
                            key: this.key,
                        })
                    });
                    const stepThreeJson = await stepThreeResult.json();
                    if (stepThreeJson?.log) this.log.push(...stepThreeJson.log);
                    if (stepThreeJson.status !== "success") {
                        this.isInstalling = false;
                        return false;
                    }

                    /* ******************************************
                     * STEP FOUR
                     ****************************************** */
                    this.installMessage = "Preparing database update...";
                    const stepFourResult = await fetch("{{ route('adminui.installer.four') }}", {
                        method: "POST",
                        headers: jsonHeaders,
                    });
                    const stepFourJson = await stepFourResult.json();
                    if (stepFourJson?.log) this.log.push(...stepFourJson.log);
                    if (stepFourJson.status !== "success") {
                        this.isInstalling = false;
                        return false;
                    }

                    /* ******************************************
                     * STEP FIVE
                     ****************************************** */
                    this.installMessage = "Updating database...";
                    const stepFiveResult = await fetch("{{ route('adminui.installer.five') }}", {
                        method: "POST",
                        headers: jsonHeaders,
                    });
                    const stepFiveJson = await stepFiveResult.json();
                    if (stepFiveJson?.log) this.log.push(...stepFiveJson.log);
                    if (stepFiveJson.status !== "success") {
                        this.isInstalling = false;
                        return false;
                    }

                    /* ******************************************
                     * STEP SIX
                     ****************************************** */
                    this.installMessage = "Installing AdminUI resources";
                    const stepSixResult = await fetch("{{ route('adminui.installer.six') }}", {
                        method: "POST",
                        headers: jsonHeaders,
                        body: JSON.stringify({
                            key: this.key,
                            version: this.version
                        })
                    });
                    const stepSixJson = await stepSixResult.json();
                    if (stepSixJson?.log) this.log.push(...stepSixJson.log);



                    /* ******************************************
                     * STEP SEVEN
                     ****************************************** */
                    this.installMessage = "Finishing installation";
                    const stepSevenResult = await fetch("{{ route('adminui.installer.seven') }}", {
                        method: "POST",
                        headers: jsonHeaders,
                        body: JSON.stringify({
                            key: this.key,
                            version: this.version
                        })
                    });
                    const stepSevenJson = await stepSevenResult.json();
                    if (stepSevenJson?.log) this.log.push(...stepSevenJson.log);

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
