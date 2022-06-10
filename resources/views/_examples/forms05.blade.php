@extends('templates.admin.index')


@section('title')
Formulários - Lista de blocos dinâmicos de campos
@endsection


@section('content-view')
@php
    //dados de exemplo para preenchimento do formulário
    $dataform = new StdClass;
    $dataform->phones_cliente=array(
        (object)[
            'id'=>1,
            'phone_ddi'=>'66',
            'phone_num'=>'1535242357',
            'phone_mark'=>'Opção XXX',
            'phone_type'=>'1',
            'phone_status'=>'a',
            'area_name'=>'clientes',
            'area_id'=>0,
        ],
        (object)[
            'id'=>2,
            'phone_ddi'=>'55',
            'phone_num'=>'15991086381',
            'phone_mark'=>'',
            'phone_type'=>'',
            'phone_status'=>'',
            'area_name'=>'clientes',
            'area_id'=>0,
        ]
    );
    
    /*
        //Opção 01 - adiciona o prefixo na var $dataform pela função TablePhonesService::adjusteData
        //$dataform->phones_cliente = \App\Services\TablePhonesService::adjusteData($dataform->phones_cliente,'cliente{N}*');
        
        //Opção 02 - incluído diretamente no parâmetro 'autodata' da função abaixo TablePhonesService::configAutoFields()
        //'autodata'=>$dataform,....
    */
    
    
    
    //variável para o autofield
    $var_fields= \App\Services\TablePhonesService::configAutoFields([
            'prefix'=>'cliente{N}*',
            'area_name'=>'cliente',
            'area_id'=>'1',
            'title'=>'Cadastro de vários telefones (bloco dinâmico mode=block)',
            'fields'=>[
                'type'=>true,'status'=>true,'num2'=>true
            ],
            //'metabox'=>false,
            'block_dinamic'=>[
                'mode'=>'block',
                'remove'=>['confirm'=>true,'ajax'=>route('admin.app.post',['example','testDelAuto'])],
                //'add'=>false,
            ],
            'autodata'=>$dataform->phones_cliente,
            
            //estes campos são de exemplos apenas para executar o teste pelo ExempleControler@testSaveAuto
            'add_columns'=>[
                //'field_custom_checkbox'=>['label'=>'Nome do cadastro','type'=>'checkbox','list'=>['a'=>'A','b'=>'B','c'=>'C']],
                //'field_custom_radio'=>['label'=>'Nome do cadastro','type'=>'radio','list'=>['a'=>'A','b'=>'B','c'=>'C']],
                
                '_tmp_controller_test'=>['type'=>'hidden','value'=>'TablePhonesService@save'],
                //'_tmp_table_id'=>['type'=>'hidden','value'=>'2'],
                '_tmp_table_param'=>['type'=>'hidden','value'=>json_encode([
                        'prefix'=>'cliente{N}*',
                    ])
                ]
            ]
    ],'all');
    
    
    
    $var_fields['form']=[
        'url_action'=>route('admin.app.post',['example','testSaveAuto']),
        'bt_save'=>true,
        'method'=>'post',
        
        //está desativado para ativar exemplo acima com 'autodata' diretamente pelo TablePhonesService::configAutoFields()
        //'autodata'=>\App\Services\TablePhonesService::adjusteData($dataform->phones_cliente,'cliente{N}*') //aqui considera como atualização de dados do form (input _method PUT)
    ];
    //$var_fields['autodata'] = $dataform->phones_cliente; //aqui não é considerado como atualização de dados
    //dd($var_fields);

@endphp

<h4>01 Template autofield com a tabela padrão de telefones - com opção de lista/adição dinâmica de blocos - </h4>

@php
echo '<strong>Variáveis informados no auto_fields:</strong>';
dump($var_fields);
@endphp

@include('templates.ui.auto_fields',$var_fields)

<br><br><br>






@php
if(false){
echo '<strong>Variáveis informados no auto_fields:</strong>';

$var_fields2 = array_merge(
        \App\Services\TablePhonesService::configAutoFields([
            'prefix'=>'cliente{N}*',
            'area_name'=>'cliente',
            'area_id'=>'1',
            'title'=>'02 Cadastro de vários telefones (bloco dinâmico mode=inline)',
            //'metabox'=>false,
            'block_dinamic'=>[
                'mode'=>'inline',
            ],
            
            //estes campos são de exemplos apenas para executar o teste pelo ExempleControler@testSaveAuto
            'add_columns'=>[
                '_tmp_controller_test'=>['type'=>'hidden','value'=>'TablePhonesService@save'],
                //'_tmp_table_id'=>['type'=>'hidden','value'=>'2'],
                '_tmp_table_param'=>['type'=>'hidden','value'=>json_encode([
                        'prefix'=>'cliente{N}*',
                    ])
                ]
            ]
        ],'phone2'),
        [
            'form'=>[
                'url_action'=>route('admin.app.post',['example','testSaveAuto']),
                'bt_save'=>true,
                
            ],
            'autodata'=>
                \App\Services\TablePhonesService::adjusteData([
                    (object)[
                        'id'=>1,
                        'phone_num'=>'15991086381',
                        'phone_mark'=>'Opção XX2',
                        'area_name'=>'clientes',
                        'area_id'=>0,
                    ],
                    (object)[
                        'id'=>2,
                        'phone_num'=>'15997840213',
                        'phone_mark'=>'Financeiro',
                        'area_name'=>'clientes',
                        'area_id'=>0,
                    ],
                ],'cliente{N}*')
        ]
    );


dump($var_fields2);
echo view ('templates.ui.auto_fields',$var_fields2);

}
@endphp




<br><br><br>

  


@endsection