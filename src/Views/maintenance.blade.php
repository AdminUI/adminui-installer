<x-adminui-installer::layout title="Site is not currently available">
    <div class="max-w-xl">
        <div class="flex justify-center mb-4 transition-colors duration-300">
            <x-adminui-installer::logo width="w-20"></x-adminui-installer::logo>
        </div>
        <p class="mt-8">
            This website is currently undergoing maintenance.<br />
            Please check back in a few minutes.
        </p>
    </div>

    <x-slot:scripts>
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
    </x-slot:scripts>
</x-adminui-installer::layout>
