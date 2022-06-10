@extends('templates.admin.index')


@section('title')
Tabs
@endsection


@section('content-view')

<h3>Tabs</h3>
@include('templates.ui.tab',[
    'data'=>[
        'aa'=>['title'=>'Tab A','content'=>'aaaaa','badge'=>'123', 'badge_color'=>'green'],
        'bb'=>['title'=>'Tab B','content'=>'bbbb','active'=>true],
        'cc'=>['title'=>'Tab C','content'=>function(){
            echo '**';
            return 'ccc';
        }],
        'dd'=>['title'=>'Tab D','content'=>'ddddd','class'=>'tab_dd','class_li'=>'li_dd','icon'=>'fa-edit'],
        'gg'=>['title'=>'Tab G','content'=>'ggg','class'=>'tab_dd','class_li'=>'li_dd','icon'=>'fa-edit','disabled'=>true],
        
        'tab_menu'=>['title'=>'Tab Menu',
            'menu'=>[
                'a'=>'Opção A',
                'b'=>'Opção B',
                'c'=>'Opção C',
                'd'=>['title'=>'Opção D - Link','link'=>'http://www.google.com.br'],
                'e'=>['title'=>'Opção E - Attr (onclick)','attr'=>'onclick="window.open(\'http://www.google.com.br\');return false;"'],
            ],
        ],
        'ee'=>['title'=>'','content'=>'Somente ícone no tab','icon'=>'fa-refresh'],
        'opt_right'=>['title'=>'Tab Right','content'=>'Content right','class_li'=>'pull-right','icon'=>'fa-gear'],
    ],
    'class'=>'myclass',
    'id'=>'myid',
    'attr'=>'data-a="b"'
])

<br><br>

<h3>Tabs com título</h3>
@include('templates.ui.tab',[
    'data'=>[
        'aa'=>['title'=>'Tab A','content'=>'aaaaa'],
        'bb'=>['title'=>'Tab B','content'=>'bbbb','active'=>true],
        'cc'=>['title'=>'Tab C','content'=>'ccc'],
    ],
    'title'=>'Título',
    'icon'=>'fa-th'
])


@endsection