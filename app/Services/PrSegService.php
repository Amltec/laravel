<?php

namespace App\Services;

use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\ProcessRobot\VarsProcessRobot;


/**
 * Classe para inserção de conteúdos gerais dentro das tabelas de dados de seguros associados ao processo do robô.
 * Utilizado para o process_name='cad_apolice'
 */
class PrSegService{
    private $models=[];
    private $processClass=null;

    /**
     * Seta a instância da classe do processo responsável por este controller, ex \App\Http\Controllers\Process\ProcessCadApoliceController::CLASS
     * Utilizado para captura de métodos como o getStatusCode()
     */
    public function setProcessClass($cls){
        $this->processClass=$cls;
    }
    protected function getProcessClass(){
        if(!$this->processClass)$this->processClass = '\\App\\Http\\Controllers\\Process\\ProcessCadApoliceController';
        return $this->processClass;
    }

    /**
     * Insere / atualiza dados nas tabelas de dados do seguros de forma automática a partir do formato de dados extraídos do pdf das classes das seguradoras. Ex: [parcela_1, parcela_2....] //usa uma matriz única com '_{n}' no final
     * @param $prod_name - valores: automovel, residencial, agricola...
     * @param $process_id - o mesmo da tabela process_robot.id
     * @param array $data - dados extraidos no padrão de cada classes de seguradora (para o cadatro de apólice)
     * @param array opt - valores:
     *                      update_all      - Se true, indica que todos os campos serão salvos, substituindo as alterações do usuário (esta opção irá apagar todo os registros de edição do usuário)!.
     *                                        Se false (default), indica que somente os campos que o usuário não alterou é que serão salvos.
     * @return array [sucess,msg]
     */
    public function saveFromExtract($prod_name,$process_id,$data,$opt=[]){
        $opt = array_merge([
            'update_all'=>false,
        ],$opt);

        //$prod_data = FormatUtility::filterNamesArrayList($data, $this->getTableModel($prod_name)->getFillable() );

        if($opt['update_all']==false){//somente os campos não alterados pelo usuário poderão serem alterados
            //captura quais campos foram alterados
                $ctrlDados = $this->getDataCtrlStatus('dados',$process_id,'user',true);
                $ctrlParcelas = $this->getDataCtrlStatus('parcelas',$process_id,'user',true);
                $ctrlProd = $this->getDataCtrlStatus($prod_name,$process_id,'user',true);

            //captura as classes dos seguros
                $segDadosClass = self::getSegClass('dados');
                $segParcelasClass = self::getSegClass('parcelas');
                $segProdClass = self::getSegClass($prod_name);

            //captura os dados alterados no db para setar nos campos em $data
                $dbDados = $this->getTableModel('dados')->where('process_id',$process_id)->first();
                //dump($dbDados);
                if($dbDados){
                    $dbDados = FormatUtility::formatData( $dbDados->toArray() , $segDadosClass::fields_format('form'), 'view');
                    if($ctrlDados){
                        foreach(array_keys($ctrlDados) as $k){
                            //if(isset($dbDados[$k]))dump([$k => $dbDados[$k]]);
                            if(isset($dbDados[$k]))$data[$k] = $dbDados[$k];
                        }
                    }
                }
                //dd('xxxxxxxxxxxxx');
                $dbParcelas = $this->getTableModel('parcelas')->where('process_id',$process_id)->get();
                if($ctrlParcelas && $dbParcelas->count()>0){
                    foreach($ctrlParcelas as $i=>$arr){
                        $n = $dbParcelas->get($i)->toArray();
                        $n = FormatUtility::formatData( $n , $segParcelasClass::fields_format('form'), 'view');
                        foreach(array_keys($arr) as $k){//obs: os nomes dos campos em $data estão assim 'field_{N}'
                            if($n && isset($n[$k]))$data[$k.'_'.($i+1)] = $n[$k];
                        }
                    }
                }

                $dbProd = $this->getTableModel($prod_name)->where('process_id',$process_id)->get();
                if($ctrlProd && $dbProd->count()>0){
                    foreach($ctrlProd as $i=>$arr){
                        $n = $dbProd->get($i)->toArray();
                        //dump($n);
                        $n = FormatUtility::formatData( $n , $segProdClass::fields_format('form'), 'view');
                        //dd($n);
                        foreach(array_keys($arr) as $k){//obs: os nomes dos campos em $data estão assim 'field_{N}', e em $arr (campos salvos pelo usuário) estão assim 'field'
                            $f=$k.'_'.($i+1);//altera o nome do campo para o padrão de matriz de lista de produtos (ex 'veiculo_fab_1')
                            if($n && isset($n[$k])){
                                $data[$f] = $n[$k];
                            }else{
                                //nenhuma ação, pois este campo foi atualizado pelo usuário
                            }
                        }
                    }
                }
                //dump('********************************');
        }

        $r=self::saveAutoDataToDB(
            $prod_name,
            $process_id,
            $data,
            FormatUtility::filterNamesArrayList($data, $this->getTableModel('parcelas')->getFillable() ),
            FormatUtility::filterNamesArrayList($data, $this->getTableModel($prod_name)->getFillable() )
        );

        $r['change_dados'] = $ctrlDados?true:false;
        $r['change_parcelas'] = $ctrlParcelas?true:false;
        $r['change_'.$prod_name] = $ctrlProd?true:false;

        return $r;
    }


