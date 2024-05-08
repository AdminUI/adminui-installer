@extends('adminui-installer::layout')

@section('title')
    AdminUI not installed | AdminUI Installer
@stop

@section('content')
    <div class="max-w-xl">
        <div class="mb-4 flex justify-center transition-colors duration-300">
            <x-adminui-installer::logo width="w-20"></x-adminui-installer::logo>
        </div>
        <p>An AdminUI installation was not detected. You must install AdminUI before registering your admin account.</p>
        <div class="mt-8 flex justify-end">
            <x-adminui-installer::button tag="a" loading="isLoading" href="{{ route('adminui.installer.index') }}">
                <x-slot:icon>
                    <svg class="-ml-1 mr-2 h-6 w-6" viewBox="0 0 24 24">
                        <path fill="currentColor" d="M5,20H19V18H5M19,9H15V3H9V9H5L12,16L19,9Z" />
                    </svg>
                </x-slot:icon>
                Go to Install Page
            </x-adminui-installer::button>
        </div>
    </div>
@stop

@push('scripts')
    <script type="module">
        const jsonHeaders = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }

        import {
            createApp
        } from 'https://unpkg.com/petite-vue?module';

        createApp({
            isLoading: false
        }).mount();
    </script>
@endpush
