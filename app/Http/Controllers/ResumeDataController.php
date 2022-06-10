<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ProcessRobot\ResumeProcessRobot;

/**
 * Classe para capturar os dados do resumo / contagem dos totais dos processos do robô
 */
class ResumeDataController extends Controller{

    /**
     * Retorna ao resumo para a página inicial
     * @param $request - valores
     *  force       - força a leitura (em desenvolvimento)
     *  filter_date - tipo do filtro da data, valores: all, created, processed  //obs: os valores created e processed filtram do dia atual
     */
    public function get_homeSuperAdmin(Request $request){
        $force = $request->input('force')=='s';
        $filter_date = $request->input('filter_date');
        $r=[];

        //*** cadastro de apólice ***
        $filter=[
            'account_id'=>$request->input('account_id'),
            'calc_time'=>true,
            'get_resume_error'=>true,
        ];
        if($filter_date=='created' || $filter_date=='processed'){
            $filter['fdate'] = $filter_date;
            $filter['date'] = date('Y-m-d');
        }//else //$filter_date==all, quer dizer que são todos os registros


        $r['cad_apolice'] = ResumeProcessRobot::getProcessRobot('cad_apolice',$filter) +
             ResumeProcessRobot::getGlobalData('cad_apolice',['account_id'=>$filter['account_id']]);

        if(!($filter_date=='created' || $filter_date=='processed')){
            $filter = ['account_id'=>$request->input('account_id')];//, 'get_last_date'=>true
            //seguradora_files (obs: o funcionamento deste caso está diferente da tabela seguradora_data)
            $r['seguradora_files.down_apo'] = ResumeProcessRobot::getProcessRobot('seguradora_files',$filter);//registro principal - lógica: os erros aqui representam que não conseguiu baixar os arquivos da área de seguradoras
            $r['seguradora_files.mark_done'] = ResumeProcessRobot::getPrSegDataFiles('seguradora_files','down_apo',$filter);//lógica: os erros aqui representam que não conseguiu marcar como concluído/não concluído na área de seguradoras
            //seguradora data
            $r['seguradora_data.apolice_check'] = ResumeProcessRobot::getPrSegDataFiles('seguradora_data','apolice_check',$filter);
            $r['seguradora_data.boleto_seg'] = ResumeProcessRobot::getPrSegDataFiles('seguradora_data','boleto_seg',$filter);
            $r['seguradora_data.boleto_quiver'] = ResumeProcessRobot::getPrSegDataFiles('seguradora_data','boleto_quiver',$filter);
        }
        //dd($r);
        return $r;
    }


    /**
     * Retorna ao resumo de dados para o painel da conta do cliente
     * @param $request - valores
     *      force      - força a leitura (em desenvolvimento)
     * @obs estes dados só são filtrados de acordo com a conta atual do usuário logado
     */
    public function get_homeAdmin(Request $request){
        $account_id = \Config::accountID();
        $force = $request->input('force')=='s';

        //*** cadastro de apólice ***
        $r = ResumeProcessRobot::getGlobalData('cad_apolice',['account_id'=>$account_id]);
        $r['count_all']=ResumeProcessRobot::getProcessRobot('cad_apolice',['account_id'=>$account_id]);
        $r['count_created']=ResumeProcessRobot::getProcessRobot('cad_apolice',['account_id'=>$account_id,'fdate'=>'created','date'=>$r['max_created']['date']  ]);
        //resumos dos erros de oprador
        $r['error_user'] = ResumeProcessRobot::calcResumeError('cad_apolice',['account_id'=>$account_id,'status_err'=>'c']);
        //controle de alterações dos registros
        $r['pr_seg_ctrl'] = ResumeProcessRobot::CadApolice_calcPrSegCtrl(['account_id'=>$account_id]);
        return $r;
    }
}
