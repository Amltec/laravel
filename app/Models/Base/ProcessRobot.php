<?php

namespace App\Models\Base;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Utilities\ValidateUtility;
use App\ProcessRobot\VarsProcessRobot;
use App\Models\Traits\AccountTrait;
use App\Models\Traits\LogTrait;
use App\Models\Traits\TaxTrait;
use App\Models\ProcessRobotData;
use App\Models\ProcessRobotExecs;
use App\Models\Broker;
use App\Models\Insurer;
use App\Models\User;
use App\Models\Account;
use App\Utilities\FormatUtility;
use Illuminate\Filesystem\Filesystem;
use Exception;

/**
 * Classe base Model para os processos do robô
 * Deve ser extendida para a respectivo model do processo (cad_apolice, seguradora_files, ...)
 */

class ProcessRobot extends Model{
    //use \App\Models\Traits\MetadataTrait;//métodos de metadata
    use AccountTrait {
        create as protected createAccountTrait;
    }
    use SoftDeletes, LogTrait, TaxTrait;

    public $timestamp = true;
    const UPDATED_AT = null;//desabilita apenas o campo updated_at

    protected $fillable = ['broker_id','insurer_id','process_name','process_prod','process_ctrl_id','process_status','process_status_changed','process_date','process_auto','created_at','updated_at','robot_id','process_test','user_id','process_next_at','locked','locked_at','account_id','process_order'];
    protected $table = 'process_robot';
    public $process_repeat_delay=3;     //tempo em minutos que um processo voltará a se repetir caso o app do robô retorno com status='T'
    public $process_repeat_delay_nn=30; //tempo em minutos que um processo voltará a se repetir caso o app retorne com status=E e status_code=quivnn (erro por excesso de tentativas)
    protected $execs_save_data=true;//indica se deve salvar os arquivos de retorno da classe \App\ProcessRobot\WSRobotController->set_process() pelo comando setText

    //captura o respectivo controller a partir do nome do processo
    private $ProcessClass=null;
    protected function getProcessClass(){
        if(!$this->ProcessClass)$this->ProcessClass = '\\App\\Http\\Controllers\\Process\\Process'.studly_case($this->attributes['process_name']).'Controller';
        return $this->ProcessClass;
    }


    //valor padrão ao criar um registro
    public function create(array $attributes){
        $attributes['process_next_at'] = date('Y-m-d H:i:s');
        return $this->createAccountTrait($attributes);//executa o comando create do trait AccountTrait
    }

    //caminho base para armazenamento dos processos (caminho seguro)
    //boolean folder_date - se false, irá retornar no diretório final (dir_final) arquivo diretamente no diretório base, se true irá retornar em ano/mês. Default true
    public function baseDir($folder_date=true){
        static $account_id;
        $account_id = $this->attributes['account_id'];
        $p = 'accounts'. DIRECTORY_SEPARATOR . $account_id . DIRECTORY_SEPARATOR . $this->process_name . DIRECTORY_SEPARATOR . $this->process_prod;
        $n = 'accounts'. DIRECTORY_SEPARATOR . $account_id . DIRECTORY_SEPARATOR . 'tmp';
        $oDate= \DateTime::createFromFormat('Y-m-d', $this->process_date);
        $r=[
            'dir'=>storage_path($p),
            'relative_dir'=>$p,
            'dir_tmp'=>storage_path($n),
            'relative_dir_tmp'=>basename(storage_path()) . DIRECTORY_SEPARATOR . $n,
            'date_dir'=>$oDate->format('Y') . DIRECTORY_SEPARATOR . $oDate->format('m'),//obs: esta informação é apenas para utilização em casos onde é divivido as pastas em ano/mês, e nem todos os processos serão utilziados
            'folder_id'=> (string)$this->attributes['id'],
        ];
        //monta o diretório final no padrão usado para gravar vários arquivos separados somente para este processo
        $r['dir_final'] = $r['dir'] . DIRECTORY_SEPARATOR . ($folder_date ? $r['date_dir'] . DIRECTORY_SEPARATOR : '') . $r['folder_id'];
        return $r;
    }


