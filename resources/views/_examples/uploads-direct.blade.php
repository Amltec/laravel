@extends('templates.admin.index')


@section('title')
Uploads - (arquivos diretos)
@endsection


@section('content-view')


@include('templates.ui.auto_fields',[
    'metabox'=>['title'=>'Upload direto após escolher o arquivo <small>(parâmetros em form[data-opt])</small>'],
    'form'=>[
        'id'=>'form2',
        'url_action'=>route('super-admin.file.postdirect','files'),
        'files'=>true,
        'data_opt'=>[
            'btAutoUpload'=>'[name=file]',
            //'onBefore'=>'@function(v){console.log("before file",v);}',
            //'onProgress'=>'@function(v){console.log("progress file",v);}',
            'onSuccess'=>'@function(v){console.log("uploaded file",v);}',
        ],
    ],
    'autocolumns'=>[
        'info01'=>['type'=>'info','text'=>'
            <b>Informações:</b><br>
            Foi usado o campo input[name=data-opt] para passar parâmetros específicos do upload.<br>
            Foi usado a função o parâmetro de data-opt do formulário para passar as configurações para este botão.<br>
            Utilizado o parâmetro "onProgress" como função callback no formato de string ("@function")(Veja em console.log())
        '],
        'file'=>['type'=>'upload','label'=>'Arquivo','class_button'=>'primary','value'=>'Upload'],
        'action'=>['type'=>'hidden','value'=>'upload'],//necessário para indicar a ação de upload
        //parâmetros adicionais
        'data-opt'=>['type'=>'hidden','value'=>
            json_encode([
                //'filename'=>'image1.png',
                //'folder'=>'files/myfolder1',
                //'private'=>true,
                //'account_off'=>true,
                //'metadata'=>['area_name'=>'x','area_id'=>1,'meta_name'=>'y','meta_value'=>3],
                
                //for image
                'max_width'=>100,
                'max_height'=>100,
                //'image_fit'=>false,
            ])
        ],
        
        
        'info02'=>['type'=>'info','text'=>function(){
        
            $file = 'logo-icon.png';
            $folder = 'files';
            echo '<hr> Informações do arquivo (<strong>público - pasta accounts não separado por data</strong>):<br> '.$folder.'/'.$file;
            dump(\App\Services\FilesDirectService::getInfo([
                'filename'=>$file,
                'folder'=>$folder,
            ],'array'));
            
            $file = 'anuncio-mf-rural.png';
            $folder = 'uploads\2019\12'; 
            echo '<hr> Informações do arquivo (<strong>privado - pasta accounts</strong>):<br> '.$folder.'/'.$file;
            dump(\App\Services\FilesDirectService::getInfo([
                'filename'=>$file,
                'folder'=>$folder,
                'private'=>true
            ],'array'));
            
            $file = 'myfile001.jpg';
            $folder = 'custom-folder'; 
            echo '<hr> Informações do arquivo (<strong>público - fora da accounts</strong>):<br> '.$folder.'/'.$file;
            dump(\App\Services\FilesDirectService::getInfo([
                'filename'=>$file,
                'folder'=>$folder,
                'account_off'=>true
            ],'array'));
            
            $file = '0001.png';
            $folder = 'files'; 
            echo '<hr> Informações do arquivo (<strong>privado - fora da accounts</strong>):<br> '.$folder.'/'.$file;
            dump(\App\Services\FilesDirectService::getInfo([
                'filename'=>$file,
                'folder'=>$folder,
                'account_off'=>true,
                'private'=>true
            ],'array'));
            
        }],
        
    ]
])




<br><br><br><br><br><br><br>


@endsection

