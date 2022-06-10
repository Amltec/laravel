<?php

namespace App\Http\Controllers\Process;

use Illuminate\Http\Request;
use App\Utilities\ValidateUtility;
use App\Utilities\FormatUtility;
use Illuminate\Support\Facades\App;
use Illuminate\Filesystem\Filesystem;
use Gate;
use Auth;

use App\Http\Controllers\Controller;
use App\ProcessRobot\VarsProcessRobot;
use App\Models\ProcessRobotResume;
use App\Models\ProcessRobotExecs;
use App\Services\AccountPassService;

/* 
 * Classe geral para todos os processos do robô
 * Esta classe deve ser extendida a partir de uma respectiva classe de processo
 * Importante: aqui deve conter todos os métodos referente ao processo do robô em geral (e também em associação com a classe \App\Http\Controllers\WSRobotsController)
 */
class ProcessController extends Controller {
    
    //*** variáveis privadas extendidas ***
    protected static $basename='';
    
    //model do processo do robô a ser definido pela classe filha
    protected $ProcessRobotModel=null;


    //*** varáveis públicas ***
    //Obs: os valores de status abaixo precisam sempre ser igual a estes: '0,p,a,f,w,e,c,i'    //mais informações consulte a documentação em .xlsx
    
    //Lista dos status disponíveis
    public static $status=[];
    
    //label de status - longo
    public static $statusLong=[];
    
    //cores dos status
    public static $statusColor=[];
    
    //menu do admin - sintaxe: [title=>..., link=>...]      //em link informar: (string) link, ou (array) [route_name,route_params]
    //caso não definido, captura o texto do menu automaticamente
    //se ==false não exibe no menu principal
    public static $menu_admin=[];
    public static $menu_superadmin=[];
    
    //submenus do admin - sintaxe: [ name=>[title=>..., link=>...], ...]        //em link informar: (string) link, ou (array) [route_name,route_params]
    //caso não definido, captura a relação de status automaticamente
    //se ==false não exibe os submenus
    public static $submenus_admin=[];
    public static $submenus_superadmin=[];
    
    
    //$name = cad_apolice, seguradora_files, seguradora_data, ...
    private $ProcessRobotModel2=[];
    public function getProcessModel($name){
        $m = $this->ProcessRobotModel2[$name]??null;
        if(!$m){
            $c = '\\App\\Models\\ProcessRobot_'.studly_case($name);
            $m = $this->ProcessRobotModel2[$name] = new $c;
        }
        return $m;
    }
    
    
    
    private function getProcessRobotModel(){
        if(!$this->ProcessRobotModel)$this->ProcessRobotModel = new \App\Models\Base\ProcessRobot;
        return $this->ProcessRobotModel;
    }
   
    /**
     * Verifica se o nome do processo e produto são válidos
     * Return boolean
     */
    public function checkProcessNames($name,$prod){
        if(isset(VarsProcessRobot::$configProcessNames[$name])){
            $n=VarsProcessRobot::$configProcessNames[$name]['products'];
            return isset($n[$prod]);
        }else{
            return false;
        }
    }
    
    
    //########## as funções abaixo estão sendo analisadas ############
    //Remove os dados da tabela resume para serem recalculados novamente
    //Atualização 19/02/2021 - função descartada, pois não está mais utilizando a tabela process_robot_resume
    //private static $ctrl_recalcResumeData=[];
    //protected function recalcResumeData($process_obj){//$process_obj = model ProcessRobot
        /*$process_date = $process_obj->process_date;
        if(!isset(self::$ctrl_recalcResumeData[$process_date])){
            $modelResume = ProcessRobotResume::where(['process_date'=>$process_date,'process_name'=>self::$basename]);
            $modelProcess = $this->getProcessRobotModel()->where(['process_date'=>$process_date,'process_name'=>self::$basename]);
            if(\Config::isSuperAdminPrefix()){//filtra pelo id da conta
                $account_id=$process_obj->account_id;
                $modelResume->where('account_id',$account_id);
                $modelProcess->where('account_id',$account_id);
            }
            
            //remove o registro da tabela resumo referente ao dia do processo alterado (para poder recalcular corretamente outros registros)
            $modelResume->delete();
            //Marca todos os registros do dia como não indexados
            $modelProcess->update(['indexed'=>false]);
            
            //controle para evitar repetição de processo
            self::$ctrl_recalcResumeData[$process_date]=true;
        }*/
    //}
    
    
    //Remove todos os dados do resumo para serem recalculados
    //Atualização 19/02/2021 - função descartada, pois não está mais utilizando a tabela process_robot_resume
    //public function get_clearResumeData(Request $request){
        /*
        $process_name = $request->input('process_name');
        
        //remove o registro da tabela resumo
        ProcessRobotResume::where(['process_name'=>$process_name])->delete();
        
        //Marca todos os registros como não indexados
        $this->getProcessRobotModel()->where(['process_name'=>$process_name])->update(['indexed'=>false]);
        
        return ['success'=>true];
        */
    //}
    
    
    
