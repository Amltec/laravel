<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Base\ProcessRobot;
use App\Models\ProcessRobotExecs;
use App\Models\Robot;
use App\Models\Account;
use App\ProcessRobot\VarsProcessRobot;
use App\ProcessRobot\FunctionsProcessRobot;
use App\Services\AccountsService;
use App\Services\AccountPassService;
use DB;
use Config;
use Exception;

/**
 * Classe para receber todas as requsições do aplicativo do robô
 * Todas as requisições para esta classe não são autenticadas
 */
class WSRobotController extends Process\ProcessController{
    private $WSRobot_config=[
        //se true libera vários registros para serem processados sob o mesmo login
        //setar true somente se houver um login com este tipo de permissão
        //até o momento (26/08/2020) deve ser sempre false
        'allow_multiple_login'=>false,
        
        //chave de autorização do robô de revisão
        'robot_review_key'=>'daO}tA?P}7QQ2:nW!FO7f]dp76@sUr8z(,sd,is&H9:98m>jrKI,Fld#JGXfy>^h',
    ];
    

    public function __construct(ProcessRobot $ProcessRobotModel, Robot $RobotModel, ProcessRobotExecs $ProcessRobotExecs, Account $AccountModel){
        $this->ProcessRobotModel = $ProcessRobotModel;
        $this->ProcessRobotExecsModel = $ProcessRobotExecs;
        $this->RobotModel = $RobotModel;
        $this->AccountModel = $AccountModel;
    }
    
    /**
     * Atualiza a variável base da model $this->ProcessRobotModel e o $ProcessModel considerando a existência da classe personalizada pela var $process_name
     * Ex: atualiza nos casos:
     *      App\Models\ProcessRobot_ProcessName     - model personalizada do processo caso exista
     *      App\Models\ProcessRobot                 - model padrão
     * Sem retorno
     * Obs: esta classe é necessário, pois no request da solicitação, não tem o campo process_name (apenas o ID), e portanto tem que ler o request ID primeiro para saber o process_name e chamar este método
     */
    private function updateClassProcessModel(&$ProcessModel){
        if($ProcessModel){
            $str = '\App\Models\ProcessRobot_'.studly_case($ProcessModel->process_name);
            if($str != get_class($this->ProcessRobotModel) && class_exists($str)){//existe a classe personalizada
                //atualiza ar vars
                $this->ProcessRobotModel = new $str;
                $ProcessModel = $this->ProcessRobotModel->find($ProcessModel->id);//chama novamente para retornar a model atualizada
            }//else - não existe, nenhuma ação
        }
    }
    
    
    