     /**
     * Insere / atualiza dados nas tabelas de dados do seguros de forma automática com dados do request form do template autofields.
     * @param $prod_name - valores: automovel, residencial, agricola...
     * @param $process_id - o mesmo da tabela process_robot.id
     * @param array $data - campos da tabela process_robot_seg_(dados|parcelas|automovel...) - precisa estar no formato de matriz única com os campos AutoFields. Ex: ['prefix{n}|_autofield_count'=>{count}, 'prefix{n}|field1'=>..., 'prefix{n}|field2'=>...]
     * @obs: usar esta função para dados que vierem diretamente do formulário para inserção no db
     * @return array [sucess,msg]
     */
    public function saveFromForm($prod_name,$process_id,$data){
        return self::saveAutoDataToDB(
            $prod_name,
            $process_id,
            $data,
            FormatUtility::filterPrefixArrayList($data,'parcelas'),
            FormatUtility::filterPrefixArrayList($data,$prod_name)
        );
    }


    public function saveAutoDataToDB($prod_name,$process_id,$data_dados,$data_parcelas,$data_prod){
        if(!in_array($prod_name,VarsProcessRobot::$tablesSegs))return ['success'=>false,'msg'=>'Parâmetro prod_name inválido'];

        //dados
        $this->setTableDados($process_id,$data_dados);

        //parcelas
        $this->delTable('parcelas',$process_id);//remove todos primeiro
        if($data_parcelas){
            $n=1;
            foreach($data_parcelas as $arr){
                $this->setTableParcelas($process_id,$arr,$n);
                $n++;
            }
        }

        //para cada tipo de seguro
        $this->delTable($prod_name,$process_id);//remove todos primeiro
        if($data_prod){
            $n=1;
            foreach($data_prod as $arr){
                if(is_array($arr)){
                    $this->setTableSeguro($prod_name,$process_id,$arr,$n);
                    $n++;
                }
            }
        }
        return ['success'=>true,'msg'=>'Dados atualizados com sucesso','dados'=>$data_dados,'parcelas'=>$data_parcelas,$prod_name=>$data_prod];
    }


    /**
     * Remove o registro nas tabelas de seguros de forma automática.
     * @param string|array $prod_name - valores: automovel, residencial, agricola...
     * @param $process_id - o mesmo da tabela process_robot.id
     * @param booelan $isTableStatus - se true, indica que irá processar sobre a model das tabelas de status, ex: 'pr_seg_dados__s'
     * @return array [sucess,msg]
     */
    public function delAutoTable($prod_name,$process_id,$isTableStatus=false){
        $this->delTable('dados',$process_id,$isTableStatus);
        $this->delTable('parcelas',$process_id,$isTableStatus);
        if(!is_array($prod_name))$prod_name=[$prod_name];
        foreach($prod_name as $pn){
            if(in_array($pn,VarsProcessRobot::$tablesSegs))$this->delTable($pn,$process_id,$isTableStatus);
        }
        return ['success'=>true,'msg'=>'Dados removidos com sucesso'];
    }


    /**
     * Remove o registro de uma das tabelas do seguro
     */
    public function delTable($table,$process_id,$isTableStatus=false){
        $this->getTableModel($table,$isTableStatus)->where(['process_id'=>$process_id])->delete();
    }


    /**
     * Insere / atualiza dados na tabela de dados do seguro.
     * @param $prod_name - valores: automovel, residencial, agricola...
     * @param $data - matriz de valores para um único registro da tabela pr_seg_dados
     * @param $opt - o mesmo de $this->formatDataDB()
     * Sem retorno
     */
    public function setTableDados($process_id,$data,$opt=[]){
        //prepara os dados para inserir no db
        $data = $this->formatDataDB('dados',$data,$opt);
        $model = $this->getTableModel('dados');
        $reg = $model->where(['process_id'=>$process_id])->first();
        if($reg){//edit
            $reg->update($data);
        }else{//create
            $data['process_id']=$process_id;
            $model->create($data);
        }
    }

    /**
     * Insere / atualiza dados na tabela de parcelas do seguro.
     * @params... consulte na classe
     * @param $process_id - o mesmo da tabela process_robot.id
     * @param $data - matriz de valores para um único registro da tabela pr_seg_dados. Campos fpgto_datavenc, fpgto_valorparc
     * @param $num - número da parcela
     * Sem retorno
     */
    public function setTableParcelas($process_id,$data,$num){
        //obs: a tabela de parcelas está no mesmo padrão de setTableSeguro(), e por isto é apenas chamado a classe
        $this->setTableSeguro('parcelas',$process_id,$data,$num);
    }


