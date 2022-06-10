@extends('templates.admin.index')

@php
    $ProcessRobotController = \App\ProcessRobot\VarsProcessRobot::class;
@endphp

@section('title')
Informações sobre o sistema
@endsection

@section('content-view')
<div style="white-space: pre-line;">
    Sistema: <strong>{{env('APP_NAME')}}</strong>
    Versão: <strong>{{env('APP_VERSION')}}</strong>
    
    <strong>Serviços disponíveis:</strong>
    @php
        $account = Config::accountData()->data;
        $account_config = Config::accountData()->data['config'];
        
        $products_active = array_filter(array_get($account_config,'cad_apolice.products_active')??[]);
        
        echo '<ul>';
        foreach($ProcessRobotController::$configProcessNames as $process_name => $opt){
            if(array_get($account_config,$process_name.'.active')!='s')continue;
            
            if(isset($opt['allow']) && !Gate::allows($opt['allow']))continue;
            
            echo '<li>'.$opt['title'] .'</li>'.
                '<ul>';
            foreach($opt['products'] as $product_name => $product_opt){
                if(isset($product_opt['allow']) && !Gate::allows($product_opt['allow']))continue;
                
                if($process_name=='cad_apolice' && ($products_active && !in_array($product_name,$products_active)))continue;//ramo não permitido
                
                
                echo '<li>'. $product_opt['title'] . '</li>';
            }
            echo '</ul>';
        }
        echo '</ul>';
    @endphp
</div>
@endsection