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
    <title>{{ $title ?? 'AdminUI Installer' }}</title>
</head>

<body class="w-full h-full font-sans text-white relative bg-no-repeat bg-gradient-to-br from-slate-800 to-indigo-900">
    <div class="absolute inset-0 -z-1 grayscale opacity-40 bg-cover bg-norepeat overflow-hidden">
        <x-adminui-installer::background></x-adminui-installer::background>
    </div>
    <main class="w-full h-full flex justify-center items-center">
        <div class="p-8 shadow-lg shadow-black bg-slate-700/50 backdrop-blur rounded mb-8">
            {{ $slot }}
        </div>
    </main>
    {{ $scripts ?? '' }}
</body>

</html>
