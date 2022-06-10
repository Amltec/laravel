<?php

namespace App\Http\Controllers\Process;

use Illuminate\Http\Request;
use App\Http\Controllers\Process\ProcessController;
use Gate;
use App\Utilities\ValidateUtility;
use App\Utilities\FormatUtility;
use App\ProcessRobot\VarsProcessRobot;
use Illuminate\Filesystem\Filesystem;

use App\Models\ProcessRobot_CadApolice;
use App\Models\ProcessRobot_SeguradoraFiles;
use App\Models\ProcessRobotExecs;
use App\Models\Insurer;
use App\Models\Broker;
use App\Models\Robot;
use App\Models\PrSeguradoraFiles;
use Auth;
use Exception;
use App\Services\JobService;
use App\Services\AccountsService;

/**
 * Classe responsável pelo processo de download de apólices da área de seguradoras no Quiver
 */
class ProcessSeguradoraFilesController extends ProcessController{
    protected static $basename='seguradora_files';
    protected $process_prod='down_apo';


    //*** varáveis públicas ***
    //Menu lateral
    public static $submenus_admin=false;
    public static $submenus_superadmin=false;


    //*** Valores de Status ***
    //Lista dos status disponíveis
    public static $status=[
        'p'=>'Aguardando robô',
        'a'=>'Em andamento',
        '0'=>'Em indexação',
        'f'=>'Finalizado',
        'e'=>'Erro',
        'c'=>'Erro Operador',
        '1'=>'Em análise',
        'w'=>'Arquivo manual',
    ];


    //label de status - longo
    public static $statusLong=[
        'p'=>'Aguardando robô',
        'a'=>'Em andamento / processamento pelo robô',
        '0'=>'Em processo de indexação de dados',
        'f'=>'Finalizado',
        'e'=>'Erro',
        'c'=>'Erro Operador',
        '1'=>'Em análise interno',
        'w'=>'Aguarando arquivo manual',
    ];

    //cores dos status
    public static $statusColor=[
        'p'=>['text'=>'text-muted','bg'=>'bg-light-blue-active'],
        'a'=>['text'=>'text-muted','bg'=>'bg-light-blue-active'],
        '0'=>['text'=>'text-muted','bg'=>'bg-aqua disabled'],
        'f'=>['text'=>'text-green','bg'=>'bg-green-active'],
        'e'=>['text'=>'text-red','bg'=>'bg-red-active'],
        'c'=>['text'=>'text-red','bg'=>'bg-red-active'],
        '1'=>['text'=>'text-yellow','bg'=>'bg-yellow'],
        'w'=>['text'=>'text-muted','bg'=>'bg-teal-active'],
    ];


    //status para process_prod=mark_done
    public static $status_pr = [
        '0'=>['text'=>'Ag. indexação para saber se dever marcar', 'info'=>'Ag. indexação para saber se dever marcar'],
        '1'=>['text'=>'Nenhuma ação (code=1)', 'info'=>'Nenhuma ação, pois este registro é considerado ignorado'],
        'a'=>['text'=>'Ag. ação no Quiver (code=A)', 'info'=>'Ag. ação para marcar como concluído no Quiver'],
        'b'=>['text'=>'Ag. ação no Quiver (code=B)', 'info'=>'Ag. Ação para marcar como não concluído no Quiver'],
        'f'=>['text'=>'Já marcado no Quiver (code=F)', 'info'=>'Já marcado como concluído ou não concluído'],
        'e'=>['text'=>'Erro ao marcar no Quiver (code=E)', 'info'=>'Erro ao marcar como concluído'],
        'i'=>['text'=>'Erro ao marcar no Quiver (code=I)', 'info'=>'Erro ao marcar como não concluído'],
        'x'=>['text'=>'Erro na localização (code=X)', 'info'=>'Erro na localização de registro base de área de seguradoras dentro do robô para associar o registro de envio manual'],
    ];
    //agrupamentos dos status de $status_pr em 'a|p|e|f' (aguardando, pronto, erro, finalizado)
    public static $status_pr_group = [
        'a' => [],  //não existe neste caso
        '0' => ['0'],
        'p' => ['a','b'],
        'e' => ['e','i','x'],
        'f' => ['1','f'],
    ];


    //Relação de erros - sintaxe: [code=>text || [short text, long text]]
    public static $statusCode = [
        'pndw00' => 'Finalizado / nenhuma ação',
        'pndw01' => 'Arquivo temporário não encontrado',
        'pndw02' => 'Erro ao pesquisar apólices (botão pesquisar)',
        'pndw03' => 'Erro ao completar o download',
        'pndw04' => 'Erro ao fazer o download',
        'pndw05' => 'ZIP: pasta padrão do sistema não encontrado (processo zip não inciado)',
        'pndw06' => 'ZIP: Erro enviar arquivo por FTP',
        'pndw07' => 'ZIP: Arquivo não encontrado para envio por FTP',
        'pndw08' => 'Erro ao focar na janela do navegador',
        'pnmk00' => 'Nenhuma apólice para processar',
        'pnmk01' => 'Erro ao marcar todos os registros',
        'pnmk02' => 'Erro ao preencher os campos de pesquisa',
        'pnmk03' => 'Não conseguiu encontrar a janela ou sessão expirada pelo Quiver',
        'pnmk04' => 'Erro ao marcar como concluído/não concluído',
        'pnmk05' => 'Nenhum resultado encontrado no período',
    ];
    public static function getStatusCode($s=null,$retAllIfNull=true){
        $a = self::$statusCode + VarsProcessRobot::$statusCode;
        return $s ? ($a[$s]??$s) : ($retAllIfNull?$a:'');
    }



    public function __construct(ProcessRobot_SeguradoraFiles $ProcessRobotModel, Insurer $InsurerModel, Broker $BrokerModel, PrSeguradoraFiles $PrSeguradoraFiles){
        $this->ProcessRobotModel = $ProcessRobotModel;
        $this->InsurerModel = $InsurerModel;
        $this->BrokerModel = $BrokerModel;
        $this->PrSeguradoraFiles = $PrSeguradoraFiles;
    }


    public function index(Request $request){
        return $this->get_list($request);
    }


    public function get_list(Request $request){//GET
        $userLogged = Auth::user();
        $data = $request->all();
        $prefix = \Config::adminPrefix();


        $filter=[
            'account_id'=>$request->input('account_id'),
            'id'=>$request->input('id'),
            'status'=>$request->input('status'),
            'dt'=>$request->input('dt'),//date aaaa-mm-d      //aceita também date_start - date_2_end (sintaxe: yyyy-mm-dd - yyyy-mm-dd)
            'dts'=>$request->input('dts'),//date start aaaa-mm-d,
            'dte'=>$request->input('dte'),//date end aaaa-mm-d,
            'status_pr_group'=>$request->input('status_pr_group'),
        ];

        $model = $this->ProcessRobotModel->whereRaw('1=1');

        //filtra pelo id da conta
        if($prefix=='super-admin' && $filter['account_id'])$model->where('process_robot.account_id',$filter['account_id']);

        //fitlra por id
        if($filter['id'])$model->where('id',$filter['id']);

        //filtra por status
        if($filter['status']){
            $s = explode(',',$filter['status']);
            $model->whereIn('process_robot.process_status',$s);
        }

        //filtra por data
        if($filter['dt'])$model->whereDate('created_at',$filter['dt']);
        if($filter['dts'])$model->whereDate('created_at','>=',FormatUtility::convertDate($filter['dts']));
        if($filter['dte'])$model->whereDate('created_at','<=',FormatUtility::convertDate($filter['dte']));

        if(in_array($userLogged->user_level,['dev','superadmin'])){
            if(_GET('is_trash')=='s')$model->onlyTrashed();
        }

        //status da tabela pr_seguradora_files agrupados por 'a|p|e|f' (aguardando, pronto, erro, finalizado)

        if($filter['status_pr_group']!=''){
            $fields=['process_robot.id','process_robot.process_name','process_robot.process_prod','process_robot.process_status','process_robot.account_id','process_robot.insurer_id','process_robot.broker_id','process_robot.created_at','process_robot.updated_at','process_robot.process_ctrl_id'];
            $sx = explode(',',$filter['status_pr_group']);//caso tenha mais de um valor

            //retira o global escopo para, pois estava conflitando com a lógica abaixo, que utilizada 2x a tabela process_robot através do relacionamento
            $model->withoutGlobalScope('account_user');
            //verificação de segurança
            if($prefix!='super-admin')$model->where('process_robot.account_id', \Config::accountID());

            $model->join('pr_seguradora_files', 'pr_seguradora_files.process_id', '=', 'process_robot.id')
                ->join('process_robot as p2', 'p2.id', '=', 'pr_seguradora_files.process_rel_id')
                ->select($fields)
                ->groupBy($fields)
                ->whereNull('p2.deleted_at')
                ->where(function($query) use($sx){
                    $query->orWhere(function($query) use($sx){
                        foreach($sx as $s){
                            $s=self::$status_pr_group[$s]??null;
                            if($s)$query->whereIn('pr_seguradora_files.status',$s);
                        }
                    });
                });
        }

        //dd(\App\Services\DBService::getSqlWithBindings($model) );

        $model=$model
                ->orderBy('id', 'desc')
                ->paginate(_GETNumber('regs')??15);

        return view('admin.process_robot.'.self::$basename.'.list',[
            'process_name'=>self::$basename,
            'model'=>$model,
            'filter'=>$filter,
            'configProcessNames'=>VarsProcessRobot::$configProcessNames,
            'status_list'=>self::$status,
            'user_logged'=>$userLogged,
            'user_logged_level'=>$userLogged->user_level,
            'thisClass'=>$this,
        ]);
    }



