<?php

namespace App\ProcessRobot;

use Auth;
use DateTime;
use DB;
use Illuminate\Support\Facades\Cache;
use App\ProcessRobot\VarsProcessRobot;
use App\Utilities\FormatUtility;
use App\Models\Base\ProcessRobot;
use App\Models\ProcessRobotData;
use App\Models\ProcessRobotExecs;

use App\Models\ProcessRobot_SeguradoraData;
use App\Models\PrSeguradoraData;


use Config;

/*
 * Classe que conta os totais processados pela tabela process_robot
 */
class ResumeProcessRobot{
    private static $ProcessRobotModel = null;

    public static function ProcessRobotModel(){
        if(!isset(self::$ProcessRobotModel))self::$ProcessRobotModel = new ProcessRobot;
        return self::$ProcessRobotModel;
    }


    /**
     * Retorna aos resumos da tabela process_robot
     * @param $process_name - nome base do process, ex: cad_apolice, seguradora_files, ...
     * @param array $opts - (veja na função os valores)
     * @return array -
     *      count_total    => (int) //total de registros
     *      count_status   => [{S} => (int), ...],    //totais por status da tabela process_robot
     *      time_avg       => (string),    //tempo médio de processamento
     */
    public static function getProcessRobot($process_name,$opts=[]){
        $opts=array_merge([
            'account_id'=>null,         //id da conta
            'process_prod'=>null,       //nome do produto / subprocesso
            'process_date'=>null,       //data que o processo foi adicionado. Formato yyyy-mm-dd
            'process_upd'=>null,        //data que o processo foi executado pelo robô (para reprocessamentos). Formato yyyy-mm-dd
            'broker_id'=>null,          //id do corretor
            'insurer_id'=>null,         //id da seguradora
            'fdate'=>'created',         //filtro tipo do campo de data. Valores: 'created', 'processed'
            'date'=>null,               //filtro por data
            'calc_time'=>false,         //calcula o tempo de processamento
            'get_last_date'=>false,     //calcula a última data de processamento
            'get_resume_error'=>false,  //captura o resumo dos erros
        ],$opts);

        $r=[];


        //captura a classe do processo
        $processClass='\\App\\Http\\Controllers\\Process\\Process'.studly_case($process_name).'Controller';

        //*** soma todos os registros do processo ***
            //monta o sql da contagem de status
            $s=['count(1) as total'];
            foreach($processClass::$status as $status=>$label){
                $s[]='COUNT(CASE WHEN process_status="'.$status.'" THEN 1 END) AS count_st_'.$status;
            }
        $model = self::ProcessRobotModel()->selectRaw(join(',',$s));
        self::filterModelX1($model,$process_name,$opts);
        //dd($model->toSql(),$model->getBindings());
        $model = $model->first();//->toArray();
        $r['count_total'] = $model->total;
        $r['count_status'] = [];
        foreach($processClass::$status as $status=>$label){
            $r['count_status'][(string)$status] = $model->{'count_st_'.$status};
        }

        //personalização por process_name
        if($process_name=='cad_apolice'){
            //tira o ignorado do total
            $r['count_total'] -= $r['count_status']['i']??0;
        }

        //*** calcula o tempo médio de processamento em segundos ***
        if($opts['calc_time']){
            $model = self::ProcessRobotModel()->selectRaw('avg(TIMESTAMPDIFF(SECOND, pe.process_start,pe.process_end)) as time_avg')
                        ->join('process_robot_execs as pe', 'process_robot.id','=','pe.process_id')
                        ->whereNotNull('pe.process_end')
                        ->whereRaw('pe.process_start < pe.process_end');
            self::filterModelX1($model,$process_name,$opts);
            //dd($model->toSql(),$model->getBindings());
            $n = $model->value('time_avg');
            //$r['time_avg'] = FormatUtility::convertTimeStrFloat($n,'str');
            $r['time_avg'] = FormatUtility::convertTimeStrFloat($n,'str','seconds');
        }

        if($opts['get_last_date']){
            $r = $r + self::getGlobalData($process_name,$opts);
        }

        if($opts['get_resume_error']){
            $r['resume_error'] = self::calcResumeError($process_name,$opts);
        }


        return $r;
    }

