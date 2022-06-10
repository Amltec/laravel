@extends('super-admin.accounts._template')

@section('title-tab')
Informações
@endsection


@section('content-tab')
@php
/*Variáveis esperadas:
    $account
    $dataAccount
    $userLogged
*/


if($dataAccount['logo_main']??false)echo '<img class="account-logo" src="'.$dataAccount['logo_main'].'">';
//if($dataAccount['logo_icon']??false)echo '<img class="account-icon" src="'.$dataAccount['logo_icon'].'">';

echo view('templates.ui.view',[
    'class_field'=>'text-right text-muted',
    'arrange'=>'3-9',
    'data'=>[
        ['title'=>'Conta','value'=>$account->account_name],
        ['title'=>'E-mail','value'=>$account->account_email],
        ['title'=>'Login','value'=>$account->account_login],
        ['title'=>'Status','value'=> $account->status_label],
        ['title'=>'Cadastro','value'=> $account->created_at, 'type'=>'datetime'],
        ['title'=>'Usuários','value'=> $account->userRelations()->count()],
        ['title'=>'Corretores Cadastrados','value'=> (string)\App\Models\Broker::where('account_id',$account->id)->count()],
        ['title'=>'Robô Exclusivo para Processamento','value'=> $account->process_single,'type'=>'sn'],
        ['title'=>'Apólices Enviadas','value'=> (string)\App\Models\Base\ProcessRobot::where('account_id',$account->id)->count()],
        ['title'=>'Último Login no Sistema','value'=> \App\Models\UserLog::where(['account_id'=>$account->id,'action'=>'login'])->orderBy('created_at', 'desc')->value('created_at')],
    ],
    'format'=>function($v){ return $v?$v:'-'; }
]);
@endphp


<style>
@media (min-width:780px){
.account-logo,.account-icon{position:absolute;max-width:100px;max-height:60px;right:30px;z-index:9;}
.account-icon{max-width:60px;max-height:60px;right:30px;z-index:9;right:150px;}
}
@media (max-width:780px){
    .account-logo,.account-icon{max-width:100px;max-height:60px;margin:0 10px 10px 0;}
}</style>

@endsection