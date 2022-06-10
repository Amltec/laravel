<?php

namespace App\Services;
use App\Models\AccountPass;
use App\Models\Account;
use App\Services\LogsService;
use App\Services\AccountsService;
use App\Utilities\ValidateUtility;
use App\Config;

/**
 * Classe de serviços de cadastramento de senhas da conta
 */
class AccountPassService{

    /**
     * Salva os dados da senha do quiver
     */
    public static function save($account_id,$data){    //$data - request->all()
        $id = $data['pass_id'];
        $action = $id?'edit':'add';

        $param1 = [
            'pass_user'=>'required|max:50',
            'pass_login'=>'required|max:50|unique:account_pass,pass_login',
            'pass_pass'=>'max:50',
        ];
        if($id){//edit
            $action='edit';
            $param1['pass_login'].=','.$id.'';
            $param1['pass_status']='required';

            if(!$data['pass_pass'])unset($data['pass_pass']);
        }else{//=a
            $param1['pass_pass']='required';
            $data['pass_area']='quiver';
            $data['pass_status']='a';
        }
        $validade = validator($data, $param1, \App\Utilities\FieldsValidatorUtility::getMessages());
        if($validade->fails()){
            return ['success'=>false,'msg'=>$validade->errors()->messages()];
        }




        //dd($id,$data);
        if($id){
            $log_area='edit';

            if($data['pass_status']=='a')$data['status_code']=null;//quer dizer que está ativado, e portanto deve limpar este campo

            if($data['pass_type']=='t')AccountPass::where(['account_id'=>$account_id,'pass_type'=>'t'])->update(['pass_type'=>null]);//limpa o campo pass_type dos demais registros, pois apenas 1 deste tipo deve existir
            $m = AccountPass::where(['account_id'=>$account_id,'id'=>$id])->first();

            if($data['pass_status']=='a' && $m->pass_status!='a'){//foi alterado o status para 'a'
                if(!AccountPassService::isNewLoginAllow($account_id))return ['success'=>false,'msg'=>'Não é possível ativar, limite de '. self::countLogin($account_id) .' logins ativos excedido'];
            }

            if($m->pass_login!=$data['pass_login']){//login alterado
                if(!self::checkAllowRemove($account_id, $m->pass_login))return ['success'=>false,'msg'=>['pass_login'=>'Este login não pode ser alterado. Se quiver prosseguir, cancele o cadastro.']];
            }

            $m->update($data);
            unset($data['status_code']);
        }else{
            $log_area='add';
            $id = AccountPass::create($data)->id;
        }
        LogsService::addFields($log_area,'account_pass',$id,$data);

        //verifica se existe algum login disponível para o robô continuar
        $logins = self::loginsList($account_id);
        if($logins)AccountsService::setRobotStart($account_id,'on');//ativa o robô

        return ['success'=>true,'msg'=> ($id ? 'Atualizado' : 'Cadastrado') .' com sucesso', 'action'=>$action];
    }


    /**
     * Apenas ativa o login que está como bloqueado ou cancelado
     */
    public static function activePass($account_id,$pass_id){
        $m = AccountPass::where(['account_id'=>$account_id,'id'=>$pass_id])->first();
        if($m){
            $m->update(['pass_status'=>'a','status_code'=>null]);
            AccountsService::setRobotStart($account_id,'on');//ativa o robô
            return ['success'=>true,'msg'=>'Ativado com sucesso'];
        }else{
            return ['success'=>false,'msg'=>'Erro ao localizar registro'];
        }
    }


    /**
     * Bloqueia o login
     */
    public static function disableLogin($account_id,$pass_id){
        AccountPass::where(['account_id'=>$account_id,'id'=>$pass_id])->update(['pass_status'=>'0']);
    }

    /**
     * Carrega a view de edição da senha do Quiver
     */
    public static function editAjax($account_id,$pass_id,$prefix){
        if($pass_id??false){//edit
            $pass=AccountPass::where('account_id',$account_id)->find($pass_id);
            if(!$pass)return 'Erro ao localizar registro';
        }else{//add
            $pass=null;
        }

        if($prefix=='super-admin'){
            $account_class='accounts';
        }else{//admin
            $account_class='account';
        }

        //utiliza a view templates.ajax_load para carregar os recursos javascript corretamente
        return view('templates.ajax_load',['view'=>$prefix.'.'.$account_class.'.pass_edit_ajax','data'=>[
            'pass'=>$pass,
            'account_id'=>$account_id,
            'allowEditLogin'=>$pass ? self::checkAllowRemove($account_id,$pass->pass_login) : true
        ]]);
    }


    /**
     * Retorna a lista das senhas cadastradas na tabela account_pass
     */
    public static function getList($account_id,$filter=[]){
        return AccountPass::where('account_id',$account_id)->orderBy('id','desc')->paginate(_GETNumber('regs')??15);
    }





    /**
     * Remove a senha do quiver
     */
    public static function remove($account_id,$id){
        $m = AccountPass::where(['account_id'=>$account_id,'id'=>$id])->first();
        if($m){
            //verifica se o login não está em uso para ser excluído
            $n=self::checkAllowRemove($account_id, $m->pass_login);
            if(!$n)return ['success'=>false,'msg'=>'Este login não pode ser excluído. Se quiver prosseguir, cancele o cadastro.'];

            //remove
            $m->delete();
            LogsService::add('remove','account_pass',$id);
            return ['success'=>true,'msg'=>'Removido com sucesso'];
        }else{
            return ['success'=>false,'msg'=>'Erro ao localizar login'];
        }
    }