    /**
     * Insere / atualiza dados na tabela de seguro de automóvel, residencial, agrícola, etc
     * @param $prod_name - valores: automovel, residencial, agricola...
     * @param $process_id - o mesmo da tabela process_robot.id     * @params... consulte na classe
     * @param $data - matriz de valores para um único registro da tabela pr_seg_dados. Campos fpgto_datavenc, fpgto_valorparc
     * @param $num - número do item (inicia em zero)
     * @param $opt - o mesmo de $this->formatDataDB()
     * Sem retorno
     */
    public function setTableSeguro($prod_name,$process_id,$data,$num,$opt=[]){
        //prepara os dados para inserir no db
        $model = $this->getTableModel($prod_name);
        $data = $this->formatDataDB($prod_name,$data,$opt);
        $reg = $model->where(['process_id'=>$process_id,'num'=>$num])->first();
        if($reg){//atualiza
            $reg->update($data);
        }else{
            $data['process_id']=$process_id;
            $data['num']=$num;
            $model->create($data);
        }
    }



    /**
     * Nomes de tabelas permitidos
     * @param booelan $isTableStatus - se true,m indica que irá capturar o model das tabelas de status, ex: 'pr_seg_dados__s'
     * @return model or null
     */
    public function getTableModel($table,$isTableStatus=false){
        $x=$isTableStatus?'__s':'';
        $arr=array_merge(['dados','parcelas'] , VarsProcessRobot::$tablesSegs);
        if(!in_array($table,$arr))return null;
        if(!isset($this->models[$table.$x])){
            $className = '\App\Models\PrSeg\PrSeg'.studly_case($table).$x;
            $this->models[$table.$x] = new $className();
        }
        return $this->models[$table.$x];
    }

    /**
     * Retorna aos dados de uma tabela: dados, parcelas, {prod}.
     * Return success - if $is_array:array - else: model record,    error - null
     */
    public function getTableData($process_id,$table='dados',$is_array=false){
        $reg = $this->getTableModel($table)->where('process_id',$process_id)->first();
        if($reg){
            return $is_array ? $reg->toArray() :$reg;
        }else{
            return null;
        }
    }


    /**
     * Captura as classes de campos do seguro (em \App\ProcessRobot\cad_apolice\Classes\Segs\Seg...)
     * @param $table - nome da tabela base 'pr_seg_...', ex: dados, parcelas, automovel, ...
     */
    public static function getSegClass($table) {
        $c='\App\ProcessRobot\cad_apolice\Classes\Segs\Seg'.studly_case($table);
        return new $c;
    }


    /**
     * Ajusta os valores para ficar compatíveis para serem inseridos no DB
     * @param $table - nome da tabela base 'pr_seg_...', ex: dados, parcelas, automovel, ...
     * @param $data - com os campos a serem alterados. Sintaxe: [name=>value]
     * @param array $opt - valores opcionais:
     *              model - model da tabela process_robot para
     * @return $data - com o valores alterados
     */
    private function formatDataDB($table,$data,$opt=[]){
        $opt = array_merge([
            'model'=>null,
        ],$opt);

        $class = '\App\ProcessRobot\cad_apolice\Classes\Segs\Seg'.studly_case($table);

        //ajustes dos campos antes de class::fields_rules
        $data = $class::fields_format_db_before($data,$opt);

        $data = FormatUtility::formatData($data,$class::fields_rules(),'db');

        //ajustes dos campos depois de class:fields_rules
        $data = $class::fields_format_db_after($data,$opt);
        return $data;
    }

    /**
     * Retorna ao texto do pdf da model ProcessRobot
     * @param type $model
     * @param string $param_fields_format - parâmetro para a função: $this->getSegClass($tb)::fields_format()
     * @param string $param_format_data - FormatUtility::formatData()
     * @param boolean $merge_apolice_check - mesclagem com os dados da apólice capturados do site da seguradora. Valores: true - os dados serão mesclados, false - serão apenas os dados extraídos do pdf
     * @return array $data_pdf
     */
    public function getDataPdf($model,$param_fields_format='view',$param_format_data='view',$merge_apolice_check=true){
        $dataPdf = $model->getText('data');
        if(!$dataPdf)return null;

        $prod_name = $model->process_prod;
        $dataPdf = FormatUtility::formatData( $dataPdf, $this->getSegClass('dados')::fields_format($param_fields_format), $param_format_data);

        foreach(['parcelas',$prod_name] as $tb){
            //filtra os campos no formato de matriz única para serem formatados abaixo
            $d = FormatUtility::filterNamesArrayList($dataPdf, $this->getTableModel($tb)->getFillable());
            $d = FormatUtility::formatDataArr($d , $this->getSegClass($tb)::fields_format($param_fields_format), $param_format_data);
            //if($tb=='automovel')dd('passou',$d);
            foreach($d as $i => $arr){
                if(is_array($arr)){//caso não seja array, está com erro na matriz (é provável que a var $dataPdf esteja com os dados do automóvel no padrão antigo (sem o '_1')... provável visualização de casos antigos)
                    foreach($arr as $f=>$v){
                        $dataPdf[$f.'_'.$i]=$v;
                    }
                }
            }
        }

        if($merge_apolice_check){//existem dados de verificação da apólice pelo site da seguradora
            if(!isset($this->classProdApoliceCheck))$this->classProdApoliceCheck = new \App\Http\Controllers\Process\SeguradoraData\ProdApoliceCheck;
            $dataPdf = $this->classProdApoliceCheck->mergeDataPdfApoliceCheck($dataPdf,$this->classProdApoliceCheck->getDataApoliceCheck($model),$prod_name);
        }

        return $dataPdf;
    }




