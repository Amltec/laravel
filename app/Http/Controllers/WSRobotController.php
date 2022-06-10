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
 *
 * Atualização 29/10/2021: foi desativado no método this->get_process() o recurso de $login_mode=insurer (pois não estava prático e não era usado), ficando apenas o login_mode=quiver
 *      No arquivo WSRobotController--(usa o loginmode=insurer)---bkp.php contém a versão original.
 */
class WSRobotController extends Process\ProcessController{
    private $WSRobot_config=[
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
     *          'release_login'     - (POST) libera o login de acesso ao quiver
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

        }else if($action=='set_process' || $action=='get_process' || $action=='get_data' || $action=='release_login'){
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
     * $request esperados: status,id,msg,continue_process(s|'')
     */
    protected function set_process($request, $key_active, $key_robot){
        //ddx($request->all());
        $r=$this->robot_check($key_active, $key_robot);
        if(!$r['success'])return $this->wsrobot_return(['status'=>'E','msg'=>$r['msg'],'action'=>'exit']);

        $status = $request->input('status');
        $id = $request->input('id');
        $continue_process = $request->continue_process=='s';//se true, não irá encerrar o registro na tabela process_execs

        //tira a trava do cadastro do login
        if(!$continue_process)AccountPassService::setLoginNotBusyByProcess($id);

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

        if(!$continue_process){
                if($status=='t'){
                    //atualiza o campo process_next_at com próxima data e hora em que será processado novamente (variável process_repeat_delay model ProcessRobot)
                    $next_at = date('Y-m-d H:i:s', strtotime($this->ProcessRobotModel->process_repeat_delay.' min', strtotime(date('Y-m-d H:i:s'))) );
                    $ProcessModel->update(['process_status'=>'p','process_next_at'=>$next_at]);//mantém o status=p (pronto para o robô) e process_next_at para ser executado novamnete mais tarde
                    return $this->wsrobot_return(['status'=>'R']);//como nada mudou (apenas o horário de agendamento), encerra retornando a função
                }else{
                    //process_status_changed=0 para indicar que foi alterado pelo robo
                    $ProcessModel->update(['process_status'=>$status,'process_status_changed'=>'0','process_order'=>null]);
                }
                $ProcessModel->setData('error_msg', $msg);//seta a mensagem de erro no campo geral
        }
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
            'continue_process'=>$continue_process,
            'return'=>['status'=>'R'],  //retorna sempre R para indicar para o autoit que fez o submit post com sucesso neste método
        ]]);

        //dd('***',$data_robot);
        if(!$continue_process){
            $login_use = $ProcessData['login_use']??null;
            if($login_use){
                //verifica e atualiza os dados de login com base no $status_code retornado
                $this->setAfterLogin($ProcessModel->account_id,$login_use,$status_code);
            }
        }

        $regExec->update(['process_end'=>date("Y-m-d H:i:s", time()),'status_code'=>$status_code]);
        //precisa gravar os dados json retornados em arquivo
        $ProcessModel->setText('exec_'.$regExec->id,$data_robot);


        if($continue_process){//gera um novo registro de controle na tabela process_robot_execs
            //seta a data e hora início de processamento
            $now = date("Y-m-d H:i:s", time());
            $count = $this->ProcessRobotExecsModel->where('process_id',$ProcessModel->id)->count();
            $this->ProcessRobotExecsModel->create(['id'=>$count+1,'process_id'=>$ProcessModel->id,'process_start'=>$now]);
        }

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

        //ajusta RobotModel->account_ids de string ex '1,2,...' para array [1,2,...]
        $robot_account_ids =$RobotModel->account_ids ? explode(',',$RobotModel->account_ids) : null;

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
                $ProcessModel = $this->filter1_get_process($this->ProcessRobotModel,$robot_account_ids,$RobotModelConfig)
                        ->where('robot_id',$RobotModel->id)
                        ->where('process_status','a')//o status tem que ser ='a' - em andamento / processamento pelo robô
                        ->where(function($query){
                                return $query
                                        ->where('process_next_at','<=',date('Y-m-d H:i:s'))
                                        ->orWhere(['process_next_at'=>null]);
                            })
                        ->orderByRaw('-process_order desc')
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
                $account_count = 0;
                if($robot_account_ids && count($robot_account_ids)>1){//está configurado para mais de uma conta exclusiva
                    $process_single = true;
                }else{
                    $process_single = false;
                }
                $account_total = $am->where(['account_status'=>'a','process_single'=>$process_single])->count();//conta quantas contas estão disponíveis para o robô processar

                while(true){
                        if($robot_account_ids && count($robot_account_ids)==1){//está configurado para uma só conta
                            $accountModel = $am->where(['account_status'=>'a','process_single'=>true])->whereMetadata(['robot_start__!='=>'off'])->find($robot_account_ids[0]);
                            if(!$accountModel){
                                if($method!='GET' || $isTest_methodPOST)DB::commit();
                                return $this->wsrobot_return(['status'=>'A','msg'=>'Nenhum registro disponível']);
                            }

                        }else{//o $RobotModel está configurado para múltiplas contas
                            if($robot_account_ids && count($robot_account_ids)>1){//está configurado para mais de uma conta exclusiva
                                $accountModel = $am->where(['account_status'=>'a','process_mark'=>false,'process_single'=>$process_single])->whereMetadata(['robot_start__!='=>'off'])->whereIn('id',$robot_account_ids)->first();
                            }else{
                                $accountModel = $am->where(['account_status'=>'a','process_mark'=>false,'process_single'=>$process_single])->whereMetadata(['robot_start__!='=>'off'])->first();//captura a próxima conta disponível na fila
                            }

                            $account_count++;
                            if(!$accountModel){//não tem mais conta disponível na fila, portanto seta process_mark=false para reiniciar
                                try{
                                    $am->where(['account_status'=>'a','process_single'=>$process_single])->update(['process_mark'=>false]);//limpa todas as contas da fila
                                }catch(Exception $ex){
                                    sleep(2);//aguarda 2s
                                    continue;
                                }
                                if(!$am->where(['account_status'=>'a','process_mark'=>false,'process_single'=>$process_single])->exists()){//verifica se existem alguma conta disponível para processar
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
                        $conf=$RobotModelConfig['filter_process_name']??null;
                        if($conf){//ordena na ordem informada em filter_process_name
                            $conf="'".str_replace(",","','",str_replace(' ','',$conf))."'";
                            $ProcessModel->orderByRaw("FIELD(process_name,". $conf .")");
                        }
                        $conf=$RobotModelConfig['filter_process_prod']??null;
                        if($conf){//ordena na ordem informada em filter_process_prod
                            $conf="'".str_replace(",","','",str_replace(' ','',$conf))."'";
                            $ProcessModel->orderByRaw("FIELD(process_prod,". $conf .")");
                        }

                        //ordem dos registros
                        $ProcessModel->orderByRaw('-process_order desc'); //registros com prioridade
                        $ProcessModel->orderBy('id', 'asc'); //demais registros na ordem de cadastro

                        //trava do registro
                        if($method!='GET' || $isTest_methodPOST)$ProcessModel->lockForUpdate();

                        //dd('a',$conf, \App\Services\DBService::getSqlWithBindings($ProcessModel), $RobotModelConfig);
                        $ProcessModel=$ProcessModel->first();//retorna apenas ao primeiro registro capturado


                        //$this->setAccountProcessMark($am);//marca que a conta já foi processada na fila
                        if(!$ProcessModel){//não achou registro na fila, quer dizer que não tem registro disponível
                            if($robot_account_ids && $robot_account_ids){//está configurado para uma só conta
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
        //dd($ProcessModel->toArray());


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


        //(Desativado) $login_mode = array_get($ConfigProcessName,'products.'.$ProcessModel->process_prod.'.login_mode', ($ConfigProcessName['login_mode']??'') );
        $login_mode = 'quiver'; //padrão nesta função


        if($method!='GET' || $isTest_methodPOST){//é apenas para visuaização pelo admin, e portanto não processa os dados abaixo
            //verifica quais logins estão disponíveis
            $login_use_available = AccountPassService::getLoginsAvailable($ProcessModel->account_id,$ProcessModel->id);

            if(!$login_use_available){//não tem logins disponíveis
                //AccountsService::setRobotStart($accountModel,'off');//pausa o robô somente nesta situação !!! analisando se esta linha é realmente necessária

                return $this->wsrobot_return(['status'=>'A','msg'=>'wbot04']);//seta status=A para que o robô aguarde e tente novamente
            }

        }else{//$method=GET
            //retorna ao primeiro login que encontrar apenas para compor a visualizão na tela
            $login_use_available = AccountPassService::loginsList($ProcessModel->account_id,['one'=>true]);
        }

        if(!$login_use_available){//erro de login (provavelmente não tem credencial ativada)
            return $this->wsrobot_return(['status'=>'A','msg'=>'wbot04']);//seta status=A para que o robô aguarde e tente novamente
        }

        //verifica se existe na configuração do corretor, o nome do usuário da configuração da central de senhas para capturar (somente o process_data irá utilizar isto)
        //obs: o arquivo WSRobotController--(usa o loginmode=insurer)---bkp.php está feito com a configuração mais completa para este caso, mas como este método atual foi todo refeito, deve
        if($ProcessModel->process_name=='seguradora_data'){
            $tmp = FunctionsProcessRobot::isActiveInsurerBrokerLogin($ProcessModel->insurer_id, $ProcessModel->broker_id);
            if(!$tmp['success'])return $this->setErrorProcess($tmp['code'],$ProcessModel,$request, $key_active, $key_robot);
            //if(($tmp['use_quiver']??'')=='s'){//o login nas seguradoras será feito pela central de senhas do quiver
                //neste caso manter os dados de login do quiver já capturados acima, mas adiciona o parâmetro user_quiver=s nas var $login_use_list
                $login_use_available['use_quiver']='s';
                $login_use_available['login_quiver']=$tmp['login_quiver']??'';
            //}else{//o login será feito diretamente no site da seguradora
            //    $login_use_available = ['ins'.$ProcessModel->insurer_id .'_bro'. $ProcessModel->broker_id => ['use_quiver'=>'n','login_quiver'=>'','user'=>$tmp['user'], 'login'=>$tmp['login'], 'pass'=>$tmp['pass'], 'code'=>$tmp['code']]]; //deixa apenas 1 opção de login
            // }
        }else{
            $login_use_available['use_quiver']='s';//para ficar compatível com o restante do código, pois agora está programado para o login_mode=quiver
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
            'login_use'=>$login_use_available,
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
        $count_p = $this->filter1_get_process($this->ProcessRobotModel,$robot_account_ids,$RobotModelConfig)
                    ->whereIn('process_status',['a','p'])//o status tem que ser ='p' (pronto para ser processado) ou 'a' (em andamento)
                    ->count();//conta os registros

        //*** Até aqui, quer dizer que achou o registro para processar ***
        $now = date("Y-m-d H:i:s", time());

        if($method!='GET' || $isTest_methodPOST){//se ==GET é apenas para visuaização pelo admin, e portanto não grava os dados abaixo
                //trava o cadastro do login
                AccountPassService::setLoginBusy($login_use_available['id'],$ProcessModel->id);

                //trava o registro do processo com o robô e altera o status ='a' - em andamento / processamento pelo robô
                $ProcessModel->update(['robot_id'=>$RobotModel->id,'process_status'=>'a']);

                //seta o login usado no respectivo registro do processo
                $ProcessModel->setData('login_use',$login_use_available['key']);

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
            'pass_id'=>$login_use_available['id'],
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
                'login_use'=>$login_use_available,//array[user,login,pass]
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
            if($account_id){
                if(is_array($account_id)){
                    $m->whereIn('account_id',$account_id);
                }else{
                    $m->where('account_id',$account_id);
                }
            }
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
            $errs = ['quil02','quil03','quil04','quil05','quil06','quil07','quil08','quil09'];
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
     * Libera o login de acesso ao quiver
     * @param Request $request - campos esperados:
     *      account_id  - ...
     *      pass_id    - id do login a ser liberado
     * @return [status:R|E]
     */
    protected function release_login($request, $key_active, $key_robot){
        AccountPassService::setLoginNotBusy($request->pass_id);
        return ['status'=>'R'];
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
