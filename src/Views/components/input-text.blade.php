@props(['model' => '', 'disabled' => '', 'placeholder' => 'Enter here', 'type' => 'text'])

<div
    class="relative flex w-full h-12 bg-white rounded border border-slate-300 focus-within:outline-none focus-within:ring focus-within:ring-blue-400">
    <div
        class="flex w-8 h-full ml-3 leading-snug font-normal text-center text-slate-300 bg-transparent text-base items-center justify-center">
        {{ $icon ?? '' }}
    </div>
    <input {!! $model ? 'v-model="' . $model . '"' : '' !!} placeholder="{{ $placeholder }}" :disabled="{{ $disabled }}"
        type="{{ $type }}"
        class="w-full h-full placeholder-slate-300 text-slate-600 relative text-sm outline-none" />
</div>