    /**
     * Retorna aos resumos da tabela pr_seguradora_data ou seguradora_files
     * @param $process_name - valores: seguradora_data, seguradora_files
     * @param $process_prod - nome base do subprocess, ex: down_apo, apolice_check...
     * @param array $opts - (veja na função os valores)
     * @return array -
     *      count_total    => (int) //total de registros
     *      count_status   => [{S} => (int), ...],    //totais por status da tabela process_robot
     *      gr_status_e    =  [s1,s2,...]             //relação de status que representam erros
     *      gr_status_f    =  [s1,s2,...]             //relação de status que representam finalizações
     *      gr_status_a    =  [s1,s2,...]             //relação de status que representam aguardando
     */
    private static $PrSegModel=[];
    private static $PrSegClass=[];
    public static function getPrSegDataFiles($process_name,$process_prod,$opts=[]){
        $opts=array_merge([
            'account_id'=>null,         //id da conta
            'process_prod'=>$process_prod,       //nome do produto / subprocesso
            'process_date'=>null,       //data que o processo foi adicionado. Formato yyyy-mm-dd
            'process_upd'=>null,        //data que o processo foi executado pelo robô (para reprocessamentos). Formato yyyy-mm-dd
            'broker_id'=>null,          //id do corretor
            'insurer_id'=>null,         //id da seguradora
            'fdate'=>'created',         //filtro tipo do campo de data. Valores: 'created', 'processed'
            'date'=>null,               //filtro por data
            'get_last_date'=>false,     //calcula a última data de processamento
        ],$opts);

        $process_name_case = studly_case($process_name);
        //captura a model
        if(!isset(self::$PrSegModel[$process_name])){
            $class = '\\App\\Models\\ProcessRobot_'.$process_name_case;
            self::$PrSegModel[$process_name] = new $class;
            self::$PrSegClass[$process_name] = '\\App\\Http\\Controllers\\Process\\Process'.$process_name_case.'Controller';
        }

        $thisClass = self::$PrSegClass[$process_name];
        $thisModel = self::$PrSegModel[$process_name];


        //*** soma todos os registros do processo ***
            //monta o sql da contagem de status
            $s=['count(1) as total'];
            foreach($thisClass::$status_pr as $status=>$label){
                $s[]='COUNT(CASE WHEN status="'.$status.'" THEN 1 END) AS count_st_'.$status;
            }
        $model = $thisModel->selectRaw(join(',',$s))
                ->join('pr_'.$process_name.' as pd', 'process_robot.id','=','pd.process_id')
                ->join('process_robot as p2', 'p2.id','=','pd.process_rel_id')
                ->whereNull('p2.deleted_at')
                ->where('process_robot.process_ctrl_id','<>','manual')
                ;
        self::filterModelX1($model,$process_name,$opts);
        //if(\Auth::user()->user_level=='dev' && $process_name=='seguradora_files')dd(  \App\Services\DBService::getSqlWithBindings($model)  );



        $model = $model->first();//->toArray();


        $r['count_total'] = $model->total;
        $r['count_status'] = [];
        foreach($thisClass::$status_pr as $status=>$label){
            $r['count_status'][(string)$status] = $model->{'count_st_'.$status};
        }
        //dd($r);
        //if(\Auth::user()->user_level=='dev' && $process_name=='seguradora_files')dd($r);
        //relação de status agrupados das tabelas pr... que representam erros
        //analise os status das respectivas classes Process{ProcessName}Controller.php (e também + informações na documentação em xlsx)
        $r['gr_status_a'] = $thisClass::$status_pr_group['a'];
        $r['gr_status_p'] = $thisClass::$status_pr_group['p'];
        $r['gr_status_0'] = $thisClass::$status_pr_group['0'] ?? [];
        $r['gr_status_e'] = $thisClass::$status_pr_group['e'];
        $r['gr_status_f'] = $thisClass::$status_pr_group['f'];

        if($opts['get_last_date']){
            $r = $r + self::getGlobalData($process_name,$opts);
        }

        return $r;
    }



    /**
     * Retorna aos dados globais para todos os procesoss
     * @param $process_name - nome base do process, ex: cad_apolice, seguradora_files,...
     * @return array -
     *      last_created   => dd/mm/aaaa   //data do último registro enviado
     *      last_updated   => dd/mm/aaaa   //data do último registro processado
     */
    public static function getGlobalData($process_name, $opts=[]){
        $opts=[
            'account_id'   => $opts['account_id']??null,     //id da conta
            'process_prod' => $opts['process_prod']??null,   //nome do produto / subprocesso
            'broker_id'    => $opts['broker_id']??null,      //id do corretor
            'insurer_id'   => $opts['insurer_id']??null,     //id da seguradora
        ];

        $r=[];

        $model = self::ProcessRobotModel()->selectRaw('max(created_at) as max_created_at, max(updated_at) as max_updated_at');
        self::filterModelX1($model,$process_name,$opts);
        //dd($model->toSql(),$model->getBindings());
        $d = $model->first()->toArray();
        $now = date('Y-m-d');

        foreach(['created','updated'] as $n){
            $is_today = $now == date('Y-m-d',strtotime($d['max_'.$n.'_at']));
            $d1 = FormatUtility::dateFormat($d['max_'.$n.'_at'],'date');
            $d2 = FormatUtility::dateFormat($d['max_'.$n.'_at'],'d M');
            $r['max_'.$n] = [
                'datetime'  => $d['max_'.$n.'_at'],
                'date'      => explode(' ',$d['max_'.$n.'_at'])[0],
                'datebr'    => $d1,
                'label'     => $is_today ? 'Hoje' : $d2,
                'label2'    => $is_today ? 'Hoje' : 'em '.$d2
            ];
        }

        return $r;
    }


