<?php

namespace App\Models;
use App\Utilities\FormatUtility;
use Illuminate\Database\Eloquent\Builder;
use App\Models\PrSeguradoraData;
use App\Models\PrCadApolice;
use DB;

/**
 * Personalização da model para o ProcessRobo para 'cad_apolice'
 */
class ProcessRobot_CadApolice extends Base\ProcessRobot{
    private static $basename='cad_apolice';
        
    
    
    //**** global scope ****
    protected static function boot(){
        parent::boot();
        static::addGlobalScope('basename', function (Builder $builder){
            $builder->where('process_robot.process_name',self::$basename);
        });
    }
    
    
    /**!!!!!!!!!!!!!!!!!! IMPORTANTE: ajustar esta função trocar por um relationcamneto JSON abaixo !!!!!!!!!!!!!!!!!!!!!!!!!!!!!
     * Filtro por campos da tabela pr_seg_.....
     * @param $table - nome da tabela. Valores: dados, parcelas, automovel, ...
     * Param array $fields_data - [meta_name=>metavalue]. Condição AND para múltiplos parâmetros.
     *                      Operadores no nome do parâmetro. Sintaxe [meta_name__op]. Caso não informado será sempre igual (=)
     *                      Exemplos:
     *                      - ['contact_sexo'=>'m', 'address_uf'=>'SP',...]
     *                      - ['contact_sexo__!='=>'m', 'address_uf__NOT'=>'SP',...]
     *                      - ['address_cidade__LIKE'=>'landia',...]
     *                      - ['address_uf__IN'=>['SP','RJ'],...]       //o valor é um array
     *                      - ['address_uf__BETWEEN'=>[1,2]]            //o valor é um array de apenas 2 índices
     *                      Operadores aceitos: '='(default), '!=', '<', '<=', '>', '>=', 'IN', 'NOT_IN', 'LIKE', 'NOT_LIKE', 'BETWEEN', 'NOT_BETWEEN'
     */
    public function scopeWherePrSeg($query,$table,$fields_data){
        $operatorList=['=', '!=', '<', '<=', '>', '>=', 'in', 'not_in', 'like', 'not_like', 'between', 'not_between'];//lista de opradores aceitos
        
        $arr=[];
        $sql=   $this->table.'.id='.
                '(select md1.process_id from pr_seg_'.$table.' as md1 where md1.process_id='.$this->table.'.id and (';
                
                foreach($fields_data as $name=>$value){
                    
                    $op='=';
                    if(strpos($name,'__')!==false){//tem operador no nome do campo
                        $tmp = explode('__', $name);
                        $name = $tmp[0];
                        if(in_array(strtolower($tmp[1]), $operatorList)){//encontrou um operador válido
                            $op= str_replace('_',' ', strtolower($tmp[1]));//converte o '_' por ' ' e deixa minúsculo para comparar corretamente abaixo
                        }
                    }
                    if(($op=='in' || $op=='not in')){
                        if(is_string($value)){
                            $value=explode(',',$value);//separador padrão
                        }
                        if(is_array($value)){
                            //aqui o $value possui um array de valores, ex: [value1, value2, ...]
                            $i=0;$tmp=[];
                            foreach($value as $v){
                                $tmp[]='?';//:value_'.$name.'_'.$i;
                                $i++;
                            }
                            $sql.='(md1.'.$name.' '.$op.' ('. join(',',$tmp) .')) or ';
                        }
                    }else if(($op=='between' || $op=='not between')){
                        if(is_string($value)){
                            $value=explode(',',$value);//separador padrão
                        }
                        if(is_array($value)){
                            //aqui o $value possui um array de 2 posições, ex: [value1, value2]
                            $sql.='(md1.'.$name.' '.$op.' ? and ?) or ';
                        }
                    }else{
                        $sql.='(md1.'.$name.' '.$op.' ?) or ';
                    }
                    
                    /*//atribui os valores corretamente para o bind sql
                    $arr['field_name_'.$name]=$name;
                    if(is_array($value)){
                        $i=0;
                        foreach($value as $v){
                            $arr['field_value_'.$name.'_'.$i]=$v;
                            $i++;
                        }
                    }else{
                        $arr['meta_value_'.$name]=$value;
                    }*/
                    $arr[$name]=$value;
                }
                //$sql=trim($sql,'or ') . ') ';
                $sql=FormatUtility::trim($sql,' or ') . ') ';
                
                //$i=count($fields_data);
                //if($i>1)$sql.='GROUP BY md1.process_id HAVING COUNT(md1.process_id)='.$i.' ';
                
                
        $sql.= ')';
        $query->whereRaw($sql,$arr);
        //dump($query->toSql(),$sql,$arr);exit;
        
        return $query;
    }
    
