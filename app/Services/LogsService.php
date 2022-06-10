<?php

namespace App\Services;

use App\Utilities\ValidateUtility;
use Auth;
use DB;
use Exception;

/**
 * Classe de serviço de logs.
 */
class LogsService{
    //field len = 7 caracteres
    public static $log_label=[
        'add'       =>'Adicionado',
        'edit'      =>'Atualizado',
        'trash'     =>'Enviado para a lixeira',
        'restore'   =>'Restaurado da lixeira',
        'remove'    =>'Excluído',
        'log'       =>'Registro de Log',
        'save'      =>'Dados salvos',
        'login'     =>'Login no sistema',
        'logina'    =>'Login automático ao acessar',
        'logoff'    =>'Logout',
        'error'     =>'Erro',
        'manual'    =>'Registro Manual',
        'review'    =>'Revisado',
        'check'     =>'Confirmado',
        'finish'    =>'Finalizado',
        'process'   =>'Processado',
        'index'     =>'Indexado',
        'status'    =>'Alteração de status',
        'down'      =>'Download concluído',
        'upload'    =>'Upload concluído',
        'fchange'   =>'Campos Alterados',
        'notf'      =>'Não encontrado',
        'ignore'    =>'Ignorado',
        'diff'      =>'Divergente',
        'lock'      =>'Bloqueado',
        'config'    =>'Configuração',
    ];
    
    
    /**
     * Adiciona um registro de log
     * @param $action - valores: add, edit, trash, restore, remove, log
     * @param $area_name, $area_id - respectivamente o nome da área e id correspondente
     * @param string|array $log_data - dos dados o log. Se ===false, então ignora e não adiciona o registro de log
     */
    public static function add($action,$area_name,$area_id,$log_data=null){
        if($log_data===false)return false;
        if($log_data && is_array($log_data))$log_data = serialize($log_data);
        $data1 = self::getInfoData1();
        try{
            DB::select('insert into user_logs (user_id,account_id,user_level,area_name,area_id,action,log_data,created_at,url,ip) values (:user_id,:account_id,:user_level,:area_name,:area_id,:action,:log_data,:created_at,:url,:ip)',[
                'user_id' => $data1['user_id'],
                'account_id' => $data1['account_id'],
                'user_level' => $data1['user_level'],
                'area_name' => substr($area_name,0,50),
                'area_id' => $area_id,
                'action' => substr($action,0,7),
                'log_data' => substr($log_data,0,21845),
                'created_at' => date("Y-m-d H:i:s", time()),
                'url'=> substr(url()->previous(),0,100),//armazena a url da página anterior do post
                'ip'=> \Request::ip(),
            ]);
            $r=true;
        } catch (Exception $e) {
            $r = $e->getMessage();
        }
        return $r;
    }
    
    /**
     * Adiciona um registro de log no padrão de campos de formulário
     * @param $action - valores: add, edit, trash, restore, remove, log
     * @param $area_name, $area_id - respectivamente o nome da área e id correspondente
     * @param array $log_data - considera a existência dos campos: _original_data e _modified_data para montar os dados com a função self::prepareData()
     * @param string $opt - veja em self::prepareData()
     */
    public static function addFields($action,$area_name,$area_id,$log_data=null,$opt=null){
        return self::add($action,$area_name,$area_id,self::prepareData($log_data,$opt));
    }
    

