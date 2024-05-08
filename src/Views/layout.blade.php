<!DOCTYPE html>
<html lang="en" class="h-full w-full">

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
                    },
                    colors: {
                        primary: "#dc2626",
                        panel: "#252529"
                    }
                }
            }
        }
    </script>
    <style>
        [v-cloak] {
            display: none;
        }
    </style>
    <link
        href="https://fonts.googleapis.com/css2?family=Fira+Sans:ital,wght@0,100;0,300;0,400;0,700;0,900;1,400&display=swap"
        rel="stylesheet">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title')</title>
</head>

<body class="relative h-full w-full bg-gradient-to-br from-red-500 to-red-800 bg-no-repeat font-sans text-white">
    <div class="-z-1 bg-norepeat pointer-events-none absolute inset-0 overflow-hidden bg-cover opacity-40 grayscale">
        <x-adminui-installer::background />
    </div>
    <main v-scope class="flex h-full w-full items-center justify-center">
        <div class="bg-panel mx-auto mb-8 max-w-full rounded p-8 shadow-lg shadow-black backdrop-blur">
            @yield('content')
        </div>
        <div v-cloak class="h-full overflow-auto bg-white text-black transition-all duration-500 ease-in-out"
            v-bind:class="{
                'w-0': !isInstalling && !installStarted,
                'w-1/2': isInstalling || installStarted,
            }">
            @yield('sidebar')
        </div>
    </main>
    @stack('scripts')
</body>

</html>
