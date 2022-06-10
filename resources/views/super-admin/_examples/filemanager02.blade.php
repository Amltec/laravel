@extends('templates.admin.index')


@section('title')
Gerenciador de Arquivos - Selação de arquivos dentro da Janela de Arquivos 
@endsection


@section('content-view')
Simula as varições com o template ui.files_list.blade.<br>
Os registros são capturados automaticamente pela view (pois não foi informado por um controller).<br>
Programado para adicionar registro usando a janela filenamager para upload (que é o próprio gerenciador de arquivos).<br>
<br><br>


@include('templates.ui.files_list',[
    'files_filter'=>[
        'regs'=>_GETNumber('regs')??5,
    ],
    'files_opt'=>[
        //'uploadComplete'=>'@function(v){console.log("***",v);}',    //custom js function
        //'uploadComplete'=>'reload',                                 //reload na página
        //'uploadSuccess'=>'@function(v){console.log("***",v);}',     //custom js function
        'uploadSuccess'=>'route_load',                                //dispara a rota 'load' ajax
        'fileszone'=>['maximize'=>'.j_files_list_zone'],
        
        //'class'=>'relative',
        //'list_class'=>'',
        
        'metabox'=>[
            'class'=>'j_files_list_zone'        //classe identificadora da zona de upload
        ],
        
        //informações adicionais para o upload
        'upload_mode'=>'filemanager',
        'upload_opt'=>[
            //adiciona informações de relacionamento com outras tabelas...
            'area_name'=>'test',
            'area_id'=>'111'
        ],
    ],
    'auto_list'=>[
        
        //exemplo de customização de rota da lista (opcional)
        'routes'=>[
            'click'=>function($reg){
                return [($reg->is_deleted?'javascript:alert("Este registro está #'. $reg->id .' excluído");void[0]':$reg->getUrl()), 'target'=>'_blank'];
            },
            
            //para carregamento ajax da lista
            //'load'=>route('admin.app.index','example').'/?name=listfiles_01&modeview='._GET('modeview').'&access='._GET('access').'&folder='._GET('folder').'&regs='._GET('regs'),
            //'load'=>route('admin.app.index','example').'/?'.rqWithQuery(['name'=>'listfiles_01','page'=>null]),//obs: esta função rqWithQuery() executa a mesma coisa da linha acima
            
            //para excluir um registro
            'remove'=>route('admin.file.remove'),
        ],
        
        //taxonomias a serem usadas na lista
        //'taxs'=>[1=>true],       //OR
        'taxs'=>[
            1=>[
                'show_list'=>'file_title',
                //'button'=>['title'=>'Texto'],
                //'tax_form_type'=>'set'
            ]
        ],
    ],
])



@endsection

