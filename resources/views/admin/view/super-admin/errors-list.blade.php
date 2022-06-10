@extends('templates.admin.index')

@php
use App\ProcessRobot\VarsProcessRobot;
if(!Gate::allows('superadmin'))return;//nega o acesso

@endphp

@section('title')
Informações sobre o sistema
@endsection

@section('content-view')
<div>
@php
    function _fx1_createListErr($block_title,$status_code_list){
        echo '<div class="box box-default">
            <div class="box-header with-border"><h3 class="box-title">'. $block_title .'</h3></div>
            <div class="box-body">';
                echo '<table class="table table-hover table-condensed">'.
                    '<tr><th class="col-code">Código</th><th class="col-description">Descrição</th><th class="col-solution">Solução</th></tr>';
                foreach($status_code_list as $code=>$label){
                    $view='errors.process_robot.'.$code;
                    if(View::exists($view)){
                        $sections = View::make($view)->renderSections();
                    }else{
                        $sections = [];
                    }
                
                    echo '<tr>'.
                            '<td>'. strtoupper($code) .'</td>'.
                            '<td style="white-space: pre-line;">'. $label .
                                (isset($sections['error-description']) ? '<br>'. $sections['error-description'] : '') .
                            '</td>'.
                            '<td style="white-space: pre-line;">'. ($sections['error-solution']??'-') .'</td>'.
                        '</tr>';
                }
                echo '</table>';
            
        echo '</div>
            </div>';
    }
    
    
    _fx1_createListErr('Erros Gerais',VarsProcessRobot::$statusCode);

    foreach(VarsProcessRobot::$configProcessNames as $process_name => $process_opt){
        $class = App::make('\App\Http\Controllers\Process\Process'. studly_case($process_name) .'Controller');
        if(isset($process_opt['can']) && !Gate::allows($process_opt['can']))continue;
        
        $statusCodeList=$class::$statusCode??null;
        if($statusCodeList){
            _fx1_createListErr($process_opt['title'],$statusCodeList);
        }
        
    }
@endphp
</div>

<style>
    .col-code{width:5%;}
    .col-description{width:45%;}
    .col-solution{width:50%;}
</style>
@endsection