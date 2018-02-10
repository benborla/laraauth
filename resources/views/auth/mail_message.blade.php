@component('auth::mail_layout')
    {{-- Header --}}
    @slot('header')
        @component('auth::mail_header', ['url' => config('app.url')])
            {{ config('app.name') }}
        @endcomponent
    @endslot

    {{-- Body --}}
    {{ $slot }}

    {{-- Subcopy --}}
    @isset($subcopy)
        @slot('subcopy')
            @component('auth::mail_subcopy')
                {{ $subcopy }}
            @endcomponent
        @endslot
    @endisset

    {{-- Footer --}}
    @slot('footer')
        @component('auth::mail_footer')
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        @endcomponent
    @endslot
@endcomponent
