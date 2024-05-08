@extends('adminui-installer::layout')

@section('title')
    Site is not currently available
@stop

@section('content')

    <div class="max-w-xl">
        <div class="mb-4 flex justify-center transition-colors duration-300">
            <x-adminui-installer::logo width="w-20"></x-adminui-installer::logo>
        </div>
        <p class="mt-8">
            This website is currently undergoing maintenance.<br />
            Please check back in a few minutes.
        </p>
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