    /**
     * Solicitação de processo pelo robo - funções: data(), set_process(), get_process()
     * Request POST|GET esperados: 
     *      key_active  - token de instalação do robo pela tabela robots.robot_keyaction
     *      key_robot   - código indenficação local do robo
     *      id           - id do processo (tabela process_robot.id)
     *      status       - valor de status (tabela process_robot.robot_status), valores: 
     *                          R - ok
     *                          E - erro
     *                          T - Tentar novamente. Se definido, não altera o status e apenas atualiza o campo 'process_next_at' para que seja tentado novamente no futuro
     *      msg          - mensagem de retorno (pode ser mensagem de erro - opcional)
     *      action       - nome da ação - valores:
     *          'robot_data'       - (POST) captura os dados do cadastro do robô a partir do token de acesso. Informar os parâmetros 'key_active'
     *          'robot_active'     - (POST) ação de ativação do robô. Informar os parâmetros: status, msg, id
     *          'set_process'      - (POST) alteração de dados do processo. Parãmetros esperados: id, status, msg
     *          'get_process'      - (POST|GET) captura de dados do processo. Parãmetros esperados: id.
     *                                      Obs: caso não informado o id, irá exibir o primeiro registro registro disponível para ser processado
     *                                      Obs2: Somente esta ação é permitida via método GET
     *          'get_process_review' - (POST|GET) captura de dados do processo para o robô de revisão. Parãmetros esperados: id.
     *                                      Obs2: o método GET é permitido apenas para usuários logados nível 'dev'
     *          'get_data'         - (POST|GET) captura um dado específico. Parâmetros esperados: process_name (cad_apolice, ...), .. sugeridos: id, field, ...
     *                                      Obs: será retornado a qualquer informação customizada solicitada...
     *                                      Obs2: o método GET é permitido apenas para usuários logados nível 'dev'
     *      //obs: os parâmetros key_active e key_robot são obrigatórios para todos os parâmetros 'action'
     * 
     * @return string xml [status, msg, action, data...]
     * @obs valores de 'status' retornados nesta função: R - ok, E - erro, A - aguardar e tentar novamente
     */
    public function data(Request $request){
        $method = $request->method();//GET, POST
        $key_active = $request->input('key_active');//token de instalação do robô
        $key_robot = $request->input('key_robot');//código de identificação do robô
        $action = $request->input('action');//ação
        //$userLogged = \Auth::user();
        if($method=='GET' && $action!='get_process' && $action!='get_data' && $action!='get_process_review')return $this->wsrobot_return(['status'=>'E','msg'=>'Acesso negado','action'=>'exit']);
        
        if($action=='robot_data'){
            $model = $this->RobotModel->where('key_active',$key_active)->first();
            if($model){
                return $this->wsrobot_return(['status'=>'R','key_active'=>$key_active,'robot_name'=>$model->robot_name,'id'=>$model->id,'robot_status'=>$model->robot_status,'robot_status_label'=>$model->status_label]);
            }else{
                return $this->wsrobot_return(['status'=>'E','msg'=>'Registro não encontrado']);
            }
            
        }else if($action=='robot_active'){
            $r=$this->robot_active($key_active, $key_robot);
            if(!$r['success'])return $this->wsrobot_return(['status'=>'E','msg'=>$r['msg']]);
            return $this->wsrobot_return(['status'=>'R']);
            
        }else if($action=='set_process' || $action=='get_process' || $action=='get_data'){
            $class_method=$action;
            return $this->$class_method($request, $key_active, $key_robot);
            
        }else if($action=='get_process_review'){
            $class_method=$action;
            return $this->$class_method($request, $key_active);
            
        }else{
            exit('Ação inválida');
        }
    }
    
    
    /*** funções complementares de wsrobot_data() ***
     * $request esperados: status,id,msg
     */
    protected function set_process($request, $key_active, $key_robot){
        //ddx($request->all());
        $r=$this->robot_check($key_active, $key_robot);
        if(!$r['success'])return $this->wsrobot_return(['status'=>'E','msg'=>$r['msg'],'action'=>'exit']);

        $status = $request->input('status');
        $id = $request->input('id');
        $msg = $request->input('msg');
        $status_code = $request->input('status_code');//???analisando melhor, talvez troque pelo campo msg apenas
        if(!$status_code)$status_code = strlen($msg)>6 ? 'err' : $msg;
        
        //limita os caracteres
        $msg = substr($msg,0,50);
        
        //dados retornado do robô
        $data_robot = $request->input('data');
        
        $tmp = trim(str_replace(['"',"'"],'',$data_robot));
        //echo '******* recebido web *******';ddx($tmp);exit;
        if($tmp){
            try{
                $data_robot = $data_robot ? json_decode($data_robot,true) : '';
            }catch(Exception $e){
                return $this->wsrobot_return(['status'=>'E','msg'=>'Erro ao decodificar json data: '. $request->input('data')]);
            }
        }
        if(!$tmp)$data_robot=[];
        //ddx($data_robot);exit;
        
        //ajusta os valores de status: do robo vem os valores: 'E,R' e ajustado para 'e,f'
        $status = ['R'=>'f','E'=>'e',''=>'e','T'=>'t'][$status]??null;
        if(!$status)return $this->wsrobot_return(['status'=>'E','msg'=>'Parâmetro status inválido']);
        
        //altera o status do processo
        $ProcessModel = $this->ProcessRobotModel->find($id);
        if(!$ProcessModel)return $this->wsrobot_return(['status'=>'E','msg'=>'Processo não encontrado - id '.$id]);
        
        //grava o retorno da execução atual
        $regExec = $this->ProcessRobotExecsModel
            ->where('process_id',$ProcessModel->id)
            ->whereNull('process_end')->first();
        if(!$regExec)return $this->wsrobot_return(['success'=>false,'msg'=>'Registro de controle da tabela p...execs não encontrado']);
        
        //atualiza a var ProcessRobotModel e $ProcessModel
        $this->updateClassProcessModel($ProcessModel);
        
        if($status=='t'){
            //atualiza o campo process_next_at com próxima data e hora em que será processado novamente (variável process_repeat_delay model ProcessRobot)
            $next_at = date('Y-m-d H:i:s', strtotime($this->ProcessRobotModel->process_repeat_delay.' min', strtotime(date('Y-m-d H:i:s'))) );
            $ProcessModel->update(['process_status'=>'p','process_next_at'=>$next_at]);//mantém o status=p (pronto para o robô) e process_next_at para ser executado novamnete mais tarde
            return $this->wsrobot_return(['status'=>'R']);//como nada mudou (apenas o horário de agendamento), encerra retornando a função
        }else{
            //process_status_changed=0 para indicar que foi alterado pelo robo
            $ProcessModel->update(['process_status'=>$status,'process_status_changed'=>'0']);
        }
        $ProcessModel->setData('error_msg', $msg);//seta a mensagem de erro no campo geral
        $ProcessData = $ProcessModel->getData();
        
        //chama a respectiva classe do processo de $ProcessModel->process_name
        $r= \App::call('\\App\\Http\\Controllers\\Process\\Process'. studly_case($ProcessModel->process_name) .'Controller@wsrobot_data_set_process',[[
            'ProcessModel'=>$ProcessModel,
            'status'=>$status,
            'status_code'=>$status_code,
            'msg'=>$msg,
            'data_robot'=>&$data_robot,
            'data'=>$ProcessData,
            'ProcessExecModel'=>$regExec,
            'return'=>['status'=>'R'],  //retorna sempre R para indicar para o autoit que fez o submit post com sucesso neste método
        ]]);
        //dd('***',$data_robot);
        $login_use = $ProcessData['login_use']??null;
        if($login_use){
            //verifica e atualiza os dados de login com base no $status_code retornado
            $this->setAfterLogin($ProcessModel->account_id,$login_use,$status_code);
        }
        
        $regExec->update(['process_end'=>date("Y-m-d H:i:s", time()),'status_code'=>$status_code]);
        //precisa gravar os dados json retornados em arquivo
        $ProcessModel->setText('exec_'.$regExec->id,$data_robot);
        
        //Retorna a 'R' para informar que foi salvo ok ou erro com sucesso
        return $this->wsrobot_return($r);
    }
    
        //seta o erro e redireciona
        private function setErrorProcess($status_code,$ProcessModel,$request, $key_active, $key_robot){
            $ProcessModel->update(['process_status'=>'e']);//seta como erro o registro
            $ProcessModel->setData('error_msg',$status_code);
            if($request->input('id')){
                return $this->wsrobot_return(['status'=>'A','msg'=>$status_code]);//seta status=A para que o robô aguarde e tente novamente
            }else{
                //volta a processar esta função novamente a procura de outro registro
                return $this->get_process($request, $key_active, $key_robot);
            }
        }

