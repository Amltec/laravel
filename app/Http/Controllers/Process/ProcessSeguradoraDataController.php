<?php

namespace App\Http\Controllers\Process;

use App\Http\Controllers\Controller;
//use App\Utilities\RequestUtility as Request;
use Illuminate\Http\Request;
use Illuminate\Filesystem\Filesystem;
use App\Utilities\ValidateUtility;
use App\Utilities\FormatUtility;
use Auth;
use Config;
use Exception;

use App\Http\Controllers\Process\ProcessController;
use App\ProcessRobot\VarsProcessRobot;
use App\ProcessRobot\FunctionsProcessRobot;
use App\ProcessRobot\seguradora_data\boleto_seg\BoletoSegConfig;
use App\Services\AccountsService;

use App\Models\ProcessRobot_SeguradoraData;
use App\Models\ProcessRobot_CadApolice;
use App\Models\PrSeguradoraData;
use App\Models\Robot;
use App\Models\ProcessRobotExecs;
use App\Models\PrCadApolice;



/**
 * Classe responsável pelo processo de ações nos sites das seguradoras
 */
class ProcessSeguradoraDataController extends ProcessController{
    protected static $basename='seguradora_data';

    private static $process_repeat_delay=180;//180 = 3h // tempo em minutos que um processo voltará a se repetir caso o status_code retornado no método wsrobot_data_set_process() seja um dos códigos que exijam reprocessamento


    //*** varáveis públicas ***
    //menu admin
    public static $submenus_superadmin=[
        'apolice_check'     => ['title'=>'Verificação de Apólices', 'link'=>['process_seguradora_data','list','?process_prod=apolice_check'] ],
        'boleto_seg'        => ['title'=>'Baixa de Boletos Segs', 'link'=>['process_seguradora_data','list','?process_prod=boleto_seg'] ],
        'boleto_quiver'     => ['title'=>'Cad. Boleto no Quiver', 'link'=>['process_seguradora_data','list','?process_prod=boleto_quiver'] ],
        'boleto_seg_quiver' => ['title'=>'Baixa de Boletos &raquo Quiver', 'link'=>['process_seguradora_data','boletos_list',''] ],
    ];

    //public static $menu_admin=false;
    //public static $submenus_admin=[];

    //*** Valores de Status ***
    //Lista dos status disponíveis
    public static $status=[
        'p'=>'Aguardando robô',
        'a'=>'Em andamento',
        //'0'=>'Em indexação',
        'f'=>'Finalizado',
        'e'=>'Erro',
        's'=>'Parado',
        '1'=>'Em Análise',
    ];

    //label de status - longo
    public static $statusLong=[
        'p'=>'Aguardando robô',
        'a'=>'Em andamento / processamento pelo robô',
        //'0'=>'Em indexação',
        'f'=>'Finalizado',
        'e'=>'Erro',
        's'=>'Parado',
        '1'=>'Em Análise',
    ];

    //status curto para o admin (os demais são para os superadmins)
    //st_ref (status de referência para as demais variáveis)


    //cores dos status
    public static $statusColor=[
        'p'=>['text'=>'text-muted','bg'=>'bg-light-blue-active'],
        'a'=>['text'=>'text-muted','bg'=>'bg-light-blue-active'],
        //'0'=>['text'=>'text-muted','bg'=>'bg-aqua disabled'],
        'f'=>['text'=>'text-green','bg'=>'bg-green-active'],
        'w'=>['text'=>'text-green','bg'=>'bg-green-active'],    //este existe apenas para ficar compatível com self::$status_pr
        'e'=>['text'=>'text-red','bg'=>'bg-red-active'],
        's'=>['text'=>'text-teal','bg'=>'bg-teal-active'],
        '1'=>['text'=>'text-yellow','bg'=>'bg-yellow'],
    ];

    //status da tabela pr_seguradora_data
    public static $status_pr=[
        'p'=>'Aguardando robô',
        'a'=>'Em andamento',
        'f'=>'Finalizado sem alterações',
        'w'=>'Finalização com alterações',
        'e'=>'Erro ao verificar',
        's'=>'Parado',
        '1'=>'Em Análise',
    ];
    //agrupamentos dos status de $status_pr em 'a|p|e|f' (aguardando, pronto, erro, finalizado)
    public static $status_pr_group = [
        'a'=>['a'],
        'p'=>['p'],
        'e'=>['e','1'],
        'f'=>['f','w'],
        's'=>['s'],
    ];


    //Relação de erros que irão gerar pendências para o operador atual manualmente
    public static $statusCode_pendencies=[
        'quis02','sega01','segl02','segl03','segl04','segl06','segl01','segd06','segl05','segl07','quif09'
    ];


    //Relação de erros - sintaxe: [code=>text || [short text, long text]]
    private static $statusCode = [
        'segl00' => 'Erro desconhecido',
        'segl01' => 'Login ou senha inválido',
        'segl02' => 'Página de seguradora não logada ou desconectada',
        'segl03' => 'Dados insuficientes para login na seguradora',
        'segl04' => 'A senha informada expirou, necessário atualizar',
        'segl05' => 'Credenciais para login (usuários) não carregada',
        'segl06' => 'Usuário bloqueado na seguradora',
        'segl07' => 'Seguradora requer atualização de senha',
        'sega01' => 'Dados da apólice não encontrado na busca no site da seguradora',
        'sega02' => 'Erro ao verificar os dados no resultadas parcelas',
        'sega03' => 'Encontrou na pesquisa os registros, mas nenhum era boleto para prosseguir',
        'sega04' => 'Erro ao localizar a janela de baixa dos boletos',
        'sega05' => 'Erro Todos os process_ids não tiveram o download concluído',
        'sega06' => 'Erro Todos os process_ids não tiveram retorno de dados',
        'sega07' => 'Erro busca de apólice por data estar fora do período permitido',
        'sega08' => 'Erro ao capturar os dados da apólice',
        'segd01' => 'Erro ao fazer o download do boleto',
        'segd02' => 'Erro ao fazer download de arquivo de verificação na seguradora',
        'segd03' => 'Arquivo não disponível para consulta',//este erro requer que seja tentado novamente
        'segd04' => 'Boleto não disponível no momento',
        'segd05' => 'Erro busca de apólice por estar fora do período permitido',
        'segd06' => 'Apólice não encontrada na seguradora (provável credencial incorreta)',
        'segd07' => 'Boleto não disponível - Todas as parcelas pagas',
        'segd08' => 'Encontrado apenas o boleto da primeira parcela',
        'segu01' => 'Erro ao enviar arquivo por FTP',
        'segu02' => 'Erro ao ao localizar arquivo para envio por FTP',
        'segz01' => 'Erro ao zipar arquivo dos boletos para envio por FTP',
        'segr01' => 'Erro de permissão de acesso',
        'quis01' => 'Seguradora informada na requisição xml não encontrado no Quiver',
        'quis02' => 'Login ou credencial do corretor não encontrado',
        'quis03' => 'Erro ao capturar a senha do corretor para a seguradora informada',
        'sdbs01' => 'ZIP: Erro ao localizar arquivo na pasta de ftp',
        'sdbs02' => 'ZIP: Falha ao abrir arquivo para extração',
        'sdbs03' => 'ZIP: Falha ao extrair arquivo',
        'sdbs04' => 'Erro ao mesclar os pdfs em um só arquivo',
        'sdbs05' => 'Erro ao mover o arquivo de diretório',
        'sdbq01' => 'Erro ao localizar boletos já gravados na pasta cad_apolice',
        'segc01' => 'Número de itens incompatível',
        'segc02' => 'Produto: Número de itens incompatível',
        'segc03' => 'Parcelas: Número de itens incompatível',
        'quif01' => 'Url do anexo vazio',
        'quif02' => 'Arquivo inválido',
        'quif03' => 'Erro ao baixar o arquivo',
        'quif04' => 'Erro ao inserir o anexo',
        'quif05' => 'Erro de permissão ao acessar o arquivo',
        'quif06' => 'Arquivo não existe',
        'quif07' => 'Parâmetros de Arquivo inválido',
        'capt01' => 'Erro ao validar o captcha',
        'quif09'   =>'Tipo de imagem da apólice não encontrado na lista de opções',
        //'segi01' => 'Falha na conexão de internet',
    ];
    public static function getStatusCode($s=null,$retAllIfNull=true){
        $a = self::$statusCode + VarsProcessRobot::$statusCode;
        return $s ? ($a[$s]??$s) : ($retAllIfNull?$a:'');
    }


