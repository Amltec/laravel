@php
//**** variáveis globais do layout ****
$dashboard=array_merge([
    'navbar'=>true,
    'header'=>true,
    'bt_back'=>'Voltar',
    'route_back'=>urldecode(_GET('rd')),
    'padding'=>true,
    'menu_collapse'=>false,
    'single_page'=>false,
    'white_page'=>false,
    'grid_page'=>false,
],$dashboard??[]);

//configuração padrão para grid_page
if($dashboard['grid_page']){
    $dashboard['header']=false;
    $dashboard['menu_collapse']=true;
}

//exibe área lateral ao menu
$hasSection_menu_side = View::hasSection('menu_side');
if($hasSection_menu_side){
    $dashboard['menu_collapse']=true;
}



$prefix = Config::adminPrefix();

//Observações gerais ...
//1) lembrete: View::getSection('title', 'Home') = @yield('title', 'Home')

//chama a classe de controle do template admin
$adminClass=App::make('\\App\\Http\\Controllers\\AdminClass\\'. studly_case($prefix) .'Class');


//global vars
$userLogged = Auth::user();
$accountVar = Config::accountData();
//dd($accountVar);

if($adminClass->prefix=='super-admin'){
    $account_name = Config::data('title');
    $logo_icon = Config::data('logo_icon');
    $ctrl_updated_at = Config::data('updated_at');
}else{
    $account_name = data_get($accountVar->model,'account_name');
    $logo_icon = data_get($accountVar->data,'logo_icon', Config::data('logo_icon'));
    $ctrl_updated_at = data_get($accountVar->data,'updated_at', Config::data('updated_at'));
}


//inclusão automática de plugins adicionais
//....


@endphp

<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <title>{{ strip_tags(View::getSection('title_bar', View::getSection('title', 'Home')))  }} | Painel | {{config('app.name')}}</title>
    
    @include('templates.admin._inc.index--head')
    
    <!-- favicon -->
    <link rel="icon" href="{{$logo_icon.'?'. $ctrl_updated_at}}" sizes="32x32" />
    <link rel="icon" href="{{$logo_icon.'?'. $ctrl_updated_at}}" sizes="192x192" />
    
    {!! $adminClass::scriptsHeader() !!}
</head>

@if($dashboard['single_page'])
    <body class="hold-transition skin-black sidebar-mini{{Config::isSuperAdminPrefix()?' super-admin':''}}">
    @include('templates.admin._inc.index--alert_dev')
    @include('templates.admin.content-single')
    {!! Form::writeScripts(true);!!}
    @stack('bottom')
    </body>
    
@else
    <body class="hold-transition skin-black sidebar-mini{{ $dashboard['navbar']?' fixed':'' }}{{ $dashboard['menu_collapse']?' sidebar-collapse':'' }}{{Config::isSuperAdminPrefix()?' super-admin':''}}">
    @include('templates.admin._inc.index--alert_dev')

    <div class="wrapper">
        @include('templates.admin.header')
        
        @include('templates.admin.menuleft')
        
        @if($dashboard['grid_page'])
             @include('templates.admin.content-grid')
        @else
            @include('templates.admin.content')
            {{--@include('templates.admin.footer')--}}
        @endif
        
    </div>

    @php
        echo $adminClass::scriptsFooter();
        echo Form::writeScripts(true);
    @endphp
    @stack('bottom')
    @stack('bottom_once')

    </body>
@endif

</html>