    /**
     * Deleta um registro de log
     * @param array $where: id, user_id, account_id, area_name, area_id
     * @obs: aceita os valroes user_id=>true e account_id=>true para indicar que estes valores serão capturados automaticamente
     */
    public static function del($where){
        if(!$where)return false;
        try{
            $table = DB::table('user_logs')->where($where)->delete();
            $r=true;
        } catch (Exception $e) {
            $r=$e->getMessage();
        }
        return $r;
    }
    
    
    /**
     * Prepara um dado de $log_data considera a var padrão de dados originais e alterados vindo do padrão do templates.ui.auto_fields
     * @param array $data - com os dados do $request->all() do form
     * @param string $opt - sintaxe (opcional): 
     *                          'allows:field1,field2' - permite apenas os campos informados
     *                          'denied:field1,field2' - nega apenas os campos informados
     * @return array[fieldname=>[original=>[value,text], modified=>[value,text], 'label'=>'...']]  OR  ===false se nenhum dado estiver alterado (pois deste modo, na função ::add() não será adicionado o logo)
     * @obs:   se 'original' for vazio, então não serã definido na matriz
     *         se @param $data = string, então retornará apenas a mesma string
     *         se os dados retornados forem vazioz, retornará a ===false
     *         o parâmetro 'text' corresponde ao texto de exibição do valor, e caso não exista, será sempre exibido o próprio valor (ex ['c','Cancelado'] ou ['c','c'])
     * Ex de uso: LogsService::add(LogsService::prepareData($request->all()) );
     * @obs2:  dados serializados dentro do campo não são permitidos (ex, [field=>serialize(...),...]
     */
    public static function prepareData($data,$opt=null){
        if($data===false)return false;
        if(gettype($data)!='array')return $data;
        
        $original_data=$data['_original_data']??null;
        $modified_data=$data['_modified_data']??null;//obs: este campo é usado apenas para capturas os labels modificados
        
        $fields_allows=[];$fields_denied=[];
        if($opt){
            if(substr($opt,0,7)=='allows:')$fields_allows = array_map('trim',explode(',',substr($opt,7)));
            if(substr($opt,0,7)=='denied:')$fields_denied = array_map('trim',explode(',',substr($opt,7)));
        }
        parse_str(html_entity_decode($original_data), $original_data);
        
        parse_str(html_entity_decode($modified_data), $modified_data);
        //dd($original_data,$modified_data);
        if(!$original_data || !$modified_data)return false;
        
        unset($data['_original_data'],$data['_modified_data'],$data['_token'],$data['_method']);
        //dd($original_data,$modified_data,$data);
        
        //armazena os dados na sintaxe $arr[fieldname] = [original=>[value,text], modified=>[value,text], 'label'=>'...']
        $arr=[];
        //verifica quais campos são diferentes
        foreach($original_data as $f=>$arr_o){//$arr_o = value, value2, disabled (_boolean_), label
            if($fields_allows && !in_array($f,$fields_allows))continue;
            if($fields_denied && in_array($f,$fields_denied))continue;
            if(in_array($f,['_token','_method']) || strpos($f,'|_autofield_count')!==false)continue;//campos ignorados
            if($arr_o['disabled']=='_true_')continue;//campo desabilitado, não precisa verificar
            
            $vm_o = $data[$f]??null;//valor modificado original
            $vm = $vm_o;            //valor modificado
            if(is_array($vm)){
                $vm=join(chr(10),$vm);//provavelmente o campo é um checkbox ou qualquer outra matriz de dados
                $vm=str_replace(chr(13),'',$vm);
            }
            $vcompare=trim($arr_o['value']);//str_replace([chr(13),chr(10)],['',''],trim($arr_o['value']));//usa o chr(10) por '|' considerando que $vm seja uma matriz, pois aí os dados passam a ser mais compatíveis
            //if($f=='insurer_doc')dd($vm,$vcompare);
            
            if(isset($modified_data[$f]) && trim($vm)!==$vcompare){
                if($modified_data[$f]['type']=='password'){
                    $vm='******';
                    $arr_o['value']=$arr_o['text']='';
                }
                $arr_m = $modified_data[$f];//label do valor modificado
                $arr[$f]=['modified'=>[$vm,array_get($arr_m,'text',$vm)],'label'=>$arr_o['label']];
                if($arr_o['value']!='')$arr[$f]['original']=[$arr_o['value'],array_get($arr_o,'text',$arr_o['value'])];
            }
        }
        //verifica se tem algum campo nos dados alterados que não está no original
        foreach($data as $f=>$vd){
            if($fields_allows && !in_array($f,$fields_allows))continue;
            if($fields_denied && in_array($f,$fields_denied))continue;
            if(strpos($f,'|_autofield_count')!==false)continue;//campos ignorados
            if(!isset($original_data[$f])){
                $arr[$f]=[
                    'modified'=>[$vd,$vd],
                    'label'=> isset($modified_data[$f]) ? ($modified_data[$f]['label']??$f) : $f
                ];
            }
        }
        //dd('*',$arr);
        if(empty($arr)){
            return false;
        }else{
            return $arr;
        }
    }
    