    private $service_active=true;

    public function __construct(ProcessRobot_SeguradoraData $ProcessRobotModel, ProcessRobot_CadApolice $ProcessRobotCadApolice, PrSeguradoraData $PrSeguradoraData){
        $this->ProcessRobotModel = $ProcessRobotModel;
        $this->ProcessRobotCadApolice = $ProcessRobotCadApolice;
        $this->PrSeguradoraData = $PrSeguradoraData;

        //verifica se a conta atual tem permissão para este serviço
        if(Config::adminPrefix()=='admin'){//é o painel do cliente
            $account_id = Config::accountID();
            if(!$account_id)exit('Acesso negado (conta não informada)');
            if(!AccountsService::isProcessActive($account_id,'seguradora_data','boleto_seg'))$this->service_active = false;//exit('Acesso negado (serviço não ativado)');
        }
    }


    public function index(Request $request){
        return $this->get_list($request);
    }


    /**
     * Lista padrão para todos os serviços de seguradora data
     */
    public function get_list(Request $request){//GET
        $r=$this->onlySuperAdmin();if($r!==true)return $r;

        $userLogged = Auth::user();
        $data = $request->all();
        $prefix = \Config::adminPrefix();
        $thisVars = VarsProcessRobot::$configProcessNames[self::$basename];

        /* Campos:
         *      dt      - date aaaa-mm-d      //aceita também date_start - date_2_end (sintaxe: yyyy-mm-dd - yyyy-mm-dd)
         *      dts     - date start aaaa-mm-d
         *      dte     - date end aaaa-mm-d
         */
        $filter = $request->all(['account_id','process_prod','id','ida','status','broker_id','insurer_id','dt','dts','dte','status_pr_group']);
        $process_prod = $filter['process_prod'];

        //$filter
        if(!in_array($process_prod,(array_keys($thisVars['products'])))){
            exit('Parâmetro "process_prod" inválido');
        }

        $fields=['process_robot.id','process_robot.process_name','process_robot.process_prod','process_robot.process_status','process_robot.account_id','process_robot.insurer_id','process_robot.broker_id','process_robot.created_at','process_robot.updated_at','process_robot.process_ctrl_id'];
        $model = $this->ProcessRobotModel->select($fields)
                ->where('process_robot.process_prod',$process_prod)
                ->leftJoin('pr_seguradora_data', 'pr_seguradora_data.process_id', '=', 'process_robot.id')
                ->groupBy($fields);


        //filtra pelo id da conta
        if($prefix=='super-admin' && $filter['account_id'])$model->where('account_id',$filter['account_id']);

        //fitlra por id
        if($filter['id'])$model->where('id',$filter['id']);
        if($filter['ida'])$model->where('process_rel_id', $filter['ida']);

        //filtra por status
        if($filter['status']!='')$model->where('process_status',$filter['status']);

        //corretor e seguradora
        if($filter['broker_id']!='')$model->where('broker_id',$filter['broker_id']);
        if($filter['insurer_id']!='')$model->where('insurer_id',$filter['insurer_id']);


        //filtra por data
        if($filter['dt'])$model->whereDate('created_at',$filter['dt']);
        if($filter['dts'])$model->whereDate('created_at','>=',FormatUtility::convertDate($filter['dts']));
        if($filter['dte'])$model->whereDate('created_at','<=',FormatUtility::convertDate($filter['dte']));

        if(in_array($userLogged->user_level,['dev','superadmin'])){
            if(_GET('is_trash')=='s')$model->onlyTrashed();
        }

        //status da tabela pr_seguradora_files agrupados por 'a|p|e|f' (aguardando, pronto, erro, finalizado)
        if($filter['status_pr_group']){
            //$model->whereIn('pr_seguradora_data.status', (self::$status_pr_group[$filter['status_pr_group']]??'FALSE') );
            $sx = explode(',',$filter['status_pr_group']);//caso tenha mais de um valor
            $model->where(function($query) use($sx){
                foreach($sx as $s){
                    $query->orWhere(function($query) use($s){
                        $query->whereIn('pr_seguradora_data.status', (self::$status_pr_group[$s]??'FALSE') );
                    });
                }
            });
        }

        //captura os totais: finalizados, erros, aguardando
        $model  ->selectRaw('(select CONCAT('.
                            'COUNT(CASE WHEN prsd.status="f" or prsd.status="w" THEN 1 END), '.   //status f
                            '"|",'.
                            'COUNT(CASE WHEN prsd.status="e" THEN 1 END), '.                      //status e
                            '"|",'.
                            'COUNT(CASE WHEN prsd.status="a" or prsd.status="p" THEN 1 END), '.    //status p,a
                            '"|",'.
                            'COUNT(CASE WHEN prsd.status="s" THEN 1 END) '.                      //status s
                        ') from pr_seguradora_data prsd '.
                            'inner join process_robot pr2 on prsd.process_rel_id = pr2.id '.
                            'where prsd.process_id=process_robot.id and pr2.deleted_at is null) as total_sts '
                    );
        //dump($model->toSql(),$model->getBindings());


        $model=$model
                ->orderBy('id', 'desc')
                ->paginate(_GETNumber('regs')??15);
        //dd($model,$process_prod);
        return view('admin.process_robot.'.self::$basename.'.'.$process_prod.'-list',[
            'process_name'=>self::$basename,
            'process_prod'=>$process_prod,
            'model'=>$model,
            'filter'=>$filter,
            'configProcessNames'=>VarsProcessRobot::$configProcessNames[self::$basename],
            'status_list'=>self::$status,
            'user_logged'=>$userLogged,
            'user_logged_level'=>$userLogged->user_level,
            'thisClass'=>$this,
            'prefix'=>$prefix,
        ]);
    }