    /**
     * Verifica se pode ser excluído (caso já tenha sido utilizado, não permite a exclusão)
     */
    public static function checkAllowRemove($account_id,$pass_login){
        //verifica se o login não está em uso para ser excluído
        if(!$pass_login)return true;
        $n= \App\Models\Base\ProcessRobot::where('account_id',$account_id)
                ->where('process_status','<>','i') //não ignorados
                ->whereData(['login_use__like'=>'%'. $pass_login .'%'])
                ->exists();
        if($n){
            return false;
        }else{
            return true;
        }
    }


    /**
     * Retorna se existe permissão para adicionar mais logins com base no número de instâncias permitidas na configuração
     * @return boolean
     */
    public static function isNewLoginAllow($account_id){
        $instances = (int)AccountsService::getConfig($account_id,'instances',1);
        $n = self::countLogin($account_id);
        return $n<$instances;
    }

    private static $count_logins=[];
    /**
     * Conta quantos logins estão cadastrados sem ser do tipo revisão (pass_type=t)
     */
    public static function countLogin($account_id){
        if(!isset(self::$count_logins[$account_id]))self::$count_logins[$account_id] = AccountPass::where(['account_id'=>$account_id])->whereNotIn('pass_status',['c','0'])->count();
        return self::$count_logins[$account_id];
    }



    /**
     * Retorna a um array de logins válidos
     */
    public static function loginsList($account_id,$opt=[]){
        $opt = array_merge([
            'review'=>false,    //(boolean) captura somente o login de revisão
            'one'=>false,       //(boolean) se true irá retornar apenas um registro (ao invés de uma lista)
            'all'=>false,       //(boolean) se true, irá retornar a todos os logins
        ],$opt);

        $m = AccountPass::where(['account_id'=>$account_id,'pass_status'=>'a']);
        if($opt['review']){$m->where('pass_type','t');}else{$m->whereNull('pass_type');}
        $m=$m->get();
        if($m->count()>0){
            $r=[];
            foreach($m as $reg){
                $k=$reg->pass_user.','.$reg->pass_login.','.$reg->id;
                $r[$k]=['id'=>$reg->id, 'key'=>$k, 'user'=>$reg->pass_user, 'login'=>$reg->pass_login, 'pass'=>$reg->pass_pass];
            }
            if($opt['one']){
                $r=array_first($r);
            }
        }else{
            $r=null;
        }
        return $r;
    }


    /**
     * Retorna a um login disponível para ser processado pelo robô (pela função \App\Http\Controllers\WSRobotController->get_process())
     * @param $process_id - se informado, indica que se estiver ocupado e se for para este $process_id, então ao mesmo registrado já vinculado
     */
    public static function getLoginsAvailable($account_id,$process_id=null){
        $reg = AccountPass::where(['account_id'=>$account_id,'pass_status'=>'a']);
        if($process_id){
            $reg->where(function($q) use($process_id){
                $q->where('process_id',$process_id)->orWhereNull('process_id');
            });
        }else{
            $reg->whereNull('process_id');
        }
        //dd( \App\Services\DBService::getSqlWithBindings($reg)     );
        $reg = $reg->whereNull('pass_type')->orderBy('process_id','desc')->lockForUpdate()->first();//já contém a trava do registro
        if($reg){
            $k=$reg->pass_user.','.$reg->pass_login.','.$reg->id;
            return [
                'id'=>$reg->id,
                'key'=>$k,
                'user'=>$reg->pass_user,
                'login'=>$reg->pass_login,
                'pass'=>$reg->pass_pass
            ];
        }else{
            return null;
        }
    }


    /**
     * Retorna a um login disponível a partir do id
     * @param $pass_id - (int) id ou 'review' (usuário de revisão)
     */
    public static function getLoginById($account_id,$pass_id='review'){
        $pass=AccountPass::where('account_id',$account_id);
        if($pass_id=='review'){
            $pass->where('pass_type','t');//usuário de revisão
        }else{
            $pass->where('id',$pass_id);
        }
        return $pass->first();
    }

    /**
     * Seta que o login está ocupado
     */
    public static function setLoginBusy($pass_id,$process_id){
        AccountPass::find($pass_id)->update(['process_id'=>$process_id]);
    }

    /**
     * Seta que o login não está ocupado
     */
    public static function setLoginNotBusy($pass_id){
        AccountPass::find($pass_id)->update(['process_id'=>null]);
    }

    /**
     * Seta que o login não está ocupado a partir do process_id
     */
    public static function setLoginNotBusyByProcess($process_id){
        AccountPass::where('process_id',$process_id)->update(['process_id'=>null]);
    }

    /**
     * Limpa os logins do Quiver que estão ocupados com processos do robô que não esteja com status 'a' Em Andamento
     * Ou seja, ocorreu algum erro no fluxo do processo em que estes logins não foram desocupados
     */
    public static function clearBusyPass(){
        $m = AccountPass::whereNotNull('process_id')->get();
        if($m->count()>0){
            $p_model = new \App\Models\Base\ProcessRobot;
            foreach($m as $pass){
                if($p_model->where('id',$pass->process_id)->where('process_status','<>','a')->exists()){
                    $pass->update(['process_id'=>null]);
                }
            }
        }
        echo 'Finalizado em '.date('Y-m-d H:m:s');
    }


    /**
     * Retorna se o registro existe a partir do login
     */
    public static function loginExists($account_id,$login){
        return AccountPass::where(['account_id'=>$account_id,'pass_login'=>$login])->exists();
    }

    /**
     * Captura o ID a partir da chave na sintaxe: 'pass_user,pass_login,id'
     */
    public static function getIdByKeyLogin($key_login){
        $n = explode(',',$key_login);
        return $n[2]??null;

    }

}
