@extends('adminui-installer::layout')

@section('title')
    Error connecting to Database | AdminUI Installer
@stop

@section('content')
    <div class="max-w-xl">
        <div class="flex justify-center mb-4 transition-colors duration-300">
            <x-adminui-installer::logo width="w-20"></x-adminui-installer::logo>
        </div>
        <p class="mb-4">
            To install AdminUI you will need to connect a database.<br />
            Please update your .env file with the correct database credentials.
        </p>
        <p class="mb-4">
            Once you have updated the .env file please revisit/reload this page.
        </p>
        <p class="mb-4">
            If you are struggling with your setup, please contact us via the <a class="text-primary font-bold"
                href="https://adminui.co.uk" target="_blank">AdminUI website</a>.
        <p class="mb-4">
            We offer basic installation from only &pound;49.99.
        </p>
    </div>
@stop
