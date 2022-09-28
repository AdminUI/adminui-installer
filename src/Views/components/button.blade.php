@props(['loading' => '', 'type' => 'button', 'tag' => 'button'])

<{{ $tag }}
    class="inline-flex relative bg-blue-500 text-white active:bg-blue-800 font-bold uppercase text-sm px-6 py-3 rounded shadow hover:shadow-lg outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150 after:absolute after:inset-0 after:z-0 after:bg-current after:opacity-0 hover:after:opacity-10 after:transition-opacity"
    :disabled="{{ $loading }}" :class="{
        'pointer-events-none': {{ $loading }}
    }"
    type="{{ $type }}" {{ $attributes }}>
    <div class="flex items-center transition-opacity" :class="{
        'opacity-0': {{ $loading }}
    }">
        {{ $icon }}
        <span>{{ $slot }}</span>
    </div>
    <div class="flex justify-center items-center absolute pointer-events-none inset-0 opacity-0 transition-opacity"
        :class="{
            'opacity-100': {{ $loading }}
        }">
        <span
            class="animate-spin w-6 h-6 border-4 border-solid border-l-transparent border-r-transparent border-t-current border-b-current rounded-full"></span>
    </div>
    </{{ $tag }}>