    protected function get_process($request, $key_active, $key_robot){
        $method = $request->method();//GET, POST
        $user = \Auth::user();
        $isTest_methodPOST = $user && $user->user_level=='dev' && $request->input('post')=='true';//se true, indica que permite alterar para o método 'post' para simular corretamente algumas funções abaixo
        
        //valida as permissões iniciais do robô
        $r = $this->validate_fnc_robo($request, $key_active, $key_robot);
        if($r['status']=='E')return $this->wsrobot_return($r);
        $RobotModel = $r['robotModel'];
        $RobotModelConfig = unserialize($RobotModel->robot_config);
        //ddx($request->all());exit;
        $global_robot_start = Config::data('robot_start');
        if($global_robot_start=='off' && ($method!='GET' || $isTest_methodPOST))return $this->wsrobot_return(['status'=>'0','msg'=>'Robô parado pelo programador']);
        
        if($method!='GET' || $isTest_methodPOST)DB::beginTransaction();
        
        $filter_process_id = $request->input('id');
        if($filter_process_id){//captura pelo id
            $ProcessModel = $this->ProcessRobotModel->find($filter_process_id);
            
            //atualiza a var ProcessRobotModel e $ProcessModel
            $this->updateClassProcessModel($ProcessModel);
            
            if(!$ProcessModel)//não achou registro na fila, quer dizer que não tem registro disponível
                    return $this->wsrobot_return(['status'=>'A','msg'=>'Nenhum registro disponível']);
            
            if($ProcessModel->process_status=='f' && $user->user_level!='dev')//registro já finalizado
                    return $this->wsrobot_return(['status'=>'F','msg'=>'Registro já finalizado. Não é possível exibir o XML do processo']);
            
            //if(empty($ProcessModel->process_ctrl_id))//campos vazios, ignora
            //        return $this->wsrobot_return(['status'=>'A','msg'=>'Nenhum registro disponível']);
            
            $accountModel = $ProcessModel->account;//dados da conta
            if($accountModel->account_status!='a'){
                //marca este registro com status de erro
                return $this->setErrorProcess('acco01',$ProcessModel,$request, $key_active, $key_robot);
            }
            
        }else{//procura o registro disponível
            $ProcessModel=null;
            if(!$isTest_methodPOST){//se for um POST de teste a partir do painel superadmin, então não precisa considerar processamento pendente do robô (pois não é uma requisição solicitada pelo robô autoit)
                //primeiro - procura se tem algum processo que ficou pendente para este mesmo robô
                $ProcessModel = $this->filter1_get_process($this->ProcessRobotModel,$RobotModel->account_id,$RobotModelConfig)
                        ->where('robot_id',$RobotModel->id)
                        ->where('process_status','a')//o status tem que ser ='a' - em andamento / processamento pelo robô
                        ->where(function($query){
                                return $query
                                        ->where('process_next_at','<=',date('Y-m-d H:i:s'))
                                        ->orWhere(['process_next_at'=>null]);
                            })
                        ->orderBy('id', 'asc')
                        ->first();//retorna apenas ao primeiro registro capturado
            }
            if($ProcessModel){
                $accountModel = $ProcessModel->account;//dados da conta
                if($accountModel->getData('robot_start')=='off'){//conta desativada
                    $ProcessModel=null;
                    $accountModel=null;
                }
            }
            
            if($ProcessModel){//achou um registro pendente
                //$accountModel = $ProcessModel->account;//dados da conta
                //nenhuma ação aqui
                
            }else{//não achou registro pendente
                //segundo - procura por um registro que está na fila de processamento
                $am=$this->AccountModel;
                if(!$RobotModel->account_id){
                    $account_total = $am->where(['account_status'=>'a','process_single'=>false])->count();//conta quantas contas estão disponíveis para o robô processar
                    $account_count = 0;
                }
                while(true){
                        if($RobotModel->account_id){//está configurado para uma só conta
                            $accountModel = $am->where(['account_status'=>'a','process_single'=>true])->whereMetadata(['robot_start__!='=>'off'])->find($RobotModel->account_id);
                            if(!$accountModel){
                                if($method!='GET' || $isTest_methodPOST)DB::commit();
                                return $this->wsrobot_return(['status'=>'A','msg'=>'Nenhum registro disponível']);
                            }
                        }else{//o $RobotModel está configurado para múltiplas contas
                            $accountModel = $am->where(['account_status'=>'a','process_mark'=>false,'process_single'=>false])->whereMetadata(['robot_start__!='=>'off'])->first();//captura a próxima conta disponível na fila
                            $account_count++;
                            if(!$accountModel){//não tem mais conta disponível na fila, portanto seta process_mark=false para reiniciar 
                                try{
                                    $am->where(['account_status'=>'a','process_single'=>false])->update(['process_mark'=>false]);//limpa todas as contas da fila
                                }catch(Exception $ex){
                                    sleep(2);//aguarda 2s
                                    continue;
                                }
                                if(!$am->where(['account_status'=>'a','process_mark'=>false,'process_single'=>false])->exists()){//verifica se existem alguma conta disponível para processar
                                    if($method!='GET' || $isTest_methodPOST)DB::commit();
                                    return $this->wsrobot_return(['status'=>'A','msg'=>'Nenhum registro disponível(2)']);
                                }

                                if($account_count<=$account_total){
                                    continue;//continua o loop para capturar a próxima conta
                                }else{
                                    if($method!='GET' || $isTest_methodPOST)DB::commit();
                                    return $this->wsrobot_return(['status'=>'A','msg'=>'Nenhum registro disponível (3)']);
                                }
                            }
                            try{
                                $accountModel->update(['process_mark'=>true]);//marca que a conta já foi processada na fila
                            }catch(Exception $ex){
                                sleep(1);//aguarda 1s
                                continue;
                            }
                        }
                        //dump([$accountModel->toArray(),$accountModel->getData('robot_start')]);
                        
                        $ProcessModel = $this->filter1_get_process($this->ProcessRobotModel,$accountModel->id,$RobotModelConfig)
                                ->where(function($query){
                                    return $query
                                            ->where('process_next_at','<=',date('Y-m-d H:i:s'))
                                            ->orWhere(['process_next_at'=>null]);
                                })
                                ->where(function($query){
                                    return $query
                                            ->where('process_status','p')//o status tem que ser ='p' - pronto para ser processado pelo robô (neste caso, qualquer robo poderá processar)
                                            ->orWhere(function($query){
                                                $query->where('process_status','a')->whereNull('robot_id');//lógica registros em andamento e que não tem um robo associado, podem ser listados neste processo
                                            });
                                });
                        //verifica se existe um filtro de configuração process_name do robô
                        $conf=$RobotModel['filter_process_name']??null;
                        if($conf){//ordena na ordem informada em filter_process_name
                            $conf="'".str_replace(",","','",$conf)."'";
                            $ProcessModel->orderByRaw("FIELD(process_name,". $conf .")");
                        }
                        $ProcessModel->orderBy('id', 'asc');
                        if($method!='GET' || $isTest_methodPOST)$ProcessModel->lockForUpdate();

                        //if($user->user_level=='dev')dd([$ProcessModel->toSql(),$ProcessModel->getBindings()]);
                        $ProcessModel=$ProcessModel->first();//retorna apenas ao primeiro registro capturado
                        
                        
                        
                        //$this->setAccountProcessMark($am);//marca que a conta já foi processada na fila
                        if(!$ProcessModel){//não achou registro na fila, quer dizer que não tem registro disponível
                            if($RobotModel->account_id){//está configurado para uma só conta
                                if($method!='GET' || $isTest_methodPOST)DB::commit();
                                return $this->wsrobot_return(['status'=>'A','msg'=>'Nenhum registro disponível (4)']);
                                
                            }else{//o $RobotModel está configurado para múltiplas contas
                                continue;//continua o loop
                            }
                            
                        }else{//achou o registro
                            break;//portanto interromper o loop para prosseguir
                        }
                        
                }
            }
        }
        
       
        //captura toda a configuração global do processo
        $ConfigProcessName = VarsProcessRobot::$configProcessNames[$ProcessModel->process_name];

        //captura todos os dados da conta
        $accountData = AccountsService::get($accountModel);
        if(($accountData->data['robot_start']??'')=='off'){//o robô esta conta está desativado
            return $this->wsrobot_return(['status'=>'A','msg'=>'wbot09']);//seta status=A para que o robô aguarde e tente novamente
        }
        $process_config = $accountData->config[$ProcessModel->process_name]??null;
        
        //verifica se a configuração é válida
        if(!$process_config){//não tem configuração
            return $this->setErrorProcess('acc02',$ProcessModel,$request, $key_active, $key_robot);
            
        }else if($process_config['active']!='s'){//serviço não ativo
            return $this->setErrorProcess('acc03',$ProcessModel,$request, $key_active, $key_robot);
        }
        
        //** verificação por login **
        $login_use='';
        $login_use_list = [];
        
        //if($ProcessModel->process_name=='seguradora_data'){//logins de cadastro na corretora
        $login_mode = array_get($ConfigProcessName,'products.'.$ProcessModel->process_prod.'.login_mode', ($ConfigProcessName['login_mode']??'') );
        
        if(!$login_mode){
            return $this->setErrorProcess('wbot04',$ProcessModel,$request, $key_active, $key_robot);
        }
        
        //captura os logins de cadastro no quiver (para $login_mode=quiver)
            if(($process_config['login_mode']??null)=='separate'){//login separado por corretor
                $broker = $ProcessModel->broker;
                if($broker->broker_col_user && $broker->broker_col_login)$login_use_list[$broker->broker_col_user .'.'. $broker->broker_col_login]=['user'=>$broker->broker_col_user, 'login'=>$broker->broker_col_login, 'pass'=>$broker->broker_col_senha];
                
            }else{//login no quiver
                //captura todos os logins do quivers (pega todas as senhas do cad_apolice)
                $login_use_list = AccountPassService::loginsList($ProcessModel->account_id);
                if(!$login_use_list){//quer dizer que não tem logins disponívels
                    AccountsService::setRobotStart($accountModel,'off');//pausa o robô somente nesta situação
                    return $this->wsrobot_return(['status'=>'A','msg'=>'wbot04']);//seta status=A para que o robô aguarde e tente novamente
                }
            }
            
        //verifica se o login é por cadastro de corretores e seguradoras
        if($login_mode=='insurer'){
            $tmp = FunctionsProcessRobot::isActiveInsurerBrokerLogin($ProcessModel->insurer_id, $ProcessModel->broker_id);
            if(!$tmp['success'])return $this->setErrorProcess($tmp['code'],$ProcessModel,$request, $key_active, $key_robot);
            if(($tmp['use_quiver']??'')=='s'){//o login nas seguradoras será feito pela central de senhas do quiver
                //neste caso manter os dados de login do quiver já capturados acima, mas adiciona o parâmetro user_quiver=s nas var $login_use_list
                foreach($login_use_list as $login=>$login_data){
                    $login_use_list[$login]['use_quiver']='s';
                    $login_use_list[$login]['login_quiver']=$tmp['login_quiver']??'';
                }
                
            }else{//o login será feito diretamente no site da seguradora
                $login_use_list = ['ins'.$ProcessModel->insurer_id .'_bro'. $ProcessModel->broker_id => ['use_quiver'=>'n','login_quiver'=>'','user'=>$tmp['user'], 'login'=>$tmp['login'], 'pass'=>$tmp['pass'], 'code'=>$tmp['code']]]; //deixa apenas 1 opção de login
            }
        }else{//$login_mode=='quiver'
            //para ficar compatível com o restante da programação, precisa ser adicionado os campos user_quiver e login_quiver
            foreach($login_use_list as $login=>$login_data){
                $login_use_list[$login]['use_quiver']='s';
                $login_use_list[$login]['login_quiver']='';
            }
        }
        //dd('fim',$login_use_list);
        
        //else{}    //nenhum login definido
        if($method!='GET' || $isTest_methodPOST){//é apenas para visuaização pelo admin, e portanto não processa os dados abaixo
            if($this->WSRobot_config['allow_multiple_login']){
                //*** permite vários logins rodando ao mesmo tempo ***
                //captura o primeiro login de $login_use_list
                foreach($login_use_list as $login=>$login_data){ $login_use=$login; break; }
                
            }else if($login_mode){//somente se existir algum tipo de login
                //*** verificação de duplicação de logins - permite apenas um login rodando ao mesmo tempo ***
                if(empty($login_use_list)){//se vazio, quer dizer que não tem nenhum login cadastrado para processar e portanto adicionar o registro como erro
                    return $this->setErrorProcess('wbot04',$ProcessModel,$request, $key_active, $key_robot);
                }
                //verifica se no usuário e login atual, já tem outra processo em andamento (para qualquer robô), pois no Quiver não é aceito mais de um mesmo usuário logado ao mesmo tempo
                foreach($login_use_list as $login=>$login_data){//pode existir mais de um login para verificação
                    $tmp_count = $this->ProcessRobotModel
                            ->where('process_status','a')//o status tem que ser ='a' - em andamento / processamento pelo robô
                            ->where('id','!=',$ProcessModel->id)//ignora o processo atual
                            ->WhereData(['login_use'=>$login])
                            ->count();
                    if($tmp_count==0){//quer dizer que este login não está sendo usado, portanto prossegue com ele
                        $login_use=$login;
                        break;
                    }
                }
                //dd($login_use,$login_use_list,$ProcessModel->id,$ProcessModel->getData('login_use'));
                
                //dd($login_use);
                if($login_use==''){//como $login_use=='', então quer dizer que existe um processo logado pelo robô com o mesmo login deste processo atual, 
                    //portanto reagenda este processo para ser executado em 10 minutos
                    $next_at = date('Y-m-d H:i:s', strtotime('10 min', strtotime(date('Y-m-d H:i:s'))) );
                    $ProcessModel->update(['process_status'=>'p','process_next_at'=>$next_at,'robot_id'=>null]);
                    $ProcessModel->setData('login_use','');
                    if($method!='GET' || $isTest_methodPOST)DB::commit();
                    if($filter_process_id){
                        return $this->wsrobot_return(['status'=>'A']);//seta status=A para que o robô aguarde e tente novamente
                    }else{
                        sleep(2);//aguarda 2s
                        //volta a processar esta função novamente a procura de outro registro
                        return $this->get_process($request, $key_active, $key_robot);
                    }
                }
                //dd($login_use);
                
            }
        }else{//$method=GET
            foreach($login_use_list as $login=>$login_data){
                $login_use=$login;//get first value from array
                break;
            }
        }
        //dd('a',$login_use_list);
        if($login_mode){//somente se existir algum tipo de login
            if(empty($login_use_list) || empty($login_use)){
                return $this->setErrorProcess('wbot05',$ProcessModel,$request, $key_active, $key_robot);
            }
        }
        
        //atualiza a var ProcessRobotModel e $ProcessModel
        $this->updateClassProcessModel($ProcessModel);
        
        //captura os dados da model
        $data = $ProcessModel->getData();
        
        
        $params = [
            'ProcessModel'=>$ProcessModel,
            'data'=>$data,
            'accountModel'=>$accountModel,
            'account_data'=>$accountData->data,
            'process_config'=>$process_config,
            'login_use'=>$login_use_list[$login_use]??'',//array[user,login,pass]
            'only_view'=>false, //está no modo de captura de dados para ser processado
            'login_mode'=>$login_mode,
        ];
        
        //Executa a classe respectiva do process_name com as possíveis variações de executação de cada caso
        $ProcessClass = '\\App\\Http\\Controllers\\Process\\Process'. studly_case($ProcessModel->process_name) .'Controller';
        $ProcessClass = \App::make($ProcessClass);
        
        $n = $ProcessClass->wsrobot_data_getBefore_process($params);
        if(($n['repeat']??false)===true){
            //volta a processar esta função novamente a procura de outro registro
            if($method!='GET' || $isTest_methodPOST)DB::commit();
            if($filter_process_id){//captura pelo id
                return $this->wsrobot_return(['status'=>'A','msg'=>'repeat']);//seta status=A para que o robô aguarde e tente novamente
            }else{
                return $this->get_process($request, $key_active, $key_robot);
            }
            
        }elseif(in_array(($n['status']??false),['A','E'])){
            return $this->wsrobot_return($n);
        }
        $ProcessModel=$n['ProcessModel']; $data=$n['data']; //atualiza as vars
        
        
        //conta o total de processos pendentes para serem processados
        //$count_p = $this->ProcessRobotModel
        $count_p = $this->filter1_get_process($this->ProcessRobotModel,$RobotModel->account_id,$RobotModelConfig)
                    ->whereIn('process_status',['a','p'])//o status tem que ser ='p' (pronto para ser processado) ou 'a' (em andamento)
                    ->count();//conta os registros
        
        //*** Até aqui, quer dizer que achou o registro para processar ***
        $now = date("Y-m-d H:i:s", time());
        
        
        if($method!='GET' || $isTest_methodPOST){//se ==GET é apenas para visuaização pelo admin, e portanto não grava os dados abaixo
                //trava o registro do processo com o robô e altera o status ='a' - em andamento / processamento pelo robô
                $ProcessModel->update(['robot_id'=>$RobotModel->id,'process_status'=>'a']);
                
                //atualiza a data que foi liberado este registro
                $RobotModel->update(['conn_last'=>$now]);
                
                //grava o retorno considerando a contagem de retorno já ocorridos
                $execModel = $this->ProcessRobotExecsModel->where('process_id',$ProcessModel->id)->whereNull('process_end')->first();
                if($execModel){//achou um regitro em aberto com processamento pendente (quer dizer que foi solicitado novamente os dados sem concluir a última execução
                    $execModel->update(['process_start'=>$now]);
                }else{
                    //seta a data e hora início de processamento
                    $count = $this->ProcessRobotExecsModel->where('process_id',$ProcessModel->id)->count();
                    $execModel = $this->ProcessRobotExecsModel->create(['id'=>$count+1,'process_id'=>$ProcessModel->id,'process_start'=>$now]);
                }
                
                //seta o login usado para evitar mais de 2 robôs em processamento com o memso login
                if($login_use)$ProcessModel->setData('login_use',$login_use);
        }else{
                //apenas captura o último registro da execução
                $execModel = $this->ProcessRobotExecsModel->where('process_id',$ProcessModel->id)->orderby('id','desc')->first();
                if(!$execModel){//nenhum processo foi executando ainda
                    //portanto gera um registro com id=1
                    $execModel=new \StdClass;
                    $execModel->id = 1;
                    $execModel->process_id = $ProcessModel->id;
                }
        }
        
        $xmlDefault = [
            'app_name'=>\Config::data('app_name'),
            'app_version'=>\Config::data('app_version'),
            'account_id'=>$ProcessModel->account_id,
            'process_name'=>$ProcessModel->process_name,
            'process_prod'=>$ProcessModel->process_prod,
            'process_sisweb'=>$login_mode,
            'process_id'=>$ProcessModel->id,
            'process_count_p'=>$count_p,//total de processos pendente para serem processados
            'process_test'=>$ProcessModel->process_test?'ok':'',
            'robot_id'=>$RobotModel->id,
            'status'=>'R',
            'action'=>'',
            'msg'=>'',
        ];
        
        //chama a respectiva classe do processo de $ProcessModel->process_name
        $r = $ProcessClass->wsrobot_data_getAfter_process([
                'ProcessModel'=>$ProcessModel,
                'ProcessExecModel'=>$execModel,
                'data'=>$data,
                'now'=>$now,
                'xmlDefault'=>$xmlDefault,
                'method'=>$method,
                'accountModel'=>$accountModel,
                'account_data'=>$accountData->data,
                'process_config'=>$process_config,
                'login_use'=>$login_use_list[$login_use]??'',//array[user,login,pass]
                'only_view'=>false, //está no modo de captura de dados para ser processado
                'login_mode'=>$login_mode,
                'user_level'=>$user ? $user->user_level : '',
            ]);
        
        if($method!='GET' || $isTest_methodPOST)DB::commit();
        
        if(($r['repeat']??false)===true){
            //volta a processar esta função novamente a procura de outro registro
            if($method!='GET' || $isTest_methodPOST){
                //remove o registro da tabela process_robot_execs
                $this->ProcessRobotExecsModel->where(['process_id'=>$ProcessModel->id,'id'=>$execModel->id])->whereNull('process_end')->delete();
            }
            if($filter_process_id){//captura pelo id
                return $this->wsrobot_return(['status'=>'A','msg'=>'repeat']);//seta status=A para que o robô aguarde e tente novamente
            }else{
                return $this->get_process($request, $key_active, $key_robot);
            }
        }else{
            return $this->wsrobot_return($r);
        }
    }
    
    
    //Filtra o model process_robot a partir das configurações do cadastro do robot (função auxiliar da tabela get_process())
    private function filter1_get_process($ProcessModel,$account_id,$RobotConfig){
        //verifica as configurações do robô e aplica os filtros
        $c=$RobotConfig;
        if($c || $account_id){
            $m=$ProcessModel->whereRaw('1=1');
            if($account_id)$m->where('account_id',$account_id);
            if($c['filter_process_name']??false) $m->whereIn('process_name',  array_map('trim',explode(',',$c['filter_process_name'])) );
            if($c['filter_process_prod']??false) $m->whereIn('process_prod',  array_map('trim',explode(',',$c['filter_process_prod'])) );
            if($c['filter_insurer_id']??false)   $m->whereIn('insurer_id',    array_map('trim',explode(',',$c['filter_insurer_id'])) );
            if($c['filter_broker_id']??false)    $m->whereIn('broker_id',     array_map('trim',explode(',',$c['filter_broker_id'])) );
            if($c['filter_process_id']??false)   $m->whereIn('id',            array_map('trim',explode(',',$c['filter_process_id'])) );
        }else{
            $m=$ProcessModel;
        }
        return $m;
    }
    


