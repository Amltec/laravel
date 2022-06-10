@extends('templates.admin.index')

@section('title')
(DEV) Teste de envio do retorno de dados do robô
@endsection

@section('content-view')

@php
if(Auth::user()->user_level!='dev')exit('negado');
    
    $key_active = '';
    $key_robot = '';
    $robotModel = \App\Models\Robot::where('robot_status','a')->first();
    if($robotModel){
        $key_active = $robotModel->key_active;
        $key_robot = $robotModel->key_robot;
    }

    $post_url = URL::to("/") .'/wsrobot/data';
    
    echo '<form method="POST" action="'. $post_url .'">
        '.csrf_field().'
        <table class="table1">
        <tr><td>POST</td><td>'.$post_url.'</td></tr>
        <tr><td>Chave do de Ativação</td><td><input type="text" name="key_active" value="'. $key_active .'"></td></tr>
        <tr><td>Chave do Robô</td><td><input type="text" name="key_robot" value="'. $key_robot .'"></td></tr>
        <tr><td>Nome da Ação</td><td><select name="action">
                    <option value="set_process">set_process</option>
                    <option value="get_process">get_process</option>
                </select></td></tr>
        <tr><td>ID do Processo</td><td><input type="text" name="id" value=""></td></tr>
        <tr><td>Status (R|E|T)</td><td><input type="text" name="status" value="E" maxlength="1"></td></tr>
        <tr><td>Mensagem de Retorno</td><td><input type="text" name="msg" value="teste ok"></td></tr>
        <tr><td>Dados</td><td><input type="text" name="data" value=""></td></tr>
        <tr><td> </td><td><input type="submit"></td></tr>
        </table>
    </form>';
    
    

    //dd((new \App\ProcessRobot\ResumeProcessRobot)->setProcessData('cad_apolice'));
    //dd((new \App\ProcessRobot\ResumeProcessRobot)->getProcessData('cad_apolice',0,true));
    
    
    //$model = \App\Models\ProcessRobot_SeguradoraFiles::find('294');
    //dd( \App::call('\\App\\Http\\Controllers\\Process\\ProcessSeguradoraFilesController@doExtracted',[$model]) );

    
@endphp


<style>
.table1 td{padding:5px;}
input,select{padding:4px 7px;border:1px solid #bbb;}
input[type=text],select{width:300px;}
input[readonly]{background:#f2f2f2;}
</style>
@endsection