    /**
     * Adiciona novo registro de processo de procura de apólices para download na Área de Seguradoras do Quiver
     * Obs: adiciona apenas 1 registro do dia na tabela process_robot.process_name='seguradora_files' para cada conta cadastrada e capturando os dados via request.
     */
    public function post_addProcess(Request $request){
        //Obs: somente se estiver em um painel super-admin é que será considerado o parâmetro $account_id, pois caso contrário já está validando pelo id conta do usuário logado na model ProcessRobotModel
        $prefix = \Config::adminPrefix();
        $account_id = $request->input('account_id');

        $dti = $request->input('dti');
        $dtf= $request->input('dtf');
        $is_upload_manual = $request->input('upload_manual')=='s';

        if($dti || $dtf){
            if(!ValidateUtility::isDate($dti))return ['success'=>false, 'msg'=>['dti'=>'Data inicial inválida']];
            if(!ValidateUtility::isDate($dtf))return ['success'=>false, 'msg'=>['dtf'=>'Data final inválida']];
            if(ValidateUtility::ifDate($dti,'>',$dtf))return ['success'=>false, 'msg'=>['dti'=>'Data inicial maior que a data final']];
        }

        if($prefix=='admin'){
            $ac_ids=[Auth::user()->getAuthAccount('id')];

        }else{//$prefix=='super-admin' ou qualquer valor diferente, processa todos (pois pode ser chamado pela rota não autenticada: site.com/process_seguradora_files/add_process)
            $ac_ids=[];
            $model=\App\Models\Account::select('id')->where('account_status','a');
            if($account_id){//somente as contas informadas
                $account_id=array_map('trim',explode(',',$account_id));
                $model->whereIn('id',$account_id);
            }//else{//todos as contas
            $model=$model->get();
            foreach($model as $reg){
                //verifica se o serviço de seguradora files está ativo
                if(array_get($reg->getData('config'),'seguradora_files.active')=='s')$ac_ids[]=$reg->id;
            }
        }
        $r=[];
        $c=0;
        if($ac_ids){
            foreach($ac_ids as $account_id){
                $n=$this->add_process($dti,$dtf,$account_id,$is_upload_manual);
                if($n['success'])$c++;
                $r[$account_id]=$n;
            }
        }
        if($prefix=='super-admin'){//várias contas
            if($ac_ids==$c){
                return ['success'=>true, 'msg'=>'Processos adicionados'];
            }else{
                $m='';
                foreach($r as $account_id=>$arr){
                    if(!$arr['success'])$m.=$account_id.': '.  (is_array($arr['msg'])?join(',',$arr['msg']):$arr['msg'])  .' | ';
                }
                return ['success'=>$c==count($ac_ids), 'msg'=>$c .' de '. count($ac_ids) .' concluídos'. ($m?' | '.trim($m,' | '):'')];
            }
        }else{//só terá a conta do usuário logado
            return array_shift($r);//retorna ao primeiro elemento da matriz
        }
    }
    //(!descartado) méthodo GET para o processo agendado
    //public function get_addProcess(Request $request){return $this->post_addProcess($request);}

    /**
     * Adiciona novo registro de processo de procura de apólices para download na Área de Seguradoras do Quiver
     * Obs: adiciona apenas 1 registro do dia na tabela process_robot.process_name='seguradora_files' e é preciso informar a data e conta desejada.
     */
    public function add_process($dti,$dtf,$account_id,$is_upl_manual=false){
        //Lógica: registra o processo em banco de dados para que o robô execute
        $is_manual=false;
        //dd($dti,$dtf);
        if($dti && $dtf){
             if(!ValidateUtility::isDate($dti))return ['success'=>false,'msg'=>['dti'=>'Data inválida']];
             if(!ValidateUtility::isDate($dtf))return ['success'=>false,'msg'=>['dtf'=>'Data inválida']];
             $dti=FormatUtility::convertDate($dti);
             $dtf=FormatUtility::convertDate($dtf);
             if(ValidateUtility::ifDate($dti,'>',$dtf))return ['success'=>'E', 'msg'=>['dti'=>'Data inicial maior que a data final']];
             $lastModel=null;
             $is_manual=true;//indica que foi inserido de forma manual a data

        }else{//$dti e $dtf não definido, portanto procura o último registro processado
            $lastModel = $this->ProcessRobotModel->withoutGlobalScope('account_user')->where(['process_name'=>self::$basename,'account_id'=>$account_id])->orderBy('id', 'desc')->first();
            //dd($account_id,$lastModel);
            if(!$lastModel){//não existe registro, portanto considera o dia anterior
                $dti = date("Y-m-d",strtotime("-1 days",strtotime(date("Y-m-d"))));//data inicial menos 1 dia
            }else{
                $lastData = $lastModel->data_array;
                $dts = array_get($lastData,'dts_search');
                if($dts)$dts = explode('|',$dts);//dti|dtf
                if(!$dts){//não achou o registro de data
                    $dti = date("Y-m-d",strtotime("-1 days",strtotime(date("Y-m-d"))));//data inicial menos 1 dia
                }else{
                    $dti = $dts[1];//deixa com o dia da última data de processamento. Motivo: sempre irá pegar/repetir o dia anteior
                }
            }
            $dtf = date("Y-m-d");//data final igual a data atual
            if(ValidateUtility::ifDate($dti,'>',$dtf))$dti=$dtf;

            //como $dti não foi informado, verifica se existe um registro com este mesmo período e caso exista não precisa inserir novamente
            $id=$this->ProcessRobotModel->withoutGlobalScope('account_user')->where(['process_name'=>self::$basename,'account_id'=>$account_id,'process_status'=>'p'])->whereData(['dts_search'=>$dti.'|'.$dtf])->value('id');
            //dd($dti,$dtf,$id);
            if($id){
                //achou o registro, quer dizer já está inserido para esta data
                return ['success'=>false,'msg'=>'Registro já inserido (ref '.$id.')'];
            }
        }

        //dd($this->ProcessRobotModel->find('342')->getPaths());

        $data=[
            'process_name'=>self::$basename,
            'process_prod'=>$this->process_prod,
            'process_ctrl_id'=>'',
            'process_status'=>$is_upl_manual?'w':'p',//p - aguardando robo, w - envio manual de arquivo pelo operador
            'process_date'=>date('Y-m-d'),
            'updated_at'=>date('Y-m-d H:i:s'),
            'user_id'=>Auth::user()->id??null,
            'account_id'=>$account_id,
        ];
        //dd($data);
        try{
            $model = $this->ProcessRobotModel->create($data);
            $r=[
                'success'=>true,
                'msg' => 'Processo adicionado',
                'action'=>'add',
                'id'=>$model->id,
                //'data' => $model->toArray(),
            ];

            //grava os dados adicionais
            $model->setData('dts_search', $dti.'|'.$dtf);
            $model->setData('filename_tmp', \App\Utilities\FunctionsUtility::keyGenerator(16).'.zip');

            //cria a pasta temporária caso não exista
            $path = $model->getPaths();
            if(!file_exists($path['upload_robo']))(new Filesystem)->makeDirectory($path['upload_robo'], $mode = 0777, true, true);


        } catch (Exception $e) {
            $r=['success'=>false,'msg'=>$e->getMessage()];
        }

        if($r['success'])$model->addLog('add',[
            'process_name'=>$data['process_name'],
            'process_prod'=>$data['process_prod'],
            'dti'=>$dti,
            'dtf'=>$dtf,
        ]);

        return $r;
    }



