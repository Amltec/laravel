@extends('templates.admin.index')


@section('title')
Diretórios (taxonomia)
@endsection


@section('content-view')
<p>Tabela de dados usando o componente tree.blade. Testando opções.</p>


@php
use App\Services\TaxsService;


$term_id = 4;
$taxs = TaxsService::getTaxListTree(
    $term_id,
    [],
    function($reg){ return '#tax-'.$reg->id; }
);


echo '<br><h4>Modelo 1</h4>';
echo view('templates.components.tree',[
    'id_menu'=>'tree02',
    'sub'=>$taxs,
    'link_force'=>false,
    //'pos_caret'=>'right',
    //'pos_caret_def'=>'right',
]);


echo '<br><h4>Modelo 2</h4>';
echo view('templates.components.tree',[
    'id_menu'=>'tree02',
    'sub'=>$taxs,
    'class_menu'=>'tree-condensed',//tree-bordered
    'show_icon'=>false,
    'select'=>'25',
    /*'routes'=>[
        'click'=>function($id,$item){ return '#tax-'.$id; }
    ]*/
    'link_force'=>false,
]);

@endphp


@endsection

