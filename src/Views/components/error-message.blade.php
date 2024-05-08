@props(['model' => ''])

<div {!! $model ? 'v-text="' . $model . '"' : '' !!} class="error-message mb-1 flex h-6 items-center px-2 text-sm text-red-300"></div>
