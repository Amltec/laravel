<div class="content-wrapper" id="content-wrapper" style="margin-left:0;{!! $dashboard['white_page']?'background:#fff;':'' !!}">
    @hasSection('title')
    <section class="content-header">
        <h1>
            @yield('title', 'Título da Página')
            <small>@yield('description')</small>
        </h1>
    </section>
    @endif
    <section class="content container-fluid text-left {!! $dashboard['padding']?'':'no-padding' !!}">
        @yield('content-view')
    </section>
</div>