     /**
     * Lista dos boletos para a visualização no painel do cliente
     */
    public function get_boletosList(Request $request){//GET
        $prefix = \Config::adminPrefix();
        $userLogged = Auth::user();
        $process_prod = $request->input('process_prod') ?? 'boleto_seg';

        $filter=[
            'account_id'=>$request->input('account_id'),
            'id'=>$request->input('id'),
            'ids'=>$request->input('ids'),
            'process_prod'=>$request->input('process_prod'),
            'status'=>$request->input('status'),
            'cpf'=>$request->input('cpf'),
            'nome'=>$request->input('nome'),
            'broker_id'=>$request->input('broker_id'),
            'insurer_id'=>$request->input('insurer_id'),
            'dt'=>$request->input('dt'),//date aaaa-mm-d      //aceita também date_start - date_2_end (sintaxe: yyyy-mm-dd - yyyy-mm-dd)
            'dts'=>$request->input('dts'),//date start aaaa-mm-d,
            'dte'=>$request->input('dte'),//date end aaaa-mm-d,
            'code'=>$request->input('code'),//campo status_code
        ];
        if($filter['status']){
            $n=explode('_',$filter['status']);//b|q_{status}
            $filter['process_prod'] = $n[0]=='q' ? 'boleto_quiver' : 'boleto_seg';
        }

        $model= $this->getProcessModel('cad_apolice')
                ->select('process_robot.id','process_robot.account_id','process_robot.process_ctrl_id','process_robot.process_prod','process_robot.process_name','process_robot.created_at','process_robot.broker_id','process_robot.insurer_id')
                ->join('pr_seguradora_data as pr',function($join){
                    $join->on('process_robot.id', '=', 'pr.process_rel_id')
                        ->where(function($q){
                            $q->where('pr.process_prod','boleto_seg')->orWhere('pr.process_prod','boleto_quiver');
                        });
                })
                ->groupBy('process_robot.id','process_robot.account_id','process_robot.process_ctrl_id','process_robot.process_prod','process_robot.process_name','process_robot.created_at','process_robot.broker_id','process_robot.insurer_id')
                ->orderBy('process_robot.created_at', 'desc');


        //***** filtros *****
        if($filter['id'] || $filter['ids']){
                if($filter['id'])$model->where('process_robot.id',$filter['id']);
                if($filter['ids'])$model->whereIn('process_robot.id',explode(',',$filter['ids']));

        }else{
                if($filter['account_id'])$model->where('process_robot.account_id',$filter['account_id']);

                $n=$filter['status'];
                if($n=='no_reg'){//retorna aos registros em que a associação com a tabela process_robot.process_prod='boleto_quiver' não existe
                    $model
                        ->whereNotExists(function($q){//somente os registros que não existem na tabela pr_seguradora_data para process_name=boleto_quiver
                                $q->select(\DB::raw(1))
                                    ->from('pr_seguradora_data as pr2')
                                    ->whereRaw('pr2.process_rel_id = pr.process_rel_id and pr2.process_prod=?',['boleto_quiver'])
                                    ->take(1);
                        })
                        ->whereIn('pr.status',['f','w']);//somente os que estão finalizados na baixa do boleto (na lógica deste sql, o campo pr.status já irá reprentar os registros de process_prod=boleto_seg)
                    //dump($model->toSql(),$model->getBindings());
                    //dd($model);
                    $filter['process_prod'] = 'boleto_seg';//altera este filtro para boleto_seg, pois somente nesta condição é que se aplica o sql acima

                //}elseif(strpos($n,',')!==false){
                //    $model->whereIn('pr.status',explode(',',$n));
                }else{
                    if($n){
                        $n = explode('_',$n)[1];
                        if(strpos($n,',')!==false){
                            $model->whereIn('pr.status',explode(',',$n));
                        }else{
                            $model->where('pr.status',$n);
                        }
                    }
                }

                if($filter['process_prod'])$model->where('pr.process_prod',$filter['process_prod']);

                if($filter['nome'] || $filter['cpf']){
                    $arr=[];
                    if($filter['nome'])$arr['segurado_nome__LIKE']='%'.$filter['nome'].'%';
                    if($filter['cpf'])$arr['segurado_doc']=$filter['cpf'];
                    if($arr)$model->wherePrSeg('dados',$arr);
                    //if($arr)$model->whereData($arr);
                    //dump($model->toSql(),$model->getBindings());
                }

                if($filter['broker_id'])$model->where('broker_id',$filter['broker_id']);
                if($filter['insurer_id'])$model->where('insurer_id',$filter['insurer_id']);

                if(strpos($filter['dt'],' - ')!==false){
                    //separa a data entre data inicial e final
                    $n=explode(' - ',$filter['dt']);
                    $filter['dts']=$n[0];
                    $filter['dte']=$n[1];
                    unset($filter['dt']);
                }elseif($filter['dt']){
                    $filter['dts']=$filter['dte']=$filter['dt'];
                    $filter['dt']=null;
                }
                foreach(['dt','dts','dte'] as $dt){
                    if(strpos($filter[$dt],'/')!==false)$filter[$dt]=FormatUtility::convertDate($filter[$dt]);
                }
                if($filter['dt'])$model->whereDate('process_robot.created_at',$filter['dt']);
                if($filter['dts'])$model->whereDate('process_robot.created_at','>=',$filter['dts']);
                if($filter['dte'])$model->whereDate('process_robot.created_at','<=',$filter['dte']);

                 if($filter['code']){
                     //$model->where(['error_msg'=>$filter['code']]);
                     $model->whereRaw(
                            'pr.process_id = ('.
                                'select md1.process_id from process_robot_data as md1 where md1.process_id=pr.process_id '.
                                    'and ( (md1.meta_name=? and md1.meta_value=?) )'.
                            ')'
                            ,['error_msg',$filter['code']]
                     );
                 }
        }

                //dump($model->toSql(),$model->getBindings());
                //dump( \App\Services\DBService::getSqlWithBindings($model) );
        $model=$model->paginate(_GETNumber('regs')??15);

        return view('admin.process_robot.seguradora_data.mode2.boleto_list',[
            'process_name'=>self::$basename,
            'process_prod'=>$process_prod,
            'model'=>$model,
            'filter'=>$filter,
            'user_logged'=>$userLogged,
            'user_logged_level'=>$userLogged->user_level,
            'thisClass'=>$this,
            'prefix'=>$prefix,
            'configPNCadApolice'=> VarsProcessRobot::$configProcessNames['cad_apolice'],
        ]);
    }