    /**
     * Retorno padrão em xml
     */
    private function wsrobot_return($r){
        $r = \App\Utilities\XMLUtility::convertArrToXml($r);
        return response($r, 200)->header('Content-Type', 'application/xml; charset=utf-8');
    }
    
    
    /**
     * Captura os dados do processo para o robô de revisar
     * Obs: por enquanto válido apenas para o process_name='cad_apolice'
     */
    protected function get_process_review($request, $key_active){
        $method = $request->method();//GET, POST
        if($method=='GET'){//apenas retorna 
            if(\Auth::user()->user_level!='dev')return $this->wsrobot_return(['status'=>'E','msg'=>'Acesso negado','action'=>'exit']);
        }else{//post - faz todas as verificações, requisição pelo robô   
            if($key_active!==$this->WSRobot_config['robot_review_key'])return $this->wsrobot_return(['status'=>'E','msg'=>'Chave inválida','action'=>'exit']);
        }
        
        $id = $request->input('id');
        if(!$id)return $this->wsrobot_return(['status'=>'E','msg'=>'Parâmetro incorreto']);
        $ProcessModel = $this->ProcessRobotModel->find($id);
        if(!$ProcessModel)return $this->wsrobot_return(['status'=>'E','msg'=>'Registro não encontrado']);
        
        //captura os dados da model
        $data = $ProcessModel->getData();
        
        //verifica os dados da conta para confirmar que é válido
        $accountModel = $ProcessModel->account;//dados da conta
        if($accountModel->account_status!='a'){
            //marca este registro com status de erro
            return $this->wsrobot_return(['status'=>'E','msg'=>'Conta cancelada']);
        }
        
        //captura todos os dados da conta
        $accountData = \Config::accountService()->get($accountModel);
        $process_config = $accountData->config[$ProcessModel->process_name]??null;
        
        //verifica se a configuração é válida
        if(!$process_config){//não tem configuração
            return $this->wsrobot_return(['status'=>'E','msg'=>'Falha ao preparar dados para o robô - configuração do serviço não encontrado','action'=>'exit']);
            
        }else if($process_config['active']!='s'){//serviço não ativo
            return $this->wsrobot_return(['status'=>'E','msg'=>'Falha ao preparar dados para o robô - serviço não ativo','action'=>'exit']);
        }
        
        //captura os dados da model
        $data = $ProcessModel->getData();
        
        
        //** verificação por login **
        //obs: o último login estará sempre separado como login de teste
        $login_use_list = AccountPassService::loginsList($ProcessModel->account_id,['review'=>true, 'one'=>true]);
        if(!$login_use_list){
            Config::accountService()->setRobotStart($accountModel,'off');//pausa o robô somente nesta situação
            return $this->wsrobot_return(['status'=>'E','msg'=>'Login não cadastrado']);
        }
        $login_use=$login_use_list['key'];
                
        $params = [
            'ProcessModel'=>$ProcessModel,
            'data'=>$data,
            'accountModel'=>$accountModel,
            'account_data'=>$accountData->data,
            'process_config'=>$process_config,
            'login_use'=>$login_use_list,//array[user,login,pass] //não usado por enquanto
            'only_view'=>true, //está de visualização de dados apenas
        ];
        
        //Executa a classe respectiva do process_name com as possíveis variações de executação de cada caso
        $ProcessClass = '\\App\\Http\\Controllers\\Process\\Process'. studly_case($ProcessModel->process_name) .'Controller';
        $ProcessClass = \App::make($ProcessClass);
        $n = $ProcessClass->wsrobot_data_getBefore_process($params,true);
        if($n['repeat']??false)return $this->wsrobot_return(['status'=>'E','msg'=>'Dados insuficientes para prosseguir']);
        
        $ProcessModel=$n['ProcessModel']; $data=$n['data']; //atualiza as vars
       
        
        //*** Até aqui, quer dizer que achou o registro para processar ***
        $now = date("Y-m-d H:i:s", time());
        
        $xmlDefault = [
            'app_name'=>\Config::data('app_name'),
            'app_version'=>\Config::data('app_version'),
            'account_id'=>$ProcessModel->account_id,
            'process_name'=>$ProcessModel->process_name,
            'process_prod'=>$ProcessModel->process_prod,
            'process_sisweb'=>'quiver',
            'process_id'=>$ProcessModel->id,
            'status'=>'R',
            'action'=>'',
            'msg'=>'',
            'quiser_login_use_all'=>$this->quiverLoginAll($ProcessModel->account_id),
        ];
        
        //chama a respectiva classe do processo de $ProcessModel->process_name
        $r = $ProcessClass->wsrobot_data_getAfter_process([
                'ProcessModel'=>$ProcessModel,
                'data'=>$data,
                'now'=>$now,
                'xmlDefault'=>$xmlDefault,
                'method'=>$method,
                'accountModel'=>$accountModel,
                'account_data'=>$accountData->data,
                'process_config'=>$process_config,
                'login_use'=>$login_use_list,//array[user,login,pass] //não usado por enquanto
                'only_view'=>true, //está de visualização de dados apenas
            ]);
        if(isset($r['success']) && $r['success']===false)return $this->wsrobot_return(['status'=>'E','msg'=> ($r['msg']??'Erro desconhecido') ]);
        
        
        return $this->wsrobot_return($r);
    }
    
    
    
    
    /**
     * Verifica e atualiza os dados de login com base no $status_code retornado
     */
    private function setAfterLogin($account_id,$login_use,$status_code){
        $pass_id = AccountPassService::getIdByKeyLogin($login_use);
        if(!$pass_id)return;
        
        $m = \App\Models\AccountPass::where(['account_id'=>$account_id,'id'=>$pass_id])->first();
        if($m){
            $arr = ['acessed_at'=>date('Y-m-d H:i:s')];
            
            //verifica se o erro retornado é erro de login (veja a descrição dos erros na classe \App\ProcessRobot\VarsProcessRobot::$statusCode)
            $errs = ['quil02','quil03','quil04','quil05','quil06'];
            if(in_array($status_code,$errs)){
                //atualiza o erro no login
                $arr['status_code']=$status_code;
                $arr['pass_status']='0';//bloqueado
            }
            $m->update($arr);
            \App\Services\LogsService::add('lock','account_pass',$pass_id,'Login do Quiver bloqueado: '. $login_use);
        }
    }
    
    
    /**
     * Retorna a lista de todos os nomes de logins do quiver
     * @return string - logins separados por '|'
     */
    private function quiverLoginAll($account_id){
        //retorna a toda a lista mesmo que o registro esteja cancelado
        $r = \App\Models\AccountPass::where(['account_id'=>$account_id])->pluck('pass_login')->toArray();
        return join('|',$r);
    }
    
    
    /**
     * Captura um dado específico. 
     * @param $request - parâmetros esperados: 
     *          - account_id - (obrigatório) id da conta
     *          - process_name - (obrigatório) nome do respectivo controller do process, ex: cad_apolice (irá procurar o controller ProcessCadApoliceController)
     *          - ... quaisquer demais parâmetros. Sugeridos: id, field, ....
     * @return [status:R|E,value: (json da função solicitada) ]
     * Lógica: irá procurar o método público wsrobot_getData do respectivo controller e espera um array contendo: [status,... demais campos]
     *      Sugestão: [status,field,...]
     */
    protected function get_data($request, $key_active, $key_robot){
        //valida as permissões iniciais do robô
        $r = $this->validate_fnc_robo($request, $key_active, $key_robot);
        if($r['status']=='E')return $this->wsrobot_return($r);
        
        $account = \App\Models\Account::where('account_status','a')->find($request->input('account_id'));
        if(!$account)return $this->wsrobot_return(['status'=>'E','msg'=>'Conta inválida']);
        
        $process_name = $request->input('process_name');
        if(!$process_name)return $this->wsrobot_return(['status'=>'E','msg'=>'Parâmetro process_name incorreto']);
        
        try{
            $r = \App::call('\\App\\Http\\Controllers\\Process\\Process'. studly_case($process_name) .'Controller@wsrobot_getData',[['account'=>$account] + $request->all()]);
            if($r['success']){
                return $this->wsrobot_return(['status'=>'R','value'=>json_encode($r)]);
            }else{
                return $this->wsrobot_return(['status'=>'E','msg'=>$r['msg']]);
            }
        }catch(Exception $e){
            return $this->wsrobot_return(['status'=>'E','msg'=>'Erro: '.$e->getMessage() .' - Info: file: '.$e->getFile(). ', line '.$e->getLine()]);
        }
    }
    
    
    /**
     * Valida se a respectiva função tem permissão para acessar as funções solicitadas pelo robô.
     * Verifica também se existe a permissão para acessar a função via método GET (somente dev)
     * @return array - [status, msg, action (status=E), robotModel (status=R)]
     */
    private function validate_fnc_robo($request, $key_active, $key_robot){
        //localiza o cadastro do robô
        if($request->method()=='GET'){//apenas retorna aos dados indenpendente do robo, para visuaização pelo admin    //obs: aqui não precisa usar a var $isTest_methodPOST, pois abaixo apenas valida o acesso pelo robô autoit
            if($request->input('robot_id')){//foi informado um id do robô para teste...
                $RobotModel = $this->RobotModel->find($request->input('robot_id'));
            }else{
                $RobotModel = $this->RobotModel->where('robot_status','a')->first();
            }
            if(!$RobotModel)return ['status'=>'E','msg'=>'Nenhum cadastro ativo de robô encontrado','action'=>'reset_token'];

        }else{//post - faz todas as verificações, requisição pelo robô
            $r=$this->robot_check($key_active, $key_robot);
            if(!$r['success'])return ['status'=>'E','msg'=>$r['msg'],'action'=>'reset_token'];
            $RobotModel = $r['robotModel'];
        }
        return ['status'=>'R','msg'=>'Sucesso','robotModel'=>$RobotModel];
    }
    
    
    //********** Funções do robô *************
    /*
     * Veriifica as credênciais do robô, se ele está ativado para as chaves informadas
     * @param string|model $key_active
     * @return [success,msg]
     */
    private function robot_check($key_active,$key_robot){
        if(empty($key_active) || empty($key_robot))return ['success'=>false,'msg'=>'Token de instalação inválida'];
        
        //verifica o registro do robô pela chave
        $RobotModel = is_object($key_active) ? $RobotModel : $this->RobotModel->where('key_active',$key_active)->first();
        if($RobotModel){
            if($RobotModel->robot_status!='a')return ['success'=>false,'msg'=>'Robô não ativado'];
            if($RobotModel->key_robot!=$key_robot)return ['success'=>false,'msg'=>'Chave do robô inválida'];
            return ['success'=>true,'robotModel'=>$RobotModel];
        }else{        
            return ['success'=>false,'msg'=>'Token de instalação inválida ou robô desativado'];
        }
    }
    
