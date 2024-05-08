<span {{ $attributes->merge([
    'class' => 'step-loader border-b-primary border-x-white border-t-white',
]) }}></span>

@push('scripts')
    <style>
        .step-loader {
            width: 28px;
            height: 28px;
            border-width: 4px;
            border-style: solid;
            border-radius: 50%;
            display: inline-block;
            box-sizing: border-box;
            animation: rotation 1s linear infinite;
        }

        @keyframes rotation {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
@endpush
