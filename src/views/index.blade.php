<x-adminui-installer::layout title="AdminUI Installer: Install">
    <div class="flex gap-8">
        <div>
            <x-adminui-installer::logo></x-adminui-installer::logo>
        </div>
        <div class="text-white/90">
            <h1 class="text-3xl font-bold">Welcome to AdminUI</h1>
            <p class="text-white/70">Some description about the project.</p>
            <p class="text-white/70">Enter your licence key in the input below to install</p>
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
            <div class="flex justify-end">
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
                isInstalling: false,
                async onSubmit() {
                    this.error = "";
                    this.isInstalling = true;
                    this.version = "";
                    this.log = [];

                    /* ******************************************
                     * STEP ONE
                     ****************************************** */
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
                        this.isInstalling = false;
                        return false;
                    }


                    /* ******************************************
                     * STEP TWO
                     ****************************************** */
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
                    const stepFiveResult = await fetch("{{ route('adminui.installer.five') }}", {
                        method: "POST",
                        headers: jsonHeaders,
                        body: JSON.stringify({
                            key: this.key,
                            version: this.version
                        })
                    });
                    const stepFiveJson = await stepFiveResult.json();
                    if (stepFiveJson?.log) this.log.push(...stepFiveJson.log);



                    /* ******************************************
                     * STEP SIX
                     ****************************************** */
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

                    this.isInstalling = false;
                    window.location.reload("{{ route('adminui.installer.register') }}");
                }
            }).mount()
        </script>
    </x-slot:scripts>

</x-adminui-installer::layout>