    /**
     * Página de visualização dos dados de cada processo/upload
     */
    public function get_show(Robot $RobotModel,Request $request){
        $r=$this->onlySuperAdmin();if($r!==true)return $r;

        $userLogged = Auth::user();
        $prefix = \Config::adminPrefix();
        $id = $request->input('id');
        $process_prod = $request->input('process_prod');
        \Config::setItemMenu('seguradora_data-'.$process_prod);

        $configProcessNames = VarsProcessRobot::$configProcessNames[self::$basename];
        $configPNCadApolice = VarsProcessRobot::$configProcessNames['cad_apolice'];

        if(!in_array($process_prod,(array_keys($configProcessNames['products'])))){
            exit('Parâmetro "process_prod" inválido');
        }

        //registro principal
            $model = $this->ProcessRobotModel->find($id);
            if(!$model)exit('Erro ao localizar registro');
            $RobotModel= $model->robot_id ? $RobotModel->find($model->robot_id) : null;

        //execuções
            $execsModel = (new ProcessRobotExecs)->where('process_id',$id)->orderBy('id','desc')->paginate(5);//somente os 10 últimos registros
            $execsModel_total = $execsModel->total();


        //lista de registros - tabela pr_seguradora_data
            $filter_rel_id = $request->input('filter_rel_id');//filtro por id da apólice
            $modelList=$this->getProcessModel('cad_apolice')->select('process_robot.*','pr_seguradora_data.status as pr_status','pr_seguradora_data.created_at as pr_created_at','pr_seguradora_data.finished_at as pr_finished_at')
                    ->join('pr_seguradora_data', 'process_robot.id', '=', 'pr_seguradora_data.process_rel_id')
                    ->where('process_id',$id)
                    ->orderBy('process_rel_id', 'desc');

                    //filtros
                    $filter=array_merge( ['cpf'=>'','nome'=>'','pr_id'=>'','pr_ids'=>'','ctrl_id'=>'','status'=>'','dtype'=>'','dt'=>'','dts'=>'','dte'=>''], $request->all() );

                    if($filter_rel_id)$modelList->where('process_rel_id',$filter_rel_id);

                    $dt_col = [
                        'dtc'=>'process_robot.created_at',
                        'dtp'=>'process_robot.updated_at',
                        'dtc_pr'=>'pr_seguradora_data.created_at',
                        'dtp_pr'=>'pr_seguradora_data.finished_at',
                    ][$filter['dtype']]??null;
                    if($dt_col){
                        foreach(['dt','dts','dte'] as $dt){
                            if(strpos($filter[$dt],'/')!==false)$filter[$dt]=FormatUtility::convertDate($filter[$dt]);
                        }
                        if($filter['dt'])$modelList->whereDate($dt_col,$filter['dt']);
                        if($filter['dts'])$modelList->whereDate($dt_col,'>=',$filter['dts']);
                        if($filter['dte'])$modelList->whereDate($dt_col,'<=',$filter['dte']);
                    }

                    if($filter['pr_ids'])$modelList->whereIn('id',explode(',',$filter['pr_ids']));
                    if($filter['pr_id'])$modelList->where('id',$filter['pr_id']);
                    if($filter['ctrl_id'] && is_numeric($filter['ctrl_id']))$modelList->where('process_ctrl_id','like','%'.FormatUtility::extractNumbers($filter['ctrl_id']).'%');


                    if($filter['nome'] || $filter['cpf']){
                        $arr=[];
                        if($filter['nome'])$arr['segurado_nome__LIKE']='%'.$filter['nome'].'%';
                        if($filter['cpf'])$arr['segurado_doc']=$filter['cpf'];
                        if($arr)$modelList->wherePrSeg('dados',$arr);
                    }
                    $n=$filter['status'];
                    if(strpos($n,',')!==false){
                        $modelList->whereIn('pr_seguradora_data.status',explode(',',$n));
                    }else{
                        if($n)$modelList->where('pr_seguradora_data.status',$n);
                    }
                    //dump($modelList->toSql(),$modelList->getBindings());
            $modelList=$modelList->paginate(_GETNumber('regs')??15);

        //carrega a view
        echo view('admin.process_robot.'.self::$basename.'.'. $process_prod .'-show',[
            'model'=>$model,
            'modelList'=>$modelList,
            'configProcessNames'=>$configProcessNames,
            'configPNCadApolice'=>$configPNCadApolice,
            'robotModel'=>$RobotModel,
            'status_list'=>self::$status,
            'status_pr'=>self::$status_pr,
            'statusColor'=>self::$statusColor,
            'user_logged'=>$userLogged,
            'process_prod'=>$process_prod,
            'thisClass'=>$this,
            'filter'=>$filter,
            'execsModel'=>$execsModel,
            'execsModel_total'=>$execsModel_total,
            'filter_rel_id'=>$filter_rel_id,
        ]);
    }


    /**
     * Remove definitivamente
     * @param int|model $model - id ou model da tabela process_robot
     */
    public function removeFinal($model){
        if(!$this->service_active)return ['success'=>false,'msg' => 'Serviço não ativado','model'=>$model];
        $r=$this->onlySuperAdmin();if($r!==true)return $r;

        if(is_int($model))$model = $this->ProcessRobotModel->onlyTrashed()->find($model);
        if($model){
            //remove o diretório do robô
            $path = $model->getPaths();
            $p = $path['upload_robo'];
            if(file_exists($p))(new Filesystem)->deleteDirectory($p);
            if(file_exists($p))return ['success'=>false,'msg' => 'Erro ao remover a pasta de upload do robô','model'=>$model];

            //remove da tabela que controle os registros que foram processados junto na área de seguradoras
            (new PrSeguradoraData)->where('process_id',$model->id)->delete();
        }

        return parent::removeFinal($model);
    }



    //restringe o acesso apenas para o dev e superadmin
    private function onlySuperAdmin(){
        return in_array(Auth::user()->user_level, ['dev','superadmin']) ? true : \Redirect::to(route('admin.index'))->send();
    }


