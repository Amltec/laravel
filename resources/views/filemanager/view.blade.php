@extends('templates.admin.index',[
    'dashboard'=>[
        'bt_back'=>false
    ]
])

@section('title')
Arquivo {{$file->file_title}}
@endsection


@section('content-view')
@include('templates.components.metabox',[
     'content'=>function() use($file,$controller){
        echo view('templates.ui.files_view',[
            'controller'=>$controller,
            'file'=>$file,
            //'onComplete'=>'@function(r){ console.log(r);alert(r.action);window.location.reload(); }',
            //'fields'=>'title,size,link' 
        ]);
    }
])

@endsection