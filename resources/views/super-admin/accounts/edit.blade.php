@extends('super-admin.accounts._template')

@section('title-tab')
Dados
@endsection


@section('content-tab')
@php
/*Variáveis esperadas:
    $account
    $dataAccount
*/

echo view('templates.ui.auto_fields',[
    'layout_type'=>'horizontal',
    'form'=>[
        'id'=>'form_account',
        'url_action'=> route('super-admin.app.update',['accounts',$account->id]),
        'url_back'=>route('super-admin.app.index','accounts'),
        'data_opt'=>[
            'focus'=>true,
        ],
        'bt_save'=>true,
        'bt_back'=>true,
        'autodata'=>$account??false,
        'url_success'=> isset($account) ? null : route('super-admin.app.edit',['accounts',':id'])
    ],
    'autocolumns'=>[
        'account_name'=>['label'=>'Nome da Conta','maxlength'=>100,'require'=>true],
        'account_email'=>['type'=>'email','label'=>'E-mail da Conta','require'=>true],
        'account_login'=>['label'=>'Login','attr'=>'readonly="readonly"'],
        'account_status'=>['type'=>'select','label'=>'Status','list'=>[''=>'','a'=>'Normal','c'=>'Cancelado'],'require'=>true],
        
        
        /*//test
        'zaccount_pass'=>['type'=>'password','label'=>'senha legal'],
        'zaccount_radio'=>['type'=>'radio','label'=>'Radio Field','list'=>['x'=>'xx11','a1'=>'Normal','c1'=>'Cancelado'],'require'=>true,'valued'=>'a1'],
        'zaccount_check[]'=>['type'=>'checkbox','label'=>'Check Field','list'=>['x'=>'xx22','a2'=>'Normal','c2'=>'Cancelado'],'require'=>true,'value'=>'x'],
        */
        
    ],
]);
@endphp

@endsection