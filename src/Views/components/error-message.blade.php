@props(['model' => ''])

<div {!! $model ? 'v-text="' . $model . '"' : '' !!} class="error-message text-red-300 text-sm h-6 flex items-center px-2 mb-1"></div>
