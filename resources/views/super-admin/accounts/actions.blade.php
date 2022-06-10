@extends('super-admin.accounts._template')

@section('title-tab')
Ações
@endsection


@section('content-tab')
@php
/*Variáveis esperadas:
    $account
    $dataAccount
    $userLogged
*/

$account_id_Logged=Auth::user()->getAuthAccount('id');

if($account_id_Logged==$account->id){
echo '<p><a href="#" onclick="fDoLogin(this,\''. $account->id .'\');return false;" class="btn btn-success margin-r-5"><i class="fa fa-unlock margin-r-5"></i> Fazer login nesta conta</a> '.
     '<a href="#" onclick="fDoLogin(this,\''. $account->id .'\',false);return false;" class="btn text-red" title="Fazer logoff"><i class="fa fa-close"></i></a></p>';
    
}else{
    echo '<p><a href="#" onclick="fDoLogin(this,\''.$account->id.'\');return false;" class="btn btn-primary"><i class="fa fa-lock margin-r-5"></i> Fazer login nesta conta</a></p>';
}

if($userLogged->user_level=='dev'){
    echo '<p><a href="#" onclick="awBtnPostData({url:\''.  route('super-admin.app.post',['accounts','doUsersReLogin',$account->id])  .'\',confirm:true},this);return false;" class="btn btn-default">Forçar login para todos os usuários desta conta</a></p>';

    if($account->account_status!='c' && $account->id>1){
        echo '<hr>';
        if($account->trashed())echo view('templates.components.button',['color'=>'primary','title'=>'Restaurar','icon'=>'fa-recycle','id'=>'bt-restore']).'<br><br>';
        echo view('templates.components.button',['color'=>'link','title'=>'Deletar conta','icon'=>'fa-trash','id'=>'bt-delete','class'=>'text-red']);
    }
}

@endphp


<script>
(function(){
    /*var tabs=$('#tab_main > ul > li').on('click',function(){
        window.window.location.hash=$(this).find('>a').attr('href');
    });
    var h=window.location.hash.replace('#','');
    if(h)tabs.find('>a[href="#'+h+'"]').click();*/
    
    $('#bt-delete').on('click',function(e){
       e.preventDefault();
       if(prompt('Digite DELETE para confirmar')!='DELETE'){alert('Negado');return false;};
       awBtnPostData({
           url:'{{route('super-admin.app.remove','accounts')}}',
           data:{id:'{{$account->id}}',_method:'DELETE',action:'{{ $account->trashed()?"remove":"trash"  }}'},
           cb:function(r){
               if(r.success){
                    @if($account->trashed())
                        alert('Conta removida com sucesso');
                        window.location.href='{{route("super-admin.app.index","accounts")."?is_trash=s"}}';
                    @else
                        alert('Esta conta foi movida para a lixeira. Para remover definitivamente, acesse a lixeira e remova novamente');
                        window.location.href='{{route("super-admin.app.index","accounts")}}';
                    @endif
               }else{
                   alert(r.msg);
               }
           }
       },this);
    });
    
    $('#bt-restore').on('click',function(e){
       e.preventDefault();
       awBtnPostData({
           url:'{{route('super-admin.app.remove','accounts')}}',
           confirm:'Restaurar esta conta da lixeira?',
           data:{id:'{{$account->id}}',_method:'DELETE',action:'restore'},
           cb:function(r){
               if(r.success){
                   window.location.reload();
               }else{
                   alert(r.msg);
               }
           }
       },this);
    });
    
}());


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