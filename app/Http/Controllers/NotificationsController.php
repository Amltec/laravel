<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Utilities\FormatUtility;


class NotificationsController extends Controller{
    
    private $user;
    public function __construct() {
        $this->user = \Auth::user();
    }

    
    /**
     * Retorna a todas as notificações referente ao usuário logado
     */
    public function get_getdata(){
        $prefix = \Config::adminPrefix();
        $r=[];
        foreach($this->user->unreadNotifications->toArray() as $n){
            $data = $n['data'];
            $r[$n['id']]=[
                'title'=>$data['data']['title'],
                'date'=>FormatUtility::dateFormat($n['created_at'],'auto'),
                'url'=>route($prefix.'.app.index',''),
            ];
        }
        return $r?$r:null;
    }
    
    
}