    /**
     * Página de visualização dos dados de cada processo/upload
     * Parâmetros de consulta da lista pr_seguradora_files: regs, process_rel_id
     */
    public function show(Robot $RobotModel,Request $req,$id){
        //dd('teste',$this->doExtracted($id));
        $userLogged = Auth::user();
        $f_status = _GET('st');
        $f_status2 = _GET('st2');//status da tabela process_robot

        //registro principal
        $model = $this->ProcessRobotModel->find($id);
        if(!$model)exit('Erro ao localizar registro');
        if($model->process_name!==self::$basename)exit('Erro ao localizar registro (2)');
        $RobotModel= $model->robot_id ? $RobotModel->find($model->robot_id) : null;

        $prsegfiles_status = self::$status_pr;
        foreach($prsegfiles_status as $s => &$v){$v['count']=0;}

        //soma os totais
        foreach($prsegfiles_status as $status=>$opt){
            $n=$this->PrSeguradoraFiles->selectRaw('(select count(1) from pr_seguradora_files where process_id=? and status=?) as total', [$model->id, (string)$status] )->value('total');
            $prsegfiles_status[$status]['count']=$n?$n:0;
        }

        $filesProcess = ProcessRobot_CadApolice::select(
                        //é obrigatório retornar a estes campos para o correto funcionamento da model ProcessRobot
                            'process_robot.id',
                            'process_robot.account_id',
                            'process_robot.process_date',
                        'process_robot.process_status',
                        'process_robot.process_name',
                        'process_robot.process_prod',
                        'process_robot.process_ctrl_id',
                        'process_robot.deleted_at',
                        'pr_seguradora_files.quiver_id',
                        'pr_seguradora_files.status as status',
                        'pr_seguradora_files.process_count',
                        'pr_seguradora_files.created_at',
                        'pr_seguradora_files.process_clone_id'
                        )
                //->with('PrSeguradoraFiles')
                ->join('pr_seguradora_files', 'pr_seguradora_files.process_rel_id', '=', 'process_robot.id')
                ->withTrashed()//pesquisa com os excluídos
                ->where('process_id',$model->id)
                ->orderBy('process_count','asc')
                ->orderBy('created_at','desc');

                        //contagem dos registros clonados
                        $tmp = clone $filesProcess;
                        $filesProcess_countClone = $tmp->whereNotNull('process_clone_id')->count();

                if($f_status2!='')$filesProcess->where('process_robot.process_status',$f_status2);//filtro por status
                //if($f_status!='')$filesProcess->where('status',$f_status);//filtro por status
                foreach(['st'=>'status','quiver_id'=>'quiver_id','rel_id'=>'process_rel_id'] as $qs=>$f){
                    $n=_GET($qs);
                    if($n!='')$filesProcess->where('pr_seguradora_files.'.$f,$n);
                }
                if($req->rel_st){//status do registro da tabela process_robot.id
                    $filesProcess->where('process_robot.process_status',$req->rel_st);
                }


        if(_GET('process_rel_id'))$filesProcess->where('process_rel_id',_GET('process_rel_id'));


        $filesProcess = $filesProcess->orderBy('process_count', 'asc')
                ->orderBy('process_prod', 'asc')
                ->orderBy('process_rel_id', 'desc')
                //;dd($filesProcess->toSql(), $filesProcess->getBindings());
                //->get();
                ->paginate(_GETNumber('regs')??50);

        $ProcessCadApolice = \App::make('\\App\\Http\\Controllers\\Process\\ProcessCadApoliceController');

        $execsModel = (new ProcessRobotExecs)->where('process_id',$id)->orderBy('id','asc')->get();



        //dd(  $model->where(['process_name'=>'seguradora_files','process_ctrl_id'=>'manual'])->value('id'), $model->toSql(), $model->getBindings()  );

        echo view('admin.process_robot.'.self::$basename.'.show',[
            'model'=>$model,
            'execsModel'=>$execsModel,
            'configProcessNames'=>VarsProcessRobot::$configProcessNames,
            'robotModel'=>$RobotModel,
            'status_list'=>self::$status,
            'user_logged_level'=>$userLogged->user_level,
            'filesProcess'=>$filesProcess,
            'filesProcess_countClone'=>$filesProcess_countClone,
            'ProcessCadApolice'=>$ProcessCadApolice,
            'prsegfiles_status'=>$prsegfiles_status,
            'f_status'=>$f_status,
            'thisClass'=>$this,
        ]);
    }


    /**
     * Remove o registro do processo
     * Obs: não é necessário informar o médido remove, pois será exatamente como o da classe pai
     */
    /*public function remove(Request $request){}*/

    /**
     * Remove definitivamente
     * @param int|model $model - id ou model da tabela process_robot
     */
    public function removeFinal($model){
        if(is_int($model))$model = $this->ProcessRobotModel->onlyTrashed()->find($model);
        if($model){
            //remove da tabela pr_process_files
            $this->PrSeguradoraFiles->where('process_id',$model->id)->delete();

            //remove todo o diretório deste processo com os arquivos
            $path = $model->getPaths();
            $fileSystem = new Filesystem;
            if(file_exists($path['dir']))           $fileSystem->deleteDirectory($path['dir']);
            if(file_exists($path['upload_robo']))   $fileSystem->deleteDirectory($path['upload_robo']);
        }

        return parent::removeFinal($model);
    }

    //esta função é apenas para teste e por isto somente o dev pode ter acesso //acesse: /super-admin/process_seguradora_files/do_extracted?id=...
    public function get_doExtracted(Request $request){
        if(Auth::user()->user_level=='dev'){
            return $this->doExtracted($request->input('id'));
        }else{
            return 'Acesso negado';
        }
    }
    public function post_doExtracted(Request $request){
        return $this->doExtracted($request->input('id'));
    }