    //********************** funções das tabelas de verificação 'pr_seg_{table}__s' **********************

    /**
     * Retorna a relação de controle de campos alterados e seus respectivos responsáveis (robo ou user) que estão gravados no db
     * @param $table - nome da tabela base 'pr_seg_...__s', ex: dados, parcelas, automovel, ... //obs: somente a tabela 'dados' retorna a um único registro, as demais retorna a uma coleção de registros
     * @param $process_id - o mesmo da tabela process_robot.id
     * @param $ctrl - quem é responsável pelo registro de alterações:
     *                 user - usuário do sistema que alterou dados da apólice
     *                 robo - dados retornado de alterações feitas pelo robô autoit
     * @param $only_changed - se true irá retornar somente os campos alterados. Defautl false.
     * @return null|array - sintaxe: [field1=>changed, field2=>changed, ...] - valores 'changed':
     *                  ''          - não modificado
     *                  user|robo   - modificado pelo usuário ou robô
     *                  manual_ok   - finalizado manualmente pelo usuário (existe apenas para $ctrl='robo' e indica que não foi o robô quem finalizou este caso)
     */
    public function getDataCtrlStatus($table,$process_id,$ctrl,$only_changed=false){
        if($ctrl=='user'){
            $change = ['0'=>'','1'=>'user'];
        }else{//robo
            $change = ['0'=>'','1'=>'robo','2'=>'manual_ok'];
        }
        $ctrl = ['user'=>'0','robo'=>'1'][$ctrl]??null;
        if(is_null($ctrl))return null;

        $model = $this->getTableModel($table,true)->where(['ctrl'=>$ctrl,'process_id'=>$process_id])->get();
        //if($table=='parcelas')dd($model);
        if($model->count()>0){
            $r=[];
            foreach($model as $i=>$reg){
                $r[$i]=[];
                foreach($reg->getAttributes() as $f=>$v){
                    if(in_array($f,['process_id','ctrl','num']))continue;
                    $v = $change[(string)$v]??null;
                    if($only_changed){
                        if($v)$r[$i][$f]=$v;
                    }else{
                        $r[$i][$f]=$v;
                    }
                }
            }
            if($table=='dados')$r=$r[0];//existe apenas um registro retornado

            if($only_changed){//remove os vazios
                foreach($r as $i => $v){
                    if($v!='' && empty($v))unset($r[$i]);
                }
            }

            return $r;
        }else{
            return null;
        }
    }


    /**
     * Função para armazenar o controle das alterações da dados
     * @param $table - nome da tabela base 'pr_seg_...__s', ex: dados, parcelas, automovel, ...
     * @param $process_id - o mesmo da tabela process_robot.id
     * @param $num - número do item (setar null se não existir na tabela)
     * @param $ctrl - quem é responsável pelo registro de alterações:
     *                 user - usuário do sistema que alterou dados da apólice
     *                 robo - dados retornado de alterações feitas pelo robô autoit
     * @param $change - tipo da alteração realizada, valores:
     *                 no ou ''    - não modificado
     *                 user|robo   - modificado pelo usuário ou robô
     *                 manual_ok   - finalizado manualmente pelo usuário (existe apenas para $ctrl='robo' e indica que não foi o robô quem finalizou este caso)
     * @param $fields - relação de campos a serem marcados. Ex: [field1,field2...] ou 'field1,field2'
     * @return void
     */
    public function setTableCtrlStatus($table,$process_id,$num,$ctrl,$change,$fields){
        $ctrl = ['user'=>'0','robo'=>'1'][$ctrl]??null;
        $change = [''=>'0','no'=>'0','user'=>'1','robo'=>'1','manual_ok'=>'2'][$change]??null;
        if(is_null($ctrl) || is_null($change))return null;

        if(is_string($fields))$fields=explode(',',$fields);
        $data = array_fill_keys($fields,$change);//converte os campos array para array associative contendo o valor da alteração $change

        $model = $this->getTableModel($table,true);
        $reg = $model->where(['ctrl'=>$ctrl,'process_id'=>$process_id]);
        if($table!='dados')$reg->where('num',$num);//tabela de dados não tem o campo num
        $reg=$reg->first();
        //if($table=='automovel')dd($reg,$data);
        if($reg){
            $reg->update($data);
        }else{
            $data['ctrl']=$ctrl;
            $data['process_id']=$process_id;
            $data['num']=$num;
            $model->create($data);
        }
    }



