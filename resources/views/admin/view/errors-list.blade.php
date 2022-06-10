@extends('templates.admin.index')

@php
use App\ProcessRobot\VarsProcessRobot;
use App\Error;
@endphp

@section('title')
Descrição dos Erros
@endsection

@section('content-view')
<div>
@php
    function _fx1_createListErr($block_title,$status_code_list){
        //códigos dos erros que não deve exibir nesta lista
        $ignore=['quid06','quid03'];
        
    
        $r='';
        foreach($status_code_list as $code=>$label){
            if(!Error::exists($code))continue;
            if(in_array($code,$ignore))continue;
            
            $r.='<div class="row">'.
                    '<div class="col-md-6">'.
                        '<h4 title="'. strtoupper($code) .' - '. $label .'">Erro: '. $label .'</h4>'.
                        '<div style="white-space: pre-line;">'. Error::get($code,'description') .'</div>'.
                    '</div>'.
                    '<div class="col-md-6"><h4 style="font-size:16px;">O que fazer</h4><div>'. Error::get($code,'solution','-') .'</div></div>'.
                '</div><hr>';
        }
        
        if($r){
            echo '<h4>'. $block_title .'</h4>'.
                '<div class="box box-default"><div class="box-body list-errors">'. $r . '</div></div>';
        }
    }
    
    
    _fx1_createListErr('Erros Gerais',VarsProcessRobot::$statusCode);

    foreach(VarsProcessRobot::$configProcessNames as $process_name => $process_opt){
        $class = '\App\Http\Controllers\Process\Process'. studly_case($process_name) .'Controller';
        //dump($class);
        if(isset($process_opt['can']) && !Gate::allows($process_opt['can']))continue;
        
        $statusCodeList=$class::$statusCode??null;
        if($statusCodeList){
            _fx1_createListErr($process_opt['title'],$statusCodeList);
        }
        
    }
@endphp
</div>

<style>
.list-errors hr:last-child{display:none;}
</style>
@endsection