    /**
     * Faz a extração do arquivo zip e upload de cada arquivo enviado
     * @param $model como: (ProcessRobot Model) model do registro, ou (int|string) id do registro
     * @return [success,msg]
     */
    public function doExtracted($ProcessModel){
        if(gettype($ProcessModel)!='object')$ProcessModel = $this->ProcessRobotModel->find($ProcessModel);
        if(!$ProcessModel)return ['success'=>false,'msg'=>'Registro não encontrado'];
        if($ProcessModel->process_status!='0'){
            return ['success'=>false,'msg'=>'Registro não preparado para indexação (status='. strtolower($ProcessModel->process_status) .')'];//o status precisa ser = 'a' (em andamento) para continuar
        }

        $data = $ProcessModel->getData();
        $path = $ProcessModel->getPaths($data);
        $process_count = (new ProcessRobotExecs)->where('process_id',$ProcessModel->id)->orderBy('id','desc')->value('id') ?? 0;

        /* Lógica para indexação:
         * Cria a pasta deste processo em /storage/app/{basename}/{process_id}
         * Move o arquivo para a pasta deste processo e extrai o zip
         * Para cada arquivo extraído, faz upload na classe \App\Http\Controllers\Process\ProcessCadApoliceController@auto_upload
         * Para cada arquivo enviado com sucesso, deleta o arquivo o respectivo arquivo (pois já foi feito upload)
         * Obs: Esta função apenas extrai os arquivos, mas não faz a indexação pela classe ProcessCadApoliceController@processFilePDF(), pois esta ação ocorrerá em segundo plano
         */
        $step = (int)($data['extraction_step']??'0');
        $fileSystem = new Filesystem;

        $files_error = $ProcessModel->getText('files_error');if(!is_array($files_error))$files_error=[];
        if($step==0){
            //verifica se o arquivo zip existe na pasta do upload do robo
            $file = $path['upload_robo'] .'/'. $data['filename_tmp'];

            if(!file_exists($file)){
                return ['success'=>false,'msg'=>'Arquivo '. $data['filename_tmp'] .' não encontrado na pasta '.$path['relative_upload_robo']];
            }

            //Cria a pasta deste processo
            if(!file_exists($path['dir']))$fileSystem->makeDirectory($path['dir'], $mode = 0777, true, true);

            //Move o arquivo para a pasta deste processo e extrai o zip
            $fileTo = $path['dir'].'/'.$data['filename_tmp'];
            $fileSystem->moveDirectory($file, $fileTo, true);//obs: neste processo o arquivo já será removido da pasta do robô
            $ProcessModel->setData('extraction_step',1);
            if(file_exists($file))@unlink($file);//remove o arquivo caso ainda exista (as vezes falha ao mover, mas copia mesmo assim)
            //dump([1,$fileTo]);
        }

        if($step<=1){
            $fileTo = $path['dir'].'/'.$data['filename_tmp'];

            $zip = new \ZipArchive;
            if($zip->open($fileTo)!==true){
                return ['success'=>false,'msg'=>'Falha ao abrir arquivo '. $data['filename_tmp'] .' para extração'];
            }
            if($zip->extractTo($path['dir'])!==true){//extrai na mesma pasta
                return ['success'=>false,'msg'=>'Falha ao extrair arquivo '. $data['filename_tmp'] .''];
            }
            $zip->close();

            $foldersList = $fileSystem->directories($path['dir']);
            foreach($foldersList as $folder){
                $foldername=basename($folder);
                if($foldername==$data['filename_tmp'])continue;
                $files_error[$foldername.'_'.$process_count]=[];
            }
            $ProcessModel->setText('files_error',$files_error);
            $ProcessModel->setData('extraction_step',2);
            //dump([2,$fileTo,$foldersList]);
        }

        if($step<=2){
            //Para cada arquivo extraído, faz upload na classe \App\Http\Controllers\Process\ProcessCadApoliceController@post_upload
            if(!isset($foldersList))$foldersList = $fileSystem->directories($path['dir']);
            foreach($foldersList as $folder){
                $foldername=basename($folder);
                if($foldername==$data['filename_tmp'])continue;

                //verifica se o nome da pasta, corresponde a um nome de produto/subprocesso registrado para o cadastro de apólices (ex automovel, ...)
                if(!$this->checkProcessNames('cad_apolice',$foldername)){
                    //apenas grava o retorno do erro
                    $files_error[$foldername.'_'.$process_count]='Nome de pasta inválida (not_product)';
                    $ProcessModel->setText('files_error',$files_error);
                    continue;
                }

                $filesList = $fileSystem->files($folder);
                //dump([3,'A',$folder,$filesList]);
                foreach($filesList as $file){
                    if(strtolower($file->getExtension())!='pdf')continue;//filtra para processar apenas arquivos pdf
                    $filename=$file->getFilename();
                    $path_file = $folder .'/'. $filename;

                    //Captura o nome do segurado e id do quiver a partir do nome do arquivo
                    //Padrão do nome do arquivo: "{nome-do-segurado}_{quiver-id}.pdf"
                    $n=pathinfo($filename)['filename'];
                    $n=explode('_',$n);
                    $quiver_id=$n[count($n)-1];
                    unset($n[count($n)-1]);
                    $segurado_nome=join(' ',$n);
                    if(!is_numeric($quiver_id))$quiver_id=null;

                    //verifica se existe o respectivo arquivo txt
                    $path_file_txt = $folder .'/'. substr($filename,0,-4).'.txt';
                    if(!file_exists($path_file_txt))$path_file_txt=null;//se o arquivo não existir, deixa vazio
                    //dd(123);

                    $data_input=[
                        'file'=>$path_file,
                        'process_name'=>'cad_apolice',
                        'process_prod'=>$foldername,
                        'account_id'=>$ProcessModel->account_id,
                        'process_auto'=>true
                    ];
                    $ProcessCadApolice = \App::make('\\App\\Http\\Controllers\\Process\\ProcessCadApoliceController');
                    $r = $ProcessCadApolice->auto_upload($data_input);

                    //adiciona o registro de log
                    $log_msg='processo ProcessSeguroraFilesController@doExtracted';
                    if($r['success']){
                        $prSegFiles = $this->PrSeguradoraFiles->create([
                            'process_id'=>$ProcessModel->id,
                            'quiver_id'=>$quiver_id,
                            'process_rel_id'=>$r['model']->id,
                            'created_at'=>$r['model']->created_at,
                            'status'=>'0',//aguardando indexação para saber se deve ser processado
                            'process_count'=>$process_count,
                        ]);

                        if($segurado_nome)(new \App\Services\PrSegService)->setTableDados($r['model']->id,['segurado_nome'=>$segurado_nome]);//analisando este código para substituir a linha acima
                        $r['model']->addLog('add',['msg'=>'Adicionado '.$log_msg,'data'=>$data_input]);//adiciona o log
                    }else{
                        if(($r['msg']??'')!='Arquivo não enviado')
                            \App\Services\LogsService::add('error', 'cad_apolice',0,'Erro ao enviar '.$log_msg.' <br>'.print_r([$r,$data_input],true));
                    }
                    //dump([3,'B',$r]);
                    if($r['success']){
                        $cadApoliceProcess=$r['model'];//captura apenas a model

                        /*** Importação do arquivo de texto extraído no pdf (é possível que em algums casos não exista este arquivo)
                             Este txt é um arquivo extraído no padrão "ws02 - Java: com pdfbox" e caso o arquivo da respectiva classe da seguradora tiver o parãmetro $pdf_engine!='ws02', então será descartado este txt e processado a extração do texto novamente
                             Este arquivo existe apenas pois o robo autoit já envia os txts extraídos (com java) junto dos pdfs.
                             Existe apenas para este de download dos arquivos de área de seguradoras (processos desta classe atual)
                         */
                        /*$file_txt=null;
                        //faz a leitura do arquivo txt
                        if($path_file_txt && file_exists($path_file_txt))$file_txt = file_get_contents($path_file_txt);
                        //grava o txt no db
                        if($file_txt){
                            $cadApoliceProcess->setData('file_text',$file_txt);
                            $cadApoliceProcess->setData('pdf_engine','autoit_ws02');//este campo é considerado pela classe \App\Http\Controllers\Process\ProcessCadApoliceController@extractTextFromPdf
                        }*/
                        //move o arquivo para dentro da pasta do processo (atualização em 16/11/2020)
                        if(file_exists($path_file_txt)){
                            $process_path = $cadApoliceProcess->baseDir();
                            $f = $process_path['dir_final'];
                            if(!file_exists($f))$fileSystem->makeDirectory($f);
                            $fileSystem->move($path_file_txt, $f.'/'.$cadApoliceProcess->id.'_text.data');
                            //remove o arquivo
                            if(file_exists($path_file_txt))$fileSystem->delete($path_file_txt);
                            //este campo é considerado pela classe \App\Http\Controllers\Process\ProcessCadApoliceController@extractTextFromPdf
                            $cadApoliceProcess->setData('pdf_engine','autoit_ws02');
                        }
                        //caso ainda exista o arquivo (ocorreu algum erro ao remover durante o upload), deleta o arquivo pdf e txt
                        if(file_exists($path_file))$fileSystem->delete($path_file);

                    }else{//erro
                        //apenas grava o retorno do erro
                        $files_error[$foldername.'_'.$process_count][]=$filename;
                    }
                }
                $ProcessModel->setText('files_error',$files_error);

                //remove a pasta
                $fileSystem->deleteDirectory($folder);
            }
            //$ProcessModel->setData('extraction_step',3);//não é necessário gravar a etapa, pois em caso de repetição, deve executar sempre a etapa 2
        }

        //verifica se a var $files_error está vazia
        $n=0;
        foreach($files_error as $f=>$v){
            if(!$v)$n++;
        }
        if($n==count($files_error))$files_error=[];
        //dd([3,$step,$files_error,$n]);

        //verifica se deu tudo certo
        $count_R = $this->PrSeguradoraFiles->where(['process_id'=>$ProcessModel->id,'process_count'=>$process_count])->count();//inseridos com sucesso
        $count_E = count($files_error);//erros ao inserir
        //dd($count_R,$count_E);
        $ProcessModel->delData('error_msg');
        $ProcessModel->delData('extraction_step');

        if($count_R==$count_E){//erro em todos
            $ProcessModel->update(['process_status'=>'e']);
            $n='Falha ao extrair arquivos';
            $ProcessModel->addLog('error',['msg'=>$n,'files_error'=>$files_error]);
            return ['success'=>false,'msg'=>$n];

        }else{//sucesso (pelo menos 1 arquivo enviado com sucesso)
            $ProcessModel->update(['process_status'=>'f']);
            $ProcessModel->addLog('log',['msg'=>($count_E==0?'Extraído com sucesso':'Extraído com erro') ,'files_error'=>$files_error]);
            if($count_E==0)$ProcessModel->delText('files_error');

            //deleta o arquivo zip, pois não é mais necessário
            $n = $path['dir'].'/'.$data['filename_tmp'];
            if(file_exists($n))@unlink($n);
            return ['success'=>true,'msg'=>'Extração processada com sucesso'];
        }
    }




