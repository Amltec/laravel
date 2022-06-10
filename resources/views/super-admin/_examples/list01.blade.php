@extends('templates.admin.index')


@section('title')
Lista de Dados 01
@endsection


@section('content-view')
<p><strog>Explorando todos os recursos</strog> - Tabela de dados usando o template auto_list.blade.</p>

@php
    use App\Utilities\FormatUtility;
    use App\Services\FilesService;
    $files = \App\Services\FilesService::getList([
        'regs'=>_GETNumber('regs')??10,
        'is_trash'=>_GET('is_trash')=='s'??false,
        'search'=>_GET('filter_q'),
    ]);
    
    //cria uma coluna adicional para simular agrupamento de dados
    foreach($files['files'] as $file){
        $file->date_group = date("F \/ Y",strtotime($file->created_at));
    }
@endphp


@include('templates.ui.auto_list',[
    'list_id'=>'my_table_id1',
    'list_class'=>'table-striped',// table-hover
    
    'data'=>$files['files'],
    'columns'=>[
        'id'=>'ID',
        'file_title'=>'Título',
        'file_size'=>[
                'Tamanho',
                'value'=>function($val,$reg=null){ return FormatUtility::bytesFormat($reg ? $reg->file_size : $val); },
                'calc_total'=>function($val,$reg){return $val+=$reg->file_size;} //função de cálculo de total e subtotal
            ],
        'folder'=>[
            'Pasta',
            'calc_total'=>function($val,$reg){ $val++;return $val; },
         ],
        'created_at'=>'Data',
        'date_group'=>'Data2',//Custom column
    ],
    'columns_show'=>_GET('columns_show'),
    'options'=>[
        'collapse'=>true,
        'checkbox'=>true,
        'select_type'=>2,
        //'header'=>true,
        'pagin'=>true,
        //'total'=>true,
        //'subtotal'=>true,
        'confirm_remove'=>true,
        'toolbar'=>true,
        'columns_sel'=>true
    ],
    'routes'=>[
        'click'=>function($reg){return ($reg->__lock_del?'#':'my-custom-tmp-page-test/'.$reg->id.'/');},
        'collapse'=>route('admin.app.index',['example']).'/?name=html',
        'remove'=>route('admin.app.remove',['example']),
    ],
    'field_group'=>'date_group',
    'field_click'=>'file_title',
    'row_opt'=>[
        //'class'=>function($reg){ return 'myclass'.$reg->id; },
        'lock_del'=>[22],
    ],
    'metabox'=>[
        'title'=>'Minha lista de dados',
        //'is_padding'=>false,
        //'fit_table'=>true
    ],
    'toolbar_menus'=>[
        'sep',
        'custom1'=>['title'=>'Custom Option 01','icon'=>'fa-edit'],
        'custom2'=>['title'=>'Custom Option 02','icon'=>'fa-folder'],
        'custom3'=>['title'=>'Custom Option 02','checkbox'=>true],
    ],
    'toolbar_buttons'=>[
        ['title'=> 'Botão 1','color'=>'primary'],
        ['icon'=>'fa-list','title'=>false, 'color'=>'info','alt'=>'Botão com menu', 
            'sub'=>['1'=>'Menu 1','2'=>['title'=>'Menu 2','checkbox'=>true],'3'=>'Menu 3',
                '4'=>[
                    'title'=>'www','class_li'=>'pull-left',
                    'sub'=>[
                            'custom1'=>['title'=>'Custom Option 01','icon'=>'fa-edit'],
                            'custom2'=>['title'=>'Custom Option 02','icon'=>'fa-folder'],
                            'custom3'=>['title'=>'Custom Option 02','checkbox'=>true],
                    ]
                ]
            ]
        ],
        //'html',
    ],
    //'html_after'=>function(){return 'wqewqewqXXX';},
])

<br>
<strong>Eventos</strong><br>
<a href="#" onclick="$('#my_table_id1').trigger('select');return false;">Selecionar Todos</a> | 
<a href="#" onclick="$('#my_table_id1').trigger('select',{id:25,select:true});return false;">Selecionar linha id 25: true</a> | 
<a href="#" onclick="$('#my_table_id1').trigger('collapse',{id:25,show:true});return false;">Colapse linha id 25: true</a> | 
<a href="#" onclick="$('#my_table_id1').trigger('collapse',{id:25,show:false});return false;">Colapse linha id 25: false</a> | 
<a href="#" onclick="$('#my_table_id1').trigger('remove',{id:25});return false;">Remove linha id 25</a> | 
<a href="#" onclick="$('#my_table_id1').trigger('remove',{id:25,confirm:'xxxx'});return false;">Remove linha id 25 + custom msg</a> | 
<a href="#" onclick="$('#my_table_id1').trigger('remove',{id:25,restore:true});return false;">Restaura linha id 25</a> | 
<a href="#" onclick="$('#my_table_id1').trigger('remove',{id:25,destroy:true});return false;">Remove definitivamente linha id 25</a> | 
<a href="#" onclick="$('#my_table_id1').trigger('remove');return false;">Remove selecionados</a> | 
<a href="#" onclick="console.log($('#my_table_id1').triggerHandler('get_select','obj'));return false;">Get Selected Rows Objs</a> | 
<a href="#" onclick="console.log($('#my_table_id1').triggerHandler('get_select'));return false;">Get Selected Rows IDs</a> | 
<a href="#" onclick="console.log($('#my_table_id1').data('options'));return false;">Get Data Options</a> | 

@endsection

