@section('title-tab')
Usuários do Quiver
@endsection


@section('content-tab')
@php
/*Variáveis esperadas:
    $account
    $dataAccount
    $userLogged
    $params[]: pass_list, is_new_login_allow
*/
use App\ProcessRobot\VarsProcessRobot;



//ajustes para caso venha do painel admin ou superadmin
$prefix = Config::adminPrefix();
if($prefix=='super-admin'){
    $account_class='accounts';
}else{//admin
    $account_class='account';
}

    
//dd($params[is_new_login_allow']);

echo view('templates.ui.auto_list',[
        'list_id'=>'pass_list',
        'data'=>$params['pass_list'],
        'columns'=>[
            'id'=>'ID',
            'pass_user'=>'Corretora',
            'pass_login'=>['Login','value'=>function($v,$reg){
                return $v . ($reg->pass_type=='t'?'<span class="text-muted" style="margin-left:10px;font-size:0.9em;">Revisão</span>':'');
            }],
            'pass_status'=>['Status','value'=>function($v,$reg){
                return $reg->status_label . ($reg->status_code ? ' - '. (VarsProcessRobot::$statusCode[$reg->status_code]??$reg->status_code) : '');
            }],
            'pass_busy'=>['Ocupado','value'=>function($v,$reg) use($prefix){
                $n=$reg->process_id;
                if($n){
                    return ''.
                        ($prefix=='super-admin' ? '<a href="#" onclick="fSetLoginNotBusy(this,\''. $reg->id .'\');return false;" class="btn btn-link" style="font-size:0.8em;margin-left:-15px;" alt="Liberar login"><i class="fa fa-close text-danger"></i></a>' : '').
                        'Sim #'. $n;
                }else{
                    return '-';
                }
            }],
            'acessed_at'=>['Último Acesso','value'=>function($v){$v=FormatUtility::dateFormat($v,'auto'); return $v?$v:'-';}],
        ],
        'options'=>[
            'checkbox'=>true,
            'select_type'=>2,
            'pagin'=>true,
            'confirm_remove'=>true,
            'toolbar'=>true,
            'search'=>false,
            'regs'=>false,
            'list_remove'=>false,
        ],
        'toolbar_buttons_right'=>[
            'new_pass'=> '<span class="text-muted">Logins disponíveis: '. $params['count_login'] .'/'.$params['instances'] .'</span>'   . ($params['is_new_login_allow']?'<a href="#" id="user_bt_pass_new" class="btn btn-primary" style="margin-left:20px;">+ Usuário</a>':'')
        ],
        'routes'=>[
            'click'=>function($reg) use($account,$prefix,$account_class){return route($prefix.'.app.get',[$account_class,'pass_edit_ajax']).'?account_id='.$account->id.'&pass_id='.$reg->id;},
            'remove'=>route($prefix.'.app.get',[$account_class,'pass_remove_ajax',$account->id] ),
        ],
        'fxield_click'=>['pass_user','pass_login'],
        'row_opt'=>[
            'class'=>function($reg){
                $cls='';
                if($reg->pass_status=='c'){
                    $cls='row-deleted';
                }elseif($reg->pass_status=='0'){
                    $cls='text-red';
                }
                return $cls;
            }
        ],
    ]);
@endphp


<script>
(function(){
    $('#pass_list').on('onOpen',function(e,opt){
        awAjax({
            type:'GET',url:opt.url,dataType:'html',
            success: function(r){
                var _fLoad=function(oHtml){
                    oHtml.html(r);
                }
                var oModal=awModal({title:'Edição de Usuário do Quiver',html:_fLoad,btClose:false,width:'lg'});
            },
            error:function (xhr, ajaxOptions, thrownError){
                awModal({title:'Erro interno de servidor',html:xhr.responseText,msg_type:'danger',btSave:false});
            }
        });
        return false;//return false para anular o click
    });
    
    @if($params['is_new_login_allow'])
    $('#user_bt_pass_new').on('click',function(e){
        e.preventDefault();
        awAjax({
            type:'GET',url:"{{route($prefix.'.app.get',[$account_class,'pass_edit_ajax']).'?account_id='.$account->id}}",dataType:'html',
            success: function(r){
                var _fLoad=function(oHtml){
                    oHtml.html(r);
                }
                var oModal=awModal({title:'Cadastro de Usuário do Quiver',html:_fLoad,btClose:false,width:'lg'});
            },
            error:function (xhr, ajaxOptions, thrownError){
                awModal({title:'Erro interno de servidor',html:xhr.responseText,msg_type:'danger',btSave:false});
            }
        });
    });
    @endif
})();
    
@if($prefix=='super-admin')
    function fSetLoginNotBusy(o,id){
        awAjax({
            url:"{{route($prefix.'.app.get',[$account_class,'set_pass_not_busy']) }}",processData:true,data:{id:id},
            success: function(r){
                if(r.success)$(o).closest('td').text('-');
            },
        });
    };
@endif
</script>


@endsection