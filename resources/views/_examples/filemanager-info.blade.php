@php

echo '<h1>Informações da Classe de Arquivos</h1>';
echo '<p>Classe: \App\Services\FilesService</p><br><br><hr><br><br>';


//Obs: se necessário, atualize os ids abaixo para executar o teste
$array_ids = [
    '112' => 'Private Image',
    '603' => 'Public Image',
    '555' => 'Private File',
    '26'  => 'Private File',
    '586'  => 'File other folder',
];

foreach($array_ids as $file_id => $info){
    $file = \App\Services\FilesService::getModel()->find($file_id);
    
    if(!$file){echo '<span style="color:red;">arquivo '.$file_id.' não encontrado</span><br>';continue;}
    echo $info.'<br>';
    
    $file->setControllerConfig('files');
    
    dump([
        'folder'=>$file->private?'private':'public',
        'url'=>$file->getUrl(),
        'path'=>$file->getPath(),
        'path_thumbnails'=>$file->getPathThumbnails(),
        'url_thumbnail(small)'=>$file->getUrlThumbnail('small'),
        'url_thumbnail(all)'=>$file->getUrlThumbnailAll(),
        'icon'=>$file->getIcon(),
    ]);
}


echo '<hr style="margin:40px 0;">';


$file_id=605;
echo '<p>Informações pelo método \App|Services\FilesService@getInfo() - file_id='.$file_id.'</p>';

$file = \App\Services\FilesService::getInfo($file_id);
dd($file);


@endphp