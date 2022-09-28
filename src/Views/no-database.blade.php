<x-adminui-installer::layout title="AdminUI Installer: Installation already exists">
    <div class="max-w-xl">
        <div class="flex justify-center mb-4 transition-colors duration-300">
            <x-adminui-installer::logo width="w-20"></x-adminui-installer::logo>
        </div>
        <p>
            To install AdminUI you will need to connect a database.<br />
            Please update your .env file with the correct database credentials.
        </p>
        <p class="mt-8">
            Once you have updated the .env file please revisit/reload this page.
        </p>
        <p class="mt-8">
            If you are struggling with your setup, please contact us via the <a href="https://adminui.co.uk"
                target="_blank">AdminUI website</a>.
        <p class="mt-8">
            We offer basic installation from only &pound;49.99.
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
