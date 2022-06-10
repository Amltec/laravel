<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use Gate;
use App\Models\Insurer as InsurerModel;
use App\ProcessRobot\cad_apolice\Classes\Data\NumQuiverData;
use App\ProcessRobot\VarsProcessRobot;
use App\Services\LogsService;

/**
 * Classe de configurações do cadastro de apólices
 */
class ConfigCadApoliceController{
    
    public function __construct(Account $AccountModel){
        if(Gate::denies('superadmin')){//por enquanto, é pormitido apenas para o superadmin
            return \Redirect::to(route('admin.index'))->send();
        }
        
        if(Gate::denies('admin')){//é negado a permissão para não administrador
            return \Redirect::to(route('admin.index'))->send();
        }
        //filtra a model pelo usuário logado
        $this->accountModel = $AccountModel->find(\Auth::user()->getAuthAccount('id'));
        if(!$this->accountModel)exit('Erro. Registro removido ou não existe');
        if($this->accountModel->account_status!='a')return ['success'=>false,'msg'=>'Esta conta está cancelada. Contate o administrador.'];
    }
    

    public function index(){
        $account = $this->accountModel;
        $config = $account->getData('config_cad_apolice');
        
        return view('admin.config_cad_apolice',[
            'account'=>$account,
            'config'=>$config,
        ]);
    }
    
    
    /**
     * Monta o exemplo de número de apólices
     */
    public function post_processExample(Request $req){
        $data = $req->all();
        $r_all=[];
        
        //array de nomes de produtos ativos, ex: automovel, residencial
        //obs: nesta função é usado a lista completa de produtos para salvar a configuração, pois todas estas informações são salvas em um único campo serializado
        $products_list = VarsProcessRobot::$configProcessNames['cad_apolice']['products'];
        
        $modelInsurer = InsurerModel::where('insurer_status','a')->get();
        foreach(['default'=>'Padrão'] + $products_list  as $prod_name => $prod_opt){
            $r=[];
            foreach($modelInsurer as $rs){
                $ex_num = $data[$prod_name.'_field-ex_num_'.$rs->id]??null;
                if(!$ex_num)continue;

                $r[$rs->id] = NumQuiverData::process($ex_num, array_filter([
                    'num_origem'    => $this->cBool($data[$prod_name.'_num_origem_'.$rs->id]),
                    'not_dot_traits'=> $this->cBool($data[$prod_name.'_not_dot_traits_'.$rs->id]),
                    'len'           => $this->cBool($data[$prod_name.'_len_'.$rs->id]),
                    'len_r'         => $this->cBool($data[$prod_name.'_len_r_'.$rs->id]),
                    'last_dot'      => $this->cBool($data[$prod_name.'_last_dot_'.$rs->id]),
                    'between_dots'  => $this->cBool($data[$prod_name.'_between_dots_'.$rs->id]),
                    'not_zero_left' => $this->cBool($data[$prod_name.'_not_zero_left_'.$rs->id]),
                ]));
            }
            $r_all[$prod_name] = $r;
        }
        
        return $r_all;
    }
    
    /**
     * Converte respectivamente as strings s|n para true|false
     */
    private function cbool($s){
        if(is_null($s)){
            return null;
        }elseif($s=='s' || $s=='n'){
            return $s=='s';
        }else{
            return $s;
        }
    }
    
    
    