    /**
     * Altera os status de todos os ids informados
     * Função para o botão 'alterar status' da lista de processos
     */
    public function post_changeAllStatus(Request $request){
        $data   = $request->all();
        if(!is_array($data['ids']))return ['success'=>false,'msg'=>'Erro de parâmetro ID'];
        if($data['status']=='')return ['success'=>false,'msg'=>['status'=>'Status não definido']];

        $next_at = $data['next_at']??null;
        if($next_at && (int)$next_at){//se $next_at='00/00/0000 00:00', então irá limpar o campo process_next_at
            if(!ValidateUtility::isDate($next_at))return ['success'=>false,'msg'=>['next_at'=>'Data inválida']];
            if(ValidateUtility::ifDate(FormatUtility::convertDate($next_at,true), '<=', date('Y-m-d H:i:s')))return ['success'=>false,'msg'=>['next_at'=>'Data precisa ser maior que agora']];
            $next_at = FormatUtility::convertDate($next_at,true);
        }

        foreach($data['ids'] as $id){
            $model = $this->ProcessRobotModel->find($id);
            if($model){
                //verifica se a conta não está cancelada, e neste caso não pode atualizar
                if($model->account->account_status!='a')continue;//pula neste caso

                $model->update(['process_order'=>null]);//limpa o campo ordem sempre ao alterar o status

                $st=$data['status'];
                $old_status = $model->process_status;

                //atualiza o status
                $arr=['process_status'=>$st];
                if($st=='p'){
                    $arr['updated_at']=date('Y-m-d H:i:s');//atualiza a data sempre que o status = Pronto para o robô
                    $arr['robot_id']=null;//limpa o id do robô para que qualquer robô possa processá-lo
                }
                if($next_at)$arr['process_next_at'] = (int)$next_at ? $next_at : null;
                $model->update($arr);

                //adiciona o log
                $model->addLog('edit','Status atualizado de: '. self::$status[$old_status] .'('.$old_status.')  - para: '. self::$status[$st] .'('.$st.')');
            }
        }

        return ['success'=>true];
    }




    /**
     * Faz o download do arquivo de modo seguro (pois esta classe só pode ser acessada por usuários logados)
     */
    public function get_download(Request $request,$id){
        if(empty($id))exit('Erro(1)');
        $model = $this->ProcessRobotModel->find($id);
        //dd($model->basePath(),$model->getPaths());
        $paths = $model->getPaths();
        $file = $paths['file'];
        if(!file_exists($file))exit('Arquivo não existe');

        //verifica se foi informado um arquivo descompatado para download
        //obs: desativado, pois no momento os arquivos descompactados são excluídos após serem feitos uploads
        /*$infile = $request->input('infile');
        if(!empty($infile)){
            $name = $infile;
            $file = $paths['dir'].'/'.$name;
            if(!file_exists($file))exit('Arquivo não existe (2)');
        }else{//arquivo principal zip
            $name = date('Y-m-d').'_'.$paths['filename'];
        }*/
        $name = date('Y-m-d').'_'.$paths['filename'];
        return response()->download($file, $name);
    }



    /**
     * Faz a confirmação que o upload manual de arquivo zip foi concluído
     */
    public function post_uploadManualConfirm(Request $request){
        $id = $request->input('id');
        $model = $this->ProcessRobotModel->find($id);

        if(!$model)return ['success'=>false,'msg'=>'Registro não encontrado'];
        if($model->process_status!='w')return ['success'=>false,'msg'=>'Registro bloqueado para esta ação (status atual='. strtoupper($model->process_status) .')'];

        //verifica se o arquivo existe no direrório
        $path = $model->getPaths();
        if(!file_exists($path['file']))return ['success'=>false,'msg'=>'Arquivo '. $path['filename'] .' não encontrado no diretório '.$path['relative_upload_robo']];

        $model->update(['process_status'=>'0']);//atualiza o status para 0 = indexação de dados
        $model->setData('extraction_step',1);//como já foi feito o upload na pasta correta, marca que já está ok a etapa '1' da extração

        //prossege com a extração de arquivos
        return $this->doExtracted($model);
    }


    /**
     * Seta uma nova execução para um registro já existente da tabela seguradora_files.
     * Este processo deve ser executado somente via agendamento automático no sistema.
     * @param $request - são os mesmos da função $this->post_addProcess()
     *                 - aceita também 'id' para executar somente para um registro em específico (caso não informado, será processado para todos)
     * @param $from_job - se true indica que esta função está sendo reprocessada a partir da função JobStart()
     * Return string - mensagem de retorno
     */
    public function get_addProcessAuto(Request $request,$from_job=false){
        $id = $request->input('id');
        $regs = $this->ProcessRobotModel->where('process_name',self::$basename);

        if($id){//se informado o id, apenas o status precisa ser alterado
            //Obs: este IF é executado a partir da requisição da página /process_seguradora_files/{id}/show
            $regs=$regs->find($id);
            //apenas altera o status para =p (pronto para o robô que já será o suficiente (obs: na lógica quando está status=p irá processar o mesmo registro capturando novamente as apólices na área de seguradoras) )
            if($regs){
                $regs->update(['process_status'=>'p']);
                $regs->setData('new_process','down_apo');
                $regs->addLog('log','Nova busca de arquivos');
            }

        }else{
            //Obs: este IF é de forma automatica pelo Cronjob do provedor ou pela função JobStart()
            //verifica se existem registros gerados no dia para tabela seguradora_files
            $regs->whereDate('created_at',date('Y-m-d'));
            if($regs->count()==0){//não existem registros do dia
                //adiciona novos registros
                try{
                    $this->post_addProcess($request);
                } catch (Exception $e){
                    if(!$from_job){//se esta função vier da função JobStart(), não precisa ser execucada para não gerar novas filas de erros
                        JobService::send($this)->delay( now()->addMinutes(15) );//espera 15 min para executar este processo na fila
                    }else{
                        \App::abort(500, $e->getMessage());
                    }
                }

            }else{//já existem processos do dia
                //apenas reconfigura para poder reprocessar de novo
                if($regs->count()==0)return ['success'=>false,'msg'=>'Nenhum registro encontrado'];
                $regs=$regs->get();
                foreach($regs as $reg){
                    //apenas altera o status para =p (pronto para o robô que já será o suficiente (obs: na lógica quando está status=p irá processar o mesmo registro capturando novamente as apólices na área de seguradoras) )
                    try{
                        $reg->update(['process_status'=>'p']);
                        $reg->setData('new_process','down_apo');
                        $reg->addLog('log','Nova busca de arquivos');
                    } catch (Exception $e){
                        if(!$from_job){//se esta função vier da função JobStart(), não precisa ser execucada para não gerar novas filas de erros
                            JobService::send($this)->delay( now()->addMinutes(15) );//espera 15 min para executar este processo na fila
                        }else{
                            \App::abort(500, $e->getMessage());
                        }
                    }
                }
            }
        }

        return ['success'=>true,'msg'=>'Processado com sucesso em '.date('Y-m-d H:i:s')];
    }

    /**
     * Função para o JobService acima
     */
    public function JobStart(){
        $this->get_addProcessAuto(new Request,true);
    }


