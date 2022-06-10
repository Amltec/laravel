<?php

namespace App\Http\Controllers\Setup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Config;
use Auth;
use Artisan;
use App\Http\Controllers\Setup\Functions\SetupFunctions;
use App\Services\MetadataService;

class SetupMaintenanceController extends Controller{
    
    public function get_index(Request $request){
        $user = Auth::user();
        if(!$user || $user->user_level!=='dev')exit('negado');
        
        
        $c=$request->input('cmd');
        $a=$request->input('action');
        if($c=='maintenance'){
            if($a=='on' || $a=='off'){
                //MetadataService::set('system', 0, 'maintenance', $a);
                SetupFunctions::updateENV(['APP_MAINTENANCE'=>$a]);
            }
        }else if($c=='robot_start'){
            if($a=='on' || $a=='off'){
                MetadataService::set('config',0,'robot_start',$a);
            }
        }
        if($c!='')return \Redirect::to( route('super-admin.app.get',['setup-maintenance','index']) )->send();
        
        $v_maintenance = env('APP_MAINTENANCE');
        $v_robot_start = MetadataService::get('config',0,'robot_start');
        
        echo '
            <h3>Ações do Desenvolvedor <br><span style="font-size:0.7em;font-weight:normal;">Ações de Permitido apenas para desenvolvedor</span></h3>
            <p>
                Modo de manutenção: <strong style="font-size:1.2em;margin-right:10px;">'. ($v_maintenance=='on'?'ativado':'desativado') .'</strong>
                <button onclick="window.location=\'?cmd=maintenance&action='. ($v_maintenance=='on'?'off':'on') .'\'">'.($v_maintenance=='on'?'desativar':'ativar').'</button>
            </p>
            
            <p>
                Execução de todos os robôs: <strong style="font-size:1.2em;margin-right:10px;">'. ($v_robot_start=='on'?'iniciado':'parado') .'</strong>
                <button onclick="window.location=\'?cmd=robot_start&action='. ($v_robot_start=='on'?'off':'on') .'\'">'.($v_robot_start=='on'?'parar':'iniciar').'</button>
            </p>
        ';
    }
    
}
