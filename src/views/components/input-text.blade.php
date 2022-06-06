@props(['model' => '', 'disabled' => '', 'placeholder' => 'Enter here'])

<div class="relative flex w-full flex-wrap items-stretch">
    <span
        class="z-10 h-full leading-snug font-normal absolute text-center text-slate-300 absolute bg-transparent rounded text-base items-center justify-center w-8 pl-3 py-3">
        {{ $icon ?? '' }}
    </span>
    <input {!! $model ? 'v-model="' . $model . '"' : '' !!} type="text" placeholder="{{ $placeholder }}" {!! isset($disabled) ? ':disabled="' . $disabled . '"' : '' !!}
        class="px-3 py-3 placeholder-slate-300 text-slate-600 relative bg-white bg-white rounded text-sm border border-slate-300 outline-none focus:outline-none focus:ring focus:ring-blue-400 w-full pl-10" />
</div>
