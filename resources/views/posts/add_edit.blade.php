@extends('templates.admin.index')

@php
/*
    Variáveis esperadas:
        $post_type
        $post
        $prefix
        $area_name
        $area_id
        $post_model
        $thisClass
        $config
*/

use App\Services\PostsService;

$labels = $config['labels'];



@endphp

@section('title')
@php
    echo $post ? PostsService::labels($labels,'edit_post'). ' <span style="font-size:14px;margin-left:5px;" class="text-muted">#'.$post->id.'</span>' : PostsService::labels($labels,'add_post');
@endphp
@endsection

@section('content-view')
@php
    
    $configEdit = $config['edit'];
    $configEditDef = $config['edit_defaults'];
    
    if(!$post){//é cadastro
        //gera um objeto vazio
        $post = new StdClass;
        $table_columns = $post_model->getConnection()->getSchemaBuilder()->getColumnListing('posts');
        foreach($table_columns as $f){$post->$f = null;}//seta os campos com valores nulos
        unset($table_columns,$f);
    }//else //atualização
    
    
    $vars=get_defined_vars();
    
    
    
    //salvar e opções de status
    function metabox_save($vars){
        extract($vars);
        $params=[];
        $html='';
        
        //valores padrões
        if(!$post->post_content_type)$post->post_content_type = $configEditDef['content_type'];
        if(!$post->post_visibility)$post->post_visibility = $configEditDef['visibility'];
        if($post->published_at)$post->published_at = FormatUtility::dateFormat($post->published_at);
        
        
        if($configEdit['status']){
            $params['post_status']=['type'=>'select','label'=>'Status','list'=>[''=>''] + $thisClass::$post_status];
            $params['lnk_options']=function(){echo '<a href="#" onclick="blkSaveOptions();return false;">+ opções</a><hr style="margin:15px 0;">';};
        }
        
        if($configEdit['content_type']){
            $params['post_content_type']=['type'=>'select','label'=>'Tipo do Conteúdo','list'=>[''=>''] + $thisClass::$post_content_type, 'class_group'=>'j-blksave-options' ];
        }
        if($configEdit['visibility']){
            $level_list = \App\Services\UsersService::$levels;
            
            $params['post_visibility']=['type'=>'select','label'=>'Visibilidade','list'=>[''=>''] + $thisClass::$post_visibility, 'attr'=>'onchange="visibilityChange();"', 'class_group'=>'j-blksave-options' ];
            $params['post_pass']=['type'=>'password','label'=>'<small class="nostrong">Senha</small>','class_label'=>'margin-bottom-none','attr'=>'min="6" max="20"','class_group'=>'j-group-visibility j-blksave-options'];
            $params['user_level']=['type'=>'select','label'=>'<small class="nostrong">Nível de Acesso</small>','class_label'=>'margin-bottom-none','list'=>[''=>''] + $level_list,'class_group'=>'j-group-visibility j-blksave-options'];
        }
        if($configEdit['date'] && $post->id){
            $params['published_at']=['label'=>'Publicação','attr'=>'data-mask="99/99/9999 99:99"', 'class_group'=>'j-blksave-options'];
            Form::loadScript('inputmask');
        }
        if($params){
            $html .= view('templates.ui.auto_fields',['autocolumns'=>$params,'autodata'=>$post]);
        }
        
        //remover
        if($configEdit['remove'] && $post->id)$html.='<a href="#" class="btn no-padd-left text-danger" onclick="if(confirm(\'Tem certeza que deseja remover?\'))awBtnPostData({url:\''. route($prefix.'.app.remove','posts') .'\',data:{_method:\'DELETE\',action:\'trash\',id:'. $post->id .'},cb:function(r){ console.log(r);return false;if(r.success)window.location.reload(); }},this);return false;">Remover</a>';
        $html.='<button type="submit" class="pull-right btn btn-primary" style="min-width:100px;">Salvar</button><div class="clearfix"></div>';
        
        $html.=view('templates.components.alert-structure',['isclose'=>false]);
        echo view('templates.components.metabox',['title'=>'Publicar','content'=>$html,'is_border'=>false,'id'=>'metabox_save']);
    }
    
    
    //campo resumo
    function metabox_resume($vars){
        extract($vars);
        $html = view('templates.ui.auto_fields',[
            'autocolumns'=>[
                'post_resume'=>['type'=>'textarea','maxlength'=>1000,'auto_height'=>200,'resize'=>false],
            ],
            'autodata'=>$post,
        ]);
        echo view('templates.components.metabox',['title'=>'Resumo','content'=>$html,'is_border'=>false]);
    }            
    
    
    //opções gerais 2
    function metabox_options2($vars){
        extract($vars);
        $params=[];
        
        if($configEdit['parent']){
            //monta a lista de todos os posts em nível hierárcico
            $parent_list = PostsService::getList($post_type,[
                'area_name'=>$area_name,
                'list_format'=>'post_title',
                'hierarchy'=>true,
                'exclude'=>$post->id,
            ]);
            $params['post_parent']=['type'=>'select','label'=>'Post Pai','list'=>[''=>'(nenhum)']+$parent_list ];
        }
        
        if($configEdit['order']){
            if(!$post->post_order)$post->post_order=0;
            $params['post_order']=['label'=>'Ordem do Post','type'=>'number', 'attr'=>'min="0" style="width:100px;"'];
        }
        
        if($params){
            $html = view('templates.ui.auto_fields',['autocolumns'=>$params,'autodata'=>$post]);
            echo view('templates.components.metabox',['content'=>$html,'is_border'=>false]);
        }
    }
    
    
    //autor
    function metabox_author($vars){
        extract($vars);
        $params=[];
        
        $user_list = (new \App\Services\UsersService)->getListByAdmin(['account_id'=>$post->account_id, 'key_names'=>['Super Admins','Usuários da Conta'], 'key_empty'=>false]);
        $params['user_id']=['label'=>'Autor','type'=>'select', 'list'=>[''=>'']+$user_list];
        
        $html = view('templates.ui.auto_fields',['autocolumns'=>$params,'autodata'=>$post]);
        echo view('templates.components.metabox',['content'=>$html,'is_border'=>false]);
    }
    
    
    //categorias
    function metabox_taxs($taxs,$post){
        if($taxs)$termsList = \App\Models\Term::whereIn('id',$taxs)->orderByRaw("FIELD(id,". join(',',$taxs) .")")->get();
        
        if($taxs && $termsList->count()>0){
            foreach($termsList as $term){
                $taxs_start=$post->id ? $post->getTaxsData($term->id,'ids') : null;
                
                echo view('templates.ui.taxs_form',[
                    'id'=>'box_terms_'.$term->id,
                    'term_id'=>$term->id,
                    'is_collapse'=>true,
                    'show_icon'=>true,
                    'taxs_start'=>$taxs_start,
                    'metabox'=>['is_border'=>false],
                    'box_is_collapse'=>false,
                ]);
            }
        }
    }
      
    
    
    $fnc_form = function() use($vars){
        extract($vars);
        
        if($configEdit['name']){
            echo '<div style="position:absolute;right:18px;margin-top:-40px;">
                    <table><tr>
                        <td>
                            Link:
                        </td>
                        <td style="padding-left:5px;">
                            <strong>'. ($post->id ? $post->post_name : 'automático') .'</strong>
                        </td>
                        <td style="padding-left:5px;">
                            <a href="#" onclick="$(this).hide().next().show().focus();return false;" class="fa fa-pencil"></a>
                            <input type="text" name="post_name" value="'. $post->post_name .'" class="hiddenx form-control" style="height:28px;">
                        </td>
                    </tr></table>
                </div>';
        }
        
        echo '<div class="clearfix">';
            echo '<div class="post-content-left" id="post-content-left">';
                    
                    //**** campos principais ****
                    $params=[
                        'post_type'=>['type'=>'hidden','value'=>$post_type],
                        'area_name'=>['type'=>'hidden','value'=>$area_name],
                        'post_title'=>['type'=>'text','placeholder'=>'Título','require'=>true,'maxlength'=>500],
                        'post_content'=>['type'=>'editor','label'=>'','auto_height'=>true,'toolbar_fixed'=>true],
                    ];
                    if(!$configEdit['title'])unset($params['title']);
                    if(!$configEdit['content'])unset($params['content']);
                    
                    echo view('templates.ui.auto_fields',['autocolumns'=>$params,'autodata'=>$post]);
                    
                    if($configEdit['resume']){
                        metabox_resume($vars);//resumo
                    };
                    
                    if($configEdit['metaboxs']){
                        foreach($configEdit['metaboxs'] as $mb){
                            $method='get_metabox'. studly_case($mb);
                            if(method_exists($thisClass,$method))echo $thisClass->$method(['post'=>$post]);
                        }
                    }
            echo '</div>';
            
            
            
            echo '<div class="post-content-right" id="post-content-right"><div id="post-content-right-in">';
                    
                    metabox_save($vars); //publicar
                    metabox_options2($vars); //opções 2
                    if($configEdit['taxs'])metabox_taxs( is_array($configEdit['taxs'])?$configEdit['taxs']:$config['taxs'], $post ); //taxs
                    if($configEdit['user'] && $post->id)metabox_author($vars);
                    
                    
            echo '</div></div>';//.col-sm-3
             
             
        echo '</div>';//.rows
    };
    
    
    
    echo view('templates.ui.form',[
        'id'=>'form_post',
        'url'=> ($post->id?route($prefix.'.app.post',['posts','update',$post->id]):route($prefix.'.app.store','posts')),
        'url_back'=>route($prefix.'.app.index','posts'),
        'method'=>'post',
        'data_opt'=>[
            'focus'=>true,
            'onSuccess'=>'@postOnSuccess',
            'clearPageShow'=>true   //sempre limpa a página ao voltar
        ],
        'bt_save'=>false,
        'bt_back'=>false,
        'class'=>'form-no-padd hiddenx',
        'content'=>$fnc_form,
        'alert_msg'=>false,
    ]);
    
    
    
    Form::loadScript('sticky');
