@extends('templates.admin.index')

@section('title')
Tabela Padrão - Endereços
@endsection


@section('content-view')
    <p>Cadastro de Endereços (utilizando o autofied) - Valor 'area_name'='endereco'.</p>
    @include('templates.ui.auto_fields',array_merge(
        \App\Services\TableAddressesService::configAutoFields([
            'prefix'=>'endereco*',
            'area_name'=>'endereco',
            'area_id'=>'1',
            'title'=>'Cadastro de Endereços',
            
            
            //estes campos são de exemplos apenas para executar o teste pelo ExempleControler@testSaveAuto
            'add_columns'=>[
                '_tmp_controller_test'=>['type'=>'hidden','value'=>'TableAddressesService@save'],
                //'_tmp_table_id'=>['type'=>'hidden','value'=>'2'],
                '_tmp_table_param'=>['type'=>'hidden','value'=>json_encode([
                        'prefix'=>'endereco*',
                        //'required'=>['address_end','address_num'],
                    ])
                ]
            ]
        ]),
        [
            'form'=>[
                'url_action'=>route('admin.app.post',['example','testSaveAuto']),
                'bt_save'=>true,
            ]
        ]
    ))
    
@endsection