@extends('super-admin.accounts._template')

@section('title-tab')
Usuários
@endsection


@section('content-tab')
@php
/*Variáveis esperadas:
    $account
    $dataAccount
    $userLogged
    $params[]: users_list,
*/


Form::loadScript('forms');


@endphp

@include('templates.ui.auto_list',[
    'list_id'=>'user_list',
    'list_class'=>'table-striped',// table-hover
    'data'=>$params['users_list'],
    'columns'=>[
        'id'=>'ID',
        'user_name'=>['Nome','value'=>function($v,$reg){
            return '<strong>'. $v .'</strong>'.
                '<br><span class="small" style="color:#999;text-transform:lowercase;">'.$reg->level_name.'</span>';
        }],
        'user_email'=>'E-mail',
        //'account_name'=>'Conta',
        'created_at'=>['Cadastro','value'=>function($v){
            return FormatUtility::dateFormat($v,'auto');
        }],
        'status_label'=>'Status'
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
    'toolbar_buttons_right'=>[
        'new_user'=>'<a href="#" id="user_bt_user_new" class="btn btn-primary">+ Usuário</a>'
    ],
    'routes'=>[
        'click'=>function($reg) use($account){return route('super-admin.app.get',['accounts','user_edit_ajax']).'?account_id='.$account->id.'&user_id='.$reg->id;},
        'remove'=>route('super-admin.app.get',['accounts','user_remove_ajax']),
    ],
    'field_click'=>'user_name',
    'row_opt'=>[
        'lock_del'=>[Auth::user()->id],//bloqueia o usuário logado
        'class'=>function($reg){return $reg->user_status=='c'?'row-deleted':'';}
    ],
])


<script>
(function(){
    $('#user_list').on('onOpen',function(e,opt){
        awAjax({
            type:'GET',url:opt.url,dataType:'html',
            success: function(r){
                var _fLoad=function(oHtml){
                    oHtml.html(r);
                }
                var oModal=awModal({title:'Edição de usuário',html:_fLoad,width:'lg',btClose:false});
            },
            error:function (xhr, ajaxOptions, thrownError){
                awModal({title:'Erro interno de servidor',html:xhr.responseText,msg_type:'danger',btSave:false});
            }
        });
        return false;//return false para anular o click
    });
    
    
    $('#user_bt_user_new').on('click',function(e){
        e.preventDefault();
        awAjax({
            type:'GET',url:"{{route('super-admin.app.get',['accounts','user_edit_ajax']).'?account_id='.$account->id}}",dataType:'html',
            success: function(r){
                var _fLoad=function(oHtml){
                    oHtml.html(r);
                }
                var oModal=awModal({title:'Edição de usuário',html:_fLoad,width:'lg',btClose:false});
            },
            error:function (xhr, ajaxOptions, thrownError){
                awModal({title:'Erro interno de servidor',html:xhr.responseText,msg_type:'danger',btSave:false});
            }
        });
    });
})();
</script>

@endsection