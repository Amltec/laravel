@extends('templates.admin.index')

@php
use \App\Services\TableContactsService;
$autodata_tmp = (object) [
    'cpf'=>'297.099.458-58',
    'name'=>'Aurelio de Morais',
    'dtnasc'=>'21/10/1982',
    'fieldname1'=>'Texto de exemplo'
];

@endphp


@section('title')
Template Autofield - Testando combinações de Formulários
@endsection


@section('content-view')
    <strong>Visualização dos parâmetros</strong>
    @php
        $param1 = [
            //exemplo por função
            function(){ return '<strong>Teste de linha escrita diretamente por um comando function(){}...</strong><br><br>'; },
            
            //exemplo 1
            TableContactsService::configAutoFields([
                'title'=>'Todos os campos',
                'autodata'=>$autodata_tmp
            ]),
            
            
            //exemplo 2
            TableContactsService::configAutoFields([
                'title'=>'Personalizando campos',
                'metabox'=>['is_border'=>false],//'header'=>false
                'fields'=>[
                    'tipo'=>true,'cpf'=>true,'cnpj'=>true,'name'=>true,'razaosocial'=>true,
                    'sexo'=>['value'=>'f','blank'=>false],  //valor inicial e retira a opção em branco
                    'status'=>[
                            'list'=>['x'=>'Status X','y'=>'Status Y', 'c'=>false],  //mescla as opções adicionando e removente
                            'label'=>'Super status',
                            'info_html'=>'<span style="color:blue">Obs: este campo teve seu Label e Options padrão alterados por parâmetro</span>'
                            ]
                ],
                'add_columns'=>[
                    'fieldname1'=>['label'=>'Campo adicional','maxlength'=>40,'class_group'=>'require','info_html'=>'Este campo foi criado adicionalmente ao padrão da tabela contatos']
                ],
                'autodata'=>$autodata_tmp,
                'prefix'=>'cad2*'
            ]),
            
            
            '<br><h3>Abaixo contém exemplos de templates adicionais</h3>',
            
            
            //apenas um campo adicional direto com o auto field
            [
                'layout_type'=>'horizontal',
                'autocolumns'=>[
                    'fieldname13'=>['type'=>'info','label'=>'Label','text'=>'Este é um campo adicional direto com o autofield'],
                ]
            ],
            
            
            //taxonomia
            'templates.ui.taxs_form'=>[
                'term_id'=>1,
                'box_is_collapse'=>false,
            ],
            
            
            
        ];
        
        dump($param1);
    @endphp


    <p>Testando combinações com autofied - Gerando formulário automático e variação de campos exemplos e opções.</p>
    @include('templates.ui.auto_groups',[
        'autogroups'=>$param1,
        'form'=>[
            'url'=>route('admin.app.post',['example','testSaveAuto']),
            'method'=>'post'
        ]
    ])
    
    
    
    


@endsection