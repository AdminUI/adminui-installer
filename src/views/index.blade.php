<!DOCTYPE html>
<html lang="en" class="w-full h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Fira Sans"', 'ui-sans-serif', 'system-ui', '-apple-system', 'BlinkMacSystemFont',
                            '"Segoe UI"',
                            'Roboto', '"Helvetica Neue"', 'Arial', '"Noto Sans"', 'sans-serif',
                            '"Apple Color Emoji"', '"Segoe UI Emoji"', '"Segoe UI Symbol"', '"Noto Color Emoji"'
                        ],
                    }
                }
            }
        }
    </script>
    <link
        href="https://fonts.googleapis.com/css2?family=Fira+Sans:ital,wght@0,100;0,300;0,400;0,700;0,900;1,400&display=swap"
        rel="stylesheet">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Install AdminUI</title>
</head>

<body class="w-full h-full font-sans text-white">
    <main
        class="w-full h-full flex justify-center items-center bg-no-repeat bg-gradient-to-br from-slate-800 to-indigo-900">
        <div class="p-8 shadow-lg shadow-black bg-slate-700/50 backdrop-blur rounded mb-8">
            <div class="flex gap-8">
                <div>
                    <svg id="Layer_1" data-name="Layer 1" class="w-40" xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 168.16 185.5">
                        <defs>
                            <style>
                                .aui-logo {
                                    fill: currentColor;
                                }

                                .aui-logo--path {
                                    fill: currentColor;
                                }

                            </style>
                        </defs>
                        <title>aui-logo-colour</title>
                        <path class="aui-logo--path"
                            d="M63,1.5c5.93.76,11.91,1.21,17.77,2.32,26.76,5.07,40,21.55,40.91,52.7.75,25.8.38,51.64.12,77.45-.09,9.22-4.18,16-14.65,15.81C97,149.6,93.31,143,93.07,133.91c-.24-9.31,0-18.63,0-28-8.46-1.67-14.35-5.54-14.26-14.81.09-9.1,5.7-13.27,14.43-15-1.48-16.3,4.36-35-12.61-46.31a33.38,33.38,0,0,0-37.53-.14C26.13,40.78,31.63,59.52,30.23,76,38.29,78.34,44,82.42,43.92,91.27c-.06,8.64-5.58,12.63-13.61,14.68,0,7.92,0,15.72,0,23.53,0,8.75-.1,17.85-11.21,20C7.62,151.6,2.87,144.54,1,134.5v-74c.51-36.28,17.93-56.8,55-59Z"
                            transform="translate(-1)" />
                        <path class="aui-logo--path"
                            d="M93,185.5c-42.07-6.2-47.16-33.88-46.92-70,.14-22.1.21-44.21,1.14-66.29.43-10.19,8.09-12.77,17.06-11.65,8.08,1,11.42,6.32,11.68,14,1,29.86-2.87,59.93,2.2,89.57,3.54,20.7,20.2,21.8,36.52,20.11,14.71-1.52,21.75-11.82,22.21-25.77.87-26.25.91-52.54,1.1-78.81,0-8.08,0-16.58,9.51-18.94,10.34-2.57,17.91,1.62,20.5,12.7v82c-1.74,28.39-15,47.21-44,53Z"
                            transform="translate(-1)" />
                        <path class="aui-logo--path"
                            d="M168,20.5c-3.81,7.08-9.33,11.29-17.73,9.64-5.8-1.14-10-4.5-11.23-10.64-1.73-8.75,2.65-14.21,10-18h8c5.05,2.28,9,5.67,11,11Z"
                            transform="translate(-1)" />
                        <circle class="aui-logo" cx="152.43" cy="15.72" r="15.22" />
                    </svg>
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
                        <div class="relative flex w-full flex-wrap items-stretch">
                            <span
                                class="z-10 h-full leading-snug font-normal absolute text-center text-slate-300 absolute bg-transparent rounded text-base items-center justify-center w-8 pl-3 py-3">
                                <svg style="w-6 h-6 mr-2" viewBox="0 0 24 24">
                                    <path fill="currentColor"
                                        d="M7 14C5.9 14 5 13.1 5 12S5.9 10 7 10 9 10.9 9 12 8.1 14 7 14M12.6 10C11.8 7.7 9.6 6 7 6C3.7 6 1 8.7 1 12S3.7 18 7 18C9.6 18 11.8 16.3 12.6 14H16V18H20V14H23V10H12.6Z" />
                                </svg>
                            </span>
                            <input v-model="key" type="text" placeholder="Your AdminUI Licence Key"
                                class="px-3 py-3 placeholder-slate-300 text-slate-600 relative bg-white bg-white rounded text-sm border border-slate-300 outline-none focus:outline-none focus:ring focus:ring-blue-400 w-full pl-10" />
                        </div>
                        <div class="text-red-300 text-sm opacity-0 transition-opacity px-2"
                            :class="{ 'opacity-100': error }">
                            @{{ error }}&nbsp;
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button
                            class="relative bg-blue-500 text-white active:bg-blue-800 font-bold uppercase text-sm px-6 py-3 rounded shadow hover:shadow-lg outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150 after:absolute after:inset-0 after:z-0 after:bg-current after:opacity-0 hover:after:opacity-10 after:transition-opacity"
                            :disabled="isInstalling"
                            :class="{
                                'pointer-events-none': isInstalling
                            }"
                            type="submit">
                            <div class="flex items-center transition-opacity"
                                :class="{
                                    'opacity-0': isInstalling
                                }">
                                <svg class="w-6 h-6 -ml-1 mr-2" viewBox="0 0 24 24">
                                    <path fill="currentColor" d="M5,20H19V18H5M19,9H15V3H9V9H5L12,16L19,9Z" />
                                </svg>
                                <span>Install</span>
                            </div>
                            <div class="flex justify-center items-center absolute pointer-events-none inset-0 opacity-0 transition-opacity"
                                :class="{
                                    'opacity-100': isInstalling
                                }">
                                <span
                                    class="animate-spin w-6 h-6 border-4 border-solid border-l-transparent border-r-transparent border-t-current border-b-current rounded-full"></span>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <script type="module">
        const jsonHeaders = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }

        import {
            createApp
        } from 'https://unpkg.com/petite-vue?module'
        createApp({
            key: "",
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
                    isInstalling = false;
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
                    isInstalling = false;
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
                    isInstalling = false;
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
                    isInstalling = false;
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



                console.log(this.log);

                this.isInstalling = false;
                /* .then(async (res) => ({
                    json: await res.json(),
                    ok: res.ok
                }))
                .then(res => {
                    if (res.ok === false) throw res.json;
                    console.log(res);
                })
                .catch(err => {
                    if (err?.errors?.key) {
                        this.error = err.errors.key[0];
                    }
                })
                .finally(() => (this.isInstalling = false)) */
            }
        }).mount()
    </script>
</body>

</html>