    /**
     * Altera os status de todos os ids informados
     * Função para o botão 'alterar status' da lista de processos
     * Análise futura: talvez padronizar este código ma classe pai????
     */
    public function post_changeAllStatus(Request $request){
        if(!$this->service_active)return ['success'=>false,'msg' => 'Serviço não ativado'];

        $r=$this->onlySuperAdmin();if($r!==true)return $r;

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
                if($model->account->account_status!='a')continue;//pula neste caso]

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
                $model->addLog('status','Status atualizado de: '. self::$status[$old_status] .'('.$old_status.')  - para: '. self::$status[$st] .'('.$st.')');
            }
        }

        return ['success'=>true];
    }


    /**
     * Altera o status de todos os ids dos processos da tabela pr_seguradora_data
     * Função para o botão 'alterar status' da lista de processos (process_name=cad_police) de cada processo principal (process_name=seguradora_data)
     */
    public function post_prChangeAllStatus(Request $request){
        if(!$this->service_active)return ['success'=>false,'msg' => 'Serviço não ativado'];

        $r=$this->onlySuperAdmin();if($r!==true)return $r;
        $data = $request->all();

        $process_id = $data['process_id']??'';
        $process_prod = $data['process_prod']??'';
        if(!$this->checkProcessNames(self::$basename,$process_prod))return ['success'=>false,'msg'=>'Erro de parâmetro process_prod'];

        if(is_array($data['ids']) && isset($data['ids'][0])){//neste caso o array estará na sintaxe: [id=>process_id,...]
            //nenhuma ação
        }else{
            try{
                $data['ids'] = json_decode($data['ids'],true);
            }catch (Exception $e){
                return ['success'=>false,'msg'=>'JSON ids inválido'];
            }
        }

        $next_at = $request->next_at;
        if($next_at && (int)$next_at){
                $next_at = FormatUtility::convertDate($request->next_at.':00',true);
        }else{
            $next_at = null;
        }

        if(!$process_id)return ['success'=>false,'msg'=>'Erro de parâmetro Process ID'];
        if(!is_array($data['ids']))return ['success'=>false,'msg'=>'Erro de parâmetro ID'];
        if($data['status']=='')return ['success'=>false,'msg'=>['status'=>'Status não definido']];

        if($process_id=='auto'){
            //neste caso o array estará na sintaxe: [id=>process_id,...]

            //$n = array_key_first($data['ids']);
            //dd($data['ids'], $n);

            //$n = json_decode($data['ids'][0],true);
            $n = $data['ids'];
            $process_id_arr=array_values($n);
            $data['ids'] = array_keys($n);

            //dd('***',$n);

        }else{
            //neste caso todos os $process_id são iguais
            $process_id_arr=[];
            foreach($data['ids'] as $id){
                $process_id_arr[]=$process_id;
            }
        }
        //dd($process_id,$process_prod,$data['ids'],$process_id_arr, '***',$data);

        $i=0;
        foreach($data['ids'] as $id){
            $process_id = $process_id_arr[$i];
            $i++;
            $model = $this->PrSeguradoraData->where(['process_id'=>$process_id,'process_prod'=>$process_prod,'process_rel_id'=>$id])->first();
            //dd('***',$model,$process_id,$process_prod  );
            if($model){
                $st=$data['status'];
                $old_status = $model->status;

                $model->update(['status'=>$st,'process_next_at'=>$next_at]);

                if($old_status==$st)continue;//se o status for igual, não precisa continuar daqui para baixo
                $model->addLog('status','De: '. self::$status_pr[$old_status] .'('.$old_status.')  - para: '. self::$status_pr[$st] .'('.$st.')');

                //atualiza o registro principal (para process_prod=seguradora_data) para o status 'p' Pronto para o robô ou 's' Parado
                if($st=='p' || $st=='s'){
                    $this->ProcessRobotModel->where('id',$process_id)->update(['process_status'=>$st]);
                }

                //atualiza o registro da tabela pr_cad_apolice para process_prod=apolice_check
                if($process_prod=='apolice_check'){
                    $m = PrCadApolice::where(['process'=>'apolice_check','process_id'=>$id])->whereNull('user_id')->first();//pega o primeiro registro (caso exista mais de um) do critério informado
                    if($m){
                        $old_status=$m->status;
                        $m->update(['status'=>$st,'is_done'=>($st=='f' || $st=='w') ]); //obs: como este registro da tabela pr_cad_apolice é automático vinculado ao pr_seguradora_data, então muda automaticamente o campo is_done
                        $m->addLog('status','Alterado de ('.$old_status.') para ('.$st.') - Registro automático (num='.$m->num.')');
                    };
                }
            }
        }

        return ['success'=>true];
    }



    /**
     * Adiciona registros de apólices para ações nos sites das seguradoras
     * @param $request - valores esperados:
     *           $ids - string ids separados por virgula ou array da tabela "process_robot.id where process_name=cad_apolice"
     *           $process_prod - processos de 'seguradora_data'.
     *           $overwrite - se true irá apagar os registros já inseridos e inserí-los novamente para processamente, false adiciona somente se não existir
     */
    public function post_addProcessCheck(Request $request){
        if(!$this->service_active)return ['success'=>false,'msg' => 'Serviço não ativado'];

        $r=$this->onlySuperAdmin();if($r!==true)return $r;

        $ids = $request->input('ids');
        $process_prod = $request->input('process_prod');
        $overwrite = $request->input('overwrite')=='s';
        if(!$ids)return ['success'=>false,'msg'=>'Nenhum registro encontrado'];
        $ids=explode(',',$ids);
        if(!$this->checkProdName($process_prod))return ['success'=>false,'msg'=>'Nome do processo inválido'];

        if($overwrite){//deleta estes registros para inserir novamente
            $this->PrSeguradoraData->where('process_prod',$process_prod)->whereIn('process_rel_id',$ids)->delete();
        }


        $servicePrCadApolice = new \App\Services\PrCadApoliceService;

        $model = $this->ProcessRobotCadApolice->whereIn('id',$ids)->get();
        $ids = array_flip($ids);
        $r=[]; $err=0;
        foreach($model as $reg){
            $n = $this->addProcessCheck($reg,$process_prod);

            if($process_prod=='apolice_check' && ($n['code']=='ok' || $n['code']=='ok2')){//sucesso: foi adicionado ou já existia
                //portando adiciona na tabela pr_cad_apolice o controle de revisão de modo automático (que está vinculado a tabela pr_seguradora_data.process_prod=apolice_check)
                $servicePrCadApolice->add($reg,'apolice_check','p','add', false);//false para não setar o usuário logado (modo automático)

                //verifica se existe um processo de revisão manual que não foi concluído (pr_cad_apolice.process=apolice_check and .process=m and .is_done=false), e neste caso pode remover pois já foi adicionado a revisão automática
                $servicePrCadApolice->getModel('pr')->where(['process'=>'apolice_check','process_id'=>$reg->id,'status'=>'m','is_done'=>false])->delete();
            }

            $r[]=$reg->id .' - '. $n['msg'];
            if(!$n['success'])$err++;
            unset($ids[$reg->id]);
        }
        if(!$r){
            $err=1;
            $r=['Nenhum ID Encontrado'];
        }
        if($ids){//se existir, quer dizer que os respectivos ids não existem
            $err=1;
            $r[]=join(',',array_keys($ids)) .' - não existe(m)';
        }
        return ['success'=>$err==0,'msg'=>join('<br>',$r)];

    }


    /**
     * Adiciona um processo na tabela pr_seguradora_data com o registro que deve ser verificado
     * Verifica e cria também o respectivo registro de controle na tabela process_robot.process_name={$process_prod}
     * @param $regCadApolice - model ProcessRobot_CadApolice do respectivo registro
     * @return [success, msg, code] - valores code:
     *      ok   - adicionado
     *      ok2  - já existe e não adicionado
     *      not  - não adicionado falta de permissão ou configuração
     */
    public function addProcessCheck($regCadApolice,$process_prod){
        if(!$this->service_active)return ['success'=>false,'msg' => 'Serviço não ativado','code'=>'not'];

        $insurer_basename = $regCadApolice->insurer->insurer_basename ?? null;
        if(!$insurer_basename)return ['success'=>false,'msg'=>'Seguradora não setada para este processo','code'=>'not'];

        if($process_prod=='apolice_check' || $process_prod=='boleto_seg'){
            $ramo=$regCadApolice->process_prod;
        }else{//boleto_quiver
            $ramo=null;
        }

        //verifica se o corretor e seguradora tem configuração e permissão para este processo
        if(!FunctionsProcessRobot::allowProcessInsurer($insurer_basename,'seguradora_data',$process_prod,$ramo))return ['success'=>false,'msg'=>'Seguradora não programada','code'=>'not'];//verifica se existe os arquivos de configuração
        if(FunctionsProcessRobot::isActiveInsurerBrokerLogin($regCadApolice->insurer_id,$regCadApolice->broker_id)['success']==false)return ['success'=>false,'msg'=>'Corretor sem dados de acesso cadastrados','code'=>'not'];//seguradora e corretatora estão com todos os dados compatíveis

        //verifica se o registro já foi verificado
        if($this->PrSeguradoraData->where(['process_rel_id'=>$regCadApolice->id,'process_prod'=>$process_prod])->exists())return ['success'=>false,'msg'=>'Registro já processado','code'=>'ok2'];

        //filtros por process_prod
        if($process_prod=='apolice_check'){
            if(!AccountsService::isProcessActive($regCadApolice->account_id,'seguradora_data','apolice_check'))return ['success'=>false,'msg'=>'Permissão negada para o processo: apolice_check','code'=>'not'];
            //else: nenhuma ação

        }else if($process_prod=='boleto_seg'){
            if(!AccountsService::isProcessActive($regCadApolice->account_id,'seguradora_data','boleto_seg',$regCadApolice->process_prod))return ['success'=>false,'msg'=>'Permissão negada para o processo: boleto_seg','code'=>'not'];

            //somente cadastro de apólice com forma de pgto 'boleto' são permitidos
            //ver código em /app/ProcessRobot/cad_apolice/Classes/Vars/QuiverVar::$pgto_codes_types
            $pgto_code = $regCadApolice->getSegData()['fpgto_tipo_code']??'';
            if(!in_array($pgto_code,['10','2'])){//10 boleto, 2 carne
                return ['success'=>false,'msg'=>'Forma de pagamento inválida. Aceito: boleto ou carnê','code'=>'not'];
            }

        }else if($process_prod=='boleto_quiver'){
            if(!AccountsService::isProcessActive($regCadApolice->account_id,'seguradora_data','boleto_quiver'))return ['success'=>false,'msg'=>'Permissão negada para o processo: boleto_quiver','code'=>'not'];

            //Obs: este processo só pode ser adicionado a partir da finalização de 'boleto_seg'
            //verifica se o registro que está sendo adicionado já está finalizado em pr_seguradora_data.process_prod='boleto_seg'
            if(!$this->PrSeguradoraData->where(['process_rel_id'=>$regCadApolice->id,'process_prod'=>'boleto_seg'])->whereIn('status',['f','w'])->exists()){
                //não existe o registro finalizado, portanto apenas encerra a função
                return ['success'=>false,'msg'=>'Não é possível adicionar registro, pois requer a finalização do processo BOLETO_SEG','code'=>'not'];
            }

        }else{
            //demais process_prod são negados
            exit('process_prod '. $process_prod .' negado');
        }

        $status_code_current='';
        $status_current='p';//p - pronto para o robo
        if($process_prod=='apolice_check' || $process_prod=='boleto_seg'){
            //verifica se existe alguma pendência de configuração antes de prosseguir
            $m = \App\Models\ProcessRobotErrors::where([
                    'account_id'=>$regCadApolice->account_id,
                    'process_name'=>self::$basename,
                    'broker_id'=>$regCadApolice->broker_id,
                    'insurer_id'=>$regCadApolice->insurer_id,
                    'status'=>'0',
                ])
                ->whereIn('process_prod',['apolice_check','boleto_seg'])
                ->first();
            if($m){//quer dizer que existe uma pendência de configuração, e neste caso este registro já deve ser setado como parado para entrar na configuração / contagem correta do processo
                $status_current='s';
                $status_code_current=$m->status_code;
            }
            //dd($regCadApolice->toArray(), $status_code_current);
        }

        //captura o id do process_name 'seguradora_data', onde está associado o corretor e seguradora
        $modelSeguradoraData = $this->ProcessRobotModel->where(['insurer_id'=>$regCadApolice->insurer_id,'broker_id'=>$regCadApolice->broker_id,'process_prod'=>$process_prod])->first();
        if($modelSeguradoraData){//encontrado
            //obs: somente se $status_current=='p' quer dizer que existe uma pendência de configuração e portanto não pode mudar o status 'process_status'=p
            //    pois somente quando o operador resolver a configuração é que este registro será liberado
            if($status_current=='p'){
                $arr = ['updated_at'=>date('Y-m-d H:i:s')];
                $s=$modelSeguradoraData->process_status;

                if($process_prod=='boleto_quiver'){
                    if($s!='s'){//se estiver parado não muda o status
                        $arr = ['process_status'=>'p'];//p - pronto para o robo
                    }
                }else{//boleto_seg, e demais processos
                    if($s!='e' && $s!='s'){//se estiver com erro ou parado não muda o status
                        $arr = ['process_status'=>'p'];//p - pronto para o robo
                    }
                }
                $modelSeguradoraData->update($arr);
            }else{
                $modelSeguradoraData->update(['process_status'=>'s','updated_at'=>date('Y-m-d H:i:s')]);
                $modelSeguradoraData->setData('error_msg',$status_code_current);
            }

        }else{//não encontrado
            $modelSeguradoraData = $this->ProcessRobotModel->create([
                'process_name'=>self::$basename,
                'process_prod'=>$process_prod,
                'insurer_id'=>$regCadApolice->insurer_id,
                'broker_id'=>$regCadApolice->broker_id,
                'process_status'=>'p',//p - pronto para o robo
                'process_date'=>date('Y-m-d'),
                'updated_at'=>date('Y-m-d H:i:s'),
                'user_id'=>null,//este processo não precisa informar o usuário
                'account_id'=>$regCadApolice->account_id,
            ]);
        }

        //cria o diretório
        $p = $modelSeguradoraData->baseDir()['dir_final'];
        if(!file_exists($p))(new Filesystem)->makeDirectory($p, 0777, true, true);


        //adiciona o registro
        $model = $this->PrSeguradoraData->create([
            'process_id'=>$modelSeguradoraData->id,
            'process_rel_id'=>$regCadApolice->id,
            'process_prod'=>$process_prod,
            'status'=>$status_current,
            'created_at'=>date('Y-m-d H:i:s'),
            'process_next_at'=> BoletoSegConfig::getNextAt($insurer_basename,'first')   //data do primeiro reprocessamento
        ]);
        //adicion ao log
        $model->addLog('add',['process_id'=>$modelSeguradoraData->id,'process_rel_id'=>$regCadApolice->id,'process_prod'=>$process_prod]);

        return ['success'=>true,'msg'=>'Adicionado com sucesso','code'=>'ok'];
    }

    /**
     * Verifica se o valor de process_prod é validopara o process_name 'seguradora_data.apolice_check'. Return boolean
     */
    private function checkProdName($prod){
        //if(isset(self::$valid_prod_names))self::$valid_prod_names=array_keys(VarsProcessRobot::$configProcessNames['seguradora_data']['products']);
        return in_array($prod,['apolice_check','boleto_seg','boleto_quiver']);
    }


    /**
     * Calcula o resumo das apólices processadas neste registro (são os registros da tabela pr_seguradora_data)
     * @return array[status1=>count1,...]
     */
    public function getDataResume($process_prod,$process_id){
        //self::$status_pr;

        $r = $this->PrSeguradoraData
                ->selectRaw('count(1) as total, status')
                ->where(['process_id'=>$process_id,'process_prod'=>$process_prod])
                ->groupBy('status')
                ->get()->pluck('total','status')->toArray();

        if($r)$r=array_merge( array_fill_keys(array_keys(self::$status_pr),0) , $r );

        return $r;
    }


    /**
     * Remove o registro da tabela pr_seguradora_data
     */
    public function post_prRemoveRegs(Request $request){
        if(!$this->service_active)return ['success'=>false,'msg' => 'Serviço não ativado'];

        $r=$this->onlySuperAdmin();if($r!==true)return $r;

        if($request->input('_method')!='DELETE')return ['success'=>false,'msg'=>'Método não permitido'];
        $process_rel_id = $request->input('id');
        $process_id = $request->input('process_id');
        $process_prod = $request->input('process_prod');//de VarsProcessRobot::$configProcessNames['seguradora_data']['products']

        //obs: este comando foi temporariamente desativado, pois ocorreu de alguns casos antigos entrarem nesta lista para ser processada, e depois retirado sua permissão, e assim não foi possível remover devido ao bloqueiao abaixo (analisar remoção deste código)
        //if(!$this->checkProdName($process_prod))return ['success'=>false,'msg'=>'Nome do processo inválido'];

        $m=$this->PrSeguradoraData->where(['process_id'=>$process_id,'process_prod'=>$process_prod,'process_rel_id'=>$process_rel_id]);
        //dd($m->get(), \App\Services\DBService::getSqlWithBindings($m) );
        $m->delete();

        if($process_prod=='apolice_check'){
            //remove da tabela pr_cad_apolice
            $m=PrCadApolice::where(['process'=>'apolice_check','process_id'=>$process_rel_id])->whereNull('user_id')->first();//pega os registros automáticos (sem usuário)
            if($m){
                $m->addLog('remove','Removido registro automático (num='.$m->num.')');
                $m->delete();
            }
        }

        return ['success'=>true,'msg'=>'Removido com sucesso'];
    }


    /**
     * Exibe a lista de execuções completa
     */
    public function get_execsList(Request $request){
        $r=$this->onlySuperAdmin();if($r!==true)return $r;
        $process_id = $request->input('process_id');
        $model = $this->ProcessRobotModel->find($process_id);
        if(!$model)exit('Erro de parâmetro 1');
        $execsModel = (new ProcessRobotExecs)->where('process_id',$process_id)->orderBy('id','desc')->paginate(_GETNumber('regs')??15);

        return view('admin.process_robot.execs_list',[
            'process_name'=>self::$basename,
            'process_prod'=>$model->process_prod,
            'model'=>$model,
            'execsModel'=>$execsModel,
            'thisClass'=>$this,
            'configProcessNames'=>VarsProcessRobot::$configProcessNames[self::$basename],
        ]);
    }

    /**
     * Exibe os dados do arquivo de execução da tabela process_robot_execs
     */
    public function get_execsFileView(Request $request){
        $r=$this->onlySuperAdmin();if($r!==true)return $r;
        $process_id = $request->input('process_id');
        $exec_id = $request->input('exec_id');

        echo '<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>';

        $model = $this->ProcessRobotModel->find($process_id);
        if(!$model)exit('Erro de parâmetro 1');

        $execsModel = (new ProcessRobotExecs)->where(['process_id'=>$process_id,'id'=>$exec_id])->first();
        if(!$execsModel)exit('Erro de parâmetro 2');

        echo '<h3>Processamento</h3>';
        dump($execsModel->toArray());

        echo '<h3>Arquivo de retorno</h3>';
        dump($execsModel->getText($model));
        echo '<a href="#" onclick="$(\'#textarea\').fadeToggle();return false;">Ver json</a><br><textarea onclick="this.select();" readonly style="display:none;width:800px;height:80px;margin-bottom:20px;" id="textarea">'. json_encode($execsModel->getText($model)) .'</textarea>';

        exit;
    }


    /**
     * Carrega a visualização do boleto do processo 'boleto_seg'
     * @param $param_id - esperado {process_id},{num_parcela}. Ex: '123,1'  //process id 123 e parcela 1
     */
    public function get_fileloadBoletoSeg($param_id){
        if(is_object($param_id)){
            $model = $param_id;
            $num_boleto = $model->_num_boleto;
        }else{
            if(strpos($param_id,',')===false)exit('Parâmetro inválido');
            list($process_id,$num_boleto) = explode(',',$param_id);
            $model = $this->ProcessRobotCadApolice->withoutGlobalScope('account_user')->find($process_id);
        }

        if(!$model){header('HTTP/1.0 404 Not Found');exit('Erro ao acessar arquivo - registro não encontrado');}

        $file = $model->getBoletoSeg()[$num_boleto]??null;
        if(!$file){header('HTTP/1.0 404 Not Found');exit('Erro ao acessar arquivo - registro de boleto não encontrado');}

        if(!file_exists($file['file_path'])){header('HTTP/1.0 404 Not Found');exit('Erro ao acessar arquivo - arquivo de boleto não encontrado');}

        //dd($file);

        header('Content-type: '.$file['file_mimetype']);
        header("Content-Length: " . $file['file_size']);
        header('Content-Disposition:inline; filename="'.$file['file_name'].'"');
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        @readfile($file['file_path']);

        return response()->file($file['file_path'], [
            'Content-Disposition' => 'inline; filename="'. $file['file_name'] .'"',
        ]);
    }


    /**
     * Inicia os processos com erros a partir do status_code
     * @param $reg - model process_robot_errors
     * @param array $params - o mesmo setado mais abaixo em ...registerError()
     *      Campos esperados: process_prod, status_code
     * Sem retorno
     */
    public function startProcessErrors($reg,$params){
        if(!$this->service_active)return ['success'=>false,'msg' => 'Serviço não ativado'];

        extract($params);

        //lógica: captura todos os registros com erro que tenham o respectivo $status_code
        $model = $this->ProcessRobotModel->where(['process_name'=>self::$basename,'process_prod'=>$process_prod])
                ->where('process_status','s')
                ->whereData(['error_msg'=>$status_code])
                ->get();
        foreach($model as $m){
            //atualiza os registros da tabela pr_seguradora_data
            $this->PrSeguradoraData
                ->where(['process_id'=>$m->id,'process_prod'=>$process_prod])
                ->where('status','s')//aqui é somente o status 's'
                ->update(['status'=>'p']);//p - pronto para o robô

            //atualiza o registro de controle
            $m->update(['process_status'=>'p']);//p - pronto para o robô
        }
    }


     /**
     * Esta classe só será iniciada a partir do controller App\Http\Controllers\wsrobotController@get_process, e é obrigatório este nome 'wsrobot_data_get_process'
     * Deve conter o restante dos comandos da solicitação do controller wsrobotController@get_process processando ao encerrar a função
     * @param com os mesmos parâmetros retornados do controller
     * @return array para montagem do xml final     //veja + em wsrobotController@get_process
     */
    public function wsrobot_data_getAfter_process($params){
        if(!$this->service_active)return ['success'=>false,'msg' => 'Serviço não ativado'];

        extract($params);
        $process_prod = $ProcessModel->process_prod;
        //dd($params);
        //verifica se tem os dados de login necessários para prosseguir
        if($login_use['use_quiver']=='n' && (empty($login_use['login']) || empty($login_use['pass']))){
            $ProcessModel->update(['process_status'=>'e']);//c - erro do cliente
            $ProcessModel->setData('error_msg','wbot03');
            return ['repeat'=>true];
        }
        $xml1 = \App::make('\\App\\Http\\Controllers\\Process\\SeguradoraData\\Prod'.studly_case($process_prod))
                ->wsrobot_data_getAfter_process($params,$this);
        if(!$xml1)exit('Erro no retorno da Process\\SeguradoraData\\Prod'. studly_case($process_prod) .' ... getAfter_process() ');
        if($xml1['repeat']??false)return $xml1;

        //verifica se a senha está vazio e deve ser exibida na tela
        $pass = ($login_use['pass'] ? ($method=='GET' ? '*******' : $login_use['pass']) : '-- erro: senha em branco --');//somente na requisição post (que vem do robo) é necessário exibir a senha


        //monta a array final
        if($login_mode=='insurer'){//o login é por cadastro de corretores e seguradoras
            if($login_use['use_quiver']=='n'){//login direto pelo site da seguradora
                $r['broker_login_corretora'] = $login_use['login'];
                $r['broker_login_usuario'] = $login_use['user'];
                $r['broker_login_senha'] = $pass;
                $r['broker_login_code'] = $login_use['code'];

            }else{//login pela central de senhas do quiver
                $r['corretor_login_corretora'] = $login_use['user'];
                $r['corretor_login_usuario'] = $login_use['login'];
                $r['corretor_login_senha'] = $pass;
            }
        }else if($login_mode=='quiver'){//logins de cadastro no arquiver
             //campos padrões para o login no quiver
             $r= [
                //dados de login do corretor
                'corretor_login_corretora'=>$login_use['user'],
                'corretor_login_usuario'=>$login_use['login'],
                'corretor_login_senha'=> $pass,
                //dados da seguradora
                'seguradora_nome_quiver'=>$ProcessModel->insurer->insurer_name,
            ];
        }

        //dd($login_use);
        $r=$xmlDefault + [
                    //dados de login do corretor
                    'broker_id'=>$ProcessModel->broker_id,
                    'insurer_id'=>$ProcessModel->insurer_id,
                    'insurer_basename'=>$ProcessModel->insurer->insurer_basename,
                    'login_use_quiver'=>$login_use['use_quiver'],
                    'login_use_quiver_broker'=>$login_use['login_quiver'],
                ] + $r + $xml1;

        return $r;
    }

    /**
     * Esta classe só será iniciada a partir do controller App\Http\Controllers\wsrobotController@set_process, e é obrigatório este nome 'wsrobot_data_set_process'
     * Esta classe deve conter o restante dos comandos da solicitação do controller wsrobotController@set_process
     * @param com os mesmos parâmetros retornados do controller
     * @return array com ['status'=>'...', msg...]      //veja + em wsrobotController@set_process
     */
    public function wsrobot_data_set_process($params){
        if(!$this->service_active)return ['success'=>false,'msg' => 'Serviço não ativado'];

        if($params['status']=='t')return $params['return'];
        extract($params);


        //*** registra os erros que irão gerar pendências para o operador atuar manualmente ***
        $is_error_pendency=false;
        if(in_array($status_code,self::$statusCode_pendencies)){
            //se chegou até aqui, é porque todos os registros não pode ser prosseguir devido a uma pendência de configuração
             //neste caso deve alterar o status='s' (parado) para todos os registros que estão com os status aguardando ou em andamento
             $this->PrSeguradoraData
                        ->where(['process_id'=>$ProcessModel->id,'process_prod'=>$ProcessModel->process_prod])
                        ->whereIn('status',['p','a'])//captura todos os status: p - aguardando robo, a - em andamento;
                        ->update(['status'=>'s']);//altera para s - parado

             $is_error_pendency=true;

             //atualiza o campo process_next_at com próxima data e hora em que será processado novamente (variável process_repeat_delay model ProcessRobot)
            $next_at = date('Y-m-d H:i:s', strtotime(self::$process_repeat_delay.' min', strtotime(date('Y-m-d H:i:s'))) );
            $ProcessModel->update(['process_status'=>'s']);//mantém o status=s (parado)
        };


        if(!$is_error_pendency){
            //retornos que devem ser reprocessados
                $code_reprocess=['sega05','segl00','segd03','segd01','capt01'];
                if(in_array($status_code,$code_reprocess)){
                    //atualiza os registros internos para pronto para o robô
                    $this->PrSeguradoraData
                        ->where(['process_id'=>$ProcessModel->id,'process_prod'=>$ProcessModel->process_prod,'status'=>'e'])
                        ->update(['status'=>'p']);//p - pronto para o robô

                    //atualiza o campo process_next_at com próxima data e hora em que será processado novamente (variável process_repeat_delay model ProcessRobot)
                    $next_at = date('Y-m-d H:i:s', strtotime(self::$process_repeat_delay.' min', strtotime(date('Y-m-d H:i:s'))) );
                    $ProcessModel->update(['process_status'=>'p','process_next_at'=>$next_at]);//mantém o status=p (pronto para o robô) e process_next_at para ser executado mais tarde
                    return $params['return'];
                }


            //nesta classe são processados as variações de cada registro da tabela pr_seguradora_data
            //o retorno da função não é necessário
                \App::make('\\App\\Http\\Controllers\\Process\\SeguradoraData\\Prod'.studly_case($ProcessModel->process_prod))
                        ->wsrobot_data_set_process($params,$this);

            //processos válidos para todos os produtos/subprocessos de seguradora_data
            //verifica tem registros pendentes para finalizar o processo principal ($ProcessModel process_name='seguradora_data')
            //obs: os status disponíveis são: p, a, f, e, s
                //obs2: o status 's' (parado) não é considerado, pois ele só voltará a ser considerado / processado quando não tiver mais a pendência da configuração responsável por este status
                $tmpModel = $this->PrSeguradoraData->select('status')->where(['process_id'=>$ProcessModel->id,'process_prod'=>$ProcessModel->process_prod])->groupBy('status');
                $tmp = $tmpModel->whereIn('status',['p','a','e'])->get()->pluck('status')->toArray(); //captura todos os status: p - aguardando robo, a - em andamento, e erro

                if($tmp){//ainda tem registros pendentes
                    if(in_array('p',$tmp)){$s='p';}//existe registros pendentes
                    else if(in_array('a',$tmp)){$s='a';}//existe registros pendentes
                    else{$s='e';}//existe apenas status de erro
                }else{//nenhum registro pendente ou com erro, portanto estão todos finalizados
                    $s='f';
                }
                //dd($tmp,$s);
                //lista dos tipos de códigos de retorno considerados erros
                $errors_stop = [
                    'quil04','segl01','segl02','segl03','segl04','sega02','sega05','sega06','sega07','segd01','segd02','segu01','segz01','quis02',
                    'quid01','quid02','quid03','quid04','quid05','quid06',
                    'segr01'
                ];

                //lista de tipos de códigos que é considerado finalizado
                $errors_ok = [
                    'segd07',   //Boleto não disponível - Todas as parcelas pagas
                ];

                if(in_array($status_code,$errors_stop)){//erros que devem tentar alterar o status para erro até que seja analisado pelo programador
                    $arr = ['process_status'=>'e'];

                }elseif(in_array($status_code,$errors_ok)){//erros que devem alterar o status para finalizado, pois não tem como prosseguir
                    $arr = ['process_status'=>$s];

                }else{//demais retornos
                    $arr = ['process_status'=>$s];

                    //estes erros podem ter a execução automática de novas tentativas mais tarde
                    if($ProcessModel->process_prod=='boleto_quiver' && ($s=='f' || $s=='a' || $s=='p')){//como para boleto_quiser é processado apenas um registro por vez, verifica se está tudo finalizado ou ainda tem registros pendentes para processar
                        $arr['process_next_at'] = null;
                    }elseif($s=='f'){//demais casos onde é sempre enviado e processado os registros em lote pelo robô
                        $arr['process_next_at'] = null;
                    }else{
                        if($continue_process){//continua o processamento, portanto não realiza do item seguinte da tabela pr_seguradora_data
                            //neste caso, não precisa reagendar e nem alterar o status, pois embora tenha tido todo retorno de finalização, o campo $continue_process indica que não acabou de processar e continuará para os demais registros
                            $arr['process_next_at'] = null;
                        }else{
                            if($s=='p' || $s=='a'){//ainda existem registros pendentes
                                $minutes = 5;
                            }else{//demais casos
                                $minutes = 60*3;//a cada 3hs
                            }
                            $arr['process_next_at'] = date('Y-m-d H:i:s', strtotime($minutes.' min', strtotime(date('Y-m-d H:i:s'))) );
                        }
                    }
                }

                //dd($arr);
                $ProcessModel->update($arr);
        }

        //*** registra os erros que irão gerar pendências para o operador atuar manualmente ***
        if($is_error_pendency){
            //se chegou até aqui, é porque todos os registros não pode ser prosseguir devido a uma pendência de configuração
            //neste caso deve alterar o status='s' (parado) para todos os registros que estão com os status aguardando ou em andamento
            $this->PrSeguradoraData
                    ->where(['process_id'=>$ProcessModel->id,'process_prod'=>$ProcessModel->process_prod])
                    ->whereIn('status',['p','a'])//captura todos os status: p - aguardando robo, a - em andamento;
                    ->update(['status'=>'s']);//altera para s - parado

            $callback=[
                'class'=>'\\App\\Http\\Controllers\\Process\\ProcessSeguradoraDataController@startProcessErrors',
                'params'=>[
                    'process_prod'=>$ProcessModel->process_prod,
                    'status_code'=>$status_code,
                ]
            ];

            \App::make('\\App\\Http\\Controllers\\Process\\ProcessErrorsController')->registerError($ProcessModel,$status_code,$callback);
        }

        return $return;
    }

}