    //*********** Escopos **************
    /**
     * Filtro por Metadato da tabela process_robot_data.
     * Param array $meta_data - [meta_name=>metavalue]. Condição AND para múltiplos parâmetros.
     *                      Operadores no nome do parâmetro. Sintaxe [meta_name__op]. Caso não informado será sempre igual (=)
     *                      Exemplos:
     *                      - ['contact_sexo'=>'m', 'address_uf'=>'SP',...]
     *                      - ['contact_sexo__!='=>'m', 'address_uf__NOT'=>'SP',...]
     *                      - ['address_cidade__LIKE'=>'landia',...]
     *                      - ['address_uf__IN'=>['SP','RJ'],...]       //o valor é um array
     *                      - ['address_uf__BETWEEN'=>[1,2]]            //o valor é um array de apenas 2 índices
     *                      Operadores aceitos: '='(default), '!=', '<', '<=', '>', '>=', 'IN', 'NOT_IN', 'LIKE', 'NOT_LIKE', 'BETWEEN', 'NOT_BETWEEN'
     */
    public function scopeWhereData($query,$meta_data){
        $operatorList=['=', '!=', '<', '<=', '>', '>=', 'in', 'not_in', 'like', 'not_like', 'between', 'not_between'];//lista de opradores aceitos

        $arr=[];
        $sql=   $this->table.'.id='.
                '(select md1.process_id from process_robot_data as md1 where md1.process_id='.$this->table.'.id and (';

                foreach($meta_data as $name=>$value){

                    $op='=';
                    if(strpos($name,'__')!==false){//tem operador no nome do campo
                        $tmp = explode('__', $name);
                        $name = $tmp[0];
                        if(in_array(strtolower($tmp[1]), $operatorList)){//encontrou um operador válido
                            $op= str_replace('_',' ', strtolower($tmp[1]));//converte o '_' por ' ' e deixa minúsculo para comparar corretamente abaixo
                        }
                    }

                    if(($op=='in' || $op=='not in') && is_array($value)){
                        //aqui o $value possui um array de valores, ex: [value1, value2, ...]
                        $i=0;$tmp=[];
                        foreach($value as $v){
                            $tmp[]='?';//:meta_value_'.$name.'_'.$i;
                            $i++;
                        }
                        $sql.='(md1.meta_name=? and md1.meta_value '.$op.' ('. join(',',$tmp) .')) or ';

                    }else if(($op=='between' || $op=='not between') && is_array($value)){
                        //aqui o $value possui um array de 2 posições, ex: [value1, value2]
                        $sql.='(md1.meta_name=? and md1.meta_value '.$op.' ? and ?) or ';

                    }else{
                        $sql.='(md1.meta_name=? and md1.meta_value '.$op.' ?) or ';
                    }

                    //atribui os valores corretamente para o bind sql
                    $arr['meta_name_'.$name]=$name;
                    if(is_array($value)){
                        $i=0;
                        foreach($value as $v){
                            $arr['meta_value_'.$name.'_'.$i]=$v;
                            $i++;
                        }
                    }else{
                        $arr['meta_value_'.$name]=$value;
                    }
                }
                //$sql=trim($sql,'or ') . ') ';
                $sql=FormatUtility::trim($sql,' or ') . ') ';

                $i=count($meta_data);
                if($i>1)$sql.='GROUP BY md1.process_id HAVING COUNT(md1.process_id)='.$i.' ';


        $sql.= ')';
        $query->whereRaw($sql,$arr);

        return $query;
    }


    //*********** Escopos **************
    /**
     * Se não existe um registro metadado
     * @param meta_name - nome do campo process_robot_data.meta_name
     */
    public function scopeWhereDataNotExists($query,$meta_name){
        $tbn='md'.rand(100, 999);
        return $query->whereRaw('NOT EXISTS (SELECT '.$tbn.'.process_id FROM process_robot_data as '.$tbn.' WHERE '.$tbn.'.process_id = process_robot.id and '.$tbn.'.meta_name=?)',[$meta_name]);
    }



    //******** atributos *********

    //label de status - curto
    public function getStatusLabelAttribute(){
        return $this->getProcessClass()::$status[$this->attributes['process_status']]??'';
    }

    //label de status - longo
    public function getStatusLongLabelAttribute(){
        return $this->getProcessClass()::$statusLong[$this->attributes['process_status']]??'';
    }

    //Retorna a classe da cor dependendo do tipo do status
    public function getStatusColorAttribute(){
        return $this->getProcessClass()::$statusColor[$this->attributes['process_status']]??[];
    }

    //Retorna a um array do relacionamento de dada
    private $cache_data=true;
    public function getDataArrayAttribute(){
        if($this->cache_data===false){//obs: só será ==false, quando outros métodos desta função atualizem estes dados e precisar forçar a leitura
            $this->load('data');//força a leitura dos dados da tabela no relacionamento
            $this->cache_data=true;
        }
        $data=$this->data;
        $r=[];
        if($data){
            foreach($data as $f=>$v){
                $r[$v->meta_name]= ValidateUtility::isSerialized($v->meta_value) ? unserialize($v->meta_value) : $v->meta_value;
            }
        }
        return $r;
    }



