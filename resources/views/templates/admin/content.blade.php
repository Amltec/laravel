<div class="content-wrapper" id="content-wrapper">
    @if($hasSection_menu_side)
        <aside class="second-sidebar scrollmin" id="second-sidebar">
            @yield('menu_side')
        </aside>
    @endif
    
    @if($dashboard['header'])
    <section class="content-header">
        @php
            if($dashboard['bt_back'] && $dashboard['route_back']){
                //echo '<a href="'. ($dashboard['route_back']==':back' ? URL::previous() : $dashboard['route_back']) .'" class="btn btn-default pull-right">'. ($dashboard['bt_back']==true?'Voltar':$dashboard['bt_back']) .'</a>';
            }
        @endphp
        @hasSection('title')
            <h1 class='pull-left'>
                @yield('title', 'Título da Página')
                <small>@yield('description')</small>
            </h1>
        @endif
        <div class="pull-right last-child-margin-r-0">
            @yield('toolbar-header')
            @php
            if($dashboard['bt_back'] && $dashboard['route_back']){
                echo '<a href="'. ($dashboard['route_back']==':back' ? URL::previous() : $dashboard['route_back']) .'" class="btn btn-default margin-r-10">'. ($dashboard['bt_back']==true?'Voltar':$dashboard['bt_back']) .'</a>';
            }
            @endphp
        </div>
        <div class="clearfix"></div>

        {{--
        <ol class="breadcrumb">
          <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
          <li class="active">Here</li>
        </ol>
        --}}
    </section>
    @endif
    
    <section class="content container-fluid text-left {!! $dashboard['padding']?'':'no-padding' !!}">@yield('content-view')</section>
</div>

@if($hasSection_menu_side)
<style>
.second-sidebar{width:250px;height:calc(100% - {{$dashboard['navbar']?'50':'0'}}px);position:absolute;top:50px;margin-left:-250px;overflow:auto;background:#F9F9F9;box-shadow:0.46875rem 0 2.1875rem rgb(4 9 20 / 3%), 0.9375rem 0 1.40625rem rgb(4 9 20 / 3%), 0.25rem 0 0.53125rem rgb(4 9 20 / 5%), 0.125rem 0 0.1875rem rgb(4 9 20 / 3%);}
.second-sidebar > div{height:100%;min-height:100%;}
.content-wrapper{padding-left:250px !important;}
</style>
@endif
