@extends('templates.admin.index')


@section('title')
Lista de Anexos
@endsection


@section('content-view')
Padrão de configuração para lista de anexos com o template ui.files_list.blade.<br>
<br>

Lista compacta<br>
@include('templates.ui.attachment_list',[
    'area_name'=>'test',
    'area_id'=>111, //area_name e area_id padrões
    'files_opt'=>[
        'list_compact'=>true,
        'columns_show'=>'file_title,created_at,file_size,status',
    ]
])

Lista normal<br>
@include('templates.ui.attachment_list',[
    'area_name'=>'test',
    'area_id'=>111, //area_name e area_id padrões
])




@endsection

