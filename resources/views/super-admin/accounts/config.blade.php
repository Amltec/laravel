@extends('super-admin.accounts._template')

@section('title-tab')
Configurações
@endsection


@section('content-tab')
@include('super-admin.account__inc.config_inc',[
    'user_level'=>'superadmin'
])
@endsection