    //filtros adicionais das demais funções desta classe
    private static function filterModelX1(&$model, $process_name, $opts){
        if(Config::adminPrefix()=='super-admin')$model->withoutGlobalScope('account_user');    //obs: é retirado o global scope, pois acima já será filtrao pelo $account_id

        $model->where('process_robot.process_name',$process_name);
        if($opts['account_id']??false)$model->where('process_robot.account_id',$opts['account_id']);
        if($opts['process_prod']??false)$model->where('process_robot.process_prod',$opts['process_prod']);
        if($opts['process_date']??false)$model->whereDate('process_robot.process_date',$opts['process_date']);
        if($opts['process_upd']??false)$model->whereDate('process_robot.process_upd',$opts['process_upd']);
        if($opts['broker_id']??false)$model->where('process_robot.broker_id',$opts['broker_id']);
        if($opts['insurer_id']??false)$model->where('process_robot.insurer_id',$opts['insurer_id']);

        if(($opts['fdate']??false) && $opts['date']??false){
            $f = $opts['fdate']=='processed'?'updated_at':'created_at';
            $model->whereDate('process_robot.'.$f,$opts['date']);
        }
    }


    /**
     * Retorna aos totais por erros agrupados por código do erro (considera o campo process_robot_data.meta_name=error_msg para esta ação)
     * @param array $opts - os mesmos da função self::filterModelX1() +
     *                      status_err - (string|array) código do erro, ex: 'e', ['e','c']. Default [e,c,i]
     * @return [code=>count,...]
     */
    public static function calcResumeError($process_name, $opts=[]){
        //todos os erros pelo metadados error_msg
        $s=$opts['status_err']??null;
        if(!$s)$s=['e','c','1'];
        if(!is_array($s))$s=[$s];

        $m = self::ProcessRobotModel()->selectRaw('count(1) as total, pd.meta_value')
                 ->join('process_robot_data as pd', 'process_robot.id','=','pd.process_id')
                 //->whereIn('process_robot.process_status',['c','f','w']) //somente os casos com erro de operador ou finalizados (pois as propostas já emitidas são finalizadas depois)
                 ->whereIn('process_robot.process_status',$s) //somente os casos com erro de operador ou finalizados (pois as propostas já emitidas são finalziadas depois)
                 ->where('pd.meta_name','error_msg') //somente os casos com erro de operador
                 ->groupBy('pd.meta_value')
                 ->orderBy('pd.meta_value','asc');
        self::filterModelX1($m,$process_name,$opts);
        $m=$m->get()->pluck('total','meta_value')->toArray();
        return $m;
    }



    /**
     * Calcula os resumos das alterações nas tabelas de controles: pr_seg_..._s
     * Válido somente para os registros finalizados e para process_name=cad_apolice
     * @param int|string $filter_day - o mesmo de $this->getProcessData()
     * @return [{table} =>[{field}=>count,...], ...]
     */
    public static function CadApolice_calcPrSegCtrl($opts=[]){
        $process_name='cad_apolice';
        $arr=[
            'dados' =>[
                'ctrl_vigencia'=>['inicio_vigencia','termino_vigencia'],
                'ctrl_premio'=>['fpgto_premio_total','fpgto_premio_liquido'],
            ],
            'automovel' =>[
                'ctrl_classe'=>['veiculo_classe']
            ],
        ];
        $r=[];
        foreach($arr as $table=>$arr){
            foreach($arr as $ctrl => $fields){
                $r[$table.'_'.$ctrl] = ['fields'=>join(',',$fields), 'count'=> self::calcPrSegCtrl_x1($process_name,$table,$fields,$opts) ];
            }
        }
        return $r;
    }
        //função complementar de CadApolice_calcPrSegCtrl() //$table - dados, parcelas, {prod_name}
        private static function calcPrSegCtrl_x1($process_name,$table,$fields,$opts){
            $r=[];
            $tbn='pr_seg_'.$table.'__s';
            $m = self::ProcessRobotModel()->selectRaw('COUNT(1) as total')
                 ->join($tbn, 'process_robot.id','=',$tbn.'.process_id')
                 ->whereIn('process_robot.process_status',['f','w']) //somente finalizados ou pendente de apólice
                 ->where(function($q) use($fields){ return $q->orWhere( array_fill_keys($fields,1) ); });//modificado pelo robo no quiver
                    ;
            self::filterModelX1($m,$process_name,$opts);
            //if($table=='dados' && Auth()->user()->user_level=='dev')dd($m->toSql(),$m->getBindings(), $m->get()->toArray());
            return $m->value('total');
        }