@endphp


<style>
.post-content-left{width:calc(100% - 290px);float:left;}
.post-content-right{width:290px;float:left;padding-left:20px;}
.alert-success{display:none !important;}

.j-visibility-fields{display:none;}
.j-blksave-options-hide{display:none;}

.form-group-post_pass,.form-group-user_level{margin-top:-16px;}

@if($post)
.form-group-post_name{display:none;}
@endif

</style>
<script>
function postCookie(v=null){//json v
    return awJCookie('post{{$post->id??'0'}}_edit',v);
};

$('#post-content-right-in').sticky({topSpacing:70});
function postOnSuccess(r){
    if(r.action_js=='refresh'){
        window.location.reload();
    }else if(r.action=='add'){
        window.location=String('{{route($prefix.'.app.get',['posts','edit',':id'])}}').replace(':id',r.id);
    };
};

function fileRelation(list_id,opt,area_name){
    awAjax({url:'{{ route($prefix.'.app.post',['posts','file_relation']) }}',processData:true,
        data:{post:{{$post->id??'false'}},files:opt.files,area_name:area_name},
        success(r){
            $('#'+list_id).trigger("load");
        },
        error(xhr){
            awModal({title:'Erro interno de servidor',html:xhr.responseText,msg_type:'danger','btSave':false});
        }
    });
};

@if($configEdit['visibility'])
function visibilityChange(){
    var o=$('[name=post_visibility]');
    var v=o.val();
    var fs=o.closest('.form-block-group').find('.j-group-visibility').addClass('j-visibility-fields');
    var s='';
    if(v=='s'){
        s='.form-group-post_pass';
    }else if(v=='u'){
        s='.form-group-user_level';
    }
    if(s)fs.filter(s).removeClass('j-visibility-fields').find(':input:visible:eq(0)').focus();
};
visibilityChange();
@endif

//exibe as opções do grupo de publicação
function blkSaveOptions(is_show){
    var o=$('#metabox_save');
    var fs=o.find('.j-blksave-options');
    var t=false;
    if(fs.eq(0).hasClass('j-blksave-options-hide') || is_show){//show
        fs.removeClass('j-blksave-options-hide');
        t=true;
    }else{//hide
        fs.addClass('j-blksave-options-hide');
    };
    postCookie({options_show:t});
};
blkSaveOptions(postCookie().options_show);

//exibe o form
$('#form_post').show();
</script>
@endsection