     /**
      * Filtro por campos da tabela pr_seg_...__s - controle de alterações 
      * @param $ctrl - user, robo
      * @param $table - nome da tabela. Valores: dados, parcelas, automovel, ...
      * @param $fields_data - [field1,field2...]
      * @param $fields_cond - AND, OR
      */
    public function scopeWherePrSegCtrl($query,$ctrl,$table,$fields,$fields_cond='and'){
        if(!$fields)return $query;
        $query->join('pr_seg_'.$table.'__s as ps'.$table, 'process_robot.id','=','ps'.$table.'.process_id')
                ->where('ps'.$table.'.ctrl',$ctrl=='user'?0:1);//modificado pelo: 0 user, 1 robo
        $q1 = FormatUtility::addPrefixArray(array_fill_keys($fields,1),'ps'.$table.'.');//$changed: 1 modificado no quiver, 0 não modificado
        //dd($q1);
        if($fields_cond=='AND'){
            $query->where($q1);
        }else{
            $query->where(function($xq) use($q1){
                return $xq->orWhere($q1);
            });
        }
        //dump($query->toSql(),$query->getBindings());exit;
        return $query;
    }
    
    
    /**
     * Retorna aos dados das tabelas de seguro (tabela pr_seg_...)
     * @param modo_array - modo como irá retornar aos dados das demais tabelas. Mais informações na classe \App\Services\PrSegServices->getAllData()
     * @param cache - se true, irá forçar o carregamento caso já esteja em cache. Default false.
     */
    private $cache_seg_data=null;
    public function getSegData($modo_array=false,$cache=false){
        if(!$this->cache_seg_data || $cache){
            $this->cache_seg_data = (new \App\Services\PrSegService)->getAllData($this,$modo_array);
        }
        return $this->cache_seg_data;
    }
    
    /**
     * Retorna aos dados do boletos (do processo seguradora_data.boleto_seg)
     * @return [ num_parcel=>[url,url_np,file_path,file_size,file_mimetype,file_name,valor,datavenc], ...]
     */
    public function getBoletoSeg(){
        $boleto_arr = $this->getText('boleto_seg');//sintaxe: [exec_id = {status_code:"",boleto:{ parcela_num1:{}, ... } }]
        if(!$boleto_arr)return null;
        
        $path=$this->baseDir()['dir_final'];
        $data = $this->getData();
        $id = $this->attributes['id'];
        $u = route( (\Config::adminPrefix()=='super-admin'?'super-admin':'admin') .'.app.get',['process_seguradora_data','fileload_boleto_seg','--param--']);
        $u_np = route('process_robot_fileload',['seguradora_data',base64_encode(serialize(['process_prod'=>'boleto_seg','user'=>$this->attributes['user_id'],'process'=>$id,'token'=>($data['token']??null) ])), '--filename--']);
        $u_np = str_replace('https://','http://',$u_np);
        
        $boleto_r=[];
        foreach($boleto_arr as $exec_id => $arr){
            if(!isset($arr['parcelas']))continue;
            
            $n='boleto_all.pdf';
            $p=$path . DIRECTORY_SEPARATOR . 'boleto_seg' . DIRECTORY_SEPARATOR . $n;
            
            if(file_exists($p)){//existe o arquivo contendo todas as parcelas em um só arquivo
                $boleto_r['all'] = [
                    'url' => str_replace('--param--', $id.',all', $u),
                    'url_np' => str_replace('--filename--', $n, $u_np),
                    'file_path' => $p,
                    'file_size' => filesize($p),
                    'file_mimetype' => mime_content_type($p),
                    'file_name' => $n,
                    'valor' => '',
                    'datavenc' => '',
                ];
                
            }else{
                //*** Obs: este é um modo mais antigo, pois desde 12/05/2021, foi implementado para sempre mesclar os pfds e retornar a um só arquivo ***
                //verifica se existe o arquivo de cada parcela (em um índice de matriz). 
                //obs: abaixo caso dentro de $boleto_arr exista $parc_num iguais dentro do loop, então irá sobrescrever prevalecendo sempre a do último índice (pois se referente a execuções mais recentes do robô)
                $last_num=0;
                foreach($arr['parcelas'] as $parc_num=>$parc_data){//erro na leitura
                    $parc_num = (int)$parc_num;
                    if($parc_num<=0)continue;//correção para o caso de vir com erro no json com número de parcela inválido (ex 0, -1, ...)
                    $n='boleto_'.$parc_num.'.pdf';
                    $p=$path . DIRECTORY_SEPARATOR . 'boleto_seg' . DIRECTORY_SEPARATOR . $n;
                    //if(\Auth::user() && \Auth::user()->user_level=='dev')dump($p, file_exists($p));
                    if(file_exists($p)){
                        $boleto_r[$parc_num] = [
                            'url' => str_replace('--param--', $id.','.$parc_num, $u),
                            'url_np' => str_replace('--filename--', $n, $u_np),
                            'file_path' => $p,
                            'file_size' => filesize($p),
                            'file_mimetype' => mime_content_type($p),
                            'file_name' => $n,
                            'valor' => $parc_data['valor']??'',
                            'datavenc' => $parc_data['datavenc']??'',
                        ];
                    }
                }
            }
        }
        //if(\Auth::user() && \Auth::user()->user_level=='dev')dd($boleto_r);
        return $boleto_r;
    }
   
    
    
    //captura o respectivo registro da tabela pr_seguradora_data
    private $modelPrSeguradoraData=null;
    public function getPrSeguradoraData($process_prod){
        if(!$this->modelPrSeguradoraData)$this->modelPrSeguradoraData = new PrSeguradoraData;
        return $this->modelPrSeguradoraData->select('process_id','process_rel_id','status','created_at','finished_at','process_next_at')->where(['process_rel_id'=>$this->attributes['id'],'process_prod'=>$process_prod])->first();
    }
    
    
    //captura o respectivo registro da tabela pr_cad_apolice (último registro inserido)
    private $modelPrCadApolice=null;
    public function getPrCadApolice($process=null){
        if(!$this->modelPrCadApolice)$this->modelPrCadApolice = new PrCadApolice;
        $arr=['process_id'=>$this->attributes['id'],'process'=>$process];
        if(!$process)unset($arr['process']);
        return $this->modelPrCadApolice->select('user_id','status','created_at','finished_at','is_done')->where($arr)->orderBy('num','desc')->first();
    }
}
    