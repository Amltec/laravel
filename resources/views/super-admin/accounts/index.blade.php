@extends('templates.admin.index')


@section('title')
Conta
@endsection


@section('toolbar-header')
    @include('templates.components.button',['title'=> '+ Conta','color'=>'primary','href'=>route('super-admin.app.get',['accounts','create'])])
@endsection


@section('content-view')


@include('templates.ui.auto_list',[
    'list_class'=>'table-striped',// table-hover
    'data'=>$accounts,
    'columns'=>[
        'id'=>'ID',
        'account_name'=>['Conta','value'=>function($v,$reg) use($account_id_Logged){
            return '<strong>'. $v .'</strong>'.
                ($reg->id==$account_id_Logged ? ' <span class="label label-info bg-navy" style="margin-left:10px;">Logado</span>' : '');
        }],
        'created_at'=>'Cadastro',
        'status_label'=>'Status',
        'actions'=>['Ações','value'=>function($v,$reg) use($account_id_Logged){
            if($reg->id==$account_id_Logged){
                return  '<a href="#" onclick="fDoLogin(this,\''. $reg->id .'\');return false;" class="btn btn-success btn-sm margin-r-5" style="width:90px;"><i class="fa fa-unlock margin-r-5"></i> Login</a>'.
                        '<a href="#" onclick="fDoLogin(this,\''. $reg->id .'\',false);return false;" class="btn btn-sm text-red" title="Fazer logoff"><i class="fa fa-close"></i></a>';
                       
            }else{
                return '<a href="#" onclick="fDoLogin(this,\''. $reg->id .'\');return false;" class="btn btn-primary btn-sm" style="width:90px;"><i class="fa fa-lock margin-r-5"></i> Login</a>';
            }
        }],
    ],
    'columns_show'=>_GET('columns_show'),
    'options'=>[
        'checkbox'=>false,
        'select_type'=>2,
        'remove'=>false,
        'pagin'=>true,
        'confirm_remove'=>true,
        'toolbar'=>true,
        'search'=>false,
        'regs'=>false
    ],
    'routes'=>[
        'click'=>function($reg){return route('super-admin.app.edit',['accounts',$reg->id]);},
        'remove'=>route('super-admin.app.remove','accounts'),
    ],
    'field_click'=>'account_name',
    'row_opt'=>[
        'lock_del'=>[1,$account_id_Logged],//bloqueia a conta atual e a conta princial (id=1)
        'class'=>function($reg){return $reg->account_status=='c'?'row-deleted':'';}
    ],
    'metabox'=>true,
])


<script>
function fDoLogin(bt,id,isLogin){
    isLogin=isLogin===false?false:true;
    if(!isLogin || (isLogin && confirm('Fazer login nesta conta?')))
    awBtnPostData({
        url:(!isLogin ? '{{route("super-admin.app.post",["accounts","do_logoff"])}}' : '{{route("super-admin.app.post",["accounts","do_login"])}}'),
        data:{id:id},
        cb:function(r){
            if(!isLogin){
                window.location.reload();
            }else{
                if(r.url){goToUrl(r.url);}else{alert(r.msg)};
            }
        }
    },bt);
}
</script>

@endsection