    /**
     * Retorna aos dados do usuário e da conta se disponíveis
     * @param where, se informado, irá filtar os dados considerando user_id===true e account_id===true para preencher estes dados automaticamente e retornar a var atualizada.
     * return array [user_id=>,user_level=>,account_id=>, ...]
     */
    private static function getInfoData1($where=null){
        $user_id=null;
        $user_level=null;
        $account_id=null;
        $user = Auth::user();
        if($user){
            $user_id=$user->id;
            $user_level=$user->user_level;
            if(\Config::adminPrefix()!='super-admin'){
                $account_id = $user->getAuthAccount('id');
            }
            if(!$account_id)$account_id=null;//deixa como null caso não retorne a um valor válido
        }
        if($where){
            if($where['user_id']===true)$where['user_id']=$user_id;
            if($where['account_id']===true)$where['account_id']=$account_id;
            return $where;
        }else{
            return ['user_id'=>$user_id,'user_level'=>$user_level,'account_id'=>$account_id];
        }
    }
    
    
    /**
     * Retorna a um resumo do log a partir dos dados de log_data
     * @param model UserLog
     */
    public static function getResumeData($model){
        return self::$log_label[$model->action]??$model->action;
    }
    
    
    /**
     * Retorna aos dados do log_data formatados
     * Return string
     */
    public static function formatLogData($log_data){
        if(ValidateUtility::isSerialized($log_data)){
            $log_data=unserialize($log_data);
            //dd($log_data);
            if(is_array($log_data)){
                $is_data_prepared=false;
                //verifica se está no formato de self::prepareData()
                foreach($log_data as $f=>$a){
                    if(is_array($a) && (isset($a['modified']) || isset($a['original']))){
                        $is_data_prepared=true;
                        break;
                    }
                }
                
                if($is_data_prepared){
                    //espera um array no formato [ fieldname => [original=>[value,text], modified=>[value,text], label=>'...' ]
                    $r='<table class="table table-bordered"><tr><th>Campo</th><th>De</th><th>Para</th></tr>';
                    //dd($log_data);
                    foreach($log_data as $f=>$a){
                        $r.='<tr>'.
                                '<td>'.
                                    ($a['label']?$a['label']:$f).
                                '</td>'.
                                '<td>'.
                                   (isset($a['original']) ? self::formatLogData_vx1($a['original']) : '-').
                                '</td>'.
                                '<td>'.
                                   (isset($a['modified']) ? self::formatLogData_vx1($a['modified']) : '-').
                                '</td>'.
                            '</tr>';
                    }
                    $r.='</table>';
                }else{
                    $r=$log_data;
                }
                
            }else{
                $r=$log_data;
            }
        }else{
            $r=$log_data;
        }
        
        return $r;
    }
    //Função complementar de formatLogData()
    private static function formatLogData_vx1($arr){//esperad $arr[0(value), 1(text)
        $val=$arr[0];
        $text=$arr[1];
        if($val==$text){
            return str_replace(chr(10),'<br>',$val);
        }else{
            if(strpos($arr[1],'|')!==false && strpos($arr[0],'|')!==false){//provavelmente este campo veio de um checkboxs e os valores estao dividos em por barra '|'
                $val=explode('|',$arr[0]);
                $text=explode('|',$arr[1]);
                $r='';
                foreach($val as $i=>$v){
                    $r.=$text[$i]. ($val[$i]?' ('.$val[$i].')':'') .' <br>';
                }
                $r=str_replace(chr(10),'<br>',$r);
                return $r;
            }else{
                $r=$arr[1] . ($arr[0]?' ('.$arr[0].')':'');
                $r=str_replace(chr(10),'<br>',$r);
                return $r;
            }
        }
    }
    
    
    //Retorna ao último id pois na função add() retorna apenas boolean
    public static function getLastId(){
        return DB::table('user_logs')->orderBy('id','desc')->value('id');
    }
    
    
    //Retorna a um html de diferença entre dois textos
    public static function textDiff($text1,$text2){
        return \App\Utilities\TextDiffUtility::toHTML($text1,$text2);
    }
    
    //Retorna ao css da função diffText()
    public static function TextDiffCss($tag_style=false){
        return \App\Utilities\TextDiffUtility::css($tag_style);
    }
    
}