    //******* funções ********
    /**
     * Captura e retorna ao texto do arquivo txt associado este registro
     * @param $name - ex de valores: text, data, exec_...
     * @param $cache - indica se deve forçar a leitura ou pegar os dados do cache
     */
    private $get_text_cache=[];
    public function getText($name,$cache=true){
        if(isset($this->get_text_cache[$name]) && $cache){
            return $this->get_text_cache[$name];
        }else{
            $p = $this->baseDir()['dir_final'] . DIRECTORY_SEPARATOR . $this->attributes['id'].'_'.$name.'.data';
            $text = file_exists($p) ? file_get_contents($p) : null;
            $text = ValidateUtility::isSerialized($text) ? unserialize($text) : $text;
            $this->get_text_cache[$name] = $text;
            return $text;
        }
    }

    /**
     * Seta um texto ao arquivo txt associado este registro
     * @param array|string $text
     * @param name - ex de valores: text, data, exec_...
     * Return boolean
     */
    public function setText($name,$text){
        if(!$this->execs_save_data)return false;
        if(is_array($text))$text = serialize($text);
        $p = $this->baseDir()['dir_final'];
        if(file_exists($p)){
            $p.= DIRECTORY_SEPARATOR . $this->attributes['id'].'_'.$name.'.data';
            $r = (new Filesystem)->put($p,$text);
            $this->get_text_cache=[];//limpa o cache
            return $r?true:false;
        }else{//não achou o diretório
            return false;
        }
    }

    /**
     * Deleta um arquivo de txt associado este registro
     * Return boolean
     */
    public function delText($name){
        $p = $this->baseDir()['dir_final'] . DIRECTORY_SEPARATOR . $this->attributes['id'].'_'.$name.'.data';
        if(file_exists($p))return (new Filesystem)->delete($p);
        return true;
    }

    /**
     * Captura um metadado
     */
    public function getData($name=''){
        $r=$this->getDataArrayAttribute();
        if($name)$r=array_get($r,$name);
        return $r;
    }


    /**
     * Seta metadado
     * @param string|int|array $value
     * Return array[success,msg]
     */
    public function setData($name,$value){
        if(is_array($value))$value = serialize($value);
        $value = substr((string)$value,0,50);
        try{
            ProcessRobotData::updateOrInsert(['process_id'=>$this->attributes['id'],'meta_name'=>$name],['meta_value'=>$value??'']);
            $r=['success'=>true,'msg' => 'Dado cadastrado'];
            $this->cache_data=false;//limpa o cache (precisa ser =false para forçar a leitura do cache)
        } catch (Exception $e) {
            $r=['success'=>false,'msg'=>$e->getMessage()];
        }
        return $r;
    }


    /**
     * Deleta um metadado
     * Return array[success,msg]
     */
    public function delData($name,$all=false){
        try{
            $model = ProcessRobotData::where('process_id',$this->attributes['id']);
            if($all===false)$model = $model->where('meta_name',$name);
            $model->delete();
            $this->cache_data=false;//limpa o cache (precisa ser =false para forçar a leitura do cache)
            $r=['success'=>true,'msg' => 'Dado excluído'];
        } catch (Exception $e) {
            $r=['success'=>false,'msg' => $e->getMessage()];
        }
        return $r;
    }


    //********** relaciomentos ***********
    //com a tabela de dados do processo: um 'processo' tem muitos 'dados' - relacionamento (1-N)
    public function data(){
        return $this->hasMany(ProcessRobotData::class,'process_id','id');
    }

    //com a tabela de corretores: uma relação de 'processo' tem 1 'broker' - relacionamento (1-1)
    public function broker(){
        return $this->belongsTo(Broker::class);
    }

    //com a tabela de corretores: uma relação de 'processo' tem 1 'insurer' - relacionamento (1-1)
    public function insurer(){
        return $this->belongsTo(Insurer::class);
    }

    //com a tabela de usuários: uma relação de 'processo' tem 1 'user' - relacionamento (1-1)
    public function user(){
        return $this->belongsTo(User::class);
    }

    //com a tabela de contas: uma relação de 'processo' tem 1 'account' - relacionamento (1-1)
    public function account(){
        return $this->belongsTo(Account::class);
    }

    //com a tabela de contas: uma relação de 'processo' tem 1 'account' - relacionamento (1-1)
    public function execs(){
        return $this->hasMany(ProcessRobotExecs::class,'process_id','id');
    }


}