    /**
     * Seta uma nova execução para marcar os registros como concluído.
     * @param Request $req - campos esperados:
     *        account_id - id da conta (opcional)
     *        id         - id específico do registro (opcional - usado para testes)
     */
    public function get_addProcessMarkdone(Request $req){
        $prRegs = $this->ProcessRobotModel
                    ->select('id','process_status')
                    ->join('pr_seguradora_files as pr', 'process_robot.id', '=', 'pr.process_id')
                    ->where('pr.status','a') //somente os registros para marcar como concluído

                    //não pode conter os status 0 - em processo de indexação, w - ag envio manual pelo operador
                    ->whereRaw('process_robot.process_status<>"0"')
                    ->where('process_robot.process_status','<>','w')

                    ->groupBy('id','process_status')
                    ->orderBy('process_robot.id','desc')//dos mais novos para os mais antigos
                    ->take(1000);

        if($req->account_id)$prRegs->where('process_robot.account_id',$req->account_id);
        if($req->id)$prRegs->where('process_robot.id',$req->id);

        //dd(\App\Services\DBService::getSqlWithBindings($prRegs) );
        $prRegs = $prRegs->get();


        if($prRegs->count()==0)return ['success'=>true,'msg'=>'Nenhum registro para processar'];

        $i=0;
        foreach($prRegs as $reg){
            //dd($prRegs, $reg->getData());
            if(in_array($prRegs,['p','a']) && in_array($reg->getData('new_process'),['down_apo','markdone'])){
                //quer dizer que está marcado para buscar registro na área de seguradoras ou marcar como concluído e está com status pronto robô ou em andamento
                //neste caso não executa nenhuma ação
            }else{
                //seta para marcar como concluído
                $reg->setData('new_process','markdone');
                $reg->update(['process_status'=>'p']);//pronto para o robô
                $i++;
            }
        }
       return ['success'=>true,'msg'=> $i==0 ? 'Nenhum registro para processar' : $i.' registro(s) processado(s)'];
    }



    //***** Função desativada. Motivo: não está sendo utilizada, pois o processo de marcar como concluído já está funcionando bem dentro do fluxo de processo de cadastro de apólices *****
    /**
     * Verifica se existem registros pendentes de processamento na tabela 'pr_seguradora_files' e altera o status da tabela process_robot correspondente para status='p' (pronto para o robô)
     */
    /*public function get_checkRegsMarkDone(){
        $regs = $this->PrSeguradoraFiles->whereIn('status',['a','b'])->get();
        if($regs){//tem registro pendente
            foreach($regs as $reg){
                $ProcessModel = $this->ProcessRobotModel->find($reg->process_id);
                if($ProcessModel){
                    $ProcessModel->update(['process_status'=>'p']);//altera para pronto para o robô
                    $ProcessModel->delData('new_process');//neste caso limpa o registro do novo subprocesso, pois já concluiu o 'down_apo'
                }else{//registro associado não encontrado
                    $reg->update(['status'=>'x']);//seta como erro ao associar o registro
                }
            }
        }
        return 'ok';
    }*/

    /**
     * Altera o status de um registro da tabela 'pr_seguradora_files'
     * Obs: se $rel_id=='all',  e espera os parâmetros: st_from e st(to)
     */
    public function post_setSegFileNewStatus(Request $request){
        $id = $request->input('id');
        $rel_id = $request->input('rel_id');
        $st_to = strtolower($request->input('st'));

        $allow_status=['0','1','a','b','f','e','i','x'];
        if(!in_array($st_to,$allow_status))return ['success'=>false];

        if($rel_id=='all'){
            $st_from = strtolower($request->input('st_from'));
            if(!in_array($st_from,$allow_status))return ['success'=>false];
            $regs=$this->PrSeguradoraFiles->where(['process_id'=>$id,'status'=>$st_from])->get();
        }else{
            $regs=$this->PrSeguradoraFiles->where(['process_id'=>$id,'process_rel_id'=>$rel_id])->get();
        }

        if($regs->count()>0){
            foreach($regs as $reg){
                $reg->update(['status'=>$st_to]);
                //atualiza o registro principal da tabela process_robot
                $ProcessModel = $this->ProcessRobotModel->find($id);
                if(!$ProcessModel)continue;
                $ProcessModel->addLog('log','Novo registro de busca de arquivos na Área de Seguradoras');
                if(in_array($st_to,['a','b'])){//a,b - ag. para marcar o registro no quiver
                    $ProcessModel->update(['process_status'=>'p']);
                    //$ProcessModel->setData('new_process','mark_done');
                }
            }
        }

        return ['success'=>true];
    }


    //** *************** webservice de ações para o app do robô ***********************
     /**
     * Esta classe só será iniciada a partir do controller App\Http\Controllers\wsrobotController@get_process, e é obrigatório este nome 'wsrobot_data_get_process'
     * Deve conter o restante dos comandos da solicitação do controller wsrobotController@get_process processando logo após a selação inicial dos registros a serem processados
     * @param com os mesmos parâmetros retornados do controller
     * @return array: [ProcessModel, data, (boolean) repeat]     //respectivos valores recebidos de $params     //veja + em wsrobotController@get_process
     *                  Obs: se repeat==true, então irá repetir recursivamente esta função
     * @obs: para retornar a nenhum registro, use: return ['status'=>'A|E','msg'=>'Nenhum registro disponível'];
     */
    public function wsrobot_data_getBefore_process($params){
        extract($params);

                /**
                 * Atualização 28/12/2021:
                 *      Desabilitado para que os arquivos enviados manualmente não sejam marcados como concluído na área de seguradoras (processo mark_done)
                 *      Somente os que forem baixados da área de seguradoras é que terão o processo mark_done para voltar lá e marcar como concluído
                 */
                if($ProcessModel->process_ctrl_id=='manual'){//não deve processar este registro
                    $ProcessModel->update(['process_status'=>'f']);//finaliza e prossegue para o próximo
                    return ['repeat'=>true];//volta a processar esta função novamente a procura de outro registro
                }


        return [
            'ProcessModel'=>$ProcessModel,
            'data'=>$params['data'],
        ];
    }

