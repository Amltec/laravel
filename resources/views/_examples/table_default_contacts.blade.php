@extends('templates.admin.index')

@section('title')
Tabela Padrão - Contatos
@endsection


@section('content-view')
@php
    //dados de exemplo para preenchimento do formulário
    $dataform = (object)[
        'id'=>1,
        'contact_tipo'=>'f',
        'contact_cpf_cnpj'=>'297.099.458.58',
        'contact_name'=>'Aurelio de Morais',
        'contact_alias'=>'Aurl',
        'contact_rg_ie'=>'34819405-5',
        'contact_sexo'=>'m',
        'contact_dtnasc'=>'1982-10-21',
        'contact_status'=>'c',
    ];
    //adiciona o prefixo na var $dataform
    //$dataform = \App\Services\TableContactsService::adjusteData($dataform,'fornecedor*');
    //dd($dataform);
@endphp



    <p>Cadastro de contatos (utilizando o autofied) - Valor 'area_name'='fornecedor'.</p>
    @include('templates.ui.auto_fields',array_merge(
        \App\Services\TableContactsService::configAutoFields([
            'prefix'=>'fornecedor*',
            'area_name'=>'fornecedor',
            'area_id'=>'1',
            'title'=>'Cadastro de Fornecedor',
            'autodata'=>$dataform,
            
            
            //estes campos são de exemplos apenas para executar o teste pelo ExempleControler@testSaveAuto
            'add_columns'=>[
                '_tmp_controller_test'=>['type'=>'hidden','value'=>'TableContactsService@save'],
                '_tmp_table_id'=>['type'=>'hidden','value'=>'2'],
                '_tmp_table_param'=>['type'=>'hidden','value'=>json_encode([
                        'prefix'=>'fornecedor*',
                        'required'=>['contact_cpf_cnpj','contact_sexo'],
                        'duplicate'=>false
                    ])
                ]
            ]
        ]),
        [
            'form'=>[
                'url_action'=>route('admin.app.post',['example','testSaveAuto']),
                'bt_save'=>true,
            ],
            //'autodata'=>$dataform
        ]
    ))
    
@endsection