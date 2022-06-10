@extends('templates.admin.index')


@section('title')
Gerenciador de Arquivos - Upload direto na Janela de Arquivos 
@endsection


@section('content-view')
Simula as variações com o template ui.files_list.blade.<br>
Os registros são capturados automaticamente pela view (pois não foi informado por um controller).<br>
Programado para adicionar registro via ajax após o upload.<br>
<br><br>


@include('templates.ui.files_list',[
    'controller'=>'files',
    'files_filter'=>[
        'regs'=>_GETNumber('regs')??5,
        'area_name'=>_GET('area_name'),
        'area_id'=>_GET('area_id'),
        'private'=>true,
    ],
    'files_opt'=>[
        
        //'uploadComplete'=>'@function(v){console.log("***",v);}',    //custom js function
        //'uploadComplete'=>'reload',                                 //reload na página
        //'uploadSuccess'=>'@function(v){console.log("***",v);}',     //custom js function
        'uploadSuccess'=>'route_load',                                //dispara a rota 'load' ajax
        'fileszone'=>['maximize'=>'.j_files_list_zone'],
        
        //'class'=>'relative',
        //'list_class'=>'',
        
        //'columns_show'=>'file_title,created_at,file_size,status',
        
        'metabox'=>[
            'class'=>'j_files_list_zone'        //classe identificadora da zona de upload
        ],
        
        //informações adicionais para o upload
        'upload_opt'=>[
            //adiciona informações de relacionamento com outras tabelas...
            'area_name'=>'test',
            'area_id'=>'111'
        ],
        
        'edit_data'=>[
            'area_name'=>'test','area_id'=>111,   //neste exemplo não é necessário, pois estes mesmos parâmetros foram informados no final desta configuração
            //'status'=>false,
        ],
    ],
    'auto_list'=>[
        'options'=>[
            'allow_trash'=>false,       //desativa a lixeira
        ],
        //exemplo de customização de rota da lista (opcional)
        'routes'=>[
            'click'=>function($reg){
                return [($reg->is_deleted?'javascript:alert("Este registro está #'. $reg->id .' excluído");void[0]':$reg->getUrl()), 'target'=>'_blank'];
            },
            
            //para carregamento ajax da lista
            'load'=>route('super-admin.app.index','example').'/?name=listfiles_01&modeview='._GET('modeview').'&access='._GET('access').'&folder='._GET('folder').'&regs='._GET('regs'),
            //'load'=>route('super-admin.app.index','example').'/?'.\App\Utilities\HtmlUtility::rqArr(['name'=>'listfiles_01','page'=>null]),//obs: esta função executa a mesma coisa da linha acima
            
            //para excluir um registro
            'remove'=>route('super-admin.file.remove','files'),                   //remove o arquivo
            //'remove'=>route('super-admin.app.post',['files','remove_relation']),    //remove a relação do arquivo
            
            //rota para edição (opcional)
            //'edit'=>'...',
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
        
        
        'row_opt'=>[
            'class'=>function($reg){ return 'myclass-'.$reg->id; },
            'actions'=>function($reg){  },
        ],
    ],
    
    'area_name'=>'test', 'area_id'=>111, //area_name e area_id padrões
])



@endsection