    /**
     * Esta classe só será iniciada a partir do controller App\Http\Controllers\wsrobotController@get_process, e é obrigatório este nome 'wsrobot_data_get_process'
     * Deve conter o restante dos comandos da solicitação do controller wsrobotController@get_process processando ao encerrar a função
     * @param com os mesmos parâmetros retornados do controller
     * @return array para montagem do xml final     //veja + em wsrobotController@get_process
     */
    public function wsrobot_data_getAfter_process($params){

        extract($params);

        $process_name_accepted = 'cad_apolice';
        $corretor_user  = $login_use['user'];
        $corretor_login = $login_use['login'];
        $corretor_senha = $login_use['pass'];

        //verifica se tem os dados de login necessários para prosseguir
        if(empty($corretor_user) || empty($corretor_login) || empty($corretor_senha)){
            $ProcessModel->update(['process_status'=>'c']);//c - erro do cliente
            $ProcessModel->setData('error_msg','wbot03');
            return ['repeat'=>true];
        }

        $dts = $ProcessModel->process_ctrl_id=='manual' ? null : explode('|',$data['dts_search']);

        //verifica se existem arquivos para marcar no quiver (como concluído ou não concluído)
        $files_mark = $this->PrSeguradoraFiles->where('process_id',$ProcessModel->id)->whereIn('status',['a','b'])->get();

        //se $new_process=='down_apo' então irá forçar o subprocesso de 'down_apo' e ignora o 'mark_done' caso exista
        $new_process = $data['new_process']??'';
        //if(\Auth::user()->id==1)dd('c',$files_mark->count(),  $new_process,   $files_mark->count()>0 && $new_process!='down_apo'      );
        if($files_mark->count()>0 && $new_process!='down_apo'){//existem registros para marcar
            //captura todos os registros já baixados de execuções anteriores
            $control_ids_on=$control_ids_off=[];

            if($dts){//origem de arquivos: área de seguradoras
                //monta na sintaxe final (string):"dts-dte:quiver_id1:quiver_id2:..."
                foreach($files_mark as $reg){
                    if(!$reg->quiver_id){//é considerado um erro
                        $reg->update(['status'=>'e']);
                        continue;
                    }
                    if($reg->status=='a'){//ag. ação para marcar como concluído no quiver
                        $control_ids_on[]=$reg->quiver_id;
                    }else if($reg->status=='b'){//ag. Ação para marcar como não concluído
                        $control_ids_off[]=$reg->quiver_id;
                    }
                }
                $control_ids_on = $control_ids_on   ? FormatUtility::dateFormat($dts[0],'date') .'-'. FormatUtility::dateFormat($dts[1],'date') . ':' . join(':',$control_ids_on) : '';
                $control_ids_off = $control_ids_off ? FormatUtility::dateFormat($dts[0],'date') .'-'. FormatUtility::dateFormat($dts[1],'date') . ':' . join(':',$control_ids_off) : '';
                //dd($control_ids_on, $control_ids_off);

            }else{//origem de arquivos: envio manual
                //como o envio é manual, monta abaixo na sintaxe final (string):"dts-dte:num_apo1:num_apo2:...|..."
                $PrSegService = new \App\Services\PrSegService;
                foreach($files_mark as $reg){
                    $m = $reg->process_robot;
                    $table_dados = $PrSegService->getTableData($reg->process_rel_id);

                    //lógica: para o período a ser informado no quiver deve ser utilizado a data da emissão + um período de 14 dias
                    $d1=$table_dados ? $table_dados->data_emissao: '';
                    if($d1){
                        $d1=FormatUtility::dateFormat( $d1 ,'date');
                        $d2=FormatUtility::dateFormat( FormatUtility::convertDate($d1 . ' +14 day' ) ,'date');
                        $k = $d1.'-'.$d2;
                        //dd($d1,$d2,$k);

                        //captura o número da apólice
                        $num_apo=$table_dados->apolice_num_quiver;
                        /***** REMOVER CÓDIGO COMENTADO *****
                         * $regProcess = $this->ProcessRobotModel->find($reg->process_rel_id);
                         * if($regProcess)$num_apo = $table_dados ? $table_dados->apolice_num_quiver : '';
                         */
                        if(!$num_apo){//provavelmente o registro não existe ou não tem dados indexados (mas deveria ter se chegou até aqui)
                            $reg->update(['status'=>'1']);//seta nenhuma ação no quiver
                            continue;//apenas prossegue no loop seguinte
                        }

                        if($reg->status=='a'){//ag. ação para marcar como concluído no quiver
                            if(!isset($control_ids_on[$k]))$control_ids_on[$k]=[];
                            $control_ids_on[$k][]=$num_apo;
                        }else if($reg->status=='b'){//ag. Ação para marcar como não concluído
                            if(!isset($control_ids_off[$k]))$control_ids_off[$k]=[];
                            $control_ids_off[$k][]=$num_apo;
                        }
                    }else{//registro sem dados suficientes
                        $reg->update(['status'=>'1']);//seta nenhuma ação no quiver (mas deveria ter se chegou até aqui)
                        continue;//apenas prossegue no loop seguinte
                    }
                    //dd('xxxx');
                }
                //altera colocando as chaves e valor em uma só string ("dts-dte:num_apo1:num_apo2:...|...")
                $arr = $control_ids_on;
                    $control_ids_on=[];
                    foreach($arr as $k=>$reg){ $control_ids_on[] = $k.':'. join(':',$reg); }
                $arr = $control_ids_off;
                    $control_ids_off=[];
                    foreach($arr as $k=>$reg){ $control_ids_off[] = $k.':'. join(':',$reg); }
                $control_ids_on = join('|',$control_ids_on);
                $control_ids_off = join('|',$control_ids_off);
            }

            if($control_ids_on || $control_ids_off){
                //captura a relação de seguradoras aceitas
                //$insurers_names = $this->InsurerModel->where(['insurer_status'=>'a'])->orderBy('insurer_basename','asc')->get()->pluck('insurer_basename')->toArray();

                $xmlDefault['process_prod']='mark_done';
                $xmlDefault['corretor_login_corretora']=$corretor_user;
                $xmlDefault['corretor_login_usuario']=$corretor_login;
                $xmlDefault['corretor_login_senha']=($method=='GET' ? '*******' : $corretor_senha);//somente na requisição post (que vem do robo) é necessário exibir a senha
                $xmlDefault['process_ctrl_id'] = $dts?'auto' : 'manual';
                $xmlDefault['control_ids_on']=$control_ids_on;
                $xmlDefault['control_ids_off']=$control_ids_off;
                //$xmlDefault['insurers_names']=join(',',$insurers_names);
                return $xmlDefault;

            }else{//não tem registros para marcar no quiver
                $ProcessModel->update(['process_status'=>'f']);//está finalizado
                $ProcessModel->setData('error_msg','');
                return ['repeat'=>true];
            }

        }else{//este registro está na fase de importação das apólices
            if($ProcessModel->process_ctrl_id=='manual'){//quer dizer que é o registro de controle de marcação de registro como concluído manual, portanto esta etapa não existe neste caso
                //finaliza, pois um registro manual nunca deve ter este bloco executado
                $ProcessModel->update(['process_status'=>'f']);
                return ['repeat'=>true];
            }


            //caminho relativo da pasta do upload do robô
            //obs: tira a pasta inicial upload_robo/, pois este processo acessado por um usuário de FTP adicional que só terão permissão a partir da pasta 'upload_robo' (e esta mesma não aparece no diretório FTP)
            $ftp_dir = $ProcessModel->getPaths($data)['relative_upload_robo'];
            $ftp_dir = ltrim($ftp_dir,'upload_robo');
            $ftp_dir = ltrim($ftp_dir,'/');

            //captura todos os registros já baixados de execuções anteriores (do mesmo registro process_robot.id)
            $control_ids = $this->PrSeguradoraFiles->where('process_id',$ProcessModel->id)->select('quiver_id')->pluck('quiver_id')->toArray();

            //captura todos os registros já baixados relativos a registro do dia anterior (considera o registro anterior ao atual)
            //motivo: por padrão é sempre procurado na área de seguradoras pelo período de dia anterior ao dia atual, e para que não duplique registros que não foram marcados do dia anterior, precisa incluir eles na lista de registros já baixados
            $process_id_old = $this->ProcessRobotModel->where('process_name',self::$basename)->where('id','<',$ProcessModel->id)->orderBy('id','desc')->value('id');
            $control_ids2 = $this->PrSeguradoraFiles->where('process_id',$process_id_old)->select('quiver_id')->pluck('quiver_id')->toArray();
            if($control_ids2)$control_ids = array_merge($control_ids,$control_ids2);
            //dd('a**',$control_ids);

            /*(analisar e descartar abaixo) xxxxxxxxxxx
            if($method!='GET' && empty($control_ids)){//não tem registros para processar, portanto marca como finalizado e segue para o próximo registro //obs: se method=GET, então é apenas uma visualização do admin e não a solicitação real do robô
                $ProcessModel->update(['process_status'=>'f']);
                $ProcessModel->setData('error_msg','Nenhum registro disponível para marcar na Área de Seguradoras');
                return ['repeat'=>true];
            }*/

            //captura a relação de seguradoras aceitas
            $insurers_names=[];
            foreach(VarsProcessRobot::$configProcessNames[$process_name_accepted]['products'] as $prod=>$opt){
                $allow = $opt['insurers_allow']??null;
                $m=$this->InsurerModel->where(['insurer_status'=>'a'])->orderBy('insurer_basename','asc');
                if($allow){
                    $m->whereIn('insurer_basename',$allow);
                }//else{//all insurers
                $insurers_names[$prod]=$m->get()->pluck('insurer_basename')->toArray();
            }
            //dd($insurers_names);

            $filename_tmp = $data['filename_tmp']??'';
            if(empty($filename_tmp)){//por algum motivo está vazio, e neste caso gera um novo valor
                $filename_tmp=\App\Utilities\FunctionsUtility::keyGenerator(16).'.zip';
                $ProcessModel->setData('filename_tmp',$filename_tmp);
            }

            $account_config = $accountModel->getData('config');
            $config_cad_apolice = AccountsService::getCadApoliceConfig($accountModel);

            //monta a array final
            $r=$xmlDefault + [
                //dados de login do corretor
                'corretor_login_corretora'=>$corretor_user,
                'corretor_login_usuario'=>$corretor_login,
                'corretor_login_senha'=> ($method=='GET' ? '*******' : $corretor_senha),//somente na requisição post (que vem do robo) é necessário exibir a senha
                'filename_tmp'=>$filename_tmp,
                'ftp_host'=>env('ROBO_FTP_HOST'),
                'ftp_user'=>env('ROBO_FTP_USER'),
                'ftp_pass'=>($method=='GET' ? '*******' : env('ROBO_FTP_PASS') ),
                'ftp_dir'=>$ftp_dir,
                'dt_start'=>FormatUtility::dateFormat($dts[0],'date'),
                'dt_end'=>FormatUtility::dateFormat($dts[1],'date'),
                'process_name_accepted'=>$process_name_accepted,
                'process_prods_accepted'=>join(',',array_keys(VarsProcessRobot::$configProcessNames[$process_name_accepted]['products'])),    //nomes dos produtos
                'control_ids'=>trim(join('|',$control_ids),'|'),
            ];

            $products_active = array_filter(array_get($account_config,'cad_apolice.products_active')??[]);
            foreach($insurers_names as $prod => $m){
                if(!$products_active || in_array($prod,$products_active)){
                    $r['insurers_names_'.$prod]=join(',',$m);
                    $r['config_down_apo_'.$prod] = $config_cad_apolice['down_apo_ramo'][$prod]??'';
                }
            }
            return $r;
        }
    }

