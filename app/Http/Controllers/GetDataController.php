<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GetDataController extends Controller {

    
    /*
     * Retorna ao nomes da cidade da pesquisa ajax do campo select2
     */
    /*public function getCidades(Request $request){
        $q=strtolower($request->get('q'));
        if(strlen($q)>2){//sempre os 20 primeiros resultados
            $tblCidades = \App\Entities\TbCidade::where('nome', 'LIKE', '%'. $q .'%')->take(20)->get();
            $r=[];
            foreach($tblCidades as $cid){
                $r[]=[
                        'id'=>$cid->nome.' - '.$cid->uf,
                        'text'=>$cid->nome.' - '.$cid->uf
                    ];
            }
            return $r;
        }else{
            return [];
        }
    }
    */
    
    /**
     * POST /get/data 
     * @param $req - campos esperados
     *      action - nome da ação, valores: 
     *                  login_ctrl  - gera o cookie de login
     *      ... demais campos personalizados
     */
    public function getData(Request $req){
        if($req->action=='login_ctrl'){
            return $this->loginCtrl();
        }
    }
    
    
    
    /**
     * Retorna ao token atualizado.
     * Method POST
     */
    public function getToken(){
        return ['token'=>csrf_token()];
    }
    
    
    //******************************************************************
    
    /**
     * Gera o cookie de controle de login
     */
    private function loginCtrl(){
        $cookie = cookie('login_ctrl', time(), 0);//0 - para ser excluído ao fechar o navegador
        return Response(['success'=>true])->withCookie($cookie);
    }
}
