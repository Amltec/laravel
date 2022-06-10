@extends('templates.admin.index')


@section('title')
Visualizador de Dados 05 - Extração completa de dados da model (taxonomias e metadados)
@endsection

@section('content-view')
@php
    $file = \App\Services\FilesService::getModel()->findOrFail(554);
    
    
    //*** adiciona novos campos para retornar ***
    
    //metadados
    $file->metadata=$file->getMetadataArray();
    
    //taxonomias
    $term_id=2;
    $file->{'taxonomys_'.$term_id}=$file->getTaxsData($term_id);
    
@endphp


<p>Escreve junto da model as respectivas taxonomias e metadados.
<br>Usando como referência o registro <strong>files.id=554</strong>.
<br>Mais detalhes veja o código deste arquivo.</p>


@include('templates.ui.view',[
    'class'=>'view-bordered',
    'class_field'=>'text-muted',
    'model'=>$file,
    
    //ajusta os campos
    'data'=>[
        'taxonomys_'.$term_id=>['title'=>true,'type'=>'taxonomy'],  //com o titte==true e type==taxonomy, carrega o nome automático da taxonomia
        'metadata'=>['title'=>'Meta Dados'], 
    ],
])
<br><br>



<h3>Carregando diretamente as taxonomias</h3>
Carregando diretamente pelo template.compoments.tag_item<br>
@php
foreach($file->getTaxsData($term_id) as $tx_id=>$tx_data){
    echo view('templates.components.tag_item',['model'=>$tx_data]);
}
@endphp
<br><br>

Carregando dentro da view usando diretamente o term_id={{$term_id}}<br>
@include('templates.ui.view',[
    'class'=>'view-bordered',
    'class_field'=>'text-muted',
    'data'=>[
        
        ['title'=>true,'type'=>'taxonomy','value'=>$file->getTaxsData($term_id)]
    ],
])
<br>

Carregando a lista de termos existentes do registro atual para montar a tabela abaixo (usa a função model::getAllTermsByThis())<br>
@php
    $terms_all = $file->getAllTermsByThis();
    $data=[];
    foreach($terms_all as $index=>$term_data){
        //taxonomias
        $file->{'taxonomys_'.$term_data->id}=$file->getTaxsData($term_data->id);
        
        //custom field data
        $data['taxonomys_'.$term_data->id] = [
            'title'=>$term_data->term_title,
            'type'=>'taxonomy',
            'value'=>$file->getTaxsData($term_data->id),
        ];
    }
    
    echo view('templates.ui.view',[
        'class'=>'view-bordered',
        'class_field'=>'text-muted',
        'data'=>$data,
    ])
@endphp
<br><br>



<h3>Carregando diretamente os metadados</h3>
Carregando dentro da view - modo 1<br>
@include('templates.ui.view',[
    'class'=>'view-bordered',
    'class_field'=>'text-muted',
    'data'=>[
        ['title'=>'Meta Dados','value'=>$file->getMetadataArray()]
    ],
])
<br>Carregando dentro da view - modo 2<br>
@include('templates.ui.view',[
    'class'=>'view-bordered',
    'class_field'=>'text-muted',
    'data'=>$file->getMetadataArray(),
    'data_type'=>'array',
])
<br>Carregando dentro da view - modo 3<br>
@include('templates.ui.view',[
    'class'=>'view-bordered',
    'class_field'=>'text-muted',
    'data'=>$file->getMetadata(),
])


@endsection
