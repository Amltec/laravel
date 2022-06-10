@extends('templates.admin.index')


@section('title')
Super Usuários
@endsection

@section('toolbar-header')
    @include('templates.components.button',['title'=> '+ Usuário','color'=>'primary','href'=>route('super-admin.app.get',['superusers','create'])])
@endsection


@section('content-view')


@include('templates.ui.auto_list',[
    'list_class'=>'table-striped',// table-hover
    'data'=>$users,
    'columns'=>[
        'id'=>'ID',
        'user_name'=>['Nome','value'=>function($v,$reg) use($user_id_Logged){
            return '<strong>'. $v .'</strong>'.
                '<br><span class="small" style="color:#999;text-transform:lowercase;">'.$reg->level_name.'</span>'.
                ($reg->id==$user_id_Logged ? ' <span class="label label-info bg-navy" style="margin-left:10px;">Logado</span>' : '')
                ;
        }],
        'user_email'=>'E-mail',
        //'account_name'=>'Conta',
        'created_at'=>['Cadastro','value'=>function($v){
            return FormatUtility::dateFormat($v,'auto');
        }],
        'status_label'=>'Status',
    ],
    'columns_show'=>_GET('columns_show'),
    'options'=>[
        'checkbox'=>true,
        'select_type'=>2,
        'pagin'=>true,
        'confirm_remove'=>true,
        'toolbar'=>true,
        'search'=>false,
        'regs'=>false
    ],
    'routes'=>[
        'click'=>function($reg){return route('super-admin.app.edit',['superusers',$reg->id]);},
        'remove'=>route('super-admin.app.remove','superusers'),
    ],
    'field_click'=>'user_name',
    'row_opt'=>[
        'lock_del'=>[Auth::user()->id],//bloqueia o usuário logado
        'class'=>function($reg){return $reg->user_status=='c'?'row-deleted':'';}
    ],
    'metabox'=>true,
])

@endsection