    public function post_save(Request $req){
        $data = $req->all();
        $config=[];
        
        //dados atuais
        $currentData = $this->accountModel->getData('config_cad_apolice');
        
        //array de nomes de produtos ativos, ex: automovel, residencial
        //obs: nesta função é usado a lista completa de produtos para salvar a configuração, pois todas estas informações são salvas em um único campo serializado
        $products_list = VarsProcessRobot::$configProcessNames['cad_apolice']['products'];
        
        //*** config: gerais ***
            $config['venc_1a_parc_cartao'] = $req->venc_1a_parc_cartao;
            $config['venc_1a_parc_debito'] = $req->venc_1a_parc_debito;
            $config['venc_1a_parc_boleto'] = $req->venc_1a_parc_boleto;
            $config['venc_1a_parc_1boleto_debito'] = $req->venc_1a_parc_1boleto_debito;
            $config['venc_1a_parc_1boleto_cartao'] = $req->venc_1a_parc_1boleto_cartao;
            $config['venc_ua_parc'] = $req->venc_ua_parc;

            //formas de pagamento
            $config['names_fpgto'] = [
                'carne'         => $req->names_fpgto_carne,
                'boleto'        => $req->names_fpgto_boleto,
                'debito'        => $req->names_fpgto_debito,
                'cartao'        => $req->names_fpgto_cartao,
                '1boleto_debito'=> $req->names_fpgto_1boleto_debito,
                '1boleto_cartao'=> $req->names_fpgto_1boleto_cartao,
            ];
            
            //anexos
            $config['names_anexo'] = [
                'apolice'   => $req->names_anexo_apolice,
                //'historico' => $req->names_anexo_historico,
                'boleto'    => $req->names_anexo_boleto,
            ];
            
            
        //*** config: cadastro de apólice ***
            $modelInsurer = InsurerModel::where('insurer_status','a')->get();
            foreach(['default'=>'Padrão'] + $products_list as $prod_name => $prod_opt){
                $r=[];
                if($prod_name!='default')$r['def'] = ($data[$prod_name.'_def']??'')=='s';
                foreach($modelInsurer as $rs){
                    $r[$rs->id] = [
                        'ex_num'        => $data[$prod_name.'_field-ex_num_'.$rs->id],
                        'num_origem'    => $this->cBool($data[$prod_name.'_num_origem_'.$rs->id]),
                        'not_dot_traits'=> $this->cBool($data[$prod_name.'_not_dot_traits_'.$rs->id]),
                        'len'           => $this->cBool($data[$prod_name.'_len_'.$rs->id]),
                        'len_r'         => $this->cBool($data[$prod_name.'_len_r_'.$rs->id]),
                        'last_dot'      => $this->cBool($data[$prod_name.'_last_dot_'.$rs->id]),
                        'between_dots'  => $this->cBool($data[$prod_name.'_between_dots_'.$rs->id]),
                        'not_zero_left' => $this->cBool($data[$prod_name.'_not_zero_left_'.$rs->id]),
                    ];
                    //dd($r[$rs->id]);
                }
                $config[$prod_name=='default' ? 'num_quiver' : 'num_quiver_'.$prod_name]=$r;
            }
        
        
        //*** config: produtos para busca de apólice ***
            $r=[];
            foreach($products_list as $prod_name => $prod_opt){
                $r[$prod_name] = str_replace([chr(10),chr(13),'||'],'|',$data['search_products_'.$prod_name]);
            }
            $config['search_products'] = $r;
            
            
        //*** config: produtos para busca de apólice ***
            $r=[];
            foreach($products_list as $prod_name => $prod_opt){
                $r[$prod_name] = str_replace([chr(10),chr(13),'||'],'|',$data['down_apo_ramo_'.$prod_name]);
            }
            $config['down_apo_ramo'] = $r;
        
            
        //*** monta o log ***
            $log = [];//dados para o log
            $insurer_names = $modelInsurer->pluck('insurer_alias','id')->toArray();
            
            foreach($config as $f=>$data){
                if(is_array($data)){
                    if(substr($f,0,10)=='num_quiver'){//números do quiver
                        $r=[];
                        foreach($data as $insurer_id=>$data2){
                            if(is_array($data2)){
                                foreach($data2 as $f2=>$v2){
                                    $v0 = $currentData[$f][$insurer_id][$f2];
                                    //dump([$insurer_names[$insurer_id],$v0,$v2]);
                                    if($v0!=$v2){
                                        $r[] = $insurer_names[$insurer_id].' - '.$f2.' - De "'. $v0 .'" Para "'.$v2.'"';
                                    }
                                }
                            }else{
                                $f2 = $insurer_id;
                                $v0 = $currentData[$f][$insurer_id];
                                $v2 = $data2;
                                if($v0!=$v2){
                                    $r[] = $f2.' - De "'. $v0 .'" Para "'.$v2.'"';
                                }
                            }
                        }
                        if($r)$log[$f]=join('<br>',$r);
                    }else{
                        foreach($data as $f2=>$data2){
                            if(is_array($data2)){
                                foreach($data2 as $f3=>$data3){
                                    if($data3 != ($currentData[$f][$f2][$f3]??null)){
                                        //$log[$f.'.'.$f2.'.'.$f3] = 'De "'. $currentData[$f][$f2][$f3] .'" Para "'.$data3.'"';
                                        $log[$f.'.'.$f2.'.'.$f3] = LogsService::textDiff($currentData[$f][$f2][$f3], $data3);
                                    }
                                }
                            }else{
                                if($data2 != ($currentData[$f][$f2]??null)){
                                    //$log[$f.'.'.$f2] = 'De "'. $currentData[$f][$f2] .'" Para "'.$data2.'"';
                                    $log[$f.'.'.$f2] = LogsService::textDiff($currentData[$f][$f2], $data2);
                                }
                            }
                        }
                    }
                }else{
                    if($data != ($currentData[$f]??null)){
                        //$log[$f] = 'De "'. $currentData[$f] .'" Para "'.$data.'"';
                        $log[$f] = LogsService::textDiff($currentData[$f], $data);
                    }
                }
            }
            //dd($log, $currentData, $config);
            if($log)$this->accountModel->addLog('config',$log);
            
            
        //dd($config, json_encode($config));
        //*** salva os dados ***
            $this->accountModel->setData('config_cad_apolice',$config);
        
        return ['success'=>true,'msg'=>'Dados salvos com sucesso'];
    }

}
