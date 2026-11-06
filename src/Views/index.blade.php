<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <title>AdminUI Installer</title>
    {!! \AdminUI\AdminUIInstaller\Services\AssetService::tags() !!}
    <style>
        [v-cloak] {
            display: none;
        }
    </style>
    <link
        href="https://fonts.googleapis.com/css2?family=Fira+Sans:ital,wght@0,100;0,300;0,400;0,700;0,900;1,400&display=swap"
        rel="stylesheet">
</head>

<body class="font-sans bg-black">
    <div id="app" data-page="{{ $page ?? 'install' }}" data-status="{{ json_encode($status) }}"
        data-registered="{{ ($page ?? null) === 'already-registered' || ($registered ?? false) ? '1' : '' }}"
        data-licence-key="{{ config('adminui-installer.test_key') ?? '' }}"
        data-base-url="{{ route('adminui.installer.index', [], false) }}"
        data-register-url="{{ route('adminui.installer.register.store', [], false) }}"
        data-admin-url="{{ Route::has('admin.home') ? route('admin.home') : '' }}"
        data-login-url="{{ Route::has('admin.login') ? route('admin.login') : '' }}"></div>
</body>

</html>