    /**
     * //função necessária nas classes filhas para o processo de exclusão final do registro
     * public function removeFinal(ProcessRobot $model){}
     */
    
    /**
     * Limpa os logins do Quiver que estão ocupados com processos do robô que não esteja com status 'a' Em Andamento
     * Ou seja, ocorreu algum erro no fluxo do processo em que estes logins não foram desocupados
     * Esta função deve ser usado dentro de um recurso agendado no sistema.
     */
    public function clearBusyPass(){
        AccountPassService::clearBusyPass();
    }
    
    
    /**
     * Remove automaticamente os registros que estão na lixeira a mais de 7 dias
     * Esta função deve ser usado dentro de um recurso agendado no sistema.
     * Remove todos os registros da tabela process_robot que estão na lixeira independente do campo process_name
     * Este processo deve ser executado somente via agendamento automático no sistema.
     * Obs: esta classe é chamada a partir da classe \App\Htto\Controllers\Site\Process\ProcessController.php->get_removeAutoTrash()
     */
    public function removeAutoTrash(){
        $remove_auto_days=7;//número de dias que o registro irá ficar na lixeira antes de ser excluído
        $dt=date('Y-m-d H:i:s', strtotime('-'.$remove_auto_days.' day', time()));//obs: a partir do 8º dia é que o registro irá aparecer para ser excluído
        $model = $this->getProcessRobotModel()->select('id','process_name')->where('deleted_at','<=',$dt)->withoutGlobalScope('account_user')->onlyTrashed()->get();
        if(!$model)return 'Concluído. Nenhum registro excluído. '. date("Y-m-d H:i:s");
        $count=0;
        foreach($model as $reg){
            //acessa o respectivo controller para excluír
            $r=\App::call('\\App\\Http\\Controllers\\Process\\Process'. studly_case($reg->process_name) .'Controller@removeFinal',[$reg->id]);
            echo '#'. $reg->id .' - '. ($r['success']?'Ok':'Erro') .' - '. $r['msg'].'<br>';
            $count++;
        }
        echo 'Concluído - '. ($count ? $count.' registro'.($count>1?'s':'') : 'Nenhum registro') .' - '. date("Y-m-d H:i:s");
    }

    
    
    /**
     * Remove o registro do processo
     * @param $request esperados:
     *      int $id - id da tabela process_robot.id
     * @param $onBefore - function callback. Return array[success, msg]. Se [sucess=false], interrompe o processo. Ex: function($model){ ...; return [sucess=>..., msg=>...] }
     */
    public function remove(Request $request, $onBefore=null){
        $is_admin = Gate::allows('admin');//se true, indica que é admin principal
        $data = $request->all();
        $id=$data['id'];
        $userLoggedLevel = Auth::user()->user_level;
        //dd($data);
        //if($userLoggedLevel=='user')return ['success'=>false,'msg' =>'Permissão negada','action'=>$data['action']];
        
        if($data['action']=='remove'){//exclui
            if(in_array($userLoggedLevel,['dev','superadmin'])){
                $model = $this->ProcessRobotModel->onlyTrashed()->find($id);
                
                if($onBefore){
                    $r = $onBefore($model);
                    if(!$r['success'])return $r;
                }
                
                $r = $this->removeFinal($model);
                $r['action']=$data['action'];
                return $r;
            }else{
                $r=['success'=>false,'msg' => 'Usuário sem permissão para remover'];
            }
            
        }else if($data['action']=='restore'){//restaura da lixeira
            $model = $this->ProcessRobotModel->onlyTrashed()->find($id);
            
            if($onBefore){
                $r = $onBefore($model);
                if(!$r['success'])return $r;
            }

            $model->addLog('restore');
            $model->restore();
            $r=['success'=>true,'msg' => 'Registro Restaurado','model'=>$model];
            
        }else if($data['action']=='trash'){//envia para lixeira
            $model = $this->ProcessRobotModel->find($id);
            if(!$model)return ['success'=>false,'msg' => 'Registro já está na lixeira','action'=>$data['action']];
            
            if($onBefore){
                $r = $onBefore($model);
                if(!$r['success'])return $r;
            }
                
            if(!in_array($userLoggedLevel,['dev','superadmin']) && $model->process_status=='f')return ['success'=>false,'msg' => 'Não é possível remover processo já finalizado(2)','model'=>$model,'action'=>$data['action']];
            $model->addLog('trash');
            $model->delete();
            $r=['success'=>true,'msg' => 'Registro removido','model'=>$model];
            
        }else{
            $r=['success'=>false,'msg' => 'Erro de parâmetro action','model'=>$model];
        }
        
        $r['action'] = $data['action'];
        return $r;
    }
    
    
    
