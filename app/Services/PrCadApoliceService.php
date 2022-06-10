<?php

namespace App\Services;
use Auth;

/**
 * Classe para Tabela de controle das ações do processo de cadastro de apólice
 * Utilizado para o process_name='cad_apolice'
 */
class PrCadApoliceService{
    private $models=[];
    
    //nomes dos processos
    public static $process=[
        'review'        => 'Revisão da Emissão',
        'apolice_check' => 'Verificação de Dados da Apólice',
    ];
    
    //status válidos para todos os processos
    public static $status=[
        '0' => 'Não iniciado',
        'p' => 'Pronto para o robô',
        'a' => 'Em andamento',
        'f' => 'Finalizado sem alterações',
        'w' => 'Finalizado com alterações',
        'e' => 'Erro',
        'y' => 'Erro parcial',
        'i' => 'Ignorado',
        'j' => 'Não processado',
        'n' => 'Não Encontrado',
        'x' => 'Excluído',
        'm' => 'Precisa de revisão manual 1',
        'n' => 'Precisa de revisão manual 2',
        'c' => 'Precisa de correção manual',
    ];
    
    //cores dos status
    public static $statusColor=[
        '0' => ['text'=>'text-light-blue','bg'=>'bg-light-blue-active'],
        'p' => ['text'=>'text-light-blue','bg'=>'bg-light-blue-active'],
        'a' => ['text'=>'text-light-blue','bg'=>'bg-light-blue-active'],
        'f' => ['text'=>'text-green','bg'=>'bg-green-active'],
        'w' => ['text'=>'text-teal','bg'=>'bg-teal-active'],
        'e' => ['text'=>'text-red','bg'=>'bg-red-active'],
        'y' => ['text'=>'text-red','bg'=>'bg-red-active'],
        'i' => ['text'=>'text-muted','bg'=>'bg-black'],
        'j' => ['text'=>'text-muted','bg'=>'bg-black'],
        'n' => ['text'=>'text-muted','bg'=>'bg-black'],
        'x' => ['text'=>'text-red','bg'=>'bg-red-active'],
        'm' => ['text'=>'text-aqua','bg'=>'bg-aqua'],
        'n' => ['text'=>'text-aqua','bg'=>'bg-aqua'],
        'c' => ['text'=>'text-aqua','bg'=>'bg-aqua'],
    ];
    
    //status por processo
    public static $status_by_process=[
        'review'=>['p','a','f','n','e','y','i'],
        'apolice_check'=>['p','a','f','w','m','n','c'],
    ];
    //retorna a lista de status por processo
    public static function getStatusByProcess($process){
        return array_intersect_key(self::$status,array_flip(self::$status_by_process[$process]));
    }
    
    public static function getProcessTitle($process){
        return self::$process[$process]??$process;
    }
    

    /**
     * Captura a model PrCadApolice ou ProcessRobot_CadApolice
     * @param $table - valores: 'cad_apolice' ou 'pr', 
     * @return Model
     */
    public function getModel($table){
        $table=['cad_apolice'=>'ProcessRobot_CadApolice', 'pr'=>'PrCadApolice'][$table]??null;
        if(!$table)return null;
        if(!isset($this->models[$table])){
            $className = '\\App\\Models\\'.$table;
            $this->models[$table] = new $className();
        }
        return $this->models[$table];
    }
    
