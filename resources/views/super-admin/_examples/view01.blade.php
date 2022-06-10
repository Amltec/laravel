@extends('templates.admin.index')


@section('title')
Visualizador de Dados 01 - Tipo de dados
@endsection

@section('content-view')
<p>Testando variações de modos de inserção e tipos de dados.</p>

@include('templates.ui.view',[
    'class'=>'view-bordered',
    'data'=>[
        'a'=>'Meu texto 1',
        'b'=>'Meu texto 2',
        'c'=>'Meu texto 3<br>a<br>b<br>c',
        'Meu texto 4',
        123456,
        function(){return 'Meu texto 5';},
        1.23,
        1.23,
        ['title'=>'Campo 1','value'=>'Valor do campo 1'],
        ['title'=>'Campo 2','value'=>function(){ return 'Valor do campo 2'; }],
        ['title'=>'Campo 3 (echo)','value'=>function(){ echo 'Valor do campo 3<br>a<br>b<br>c'; }],
        ['title'=>'Campo 4','value'=>'2019-10-25', 'type'=>'date'],
        ['title'=>'Campo 5','value'=>'123.56', 'type'=>'price','class_row'=>'text-danger','id'=>'id-field-5','attr'=>['data-a'=>'1','data-b'=>'2'] ],
        ['title'=>'Campo 6 - Array','value'=>['1',2,'a'=>'b']],
        ['title'=>'Campo 7 - Dump Value','value'=>['1',2,'a'=>'b'],'type'=>'dump'],
        
        ['value'=>'texto <strong>HTML</strong> dentro deste container sem margem interna','class_row'=>'no-padding text-center'],
        '***********************************************************************************',
        ['title'=>'Link','type'=>'link','value'=>'https://www.google.com.br'],
        ['title'=>'Arquivo','type'=>'file','value'=>'https://www.ideau.com.br/getulio/restrito/upload/revistasartigos/277_1.pdf'],
        ['title'=>'Imagem','type'=>'img','value'=>'https://img1.ibxk.com.br/2019/02/25/25044125149117.jpg?w=700'],
        ['title'=>'Vídeo Viemo ','type'=>'video','value'=>'//player.vimeo.com/video/337674789?title=0&portrait=0&byline=0&autoplay=1&loop=1'],
        ['title'=>'Vídeo MP4 ','type'=>'video','value'=>'http://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4'],
        ['title'=>'Vídeo Youtube','type'=>'youtube','value'=>'https://www.youtube.com/watch?v=EngW7tLk6R8'],
        ['title'=>'Áudio File','type'=>'audio','value'=>'https://www.itapeva.sp.gov.br/wp-content/uploads/2018/10/independencia-do-brasil.mp3'],
        ['title'=>'Iframe','type'=>'iframe','value'=>'http://localhost/aurlwebapp/','attr_value'=>['style'=>'outline:1px solid red'] ],
        
    ]
])

<br><br><br><br><br><br><br><br><br>

@endsection
