@extends('templates.admin.index')


@section('title')
Lista de Dados 03 - Taxonomias - Automático
@endsection


@section('content-view')
<p>Exemplo de recursos de taxonomia automático por parâmetro na view auto_list.blade.<br>
Neste exemplo foram adicionados 2 termos na lista.</p>

@php
    use App\Services\FilesService;
    $files = \App\Services\FilesService::getList([
        'regs'=>5,
        'taxs_id'=>_GET('taxs_id'),
    ]);
    
    
    echo view('templates.ui.auto_list',[
        'list_id'=>'my_table_id1',
        'list_class'=>'table-striped',// table-hover
        'data'=>$files['files'],
        'columns'=>[
            'id'=>'ID',
            'file_title'=>'Título',
            'created_at'=>'Data',
        ],
        'options'=>[
            'checkbox'=>true,
            'select_type'=>2,
            'toolbar'=>true,
            'toolbar_menu'=>false,
            'remove'=>false,
            'reload'=>false,
            'footer'=>false,
            'search'=>false,
        ],
        'metabox'=>[
            'fit_table'=>true //para a tabela encaixar no metabox considerando o padding do metabox
        ],
        
        //taxonomias a serem usadas na lista
        //'taxs'=>[1=>true],       //OR
        'taxs'=>[
            //obs: a chave '1' e '2' é o term_id
            2=>[
                'show_list'=>'file_title',
                'button'=>['title'=>'Tags Especiais','icon'=>'fa-circle-o'],
            ],
            1=>[
                'show_list'=>'file_title',
                'button'=>['icon'=>'fa-tags'],
                'tax_form_type'=>'list'
            ],
        ],
    ])->render();//o render é para em caso de erro retornar a view

@endphp


<style>
.col-file_title{width:calc(80% - 38px - 30px);}
.col-created_at{width:20%;}
</style>

@endsection

