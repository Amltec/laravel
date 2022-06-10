@extends('templates.admin.index')

@section('title')
Tabela Padrão - E-mails
@endsection


@section('content-view')
    <p>Cadastro de E-mails (utilizando o autofied) - Valor 'area_name'='cliente'.</p>
    @include('templates.ui.auto_fields',array_merge(
        \App\Services\TableMailsService::configAutoFields([
            'prefix'=>'cliente*',
            'area_name'=>'cliente',
            'area_id'=>'1',
            'title'=>'Cadastro E-mails 1',
            
            
            //estes campos são de exemplos apenas para executar o teste pelo ExempleControler@testSaveAuto
            'add_columns'=>[
                '_tmp_controller_test'=>['type'=>'hidden','value'=>'TableMailsService@save'],
                '_tmp_table_id'=>['type'=>'hidden','value'=>'9'],
                '_tmp_table_param'=>['type'=>'hidden','value'=>json_encode([
                        'prefix'=>'cliente*',
                        //'required'=>['contact_cpf_cnpj','contact_sexo'],
                        //'duplicate'=>false
                    ])
                ]
            ]
        ],'all'),
        [
            'form'=>[
                'url_action'=>route('admin.app.post',['example','testSaveAuto']),
                'bt_save'=>true,
            ]
        ]
    ))
    
@endsection