@extends('admin.account._template')

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
        'url_action'=> route('admin.app.post',['account','datasave']),
        'data_opt'=>[
            'focus'=>true,
        ],
        'bt_save'=>true,
        'bt_back'=>false,
        'autodata'=>$account??false,
        //'url_success'=>route('admin.app.index','account')   //redireciona após salvar
    ],
    'autocolumns'=>[
        'account_name'=>['label'=>'Nome da Conta','maxlength'=>100,'require'=>true],
        'account_email'=>['type'=>'email','label'=>'E-mail da Conta','require'=>true],
    ],
]);
@endphp

@endsection