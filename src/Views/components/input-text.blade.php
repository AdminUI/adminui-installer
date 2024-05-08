@props(['model' => '', 'disabled' => 'false', 'placeholder' => 'Enter here', 'type' => 'text'])

<div class='relative flex h-12 w-full rounded border border-slate-300 bg-white focus-within:outline-none focus-within:ring focus-within:ring-blue-400'
    v-bind:class="{
    'opacity-50': {{ $disabled }}
}">
    <div
        class="ml-3 flex h-full w-8 items-center justify-center bg-transparent text-center text-base font-normal leading-snug text-slate-300">
        {{ $icon ?? '' }}
    </div>
    <input {!! $model ? 'v-model="' . $model . '"' : '' !!} placeholder="{{ $placeholder }}" type="{{ $type }}"
        class="relative h-full w-full text-sm text-slate-600 placeholder-slate-300 outline-none" />
</div>