    /**
     * O mesmo da função setTableCtrlStatus(), mas executa automaticamente para as seguintes tabelas: dados, parcelas, {prod...}
     * @param array $data_dados, $data_parcelas, $data_prod - matriz de nomes de campos que foram alterados, ex: [field1, field2...]
     */
    public function setAutoCtrlStatus($prod_name, $process_id, $ctrl, $change, $data_dados, $data_parcelas, $data_prod){
        if($data_dados){
            $this->setTableCtrlStatus('dados', $process_id, null, $ctrl, $change, $data_dados);
        }
        if($data_parcelas){
            $n=1;
            $this->delTable('parcelas',$process_id,true);//remove todos primeiro
            foreach($data_parcelas as $arr){
                $this->setTableCtrlStatus('parcelas', $process_id, $n, $ctrl, $change, $arr);
                $n++;
            }
        }
        if($data_prod){
            $n=1;
            $this->delTable($prod_name,$process_id,true);//remove todos primeiro
            foreach($data_prod as $arr){
                $this->setTableCtrlStatus($prod_name, $process_id, $n, $ctrl, $change, $arr);
                $n++;
            }
        }
        return ['success'=>true,'msg'=>'Dados atualizados com sucesso'];
    }


    /**
     * Retorna a relação de nomes de campos que foram alterados / estão divergentes em relação aos dados extraídos do pdf
     * @param $table - nome da tabela base 'pr_seg_...__s', ex: dados, parcelas, automovel, ... //obs: somente a tabela 'dados' retorna a um único registro, as demais retorna a uma coleção de registros
     * @param $processModel - model do processo do robô de cadastro de apólice
     * @param $data - dados para comparação (campos sintaxe [campo=>valor])
     * @param $ctrl - quem é responsável pelo registro de alterações:
     *                 user - usuário do sistema que alterou dados da apólice
     *                 robo - dados retornado de alterações feitas pelo robô autoit
     *                 caso não definido - apenas compara os dados do pdf com a var $data
     * @return array - lista dos nomes dos campos divergentes,
     *                  para $table='dados' - ex: [field1,field2,...]
     *                  para $table='parcelas|{prod}...' - ex: [1=>[field1,field2,...], 2=>[field1,field2,...], ...]
     */
    public function getCtrlFieldsChanged($table,$processModel,$data,$ctrl=null){
        $fields_valid = $this->getTableModel($table)->getFillable();//captura somente os campos válidos
        $segClass = self::getSegClass($table);
        $r=[];

        //ajusta os valores de visualização para ficar compatível os valores para a comparação
            /*######## remover este código #########
            $dataPdf = $processModel->getText('data');
            $dataPdf = FormatUtility::formatData( $dataPdf, $segClass::fields_format(), 'view');
            */
        $dataPdf = $this->getDataPdf($processModel,'view','view');

        if($ctrl)$ctrl = $this->getDataCtrlStatus($table,$processModel->id,$ctrl,true);

        if($table=='dados'){
            $r = $this->getCtrlFieldsChanged_x1($dataPdf,$data,$fields_valid,$segClass,$ctrl);
        }else{//parcelas, {prod_name}
            foreach($data as $i=>$d){//obs: $i inicia em 1 (e não em 0)
                $r[$i] = $this->getCtrlFieldsChanged_x1($dataPdf,$d,$fields_valid,$segClass,($ctrl[$i-1]??null),$i,$table);
            }
            //if($table=='automovel')dd('x1',$data,$dataPdf,$ctrl,$r);
        }

        return $r;
    }
    //complemento de getCtrlFieldsChanged()
    private function getCtrlFieldsChanged_x1($dataPdf,$data,$fields_valid,$segClass,$ctrl,$i_field=null,$table=null){
        $r=[];
            //remover linha se estiver tudo ok....
            //if($table=='automovel')dump('Y1',$dataPdf,$data);
            $data = FormatUtility::formatData( $data, $segClass::fields_format(), 'view');
            //if($table=='automovel')dd('Y2',$data);
            //dd('z1',$dataPdf['veiculo_zero_1'],$data['veiculo_zero'], $segClass::fields_format());

        //monta a lista dos campos diferentes
        foreach($data as $k=>$v){
            if(!in_array($k,$fields_valid))continue;
            $f=$k.($i_field?'_'.$i_field:'');
            $vFrom=($dataPdf[$f] ?? $dataPdf[$k] ?? ''); //obs: o comando ($dataPdf[$f] ?? $dataPdf[$k] ?? '') está compatível para identificar os nomes de campos ex: 'veiculo_ano_{N}' ou 'veiculo_ano', pois em alguns casos está escrito deste modo o nome do campo em $data
            $vTo=$v;
            $type=$segClass::fields_rules_view()[$k]??'';
            //if($k=='veiculo_fab_code')dd($k,$vFrom,$vTo, $type,ValidateUtility::equalsDataField($vFrom,$vTo,$type));
            if(!ValidateUtility::equalsDataField($vFrom,$vTo,$type))$r[]=$k;
        }
        //verifica quais alterações já foram realizadas (pois se foi alterado novamente para um mesmo valor do pdf, precisa constar que foi alterado da mesma forma)
        if($ctrl){
            foreach($ctrl as $k=>$v){
                if(!in_array($k,$r))$r[]=$k;
            }
        }
        //if($table=='automovel')dd('Y3',$dataPdf,$data);
        return $r;
    }


