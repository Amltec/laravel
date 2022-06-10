@extends('templates.admin.index')

@section('title')
Nova Conta
@endsection


@section('content-view')
@php

echo view('templates.ui.auto_fields',[
    'layout_type'=>'horizontal',
    'form'=>[
        'id'=>'form_account',
        'url_action'=> route('super-admin.app.store','accounts'),
        'url_back'=>route('super-admin.app.index','accounts'),
        'data_opt'=>[
            'focus'=>true,
        ],
        'bt_save'=>true,
        'bt_back'=>true,
        'autodata'=>$account??false,
        'url_success'=> isset($account) ? null : route('super-admin.app.edit',['accounts',':id'])
    ],
    'metabox'=>true,
    'autocolumns'=>[
        'account_name'=>['label'=>'Nome da Conta','maxlength'=>100,'require'=>true],
        'account_email'=>['type'=>'email','label'=>'E-mail da Conta','require'=>true],
        'account_login'=>['label'=>'Login','require'=>true,'maxlength'=>50],
    ],
]);




@endphp

@endsection