    /**
     * Ativa um cadastro do robô, atualizando as chaves de acesso
     * @param string|model $key_active
     * @return [success,msg]
     */
    private function robot_active($key_active,$key_robot){
        if(empty($key_active) || empty($key_robot))return ['success'=>false,'msg'=>'Token de instalação inválida'];
        
        //verifica o registro do robô pela chave
        $RobotModel = is_object($key_active) ? $key_active : $this->RobotModel->where('key_active',$key_active)->first();
        if(!$RobotModel){
            return ['success'=>false,'msg'=>'Token de instalação inválida'];
        }else{
            if($RobotModel->robot_status=='a' || $RobotModel->robot_status=='0'){
                $RobotModel->update(['robot_status'=>'a','key_robot'=>$key_robot]);
                return ['success'=>true];
            }else{
                return ['success'=>false,'msg'=>'Cadastro de robô bloqueado (STATUS='. strtoupper($RobotModel->robot_status) .')'];
            }
        }
    }
    
    
    /**
     * Desativa o acesso ao robô, por provável incompatibilida da chave
     * @param string|model $key_active
     * @return [success,msg]
     */
     //xxx analisando a real necessidade desta função
     /* private function robot_desactive($key_active){
        if(empty($key_active) || empty($key_robot))return ['success'=>false,'msg'=>'Token de instalação inválida'];
        $RobotModel = is_object($key_active) ? $key_active : $this->RobotModel->where('key_active',$key_active)->first();
        if($RobotModel){
            if($RobotModel->robot_status=='a' || $RobotModel->robot_status=='0')
                $RobotModel->update(['process_status'=>'e']);
        }
        return ['success'=>true];
    }
    */
}   