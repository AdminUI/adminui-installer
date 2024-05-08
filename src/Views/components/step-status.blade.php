@props(['key' => '', 'loadingText' => '', 'doneText' => ''])

<li v-if="status.{{ $key }}" class="flex flex-wrap items-center">
    <div v-if="status.{{ $key }} === 'loading'" class="flex h-9 items-center pl-0.5">
        <x-adminui-installer::loader class="mr-2" />
        <span class="animate-pulse">{{ $loadingText }}</span>
    </div>
    <div v-else class="flex h-9 w-full items-center">
        <x-adminui-installer::icon-check class="mr-2 w-8 text-green-600" />
        {{ $doneText }}
        @if (!empty($append))
            <div class="grow"></div>
            <div>{{ $append ?? '' }}</div>
        @endif
    </div>
    {{ $footer ?? '' }}
</li>