    /**
     * Adiciona um registro na tabela pr_cad_apolice
     * @param $process_id - string|int id da tabela ou model (ProcessRobot_CadApolice ou PrCadApolice)
     * @param $process - nome do processo, valores: review, apolice_check , boleto_seg, boleto_quiver (mais informações na documentação em xlsx)
     * @param $status - valores de status
     * @param $action - valores: 
     *                      auto - (default) adiciona ou atualiza o registro caso exista
     *                      add  - apenas adiciona caso não exista
     *                      add+ - adiciona mesmo já existindo o processo (somente para um status diferente, ex: se já houver um registro com status='p', e for adicionar outro com este mesmo status, não será adicionado )
     *                      edit - apenas atualiza
     * @param $set_user - se true irá informar automaticamente o usuário logado
     * @return [success,msg,model]
     * 
     * !IMPORTANTE: este método está ficando sem utilidade, portanto considerar removê-lo futuramente (aí as respectivas ações de add/edit são executados diretamente pela model)... Obs2: aparentemente é mais a ação de 'edit' que está redundante.. analisar
     */
    public function add($process_id,$process,$status,$action='auto',$set_user=false){
        $is_add=true;
        $userLogged = Auth::user();
        $user_id = $set_user ? ($userLogged ? $userLogged->id : null) : null;
        $msg='';
        $model = null;
        
        if(is_object($process_id) && strpos(get_class($process_id),'PrCadApolice')!==false){
            $model = $process_id;
            $process_id = $model->process_id;
            
        }elseif(is_object($process_id)){//model de ProcessRobot_CadApolice
             $process_id = $process_id->id;
        }
        
        if(!$model)$model = $this->get($process_id,$process,'',$set_user,true);
        
        if($model && $action=='add+' && $model->status==$status)return ['success'=>false, 'msg'=>'Nenhuma ação' ,'model'=>$model];
        
        if($action=='auto' || $action=='edit'){
            if($model){
                $is_dd=false;
                $arr = ['status'=>$status];
                if($user_id)$arr['user_id']=$user_id;
                
                //status de finalizações, portanto atualiza a data finished_at
                if(in_array($status,['f','w','e','y','n','j','x']))$arr['finished_at']=date('Y-m-d H:i:s');
                
                $model->update($arr);
                $model->addLog('edit',$arr);
                $msg='Atualizado com sucesso';
            }else{
                if($action=='edit')$is_dd=false;
            }
        }else if($action=='add' && $model){//quer dizer que já existe e não tem permissão para adicionar novos
            $is_add=false;
        }
        //dd($action,$is_add,$msg);
        if($is_add && ($action=='auto' || $action=='add'|| $action=='add+')){
            $num = $this->getLastNum($process_id,$process) + 1;
            $arr = [
                'process_id'=>$process_id,
                'num'=>$num,
                'process'=>$process,
                'status'=>$status,
                'user_id'=>$user_id,
                'created_at'=>date('Y-m-d H:i:s')
            ];
            $model = $this->getModel('pr')->create($arr);
            $model->addLog('add',$arr);
            $msg='Adicionado com sucesso';
        }
        
        return ['success'=>($msg?true:false), 'msg'=>($msg?$msg:'Nenhuma ação') ,'model'=>$model];
    }
    
    /**
     * Atualiza os campos campos status, finished_at, user_id
     * @return boolean
     */
    public function set($process_id,$process,$status,$set_user=false){
        return $this->add($process_id,$process,$status,'edit',$set_user);
    }
    
    /**
     * Captura os dados do registro
     * @param $process_id - string|int id da tabela ou $model (para process_robot.id para process_name=cad_apolice)
     * @param $process - nome do processo, valores: review, apolice_check , boleto_seg, boleto_quiver (mais informações na documentação em xlsx)
     * @param $status - se definido irá filtrar os resutado pelo campo status
     * @param $one - se true - irá retornar apenas a model de 1 (último) registro, false - irá retorna a lista de registros
     * @return $model
     */
    public function get($process_id,$process,$status='',$exists_user=null,$one=false){
        $arr = ['process_id'=>$process_id,'process'=>$process];
        if($status)$arr['status']=$status;
        $model = $this->getModel('pr')->where($arr)->orderBy('num','desc');
        if($exists_user===true){
            $model->whereNotNull('user_id');
        }elseif($exists_user===false){
            $model->whereNull('user_id');
        }
        return $one ? $model->first() : $model->get();
    }
    
    public function getLastNum($process_id,$process){
        return $this->getModel('pr')->where(['process_id'=>$process_id,'process'=>$process])->orderBy('num','desc')->value('num')??0;
    }
    
    
    /**
     * Verifica se o valor de $process é valido
     */
    private function checkProcessName($prod){
        return in_array($prod, array_keys($process));
    }
    
}