    /**
     * Calcula os resumos do processo seguradora_data.boleto_seg|boleto_quiver
     * @param array $opts - valores:
     *          process_prod    - boleto_seg, boleto_quiver
     */
    public static function SeguradoraData_calcBoleto($opts=[]){
        $opt = array_merge([
            'process_prod'=>'boleto_seg',
            'account_id'=>null,
        ],$opts);

        $model = PrSeguradoraData::selectRaw(
                    'count(1) as total, '.
                    'COUNT(CASE WHEN pr_seguradora_data.status="f" THEN 1 END) AS count_st_f, '.
                    'COUNT(CASE WHEN pr_seguradora_data.status="w" THEN 1 END) AS count_st_w, '.
                    'COUNT(CASE WHEN pr_seguradora_data.status="e" THEN 1 END) AS count_st_e, '.
                    'COUNT(CASE WHEN pr_seguradora_data.status="s" THEN 1 END) AS count_st_s, '.
                    'COUNT(CASE WHEN pr_seguradora_data.status="1" THEN 1 END) AS count_st_1, '.
                    'COUNT(CASE WHEN pr_seguradora_data.status="a" or pr_seguradora_data.status="p" THEN 1 END) AS count_st_p '
                )
                ->join('process_robot as p', 'p.id','=','pr_seguradora_data.process_id')
                ->where('pr_seguradora_data.process_prod',$opt['process_prod']);

        if($opt['account_id']){
            $model->where('p.account_id',$opt['account_id']);
        }

        $model=$model->first();

        if($opt['process_prod']=='boleto_seg'){
            $r = [
                'total'=>$model->total,
                'labels'=>[
                    'p'=>'Aguardando Robô',
                    'f'=>'Finalizado sem alterações',
                    'w'=>'Finalizado com boleto',
                    'e'=>'Erro',
                    's'=>'Parado',
                    '1'=>'Em Análise',
                ],
                'status'=>[
                    'p'=>$model->count_st_p,
                    'f'=>$model->count_st_f,
                    'w'=>$model->count_st_w,
                    'e'=>$model->count_st_e,
                    's'=>$model->count_st_s,
                    '1'=>$model->count_st_1,
                ],
                'errors'=>[]
            ];

        }else{//boleto_quiver
            $r = [
                'total'=>$model->total,
                'labels'=>[
                    'p'=>'Aguardando Robô',
                    'w'=>'Finalizado',
                    'e'=>'Erro',
                    's'=>'Parado',
                    '1'=>'Em Análise',
                ],
                'status'=>[
                    'p'=>$model->count_st_p,
                    'w'=>$model->count_st_f + $model->count_st_w,
                    'e'=>$model->count_st_e,
                    's'=>$model->count_st_s,
                    '1'=>$model->count_st_1,
                ],
                'errors'=>[]
            ];
        }

        $model = ProcessRobot_SeguradoraData::select('id')
                ->where(['process_robot.process_prod'=>$opt['process_prod']])
                ->whereIn('process_robot.process_status',['e','p'])
                ->join('pr_seguradora_data as pr', 'pr.process_id','=','process_robot.id')
                ->whereIn('pr.status',['e','s','1'])
                ->groupBy('id')
                ->get();
        if($model->count()>0){
            $modelPr = new PrSeguradoraData;
            foreach($model as $reg){
                $status_code = $reg->getData('error_msg');
                if(!isset($r['errors'][$status_code])){
                    $r['errors'][$status_code]=[
                        'text'  => \App\Http\Controllers\Process\ProcessSeguradoraDataController::getStatusCode($status_code,false),
                        'count' => 0,
                    ];
                }
                $r['errors'][$status_code]['count'] += $modelPr->where(['process_id'=>$reg->id,'status'=>'e'])->count();
            }
        }
        //dd($r);
        return $r;
    }
}

