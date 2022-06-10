@extends('admin.account._template')

@section('title-tab')
Imagens
@endsection


@section('content-tab')
@php
/*Variáveis esperadas:
    $account
    $dataAccount
*/

$dataAccount['logo_main']=$dataAccount['logo_main']??'';
$dataAccount['logo_icon']=$dataAccount['logo_icon']??'';

echo '<p><strong>Logotipo</strong> - Dimensões recomendadas: 360x160px - formato PNG</p>';
echo view('templates.components.uploadbox',[
    'controller'=>'files',
    'name'=>'logo-upload',
    'title'=>'Selecionar arquivo',
    'value'=> ($dataAccount['logo_main']?$dataAccount['logo_main'].'?ctrl='.$dataAccount['updated_at']:''),
    'upload_db'=>false, //para não registrar na tabela 'files'
    'upload'=>[
        //parâmetros obrigatórios para localização do arquivo sem registrar no DB
        'filename'=>'logo-main.png',
        'folder'=>'files',
        'account_off'=>false,
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
    'value'=> ($dataAccount['logo_main']?$dataAccount['logo_icon'].'?ctrl='.$dataAccount['updated_at']:''),
    'upload_db'=>false, //para não registrar na tabela 'files'
    'upload'=>[
        //parâmetros obrigatórios para localização do arquivo sem registrar no DB
        'filename'=>'logo-icon.png',
        'folder'=>'files',
        'account_off'=>false,
        'thumbnails'=>false,
        'accept'=>'image/*',
    ],
    'upload_form'=>[
        'onSuccess'=>'@uplSuccess',
    ],
    'upload_view'=>['width'=>200,'height'=>200]
]);


@endphp

<script>
function uplSuccess(r){
    //atualiza a url da imagem retornada
    awAjax({
        url: "{{route('admin.app.post',['account','files_update'])}}",
        data: {action:'logo',id:'{{$account->id}}',url:r.file_url,filename:r.file_name},
        processData: true
    });
    setTimeout(function(){ awObjUploadProgress.base.hide(); },500);//oculta a janela de progresso do upload
};
</script>
@endsection