@extends('templates.admin.index')

@section('title')
Configurações do Sistema
@endsection




@section('content-view')
@php
/*
Variáveis esperadas
    $configData
    $userLogged
*/


$configData['logo_main']=$configData['logo_main']??'';
$configData['logo_icon']=$configData['logo_icon']??'';


echo view('templates.ui.tab',[
    'data'=>[
        'geral'=>['title'=>'Geral','content'=>function() use ($configData){
                echo view('templates.ui.auto_fields',[
                    'layout_type'=>'horizontal',
                    'autocolumns'=>[
                        'action'=>['type'=>'hidden','value'=>'data'],
                        'title'=>['label'=>'Nome do Sistema','maxlength'=>100,'attr'=>'disabled="disabled"'],
                        'email'=>['type'=>'email','label'=>'E-mail de Suporte','require'=>true],
                        //'logo'=>['type'=>'button_field','label'=>'Logo','title'=>'Escolher arquivo','color'=>'primary','id'=>'btlogo','info_html'=>'Envie imagem no formato PNG'],
                    ],
                    'autodata'=>$configData,
                    'form'=>[
                        'url_action'=>route('super-admin.app.post',['config','update']),
                        'data_opt'=>['focus'=>true],
                        'bt_save'=>'Salvar',
                    ],
                ]);
        }],
        
        
        'image'=>['title'=>'Logos','content'=>function() use ($configData){
                echo '<p><strong>Logotipo</strong> - Dimensões recomendadas: 360x160px - formato PNG</p>';
                echo view('templates.components.uploadbox',[
                    'controller'=>'files',
                    'name'=>'logo-upload',
                    'title'=>'Selecionar arquivo',
                    'value'=> $configData['logo_main'] ? $configData['logo_main'] .'?ctrl='.$configData['updated_at'] : '',
                    'upload_db'=>false, //para não registrar na tabela 'files'
                    'upload'=>[
                        //parâmetros obrigatórios para localização do arquivo sem registrar no DB
                        'filename'=>'logo-main.png',
                        'folder'=>'files',
                        'account_off'=>true,
                        'thumbnails'=>false,
                        'accept'=>'image/*',
                    ],
                    'upload_form'=>[
                        'onSuccess'=>'@uplSuccess',
                    ],
                    'upload_view'=>['width'=>200,'height'=>200]
                ]);

                echo '<hr>';

                echo '<p><strong>Ícone</strong> - Dimensões recomendadas: 128x128px - formato PNG</p>';
                echo view('templates.components.uploadbox',[
                    'controller'=>'files',
                    'name'=>'logo-icon',
                    'title'=>'Selecionar arquivo',
                    'value'=> $configData['logo_icon'].'?ctrl='.$configData['updated_at'] ? $configData['logo_icon'] : '',
                    'upload_db'=>false, //para não registrar na tabela 'files'
                    'upload'=>[
                        //parâmetros obrigatórios para localização do arquivo sem registrar no DB
                        'filename'=>'logo-icon.png',
                        'folder'=>'files',
                        'account_off'=>true,
                        'thumbnails'=>false,
                        'accept'=>'image/*',
                    ],
                    'upload_form'=>[
                        'onSuccess'=>'@uplSuccess',
                    ],
                    'upload_view'=>['width'=>200,'height'=>200]
                ]);
        }],
        
        
        'actions'=>['title'=>'Ações','content'=>function() use ($configData, $userLogged){
            if($userLogged->user_level=='dev'){
                echo '<br>';
                echo '<p><a href="#" onclick="awBtnPostData({url:\''.  route('super-admin.app.post',['config','doUsersReLogin'])  .'\',confirm:true},this);return false;" class="btn btn-default">Forçar login para todos os usuários de contas as contas</a></p>';
            }
        }],
        
    ],
]);

@endphp


<script>
function uplSuccess(r){
    //atualiza a url da imagem retornada
    awAjax({
        url: "{{route('super-admin.app.post',['config','update'])}}",
        data: {action:'logo',url:r.file_url,filename:r.file_name},
        processData: true
    });
    setTimeout(function(){ awObjUploadProgress.base.hide(); },500);//oculta a janela de progresso do upload
};
</script>
@endsection