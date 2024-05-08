@extends('adminui-installer::layout')

@section('title')
    Already Registered | AdminUI Installer
@stop

@section('content')
    <div class="max-w-xl">
        <div class="mb-4 flex justify-center transition-colors duration-300">
            <x-adminui-installer::logo width="w-20"></x-adminui-installer::logo>
        </div>
        <p class="mb-2">An admin account has already been setup for AdminUI.</p>
        <p>Please use this to login to AdminUI and then create
            additional admin accounts from there.</p>
    </div>
@stop