    /**
     * Esta classe só será iniciada a partir do controller App\Http\Controllers\wsrobotController@set_process, e é obrigatório este nome 'wsrobot_data_set_process'
     * Esta classe deve conter o restante dos comandos da solicitação do controller wsrobotController@set_process
     * @param com os mesmos parâmetros retornados do controller
     * @return array com ['status'=>'...', msg...]      //veja + em wsrobotController@set_process
     */
    public function wsrobot_data_set_process($params){
        /* Lógica: esta função será chamada para informar que o envio por FTP do arquivo foi concluído.
         * Seta o registro com status='0' para indicar que terminou salvar os arquivos e está na fase de indexação
         */
        extract($params);

        if($status=='t')return $return;//status='t' (tentar novamente)
        if($status=='e')return $return;//status='e' (erro interno, não deveria ocorrer, e portanto todo este método não deve ser executado)

        //verifica se existem arquivos para marcar no quiver (como concluído ou não concluído)
        $files_mark = $this->PrSeguradoraFiles->where('process_id',$ProcessModel->id)->whereIn('status',['a','b'])->get();

        //se $new_process=='down_apo' então irá forçar o subprocesso de 'down_apo' e ignora o 'mark_done' caso exista
        $new_process = $data['new_process']??'';
        //dd($files_mark->count(),$new_process,$params);
        if($files_mark->count()>0 && $new_process!='down_apo'){//existem registros para marcar
            //grava que todos os registros de apólices retornados em $data_robot (para $status='f') associados foram marcados como concluído
            if($status=='f'){
                $msg = $data_robot['ids_ret']??'';
                //espera $msg no padrão: sintaxe: {quiver_id or apo_num}={status}|...   status=R (ok), E (erro), N (Não encontrado)
                //lógica do IF: verifica se na string $msg pelo menos tem um campo (number){id}=(string 1){status}
                $tmp=explode('=',explode('|',$msg)[0]);
                if(is_numeric($tmp[0]) && strlen($tmp[1]??'')==1){
                    $quiver_ids=explode('|',$msg);
                    $count_R=0;
                    //$msg='Processado com sucesso';
                    //dd($quiver_ids);
                    foreach($quiver_ids as $q_id){
                        if(!$q_id)continue;
                        list($n,$s)=explode('=',$q_id);//esperado: quiver_id = status
                        if($n){
                            if($ProcessModel->process_ctrl_id=='manual'){
                                //$reg = $this->ProcessRobotModel
                                $reg = $this->PrSeguradoraFiles
                                        ->select('pr_seguradora_files.*')
                                        ->join('process_robot', 'process_robot.id', '=', 'pr_seguradora_files.process_rel_id')
                                        ->where('process_id',$ProcessModel->id)
                                        ->whereRaw('replace(replace(replace(process_ctrl_id,".",""),"-","")," ","") LIKE ?',['%'.str_replace(['-','.',' '],'',$n).'%']);
                                $reg=$reg->get();
                            }else{
                                $reg = $this->PrSeguradoraFiles->where(['process_id'=>$ProcessModel->id,'quiver_id'=>$n])->get();
                            }
                            //dd($n,$s,$ProcessModel->id,$ProcessModel->process_ctrl_id,$reg);
                            if($reg){
                                foreach($reg as $rg){
                                    $st_orig = $rg->status;//captura o status original
                                    //dd($rg, $st_orig, $s,'***', ($s=='R'?'f': ($st_orig=='a'?'e':'i') ));
                                    //lógica: se retornar a R, então status=F   //  se retornar a E (erro), verifica no status original se a ação é de marcar ou não como conluído, para setar os repectivos erros: E ou I
                                    $rg->update(['status'=> ($s=='R'?'f': ($st_orig=='a'?'e':'i') ) ]);
                                    //dump([$rg->process_rel)id, ($s=='R'?'f': ($st_orig=='a'?'e':'i') ) ]);
                                }
                            }
                            if($s=='R')$count_R++;//conseguiu marcar com sucesso
                        }
                    }
                    //dd('****', $count_R);
                    //conta quantos registros ainda estão pendentes para serem marcados
                    $count = $this->PrSeguradoraFiles->where('process_id',$ProcessModel->id)
                            ->whereIn('status',['a','b'])//a - ag. ação para marcar como concluído no quiver, b - ag. Ação para marcar como não concluído
                            ->count();
                    if($count==0){//todos os registros foram marcados
                        //atualiza o status='f' - pois agora está finalizado todo o processo
                        $ProcessModel->update(['process_status'=>'f']);
                    }else{
                        //if($count_R==count($data_robot)){}//todos os registros informados no retorno foram marcados
                        //Obs: até arqui, quer dizer que tem registros pendentes, portanto atualiza o status para 'p' (pronto para o robô) para que possa ser processado novamente
                        $next_at = date('Y-m-d H:i:s', strtotime('30 min', strtotime(date('Y-m-d H:i:s'))) );
                        $ProcessModel->update(['process_status'=>'p','process_next_at'=>$next_at]);
                    }
                    $ProcessModel->delData('error_msg');

                }else{
                    //atualiza o status='f' - pois agora está finalizado todo o processo
                    $ProcessModel->update(['process_status'=>'f']);
                    $ProcessModel->delData('error_msg');
                }

            }else{
                //deu algum erro ao marcar, postanto reagenda para tentar novamente (por isto o status=p (pronto para o robô) )
                $next_at = date('Y-m-d H:i:s', strtotime('30 min', strtotime(date('Y-m-d H:i:s'))) );
                $ProcessModel->update(['process_status'=>'p','process_next_at'=>$next_at]);//obs: aqui não precisa atualizar o campo 'updated_at'
            }




        }else{//este registro está na fase que recebe o retorno da importação das apólices
            if($msg=='pndw00'){//quer dizer que nenhum arquivo foi encontrado
                //atualiza o status='f' (finalizado)
                if($files_mark->count()>0){//ainda existem registros pendentes de processamento no Quiver
                    $s='a';
                }else{//está finalizado
                    $s='f';
                }
                //dd($msg,$s,$files_mark->count());
                $ProcessModel->update(['process_status'=>$s]);

            }else{
                $path = $ProcessModel->getPaths($data);

                //verifica se o arquivo existe
                if(empty($data['filename_tmp']))return $return;//se filename_tmp='', quer dizer que foi este trecho fora da ordem / lógica prevista, e neste caso apenas retorna ao padrão, pois seguirá dentro da lógica seguirá a programação e quando voltar aqui não terá mais erro
                $file = $path['upload_robo'] .'/'. $data['filename_tmp'];

                if(!file_exists($file)){
                    $ProcessModel->update(['process_status'=>'e']);
                    $ProcessModel->setData('error_msg', 'pndw01');
                    return ['status'=>'E','msg'=>'pndw01'];
                }

                //atualiza o status='0' (em indexação)
                $ProcessModel->update(['process_status'=>'0']);

                //faz a extração dos registros
                //obs: mesmo que este processo de erro não tem problema, pois ele também está agendado para ser executado em segundo plano
                $r=$this->doExtracted($ProcessModel);

                if(empty($return['msg']))$return['msg']='';
                $return['msg']=trim($return['msg'] .' Extração: '.$r['msg']);
            }

            //neste caso limpa o registro do novo subprocesso, pois já concluiu o 'down_apo'
            $ProcessModel->delData('new_process');
        }

        return $return;//return ok
    }



}