    /**
     * Remove definitivamente
     * @param int|model $model - id ou model da tabela process_robot
     */
    public function removeFinal($model){
        if(is_int($model))$model = $this->ProcessRobotModel->onlyTrashed()->find($model);
        if($model){
            //remove a pasta do processo
            $p = $model->baseDir()['dir_final']??null;
            if(file_exists($p))(new Filesystem)->deleteDirectory($p);
            if(file_exists($p))return ['success'=>false,'msg' => 'Erro ao remover pasta do processo'];////erro ao remover pasta
            
            //remove a tabela de execuções dos processos do robo
            (new ProcessRobotExecs)->where('process_id',$model->id)->delete();
            
            //remove a taxonomia
            $model->delTaxRelation();
            
            //remove todos os metadados
            $model->delData('',true);
            
            //remove os metadados da tabela global metadata
            \App\Services\MetadataService::del('process_robot', $model->id);
            
            //adiciona o log
            $model->addLog('remove');
            
            //remove o registro principal
            $model->forceDelete();
            
            return ['success'=>true,'msg' => 'Processo removido com sucesso','model'=>$model];
        }else{
            return ['success'=>false,'msg' => 'Erro ao encontrar processo para remover','model'=>$model];
        }
    }

     /**
     * Limpa o campo de agendamento do processo (processo padrão para todos os casos)
      * 
     */
    public function post_clearNextAt(Request $request){
        $this->ProcessRobotModel->find($request->input('id'))->update(['process_next_at'=>null]);
        return ['success'=>true];
    }
    
    

    //** *************** webservice de ações para o app do robô ***********************
    /**
     * Esta classe só será iniciada a partir do controller App\Http\Controllers\wsrobotController@get_process, e é obrigatório este nome 'wsrobot_data_get_process'
     * Deve conter o restante dos comandos da solicitação do controller wsrobotController@get_process processando logo após a selação inicial dos registros a serem processados
     * @param com os mesmos parâmetros retornados do controller
     * @return array: [ProcessModel, DataModel]     //respectivos valores recebidos de $params     //veja + em wsrobotController@get_process
     *                  Obs: se repeat==true, então irá repetir recursivamente esta função
     * @obs: para retornar a nenhum registro, use: return ['status'=>'A|E','msg'=>'Nenhum registro disponível'];
     */
    public function wsrobot_data_getBefore_process($params){return $params;}

    /**
     * Esta classe só será iniciada a partir do controller App\Http\Controllers\wsrobotController@get_process, e é obrigatório este nome 'wsrobot_data_get_process'
     * Deve conter o restante dos comandos da solicitação do controller wsrobotController@get_process processando ao encerrar a função
     * @param com os mesmos parâmetros retornados do controller
     * @return array para montagem do xml final     //veja + em wsrobotController@get_process
     *              Obs: se repeat==true, então irá repetir recursivamente esta função
     * @obs: para retornar a nenhum registro, use: return ['status'=>'A|E','msg'=>'Nenhum registro disponível'];
     */
    public function wsrobot_data_getAfter_process($params){return $params['xmlDefault'];}
    
    /**
     * Esta classe só será iniciada a partir do controller App\Http\Controllers\wsrobotController@set_process, e é obrigatório este nome 'wsrobot_data_set_process'
     * Esta classe deve conter o restante dos comandos da solicitação do controller wsrobotController@set_process
     * @param com os mesmos parâmetros retornados do controller
     * @return array com ['status'=>'...']      //veja + em wsrobotController@set_process
     */
    public function wsrobot_data_set_process($params){return $params['return'];}
    
    
    /**
     * Esta classe só será iniciada a partir do controller App\Http\Controllers\wsrobotController@set_error, e é obrigatório este nome 'wsrobot_data_set_error'
     * Esta classe deve ser chamada sempre que ocorrer um erro na classe \wsrobotController
     * @param $status e $msg do erro
     * @return void
     */
    /*//em análise
    public function wsrobot_data_set_error($model,$status,$msg){
        $model->update(['process_status'=>$status]);//seta como erro o registro
        $model->setData('error_msg',$msg);
    }
    */
    
    /**
     * Procura na string de mensagem retornada do robô um respectivo campo dentro do padrão: {field:value}
     * @param $field - nome do campo
     * @param $str_msg - pode estar nos formatos:
     *                          {field:value...} *sep* qualquer outra string
     *                          qualquer outra string
     *                          Ex para cadastro de apólice: '{blocks:dado,prod,premio,anexo}*sep*...'
     * @return string com o valor do campo. Pode retornar a '' se não encontrar.
     */
    /*protected function wsrobot_getFieldInMsg($field,$str_msg){
        $r='';
        $nx=explode('*sep*',$str_msg);
        foreach($nx as $i => $line){
            $n='{'.$field.':';
            if(substr(strtolower($nx[$i]),0,strlen($n))==$n){
                $r=rtrim(substr($line,strlen($n)),'}');
                break;
            }
        }
        return $r;
    }*/
}