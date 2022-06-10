@extends('templates.admin.index')

@section('title')
    Execução do Robô
@endsection

@section('content-view')
@php
    use App\Services\AccountPassService;
    
    $prefix = Config::adminPrefix();
    if($prefix=='super-admin')exit('não disponível');
    

    $accountData = Config::accountData();
    $robot_start = $accountData->data['robot_start']??'on';
    
    
    
    $logins_list = AccountPassService::getList(Config::accountID());
    $logins_dt_last='';
    $logins_err = 0;
    foreach($logins_list as $reg){
        if($logins_dt_last<$reg->acessed_at)$logins_dt_last = $reg->acessed_at;
        if($reg->pass_status=='0')$logins_err++;
    };
    //dd($accountData->config);
    echo '
    <div class="box">
        <div class="box-body">
            <table class="table table-borderless" style="width:400px;">
                <tr><td class="strong">Status do Robô</td><td><i class="fa fa-stop text-'. ($robot_start=='on'?'green':'red') .'"></i> Robô '. ($robot_start=='on'?'em execução':'parado') .'</td></tr>
                <tr><td class="strong">Instâncias Disponíveis</td><td>'. ($accountData->config['instances']??'-') .'</td></tr>
                <tr><td class="strong">Última execução</td><td>'. ($logins_dt_last ? $logins_dt_last : '-') .'</td></tr>
                <tr><td class="strong">Logins com Erro</td><td>'. ($logins_err ? $logins_err : 'Nenhum') .'</td></tr>
                ';
                
                if($robot_start=='off'){
                    if($logins_list->count()==0){
                        $m='Logins não cadastrados';
                    }else{
                        $m='Logins Bloqueados';
                    }
                    echo '<tr><td class="strong">Motivo</td><td><a href="'. route('admin.app.index','account_pass') .'" class="text-red">'.$m.' <span class="btn btn-danger btn-xs" style="margin-left:10px;">Corrigir</span></a></td>';
                }
                
       echo'</table>
        </div>
    </div>
    ';

@endphp

@endsection