    /**
     * Retorna a todos os dados das tabelas: pr_seg_dados, pr_seg_parcelas, pr_seg_{prod}, unificados em só array
     * @param $prod - nome do produto, ex: 'automovel',...
     * @param $modo_array - modo como irá retornar aos dados das demais tabelas, ex:
     *                    true - tabelas parcelas e {prod}, etc irão retornar como array, ex: [parcelas=>[arr parcela1, ...], {prod}=>[...] ]
     *                    false - (default) tabelas parcelas e {prod}, etc irão retornar em matríz única, ex: ['datavenc_1','datavenc_2',... 'veiculo_1','veiculo_2',... ]  //retorna a sintaxe: 'fieldname_{N}'
     * @return array
     */
    public function getAllData($processModel,$modo_array=false){
        $id = $processModel->id;
        $prod = $processModel->process_prod;
        $data = [];

        //captura as classes
        $segDadosClass = $this::getSegClass('dados');
        $segParcelasClass = $this::getSegClass('parcelas');
        $segProdClass = $this::getSegClass($prod);

        //*** obs: captura os dados do db, que já contém os dados alterados estando em sua versão final para ser exibido / utilizado pelo robô ***

        //dados
        $model = $this->getTableModel('dados')->where('process_id',$id)->first();
        if(!$model)return null;
        $arrDados = $model->toArray();
        unset($arrDados['process_id']);
        $data = FormatUtility::formatData( $arrDados, $segDadosClass::fields_format('form'), 'view');
        $data = $segDadosClass::fields_show($data);
        //dd($data);
        if($modo_array)$data=['dados'=>$data];

        //parcelas
        $this->getAllData_x1('parcelas',$segParcelasClass,$id,$data,$modo_array);

        //produtos
        $this->getAllData_x1($prod,$segProdClass,$id,$data,$modo_array);
        //dd($data);

        return $data;
    }


    //função complementar de getAllData() //$name = parcelas, $prod...
    //sem retorno
    private function getAllData_x1($name,$seg_class,$id,&$data,$modo_array){
        $tmp=[];
        $arr_data = $this->getTableModel($name)->where('process_id',$id)->orderBy('num','asc')->get();
        if($modo_array){
            foreach($arr_data as $i => $arr){
                $arr = FormatUtility::formatData( $arr->toArray(), $seg_class::fields_format('form'), 'view');
                $arr = $seg_class::fields_show($arr);
                foreach($arr as $f => $v){
                    if($f=='num' || $f=='process_id')continue;
                    if(!isset($tmp[$arr['num']]))$tmp[$arr['num']]=[];
                    $tmp[$arr['num']][$f]=$v;
                }
            }
            $data[$name]=$tmp;
        }else{
            $data[$name.'__count']=count($arr_data);
            foreach($arr_data as $i => $arr){
                $arr = FormatUtility::formatData( $arr->toArray(), $seg_class::fields_format('form'), 'view');
                $arr = $seg_class::fields_show($arr);
                //if($name=='automovel')dd($arr_data,$arr);
                foreach($arr as $f => $v){
                    if($f=='num' || $f=='process_id')continue;
                    $tmp[$f.'_'.($i+1)] = $v;
                }
            }
            $data+=$tmp;
        }
    }


    /**
     * Junta as matrizes de $arrDados|Parcelas|Prod em uma só, ex: ['apolice_num=>...,'datavenc_1'=>...,'datavenc_2'=>...]  //retorna a sintaxe: 'fieldname_{N}'
     * @param $arrDados - array dados
     * @param $arrParcelas,$arrProd - sintaxe esperada [1=>$arrParcela, 2=>$arrParcela... ]
     * @param $prod_name - valores: automovel, residencial...
     * @return array - matriz única
     */
    public function joinData($arrDados,$arrParcelas,$arrProd,$prod_name,$ret=false){
        //dados
        $data = $arrDados;
        //parcelas e prod
        foreach(['parcelas'=>$arrParcelas, $prod_name=>$arrProd] as $name => $arrVar){
            $data[$name.'__count']=count($arrVar);
            foreach($arrVar as $i=>$arr){
                foreach($arr as $f=>$v){
                    $data[$f.'_'.$i]=$v;
                }
            }
        }
        return $data;
    }

    /**
     * Divide a matriz única em dados, parcelas, {prod}
     * @param array $data - matriz única de dados
     * @paam $prod_name - valores: automovel, residencial...
     * @return array - sintaxe [dados, parcelas=>[1=>parcela], {prod}=>[1=>...] ]
     */
    public function splitData($data,$prod_name){
        //captura as classes
        $segDadosClass = $this::getSegClass('dados');
        $segTmpClass = [
            'parcelas'=> $this::getSegClass('parcelas'),
            $prod_name => $this::getSegClass($prod_name)
        ];

        //dados
        $arr_dados=[];
        foreach($segDadosClass::fields_labels() as $f=>$v){
            if(isset($data[$f]))$arr_dados[$f]=$data[$f];
        }

        //parcelas e prod
        $tmp=[];
        foreach(['parcelas',$prod_name] as $name){
            $arr_tmp=[];
            for($i=1;$i<=999;$i++){
                $r=[];
                foreach($segTmpClass[$name]::fields_labels() as $f=>$v){
                    if(isset($data[$f.'_'.$i]))$r[$f]=$data[$f.'_'.$i];
                }
                if($r){
                    $arr_tmp[$i]=$r;
                }else{//não tem mais registros
                    break;
                }
            }
            $tmp[$name]=$arr_tmp;
        }

        return ['dados'=>$arr_dados,'parcelas'=>$tmp['parcelas'],$prod_name=>$tmp[$prod_name]];
    }


