@component('mail::layout')
    {{-- Header --}}
    @slot ('header')
        @component('mail::header', ['url' => config('app.url')])
            {{ config('app.name') }}
        @endcomponent
    @endslot

    @php
        if(isset($html)){
            echo $html;
        }elseif(isset($text)){
            echo htmlspecialchars($text);
        }
    @endphp
    
    {{-- Subcopy --}}
    @slot('subcopy')
        @component('mail::subcopy')
        <!-- subcopy -->
        @endcomponent
    @endslot

    {{-- Footer --}}
    @slot ('footer')
        @component('mail::footer')
            © {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')
        @endcomponent
    @endslot
@endcomponent