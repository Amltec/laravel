@extends('templates.admin.index')


@section('title')
Accordions
@endsection


@section('content-view')

<h3>Tabs</h3>
@include('templates.ui.accordion',[
    'data'=>[
        'aa'=>['title'=>'Tab A','content'=>'aaaaa','badge'=>'123','badge_color'=>'green'],
        'bb'=>['title'=>'Tab B','content'=>'bbbb','active'=>true],
        'cc'=>['title'=>'Tab C','content'=>function(){
            echo '**';
            return 'ccc';
        }],
        'dd'=>['title'=>'Tab D','content'=>'ddddd','class'=>'tab_dd','class_li'=>'li_dd','icon'=>'fa-edit'],
        'ee'=>['title'=>'Tab E','content'=>'Tab com conteúdo na direita','right'=>'<span class="text-muted">Texto na Direita</span>'],
        
        'ff'=>['title'=>'Tab F','content'=>'Tab com conteúdo na direita','right'=>function(){ echo view('templates.components.button',['title'=>'Botão','size'=>'xs','color'=>'info']); }],
        'gg'=>['title'=>'Tab G','content'=>'Tab com menu na direita','right'=>function(){
            echo view('templates.components.button',['title'=>false,'icon'=>'fa-gear','class_menu'=>'dropdown-menu-right','color'=>'link',
                'sub'=>[
                        '1'=>'Menu 1',
                        '2'=>'Menu 2',
                        '3'=>'Menu 3',
                    ]
            ]);
         }],
        'hh'=>['title'=>'Tab H','content'=>'Tab com menu na direita','right'=>function(){
            echo '<div class="btn-group"><a href="#" data-toggle="dropdown">Abrir menu com link 3 <span class="caret"></span></a>',
                view('templates.components.menu',['class_menu'=>'dropdown-menu-right','sub'=>[
                        '1'=>'Menu 1',
                        '2'=>'Menu 2',
                        '3'=>'Menu 3',
                    ]
                ]),
                '</div>';
         }],
    ],
    'class'=>'myclass',
    'id'=>'myid',
    'attr'=>'data-a="b"'
])


@endsection