    /**
     * Executa todas as validações das classes Segs\Seg{table}.php
     * @param $arr_parcelas|prod - sintaxe: [1=> [field1=>..., field2=>..], 2=>[...] ...]
     * @param $prod_name - valores: automovel, residencial, agricola...
     * @param array $opt - (valores na função)
     * @return true || [field1=>msg1,... ]   ... também pode retornar a 'code' (opcional), ex: [field=>msg, code=>...]
     */
    public function validateAll(&$arr_dados,&$arr_parcelas,&$arr_prod,$prod_name,$opt=[]){
        $opt=array_merge([
            //as mesmas opções de \App\ProcessRobot\cad_apolice\Classes\Segs\SegDados.php
            'extract_test'=>false,
            'processModel'=>null,       //necessário para class SegDados::validateAll() e verificação da seguradora e corretor
            'source'=>null,

            //Campos obrigatórios para personalização adicional do respectivo validate já executado para tabela PrSeg...
            'validate_required'=>null,//sintaxe field=>boolean
            'validate_bro_ins'=>true,//indica se deve verificar o cadastro de corretor e seguradora (precisa estar definido 'processModel' acima)

            //demais opções
            'check_pgto'=>true,     //indica se deve verificar a compatibilidade do pgto
            'allow_change'=>false,  //indica se deve permitir que as funções validate abaixo corrigjam/atualizar as respectivas vars arr_dados|parcelas|prod
        ],$opt);

        $return=['validate'=>[]];

        $classDados = $this->getSegClass('dados');
        $classParcelas = $this->getSegClass('parcelas');
        $classProd = $this->getSegClass($prod_name);

        if($opt['allow_change']){
            $data_split=['dados'=>&$arr_dados,'parcelas'=>&$arr_parcelas,$prod_name=>&$arr_prod];
        }else{
            $data_split=['dados'=>$arr_dados,'parcelas'=>$arr_parcelas,$prod_name=>$arr_prod];
        }

        //*** observações: abaixo as vars $arr_... são de referência (&) para que possam ser modificadas pela função validateAll() de cada classe PrSeg... ***
        $data_param = ['dados'=>$arr_dados,'parcelas'=>$arr_parcelas,$prod_name=>$arr_prod, 'processModel'=>$opt['processModel'] ];

        //dados
            $arr_dados = FormatUtility::formatData($arr_dados,$classDados::fields_rules_extract($data_param),'view');
            //validação dos campos
            $validate=ValidateUtility::validateData($arr_dados,$classDados::fields_rules_extract($data_param),$opt['validate_required'],['required_all'=>false]);//ajusta os valores antes de validar
            if($validate!==true)$return = ['success'=>false,'msg'=>'Campos inválidos','validate'=>array_merge($return['validate'],$validate),'code'=>'read01'];
            //validação extra
            $validate=$classDados::validateAll($arr_dados,$data_split,[ //retorna somente aos campos não validados
                'extract_test'=>$opt['extract_test'],
                'processModel'=>$opt['processModel'],
                'source'=>$opt['source']
            ]);
            if($validate!==true)$return = ['success'=>false,'msg'=>($validate['msg']??'Campos inválidos'),'validate'=>array_merge($return['validate'],$validate),'code'=>'read01' ];

        //parcelas, {prod}
            foreach(['parcelas'=>$classParcelas,$prod_name=>$classProd] as $group => $class){
                foreach($data_split[$group] as $i => &$arr){
                    if(!$arr)continue;
                    $arr = FormatUtility::formatData($arr,$class::fields_rules_extract($data_param),'view');//ajusta os valores antes de validar

                    //validação dos campos
                    $validate=ValidateUtility::validateData($arr,$class::fields_rules_extract($data_param),$opt['validate_required'], ['sufix'=>$i,'required_all'=>false]);
                    //if($group=='automovel')dd($validate);

                    if($validate!==true)$return = ['success'=>false,'msg'=>'Campos inválidos','validate'=>array_merge($return['validate'],$validate),'code'=>($validate['code']??'read01') ];
                    //validação extra
                    $validate=$class::validateAll($arr,$data_split,[
                        'sufix'=>$i,
                        'validate_required'=>$opt['validate_required'],
                    ]);//retorna somente aos campos não validados
                    if($validate!==true)$return = ['success'=>false,'msg'=>($validate['msg']??'Campos inválidos'),'validate'=>array_merge($return['validate'],$validate),'code'=>'read01' ];
                }
            }
        //dd('xxxxxxxxxxxxxxxxxxxxxxxxxx',$return);
        //valida todos os campos de pgto
        if($opt['check_pgto']){
            //if(\Auth::user()->user_level=='dev')dd(12347,$data_split['parcelas'],'***',array_filter($arr_dados),array_filter($data_split['parcelas']));
            if(empty(array_filter($data_split['parcelas']))){
                $return = ['success'=>false,'msg'=>$this->getProcessClass()::getStatusCode('read11'), 'code'=>'read11', 'validate'=>[]];
            }else{
                $n = \App\ProcessRobot\cad_apolice\Classes\Data\PgtoData::validateAll($arr_dados,$data_split['parcelas']);
                if(!$n['success']){
                    $return = ['success'=>false,'msg'=>$n['msg'],'code'=>($n['code']??'read11'), 'validate'=>$return['validate']];
                    return $return;
                    //dd($n,$return);
                }
            }
        }

        //verifica o corretor e seguradora
        $m=$opt['processModel'];
        if($m && $opt['validate_bro_ins']){
            $err=[];
            if(!$m->broker)$err[]='bro01';
            if(!$m->insurer)$err[]='ins01';
            if($m->broker && $m->broker->broker_status!='a')$err[]='bro02';
            if($m->insurer && $m->insurer->insurer_status!='a')$err[]='ins02';
            foreach($err as $c){
                $return = ['success'=>false, 'msg'=>$this->getProcessClass()::getStatusCode($c), 'code'=>$c, 'validate'=>$return['validate']];
            }
        }

        //dd($return);
        if($return['validate'] || array_get($return,'success')===false){
            return $return;
        }else{
            return true;
        }
    }


