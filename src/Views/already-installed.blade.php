@extends('adminui-installer::layout')

@section('title')
    Already Installed | AdminUI Installer
@stop

@section('content')
    <div class="max-w-xl">
        <div class="mb-4 flex justify-center transition-colors duration-300">
            <x-adminui-installer::logo width="w-20"></x-adminui-installer::logo>
        </div>
        <p>An AdminUI installation already exists on your system. If you haven't registered an admin account, click the
            button below. Otherwise, please use the System Update function in your AdminUI software to perform an
            update.</p>
        <div class="mt-8 flex justify-end">
            <x-adminui-installer::button tag="a" loading="isLoading" href="{{ route('adminui.installer.register') }}">
                <x-slot:icon>
                    <svg class="-ml-1 mr-2 h-6 w-6" viewBox="0 0 24 24">
                        <path fill="currentColor"
                            d="M6 8C6 5.79 7.79 4 10 4S14 5.79 14 8 12.21 12 10 12 6 10.21 6 8M10 14C5.58 14 2 15.79 2 18V20H13.09C13.04 19.67 13 19.34 13 19C13 17.36 13.66 15.87 14.74 14.78C13.41 14.29 11.78 14 10 14M19 15L16 18H18V22H20V18H22L19 15Z" />
                    </svg>
                </x-slot:icon>
                Go to Registration Page
            </x-adminui-installer::button>
        </div>
    </div>
@stop
