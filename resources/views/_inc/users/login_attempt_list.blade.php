@php
/*
Parâmetros: 
    $user_id, 
    boolean $is_title - default true
    boolean $is_metabox - default true
*/


$is_title = $is_title??true;
$is_metabox = $is_metabox??true;

$loginAttempt = new \App\Models\LoginAttempt;
$regs = $loginAttempt->where('user_id',$user_id)->get();


if($regs){
    $r='';
    foreach($regs as $reg){
        $r.='<tr><td>'. $reg->created_at .'</td><td>'. $reg->ip .'</td></tr>';
    }
    if($r){
        $r='<table class="table"><thead><tr><th>Data</th><th>IP</th></tr></thead>'.$r.'</table>';
        if($is_metabox){
            echo view('templates.components.metabox',[
                    'title'=>'Tentativas de Login',
                    'header'=>$is_title,
                    'content'=>$r,
                ]);
        }else{
            echo ($is_title ? '<h3>Tentativas de Login</h3>' : false) . $r;
        }
    }else{
        echo 'Nenhum registro';
    }
}



@endphp