    /**
     * Verifica se o registro está pronto para ser processado pelo robô.
     * Executa todas as validações a partir da model ProcessRobot
     * @param $model - da tabela process_robot
     * @param $upd_status - se true irá atualizar o process_status='p' caso esteja validado
     * @return booelan || array error
     */
    public function validateByModel($model,$upd_status=false){
        $pn = $model->process_prod;
        $arr = $this->getAllData($model,true);
        if(!$arr)return false;
        $validate = $this->validateAll($arr['dados'], $arr['parcelas'], $arr[$pn], $pn, ['processModel'=>$model]);

        if($validate && $upd_status && !in_array($model->process_status,['f','w'])){//o registro não pode estar finalizado ou pendênte de apólice (considerado finalizado)
            $model->update(['process_status'=>'p']);
            $model->setData('error_msg','');
        }

        return $validate;
    }

    /**
     * Verifica se o nome da tabela e campos são válidos / existem
     */
    public function checkNames($table=null,$field=null){
        //verifica o nome da tabela
        if($table){
            if(! (in_array($table,['dados','parcelas']) || in_array($table,VarsProcessRobot::$tablesSegs) ))return false;
        }

        //verifica o nome do campo
        if($field){
            if(strpos($field,'__')!==false)$field=explode('__',$field)[0];//caso tenha os comandos no nome do campo, ex: '__IN','__LIKE',... retira para verificar apenas o nome do campo
            $fields_valid = $this->getTableModel($table)->getFillable();
            return in_array($field,$fields_valid);
        }

        return false;
    }
    /*public function checkNames($table,$field){
        return self::checkNames($table,$field);
    }*/


    /**
     * Retorna ao código a partir do texto informado, das variáeveis \App\ProcessRobot\cad_apolice\Vars\....
     * @param $data - array de campos para serem analisados e alterados
     * @return $data - alterado
     * Obs: esta função irá pegar os respectivos campos de texto (que tem código), ex: 'fpgto_tipo' e irá ler os respectivos campos de texto (from, to) e atualizar os respectivos campos de código, ex: 'fpgto_tipo_code'
     *      Estas variáveis estão na função self::getSegClass($table)::getVarsCodeFromText()
     */
    public static function getVarCodeByText($table,$data){
        $arr_vars = self::getSegClass($table)::getVarsCodeFromText();
        if(!$data)return $data;
        if(!$arr_vars)return $data;
        $r=null;

        foreach($arr_vars as $field => $arr){
            $v0 = $data[$field][0]??null; //captura o campo from
            $v1 = $data[$field][1]??null; //captura o campo to

            $v0 = trim(strtoupper(FormatUtility::removeAcents($v0)));
            $v1 = trim(strtoupper(FormatUtility::removeAcents($v1)));

            foreach($arr as $code=>$text2){

                if(is_array($text2)){//ex: QuiverAutomovelVar::$fabricante_code pode retornar em $text um array de valores com o mesmo código
                    //neste caso verifica cada item
                    $n=[];
                    foreach($text2 as $t2){
                        $n[] = trim(strtoupper(FormatUtility::removeAcents($t2)));
                    }
                }else{
                    $n = [trim(strtoupper(FormatUtility::removeAcents($text2)))];
                }

                foreach($n as $a){
                    if($v0==$a){
                        $data[$field.'_code'][0] = $code;
                    }
                    if($v1==$a){
                        $data[$field.'_code'][1] = $code;
                    }
                }
            }
        }

        return $data;
    }
}
