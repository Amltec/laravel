<?php

namespace App\Http\Controllers\Process;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Filesystem\Filesystem;
use App\Utilities\ValidateUtility;
use App\Utilities\FormatUtility;
use Gate;
use Auth;
use Exception;
use Config;

use App\Http\Controllers\Process\ProcessController;
use App\ProcessRobot\VarsProcessRobot;

use App\Models\ProcessRobot_CadApolice as ProcessRobot;
use App\Models\Insurer;
use App\Models\Broker;
use App\Models\Robot;
use App\Models\PrCadApolice;
use App\Models\PrCadApoliceData;
use App\Models\PrSeguradoraFiles;
use App\Models\PrSeguradoraData;
use App\Models\ProcessRobotExecs;

use App\Services\FilesDirectService;
use App\Services\PrSegService;
use App\Services\AccountsService;
use App\ProcessRobot\cad_apolice\Classes\Vars\CadApoliceReadTextVar;
use App\Services\AccountPassService;
use App\Services\MetadataService;

use \App\Utilities\FilesUtility;
use stdClass;

/**
 * Classe responsável pelo processo de cadastro de apólices no Quiver
 */
class ProcessCadApoliceController extends ProcessController{

    protected static $basename='cad_apolice';


    //*** varáveis públicas ***
    //Menu lateral
    //menu admin
    public static $submenus_superadmin=[
        'cad_apolice'       => ['title'=>'Emissão no Quiver',       'link'=>['process_cad_apolice','list'] ],
        'cad_apolice-order' => ['title'=>'Emissão Prioridades',     'link'=>['process_cad_apolice','list-order'] ],
        'apolice_check'     => ['title'=>'Verificação de Apólices', 'link'=>['process_cad_apolice_pr','list','?pr_process=apolice_check'] ],
        //'review'        => ['title'=>'Revisão no Quiver',       'link'=>['process_cad_apolice_pr','list','?pr_process=review'] ],
        //'boleto_seg'    => ['title'=>'Baixa de Boletos Segs',   'link'=>['process_cad_apolice','list','?pr_process=boleto_seg'] ],
        //'boleto_quiver' => ['title'=>'Cad. Boleto no Quiver',   'link'=>['process_cad_apolice','list','?pr_process=boleto_quiver'] ],
    ];
    public static $submenus_admin=[
        'cad_apolice-all'   => ['title'=>'Todos',                 'link'=>['admin.app.get',['process_cad_apolice','list']] ],
        'cad_apolice-0'     => ['title'=>'Leitura',               'link'=>['admin.app.get',['process_cad_apolice','list?status=o,0']] ],
        'cad_apolice-p'     => ['title'=>'Pronto para Emitir',    'link'=>['admin.app.get',['process_cad_apolice','list?status=p']] ],
        'cad_apolice-a'     => ['title'=>'Processando',           'link'=>['admin.app.get',['process_cad_apolice','list?status=a']] ],
        'cad_apolice-f'     => ['title'=>'Concluído',             'link'=>['admin.app.get',['process_cad_apolice','list?status=f']] ],
        'cad_apolice-w'     => ['title'=>'Pendente de Apólice',   'link'=>['admin.app.get',['process_cad_apolice','list?status=w']] ],
        'cad_apolice-e'     => ['title'=>'Suporte Correção',      'link'=>['admin.app.get',['process_cad_apolice','list?status=e,1']] ],
        'cad_apolice-c'     => ['title'=>'Operador Manual',       'link'=>['admin.app.get',['process_cad_apolice','list?status=c']] ],
        'cad_apolice-order' => ['title'=>'Prioridades',           'link'=>['admin.app.get',['process_cad_apolice','list-order']] ],
    ];




    //*** varáveis públicas ***
    //Lista dos status disponíveis
    public static $status=[
        'o'=>'Extraindo Dados',
        '0'=>'Leitura do Documento',
        'p'=>'Pronto para Emitir',
        'a'=>'Em Processo',
        'f'=>'Concluído',
        'w'=>'Pendente de Apólice',
        'e'=>'Correção Suporte',
        'c'=>'Operador Manual',
        'i'=>'Ignorado',
        '1'=>'Análise Suporte',
    ];


    //label de status - longo
    public static $statusLong=[
        'o'=>'Extraindo informações da Apólice',
        '0'=>'Fazendo a leitura da apólice',
        'p'=>'Aguardando a emissão pelo robô',
        'a'=>'Em processamento pelo robô',
        'f'=>'Emissão concluída',
        'w'=>'Emissão concluída mas com apólice pendente',
        'e'=>'Pendente de correção pelo suporte do robô',
        'c'=>'Pendente de correção manual pelo operador',
        'i'=>'Está ignorado e não será rocessado',
        '1'=>'Em análise pelo suporte do robô',
    ];

    //status curto para o admin (os demais são para os superadmins)
    //st_ref (status de referência para as demais variáveis)
    public static $status_to_admin=[
        'o,0'   =>['label'=>'Leitura',              'st_ref'=>'0'],
        'p'     =>['label'=>'Pronto para Emitir',   'st_ref'=>'p'],
        'a'     =>['label'=>'Processando',          'st_ref'=>'a'],
        'f'     =>['label'=>'Concluído ',           'st_ref'=>'f'],
        'w'     =>['label'=>'Pendente de Apólice',  'st_ref'=>'w'],
        'e,1'   =>['label'=>'Suporte Correção',     'st_ref'=>'e'],
        'c'     =>['label'=>'Operador Manual',      'st_ref'=>'c'],
    ];


    //cores dos status
    public static $statusColor=[
        'o'=>['text'=>'text-light-blue','bg'=>'bg-light-blue-active'],
        '0'=>['text'=>'text-light-blue','bg'=>'bg-light-blue-active'],
        'p'=>['text'=>'text-light-blue','bg'=>'bg-light-blue-active'],
        'a'=>['text'=>'text-light-blue','bg'=>'bg-light-blue-active'],
        'f'=>['text'=>'text-green','bg'=>'bg-green-active'],
        'w'=>['text'=>'text-teal','bg'=>'bg-teal-active'],
        'e'=>['text'=>'text-yellow','bg'=>'bg-yellow'],
        'c'=>['text'=>'text-red','bg'=>'bg-red-active'],
        'i'=>['text'=>'text-red','bg'=>'bg-red-active'],
        '1'=>['text'=>'text-yellow','bg'=>'bg-yellow'],
    ];


    //Relação de erros - sintaxe: [code=>text || [short text, long text]]
    public static $statusCode = [
        'quiv01'   =>'O cliente já tem uma apólice com o número informado',
        'quiv02'   =>'Não foram geradas parcelas de repasse',
        'quiv03'   =>'Campos bloqueados',
        'quiv04'   =>'Já existe veículo na lista cadastrado com o chassi informado',
        'quiv05'   =>'Cadastro de cliente incompleto no Quiver',
        'quiv06'   =>'Não conseguiu clicar no botão gravar',
        'quiv07'   =>'Não conseguiu atualizar os campos',
        'quiv08'   =>'Erro ao salvar',
        'quiv09'   =>'Forma de pagamento no Quiver não encontrado',
        'quiv10'   =>'Erro ao atualizar IOF',
        'quiv11'   =>'Falha ao confirmar o valor dos campos',
        'quiv12'   =>'O nível FILIAL no novo grupo de produção não pode ser diferente',
        'quiv13'   =>'Operação inválida. Existem parcelas já baixadas',
        'quiv14'   =>'Para o tipo comissão com % variando por parcela a distribuição de comissões deve ser informada',
        'quiv15'   =>'Erro ao carregar a tela do bloco',
        'quiv16'   =>'Erro ao avançar para a próxima página para a conferência de parcelas',
        'quiv17'   =>'Erro na confirmação das parcelas',
        'quiv18'   =>'Quantidade de parcelas deveriam ser iguais pelo Quiver',
        'quiv19'   =>'Erro ao capturar valores adicionais da parcela',
        'quiv20'   =>'Falha na conferência do valor total',
        'quiv21'   =>'Erro ao converfir o IOF / valor total',
        'quiv22'   =>'Erro ao inserir valor total',
        'quiv23'   =>'Erro ao verificar dados do pagamento',
        'quiv24'   =>'O valor pago deve ser informado',
        'quiv25'   =>'IOF diferente de 7,38%',
        'quiv26'   =>'Falha ao atualizar as parcelas',
        'quiv27'   =>'Você ainda não foi associado a um Nível Hierárquico',
        'quiv28'   =>'Fabricante do veículo não informado',
        'quiv29'   =>'Existe outra apólice com o número informado',
        'quiv30'   =>'Distribuição de comissões informada - alterar tipo de comissão',
        'quiv31'   =>'Opção FABRICANTE NÂO ENCONTRADO ausente na lista opções',
        'quiv32'   =>'Fabricante da apólice não encontrado na lista de opções do Quiver',
        'quiv33'   =>'Forma de pagamento da apólice não encontrado na lista de opções',
        'quiv34'   =>'Uma ou mais formas de pagamento requerida na configuração do prêmio não encontrado na lista de opções',
        'quiv35'   =>'O número do chassi está incompleto',
        'quiv36'   =>'Iof divergente calculado pelo Quiver',
        'quiblk'   =>'Erro ao salvar blocos de dados',
        'quif01'   =>'Url do anexo vazio',
        'quif02'   =>'Arquivo inválido',
        'quif03'   =>'Erro ao baixar o arquivo',
        'quif04'   =>'Erro ao inserir o anexo',
        'quif05'   =>'Erro de permissão ao acessar o arquivo',
        'quif06'   =>'Arquivo não existe',
        'quif07'   =>'Parâmetros de Arquivo inválido',
        'quif08'   =>'Uma ou mais tipos de imagens requerida na configuração do anexo não encontrado na lista de opções',
        'quif09'   =>'Tipo de imagem da apólice não encontrado na lista de opções',
        'read00'   =>'Documento inválido / não identificado',
        'read01'   =>'Campos inválidos',
        'read02'   =>'Ramo inválido',
        'read03'   =>'Endosso - não processado',
        'read04'   =>'Frota - não processado',
        'read05'   =>'Divergência no valor do prêmio total em relação as parcelas',
        'read06'   =>'Arquivo com senha',
        'read07'   =>'Erro na leitura do arquivo',
        'read08'   =>'Emissão fora do limite da vigência',
        'read09'   =>'Emissão fora do prazo permitido',
        'read10'   =>'Data início e término da vigência incompatíveis',
        'read11'   =>'Erro na conferência dos valores do prêmio ou parcelas',
        'read12'   =>'Divergência nos valores: Prêmio Total+Líquido+Custo+Adicional+Serviços+Juros+Iof',
        'read13'   =>'Divergência no valor: Iof',
        'read14'   =>'Resumo da Apólice - não processado',
        'read15'   =>'Quantidade de itens não compatível com a apólice',
        'read16'   =>'Via do corretor - não processado',
        'read17'   =>'IOF com valor zero',
        'read18'   =>'Arquivo bloqueado para leitura',
        'read19'   =>'PDF Engine incompatível com a classe da seguradora',
        'read20'   =>'PDF com número de páginas acima do limite para leitura',
        'read21'   =>'Vencimento das parcelas fora de ordem',
        'read22'   =>'Vencimento das parcelas não podem ser iguais',
        'read23'   =>'Juros não está incluso nas parcelas',
        'read24'   =>'Mais de um item encontrado na leitura da apólice',
        'read25'   =>'Um ou mais campos não compatíveis na verificação extra dos dados',
        'robo01'   =>'Erro: nenhum bloco foi processado',
        'file01'   =>'Erro ao acessar arquivo pdf',
        'file02'   =>'Erro ao renomear arquivo',
        'file03'   =>'Arquivo duplicado',
        'extr01'   =>'Erro na extração do arquivo',
        'extr02'   =>'Texto extraído com duas apólices divergentes',
        'extr03'   =>'Padrão de número da apólice do Quiver inválido',
        'extr04'   =>'Erro na leitura do arquivo por problemas na geração do PDF', //Descrição2: Método de extração incompatível com o informado pela classe
        'extr05'   =>'Erro na extração do arquivo no modo de verificação extra',
        'quic01'   =>'Opção CHASSI ou C.P.F./C.N.P.J ausente na lista de pesquisa de apólice',
    ];

    public static function getStatusCode($s=null,$retAllIfNull=true){
        $a = self::$statusCode + VarsProcessRobot::$statusCode;
        return $s ? ($a[$s]??$s) : ($retAllIfNull?$a:'');
    }



    public function __construct(ProcessRobot $ProcessRobotModel,Insurer $InsurerModel, Broker $BrokerModel){
        $this->ProcessRobotModel = $ProcessRobotModel;
        $this->InsurerModel = $InsurerModel;
        $this->BrokerModel = $BrokerModel;
    }

    /**
     * Retorna a classe do serviço App\Services\PrCadApoliceService
     */
    private $servicePrCadApolice=null;
    public function servicePrCadApolice(){
        if(!$this->servicePrCadApolice)$this->servicePrCadApolice = new \App\Services\PrCadApoliceService;
        return $this->servicePrCadApolice;
    }


    /**
     * Retorna a classe controller ProcessSeguradoraDataController
     */
    private $ProcessSeguradoraData=null;
    private function getSeguradoraDataController(){
        if(!isset($this->ProcessSeguradoraData))$this->ProcessSeguradoraData =  \App::make('\\App\\Http\\Controllers\\Process\\ProcessSeguradoraDataController');
        return $this->ProcessSeguradoraData;
    }



    /**
     * Página de visualização dos dados de cada processo/upload
     */
    public function show(Robot $RobotModel,$id){
        $userLogged = Auth::user();
        $model = $this->ProcessRobotModel->find($id);
        if(!$model)exit('Erro ao localizar registro');
        if($model->process_name!==self::$basename)exit('Erro ao localizar registro (2)');
        $RobotModel= $model->robot_id ? $RobotModel->find($model->robot_id) : null;

        $prefix = Config::adminPrefix();
        Config::setItemMenu($prefix=='super-admin'?'cad_apolice-cad_apolice':'cad_apolice-all');

        $execsModel = (new ProcessRobotExecs)->where('process_id',$model->id)->orderBy('id','asc')->get();

        //se a data agendada for menor que a data do último processamento, não precisa exibir
        if(ValidateUtility::ifDate($model->process_next_at,'<=',date("Y-m-d H:i:s")))$model->process_next_at=null;

        $view = \Request::input('view');

        return view('admin.process_robot.'.self::$basename.'.show' .($view?'-'.$view:'') ,[
            'model'=>$model,
            'execsModel'=>$execsModel,
            'configProcessNames'=>VarsProcessRobot::$configProcessNames,
            'configProcessProd'=>VarsProcessRobot::$configProcessNames[$model->process_name]['products'][$model->process_prod],
            'fileInfo'=>$this->getFileInfoPDF($id),
            'robotModel'=>$RobotModel,
            'status_list'=>self::$status,
            'status_color'=>self::$statusColor,
            'user_logged_level'=>$userLogged->user_level,
            'user_logged_id'=>$userLogged->id,
            'thisClass'=>$this,
        ])->render();
    }

    /**
     * Página de visualização de todos os processos relacionados
     */
    public function get_showDataRel($id){
        $userLogged = Auth::user();
        if(!in_array($userLogged->user_level,['dev','superadmin']))exit('Acesso negado');
        $model = $this->ProcessRobotModel->find($id);
        if(!$model)exit('Registro não encontrado');
        return view('admin.process_robot.'.self::$basename.'.show_data_rel',[
            'model'=> $model,
            'userLogged'=>$userLogged,
            'configProcessNames'=>VarsProcessRobot::$configProcessNames,
            'configProcessProd'=>VarsProcessRobot::$configProcessNames[$model->process_name]['products'][$model->process_prod],
            'thisClass'=>$this,
        ]);
    }


    /**
     * Página com a lista de registros marcados como prioridade
     */
    public function get_listOrder(Request $req){
        Config::setItemMenu('cad_apolice-cad_apolice-order');
        $model = $this->ProcessRobotModel
            ->select('process_robot.*')->where('process_name',self::$basename)
            ->where('process_status','p')->whereNotNull('process_order')->orderByRaw('-process_order desc')
            ->paginate(_GETNumber('regs')??15);

        return view('admin.process_robot.'.self::$basename.'.list-order', [
            'model'=>$model,
            'configProcessNames'=>VarsProcessRobot::$configProcessNames,
            'types_apolices_list'=>VarsProcessRobot::$typesApolices,
            'thisClass'=>$this,
        ]);
    }
    /**
     * Retorna a quantos registros estão na fila de prioridades
     */
    private function countRegsOrder(){
        return $this->ProcessRobotModel
            ->select('process_robot.*')->where('process_name',self::$basename)
            ->where('process_status','p')->whereNotNull('process_order')->orderByRaw('-process_order desc')->count();
    }


    /**
     * Página de lista de processos/uploads
     * @param $request - parâmetros aceitos:
     *      account_id, cpf, id, ctrl_id, status, process_name, process_prod, data_type, dt, dts, dte, broker_id, insurer_id, msg, cfilter,
     *      seguradora_files-down_apo-id    - filtro por registros relacionados ao registro id da área de seguradoras
     *      ids - pesquisa por vários ids separados por virgula
     *      taxs_id - ids das taxonomias separados por virgula
     * @param $return - modo de retorno. Valores: view (default), array ids
     */
    public function get_list(Request $request,$return='view'){
        $userLogged = Auth::user();
        $data = $request->all();
        $prefix = \Config::adminPrefix();
        //dd($data);

        $filter=[
            'account_id'=>$request->input('account_id'),
            //'type'=>$request->input('type'),
            'cpf'=>$request->input('cpf'),
            'nome'=>$request->input('nome'),
            'segdados'=>$request->input('segdados'),//sintaxe: segdados=campo:valor
            'id'=>$request->input('id'),
            'ids'=>$request->input('ids'),
            'ctrl_id'=>$request->input('ctrl_id'),
            'code'=>$request->input('code'),//campo status_code
            'status'=>$request->input('status'),
            'ctrl_user'=>$request->input('ctrl_user'),//campos alterados pelo usuário
            'ctrl_robo'=>$request->input('ctrl_robo'),//campos alterados pelo robo
            'process_name'=>self::$basename,
            'process_prod'=>$request->input('process_prod'),
            'process_auto'=>$request->input('process_auto'),//valores: n|s
            'data_type'=>$request->input('data_type'),
            'dtype'=>$request->input('dtype'),//valores: 'c' cadastro, 'p' processamento
            'dt'=>$request->input('dt'),//date aaaa-mm-d      //aceita também date_start - date_2_end (sintaxe: yyyy-mm-dd - yyyy-mm-dd)
            'dts'=>$request->input('dts'),//date start aaaa-mm-d,
            'dte'=>$request->input('dte'),//date end aaaa-mm-d,
            'broker_id'=>$request->input('broker_id'),
            'insurer_id'=>$request->input('insurer_id'),
            'msg'=>$request->input('msg'),
            'cfilter'=>$request->input('cfilter'),
            'taxs_id'=>$request->input('taxs_id'),
            'quiver_id'=>$request->input('quiver_id'),
            'req_fill_manual'=>$request->input('req_fill_manual'),

            //filtros adicionais (somente via querystring)
            'seguradora_files-down_apo-id'=>$request->input('seguradora_files-down_apo-id'),//filtra pela area de seguradoras

            //filtros pela tabela pr_seg_...
            //sintaxe do campo: prseg,{table},{field}=valor (aceita mais valores separados por virgula (whereIn)
            //ex: prseg,dados,tipo_pessoa=f,j
        ];


        //ajuste de acordo com o parâmetro filter_actions
        //distribui as as opções existentes aqui nas vars abaixo..
        //obs: solução temporária.. programar melhor
        if(strpos($request->filter_actions,'process_auto')!==false){
            $filter['process_auto'] = $request->filter_actions=='process_auto_s'?'s':'n';// valores esperados: s|n
        }


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

        $model = $this->ProcessRobotModel->select('process_robot.*')->where('process_name',self::$basename);


        if($prefix=='super-admin' && $filter['account_id'])$model->where('account_id',$filter['account_id']);

        if($filter['ids'])$model->whereIn('id',explode(',',$filter['ids']));

        if($filter['id'])$model->where('id',$filter['id']);
        if($filter['ctrl_id'] && is_numeric($filter['ctrl_id']))$model->where('process_ctrl_id','like','%'.FormatUtility::extractNumbers($filter['ctrl_id']).'%');

        if($filter['nome'] || $filter['cpf']){
            $arr=[];
            if($filter['nome']){
                $arr['segurado_nome__LIKE']='%'.$filter['nome'].'%';
            }
            if($filter['cpf'])$arr['segurado_doc']=$filter['cpf'];
            if($arr)$model->wherePrSeg('dados',$arr);
            //if($arr)$model->whereData($arr);
            //dump($model->toSql(),$model->getBindings());
        }
        if($filter['segdados']){
            $n=explode(':',$filter['segdados']);
            if($n[0] && ($n[1]??false)){
                $model->wherePrSeg('dados',[$n[0]=>$n[1]]);
            }
        }

        if($filter['broker_id'])$model->where('broker_id',$filter['broker_id']);
        if($filter['insurer_id'])$model->where('insurer_id',$filter['insurer_id']);

        //status code
            $n=$filter['code'];
            if($n){
                $is_not=false;
                if(substr($n,0,4)=='not:'){
                    $is_not=true;
                    $n=substr($n,4);//retira o not
                }
                //verifica se algum código tem '..'
                $r=[];
                $codeslist=self::getStatusCode();
                foreach(explode(',',$n) as $i=>$c){
                    if(substr($c,-2)=='..'){
                        $c=substr($c,0,-2);
                        //procura por todos os códigos com o mesmo prefixo e adiciona-o para a pesquisa
                        foreach($codeslist as $cl =>$vl){
                            if($c==substr($cl,0,strlen($c))){
                                $r[]=$cl;
                            }
                        }
                    }else{
                        $r[]=$c;
                    }
                }
                $n=$r;
                //dd($n);
                if($is_not){
                    $model->whereData(['error_msg__NOT_IN'=>$n]);
                }else if(count($n)>1){
                    $model->whereData(['error_msg__IN'=>$n]);
                }else{
                    $model->whereData(['error_msg'=>join('',$n)]);
                }
            }


        //status
        if(_GET('is_trash')!='s'){//obs: não executa a pesquisa por status quando está na lixeira
            $n=$filter['status'];
            if($n=='all'){//filtra por todos + ignorados
                //sem filtro aqui
            }elseif($n=='allx'){//filtra por todos + ignorados + removidos
                $model->withTrashed();
            }elseif($n!=''){//filtra por status
                if(strpos($n,',')!==false){
                    $model->whereIn('process_status',explode(',',$n));
                }else{
                    $model->where('process_status',$n);
                }
            }else{
                //filta todos mas desconsidera o ignorados (em análise)
                $model->where('process_status','<>','i');
            }
        }


        //custom filter
        if($filter['cfilter']=='process_test:s'){
            $model->where('process_test','s');

        }else if(substr($filter['cfilter'],0,11)=='error_code:'){//filtro por código do erro
            $n = str_replace('error_code:','',$filter['cfilter']);//valores: not_insurer, repeat, endosso, other
            if($n!=''){
                $model->whereIn('process_status',['e','i','1']);//filtra apenas para os status: erro, ignorado, análise

                $model=$model->join('process_robot_data',function($query) use($filter,$n){
                    return $query->on('process_robot.id','=','process_robot_data.process_id')
                                ->where('process_robot_data.meta_name','error_code')
                                ->where('process_robot_data.meta_value',$n=='other'?'':$n);
                });
            }
        }

        //os campos abaixo não estão no formulário de filtro, mas existem para filtragem da lista
        if($filter['process_prod'])$model->where('process_prod',$filter['process_prod']);
        if($filter['process_auto'])$model->where('process_auto',$filter['process_auto']=='s');
        if($filter['data_type'])$model->wherePrSeg('dados',['data_type'=>$filter['data_type']]);

        $dt_col = $filter['dtype']=='p'?'updated_at':'created_at';
        if($filter['dt'])$model->whereDate($dt_col,$filter['dt']);
        if($filter['dts'])$model->whereDate($dt_col,'>=',$filter['dts']);
        if($filter['dte'])$model->whereDate($dt_col,'<=',$filter['dte']);

        if($filter['msg']){
            //lógica: filtra pelo status_code da variável (array)self::getStatusCode() para extrair os códigos procurados, e a partir desta lista é que procura no metadado 'error_msg'
            if(strlen($filter['msg'])<=6){//é um código
                $arr_codes=[$filter['msg']=>''];
            }else{//é um texto
                $arr_codes = array_where(self::getStatusCode(),function($value,$key) use($filter){
                    return stripos($value,$filter['msg'])!==false;
                });
            }
            $model=$model->join('process_robot_data',function($query) use($filter,$arr_codes){
                return $query->on('process_robot.id','=','process_robot_data.process_id')
                            ->where('process_robot_data.meta_name','LIKE','error_msg%')
                            ->whereIn('process_robot_data.meta_value',array_keys($arr_codes));
            });
            $model->groupBy('id','broker_id','insurer_id','process_name','process_prod','process_ctrl_id','process_status','process_status_changed','process_date','created_at','updated_at','process_test','deleted_at','robot_id','user_id','process_next_at','locked','locked_at','account_id','process_auto');
            //dd($arr_codes, \App\Services\DBService::getSqlWithBindings($model) );
        }


        //filtro de alterações pelo usuário e robo
        if($filter['ctrl_robo'] || $filter['ctrl_user']){
            foreach(['ctrl_robo','ctrl_user'] as $ctrl){
                $n=explode(',',$filter[$ctrl]);
                if(!$n)continue;
                $ctrl=str_replace('ctrl_','',$ctrl);
                //filtra os campos por area
                foreach(['dados','parcelas','automovel'] as $table){
                    $c = '\App\ProcessRobot\cad_apolice\Classes\Segs\Seg'.studly_case($table);
                    $keys = array_keys($c::fields_labels());
                    $arr = array_intersect($n,$keys);//captura as as arrays válidas de cada área
                    if($arr)$model->wherePrSegCtrl($ctrl,$table,$arr);
                }
            }
        }
        //if($userLogged->user_level=='dev')dd( \App\Services\DBService::getSqlWithBindings($model) );


        //'taxs_id'=> _GET('taxs_id'),
        if($filter['taxs_id'])$model->whereTax($filter['taxs_id'],'cad_apolice');

        if(in_array($userLogged->user_level,['dev','superadmin'])){
            if(_GET('is_trash')=='s')$model=$model->onlyTrashed();
        }

        //filter by meta data
        $arr=[];
        foreach(['quiver_id','req_fill_manual'] as $f){
            $v = $filter[$f];
            if($v)$arr[$f]=$v;
        }
        if($arr)$model->whereData($arr);


        //filtros adicionais
            //filtra pela area de seguradoras
            if($filter['seguradora_files-down_apo-id']){
                $model->join('pr_seguradora_files', 'pr_seguradora_files.process_rel_id', '=', 'process_robot.id')
                      ->where('pr_seguradora_files.process_id',$filter['seguradora_files-down_apo-id']);
                if($request->input('seguradora_files-down_apo-clone')=='s'){
                    $model->whereNotNull('pr_seguradora_files.process_clone_id');
                }
            }


        //filtros pela tabela pr_seg_...
        //sintaxe do campo: prseg,{table},{field}=valor (aceita mais valores separados por virgula (whereIn)
        //ex: prseg,dados,tipo_pessoa=f,j
        //Obs para programador: melhorar a lógica das funções abaixo para ficar com melhor desempenho
        $PrSegService = new PrSegService;
        foreach($data as $f=>$v){
            if(strtolower(substr($f,0,6))=='prseg,'){
                $n=explode(',',$f);
                //dump([ $n[1],[$n[2]=>$v] ]);
                //verifica os nomes das tabelas
                if($PrSegService->checkNames($n[1],$n[2])){
                    $model->wherePrSeg($n[1],[$n[2]=>$v]);
                }
            }
        }

        //filtro de finalização manual pelo usuário/operador
        $n=$request->st_change_user;
        if($n){//valores: s|n
            if($n=='s'){
                $model->whereData(['st_change_user__LIKE'=>'f|%']);
            }elseif($n=='n'){
                $model->whereDataNotExists('st_change_user');
            }
        }



        //if($userLogged->user_level=='dev')dump( \App\Services\DBService::getSqlWithBindings($model) );



        if($return=='ids'){
            if(in_array($userLogged->user_level,['dev','superadmin'])){
                $ids=[];
                $ids=$model->select('id')->take(10000)->pluck('id')->toArray();
                return $ids;
            }else{
                return 'acesso negado';
            }

        }else{//==view
            $model=$model
                ->orderBy('id', 'desc')
                ->paginate(_GETNumber('regs')??15);

            //captura a lista de $fileInfoas
            $insurers_list = $this->InsurerModel->orderBy('insurer_alias','asc')->pluck('insurer_alias','id')->toArray();

            //catpura a lista corretores
            $brokers_list = $this->BrokerModel->selectRaw('id, CONCAT(broker_alias,IF(broker_status="c"," - Cancelado","")) as broker_alias')->orderBy('broker_status','asc')->orderBy('broker_alias','asc')->pluck('broker_alias','id')->toArray();

            return view('admin.process_robot.'.self::$basename.'.list'.($userLogged->user_level=='dev' && isset($data['view'])?'-'.$data['view']:''), [
                'model'=>$model,
                'filter'=>$filter,
                'configProcessNames'=>VarsProcessRobot::$configProcessNames,
                'insurers_list'=>$insurers_list,
                'brokers_list'=>$brokers_list,
                'status_list'=>self::$status, //$prefix=='super-admin' ? self::$status : array_map(function($v){return $v['label'];},self::$status_to_admin),
                'types_apolices_list'=>VarsProcessRobot::$typesApolices,
                'user_logged_level'=>$userLogged->user_level,
                'thisClass'=>$this,
                'pr_process'=>null,
                'count_regs_order'=>$this->countRegsOrder(),
            ])->render();
        }
    }


    /**
     * Janela de upload / cadastro um novo processo
     */
    public function create(Request $request){
        if(!AccountsService::isProcessActive(Config::accountID(),'cad_apolice'))exit('Acesso negado (serviço não ativado)');
        return view('admin.process_robot.'.self::$basename.'.upload',['configProcessNames'=>VarsProcessRobot::$configProcessNames]);
    }


    /**
     * Remove o registro do processo
     */
    public function remove(Request $request, $onBefore=null){
        $r = parent::remove($request,function($model){
            $userLoggedLevel = Auth::user()->user_level;
            $is_change_status_data = (in_array($userLoggedLevel,['dev','superadmin'])) || (!in_array($userLoggedLevel,['dev','superadmin']) && !in_array($model->process_status,['e','1']));
            if(!$is_change_status_data){
                return ['success'=>false,'msg'=>'Registro não pode ser alterado (em análise pelo suporte)'];
            }
             return ['success'=>true];
        });
        if(!$r['success'])return $r;

        if($r['success'] && in_array($r['action'],['trash','remove'])){
            $this->setProcessMakeDone($r['model'],'none');//nenhuma ação no quiver
        }
        return $r;
    }

    /**
     * Remove definitivamente
     * @param int|model $model - id ou model da tabela process_robot
     */
    public function removeFinal($model){
        if(is_int($model))$model = $this->ProcessRobotModel->onlyTrashed()->find($model);
        if($model){
            //remove da tabela que controle os registros marcados como concluídos no quiver
            PrSeguradoraFiles::where('process_rel_id',$model->id)->delete();

            //remove da tabela que controle os registros que foram processados junto na área de seguradoras
            PrSeguradoraData::where('process_rel_id',$model->id)->delete();

            //remove da tabela que controle do cadastro de apólices
            PrCadApolice::where('process_id',$model->id)->delete();
            PrCadApoliceData::where('process_id',$model->id)->delete();

            //remove os dados do seguros extraídos (tabelas pr_seg_...)
            $PrSegService = new PrSegService;
            $PrSegService->delAutoTable(VarsProcessRobot::$tablesSegs,$model->id);//remove os dados dos campos
            $PrSegService->delAutoTable(VarsProcessRobot::$tablesSegs,$model->id,true);//remove os dados das tabelas de controle de alterações

            //remove da tabela dev_process_robot_test_read_pdf
            \DB::delete('delete from dev_process_robot_test_read_pdf where process_id=?',[$model->id]);
        }

        return parent::removeFinal($model);
    }


    /**
     * Faz upload manual da apólice e registra na tabela via POST form
     */
    public function post_upload(Request $request) {
        $r = $this->auto_upload($request->all());

        if($r['success']){
            $model=$r['model'];
            $r = $this->processFilePDF($model);//indexa os dados
            $model->addLog('add',['processFilePDF()'=>$r,'data'=>$this->getDataLogProcess($model)]);

        }else{
            if(($r['msg']??'')!='Arquivo não enviado')
                \App\Services\LogsService::add('error', self::$basename,0,'Erro ao enviar arquivo manualmente <br>'.print_r([$r,$request->all()],true));
        }
        return $r;
    }

    /**
     * Faz upload da apólice e registra na tabela via POST form
     * @param array $data - parâmetros esperados:
     *      file - input file data or string path file PDF
     *      process_name - nome do processo, ex: cad_apolice
     *      process_prod - nome do produto, ex: automovel
     *      account_id   - id da conta (se não informado, captura automaticamente o ID pela model ProcessRobotModel (com Auth::user()->getAuthAccount('id')))
     *      process_auto - se true indica que este processo está sendo inserido automatica (pelo processo de SeguradoraFiles, por ex), se false (default) é envio manual
     * @return array[success,msg,action, (array)data, processModel ]
     */
    public function auto_upload($data){
        $process_date = date('Y-m-d');
        if(empty($data['process_prod']))return ['success'=>false,'msg'=>'Produto inválido'];
        if(!$this->checkProcessNames(self::$basename,$data['process_prod']))return ['success'=>false,'msg'=>'Nomes de processos ou produtos inválidos'];
        $account_id = $data['account_id']??null;
        if(!$account_id)$account_id = \Config::account()->id;
        if(!$account_id)return ['success'=>false,'msg'=>'ProcessCadApolice - AutoUpload - ID da conta inválido'];

        if(gettype($data['file'])=='string' && file_exists($data['file'])){
            //passou
        }else if(empty($data['file']) || gettype($data['file'])!='object'){
            return ['success'=>false,'msg'=>'Arquivo não enviado'];
        }

        $userLogged = Auth::user();
        //cadastra no db
        $datamodel=[
            //'broker_id'=>null,
            //'insurer_id'=>null,
            'process_name'=>self::$basename,
            'process_prod'=>$data['process_prod'],
            'process_ctrl_id'=>'',
            'process_status'=>'0',//=0 - em indexação de dados
            'process_date'=>$process_date,
            'process_auto'=> ($data['process_auto']??false)===true,
        ];
        if($userLogged)$datamodel['user_id']=$userLogged->id;
        if($account_id)$datamodel['account_id']=$account_id;
        try{
            $model = $this->ProcessRobotModel->create($datamodel);
            $r=[
                'success'=>true,
                'msg' => 'Upload concluído',
                'action'=>'add',
                //'url_edit' => route('admin.app.edit',['robots',$model->id]),
                'data' => $model->toArray(),
                'model' => $model,
            ];
        } catch (Exception $e) {
            $r=['success'=>false,'msg'=>$e->getMessage()];
        }
        if(!$r['success'])return $r;
        $ret = $r;

        //upload
        $r = FilesDirectService::uploadFile($data['file'],[
            'private'=>true,
            'folder'=>self::$basename .'/'. $data['process_prod'],
            'account_off'=>false,
            'folder_date'=>true,
            'date'=>$process_date,
            'accept'=>['application/pdf'],
            'account_id'=>$account_id,
            'folder_id'=>$model->id,
        ]);
        if(!$r['success']){
            $model->delete();//move o registro para a lixeira
            return $r;
        }

        $file = $r;
        $fileOriginalName = $file['filename_original'];
        $process_test= strtolower(substr($file['file_name'],0,6))=='teste-';//se true, é um arquivo de teste
        $model->update(['process_test'=>$process_test]);

        //adiciona o registro na tabela pr_seg_dados para gravar apenas o nome original do arquivo no campo segurado_nome
        (new PrSegService)->setTableDados($model->id, ['segurado_nome'=>$fileOriginalName]);

        if(!$datamodel['process_auto']){//é um upload enviado manualmente
            //verifica se esta conta está com o serviço de área de seguradoras ativo
            $account = AccountsService::get($model->account_id);
            if(($account->config['seguradora_files']['active']??null) === 's' ){//tem permissão para acessar esta configuração
                    //insere o registro de upload manual e deve inserir na tabela 'pr_seguradora_files' para marcar como concluído no quiver
                    $this->addRegManual_SeguradoraFiles($model->account_id);//verifica e adiciona o registro de controle de arquivo manual pela área de seguradoras

                    //captura o id do registro manual
                    $x=(new PrSeguradoraFiles)->create([
                        'process_id'=> $this->getProcessModel('seguradora_files')->where('process_ctrl_id','manual')->value('id'),//captura o id do registro manual
                        'quiver_id'=>null,
                        'process_rel_id'=>$model->id,
                        'created_at'=>date('Y-m-d H:i:s'),
                        'status'=>'0',//aguardando indexação para saber se deve ser processado
                    ]);
            }
        }

        //renomeia o arquivo a partir do id da model e atualiza o campo process_ctrl_id
        try{
            //grava o nome original do arquivo
            $model->setData('file_original_name', $fileOriginalName);
            //renomeia
            $p=$file['file_dir'] . DIRECTORY_SEPARATOR . $model->id .'.'. $file['file_ext'];
            (new Filesystem)->move($file['file_path'], $p);
            //$model->update(['file_ext'=>$file['file_ext']]);//remover esta linha, pois este campo não existe mais nesta tabela
        } catch (Exception $e){
            $model->addLog('error','Upload: Erro ao renomear arquivo. '.$e->getMessage());
            return $this->setStatus($model,'e','file02');
        }

        return $ret;
    }


    /**
     * Exibe os dados extraído do arquivo pdf diretamente
     * @param int $id - id tabela process_robot
     * @param string $type - valores 'txt', 'xml'
     * @obs: aceita o Request GET force=ok|all (para $type=xml) para fazer novamente a leitura dos dados xml
     */
    public function get_pageFileExtracted(Request $request,$id){
        $type = $request->input('type');
        $model = $this->ProcessRobotModel->find($id);


        /*
        $file_info = $this->getFileInfoPDF($model);
        $path = $file_info['file_path'];
        //$text = FilesUtility::readPDF($path,['engine'=>'ws02']);
        $pages = FilesUtility::getPDFPages($path);
        dd($path, $pages);*/


        if(!$model)return 'Erro ao localizar registro';
        $force = $request->input('force');
        if($type=='xml' && ($force=='ok' || $force=='all')){
            //extrai os dados xml novamente
            $r=$this->extractTextFromPdf($id,$force,$request->input('pdf_engine'));//true - para forçar a leitura do texto do pdf

            unset($r['process_model']);
            return view('admin.process_robot.'.self::$basename.'.view_dataxml',['model'=>$model,'data'=>$r,'thisClass'=>$this]);
        }

        if($type=='txt'){
            $r = $model->getText('text') ?? '';
            return $r ? '<pre>'.$r.'</pre>' : 'Texto não gravado ou vazio';

        }else if($type=='xml'){
            $r = $model->getText('data');
            $r = \App\Utilities\XMLUtility::convertArrToXml($r);
            return response($r, 200)->header('Content-Type', 'application/xml');

        }else{
            return 'Erro de parâmetro';
        }
    }

    /**
     * Faz o processo de indexação/leitura do pdf e gravação dos dados no DB de todos que estiverem com process_status=0
     * @obs este processo é agendado para ser executado em segundo plano a cada 5 minutos (executado via GET na url /process_cad_apolice/processFilesAll)
     * @obs2 se chamado a rota /admin/process_cad_apolice/processFilesAll - será considerado somente a conta do usuário logado no momento
     *       se chamado a rota /super-admin/...processFilesAll ou /...processFilesAll- será considerado somente todas as contas ativas
     * @obs3 aceita os parâmetros GET
     *      id - que neste caso considera apenas o registro do respecivo id para processar
     *      lock - se=='n' ignora a trava de registro
     *      regs - (int) número de registros por processamento (opcional). Default 50.
     *      from - origem da requisição. Defaul '' - web, 'autoit' requisição pelo autoit
     */
    public function get_processFilesAll(Request $request){
        //tempo em minutos que irá durar a expiração de registros travados
        $lock_minutes = 3;
        $lock_expire = date('Y-m-d H:i:s', strtotime('-'.$lock_minutes.' min', strtotime(date('Y-m-d H:i:s'))) );
        //dd(date('Y-m-d H:i:s'),$lock_expire);

        $regs = (int)$request->input('regs',50);
        $get_id = $request->input('id');
        $qs_lock=$request->input('lock');
        //dd($qs_lock,$regs);

        $model=$this->ProcessRobotModel
                ->where([
                    'process_name'=>self::$basename,
                    'process_status'=>'0',
                ]);
        if($get_id){
            $model->where('id',$get_id);
        }else{
            if($qs_lock!='n'){//se =='n' ignora a trava
                $model->where(function($query) use($lock_expire){
                        return $query
                            ->where('locked',false)
                            ->orWhereNull('locked')
                            ->orWhere(function($query) use($lock_expire){
                                return $query->where('locked',true)->where('locked_at','<=',$lock_expire);
                            });
                    });
            }
            $model->orderBy('id', 'asc');
        }
        //dd($model->toSql(),$model->getBindings());
        $model=$model->paginate($regs);
        try{
            if($model){
                foreach($model as $reg){
                    $reg->update(['locked'=>true,'locked_at'=>date('Y-m-d H:i:s')]);//trava o registro
                    $this->processFilePDF($reg);
                    $reg->update(['locked'=>false]);//destrava
                }
            }
        }catch(Exception $ex){
            //aqui representa um erro de servidor, portanto move automaticamente para a pasta 'e' (Correção Suporte)
            $reg->update(['process_status'=>'e']);
            return ['success'=>false,'msg'=>$ex->getMessage()];
        }

        if($request->wantsJson() || $request->from=='autoit'){
            return ['success'=>true,'processed'=>$model->count(),'pending'=>$model->total()-$model->count()];
        }else{
            exit('Finalizado');
        }
    }


    /**
     * Inicia o processo de indexação/leitura do pdf e gravação dos dados no DB
     * @param int|object model $id - da tabela process_robot
     * @param boolean $isReprocess - se true - quer dizer que é um reprocessamento de dados a partir do texto do pdf, false (default) - é o primeiro processamento após o upload do pdf
     *                                      Obs: caso de algum erro neste processo, irá marcar o registro com o status='e'.
     * @return array [success,msg]
     */
    public function processFilePDF($id,$isReprocess=false){
        $extract = $this->extractTextFromPdf($id,$isReprocess);//se $isReprocess=true, então irá setar nesta função extractTextFromPdf para forçar a leitura de dados
        $model = $extract['process_model'];
        //dd('C',$extract);

        if($model->process_status=='o' || ($extract['process_wait']??false)==true){//status de extração do texto do pdf em ocr
            return ['success'=>true,'msg'=>'Registro aguardando extração do texto','process_status'=>$model->process_status];
        }

        //remove o campo abaixo pois se chegou até aqui é porque já foi pelo menos extraído com o texto necessário
        $model->delData('pdf_extract_count');
        //if(\Auth::user()->user_level=='dev')dd($model->getData());

        if($isReprocess==false){//se for reprocessamento, não precisa verificar
            //prossegue somente se o registro tiver o status=0 - em processo de indexação de dados
            if(in_array($model->process_status,['f','w'])){
                $this->setProcessMakeDone($model,'on');//ignora de marcar na área de seguradoras
                return ['success'=>false,'msg'=>'Registro já processado. Status: '.$model->status_label,'process_status'=>$model->process_status];
            }
        }

        $id = $model->id;
        $data = $extract['file_data']??null;

        //atualiza os valores metadados
        if($extract['file_text']??false)$model->setText('text',$extract['file_text']);
        //dd(1111);
        if($data){//existem dados retornados
            //grava em php os dados extraídos
            $model->setText('data',$data);

            //grava no db
            //obs: na função abaixo grava somente os dados que não foram alterados pelo usuário
            $PrSegService = new PrSegService;
            $r=$PrSegService->saveFromExtract($model->process_prod, $model->id, $data);
            //if(Auth::user() && Auth::user()->id==1)dd('x2',$model->process_prod, $model->id, $data);
            if(!$r['success'])return ['success'=>false,'msg'=>'Erro ao salvar dados no banco de dados','code'=>'wbot01'];

            //verifica se existem dados do usuários já preenchidos, que foram mesclados aos dados extraídos, e neste caso valida novamente
            if($r['change_dados'] || $r['change_parcelas'] || $r['change_'.$model->process_prod]){
                $r=$PrSegService->validateAll($r['dados'], $r['parcelas'], $r[$model->process_prod], $model->process_prod, ['processModel'=>$model]);
                if($r==true){
                     //ajusta os valores que podem vir vazios para o padrão já salvo no banco de dados
                        if(!$extract['insurer_id']){
                            $extract['insurer_model']=$model->insurer;
                            $extract['insurer_id']=$model->insurer_id;
                        }
                        if(!$extract['broker_id']){
                            $extract['broker_model']=$model->broker;
                            $extract['broker_id']=$model->broker_id;
                        }
                        $extract['success']=true;
                        $extract['code']='ok';
                        $extract['msg']=self::getStatusCode('ok');

                }else{//erro na validação
                        $extract['success'] = false;
                        $extract['validate'] = $r['validate'];
                        $extract['code'] = $r['code'];
                }
            }
        }

        //ajusta demais valores iniciais
            $arr_update=[];
            $apolice_num = array_get($data,'apolice_num');
            if(!$apolice_num)$apolice_num = $model->process_ctrl_id;
            $data_type = array_get($data,'data_type');
            if(!$data_type)$data_type = $model->getSegData()['data_type']??'';

        $markdone_action='';
        //erro

        if(!$extract['success']){
            $st=($extract['ignore']??false)?'i':'e';
            $code=$extract['code']??'';
            $n=self::getStatusCode($code,false);
            if(in_array($code,['extr02','read07','read06','read18'])){//erro na extração, leitura, arquivo com senha ou bloqueado       //erro na extração obs: o código 'extr01' será gerado sempre pelo método 'cbFileExtractText'
                //como é erro de leitura de arquivo, o mesmo deve ser removido
                $this->setProcessMakeDone($model,'none');//ignora a ação na área de seguradoras
                $model->update(['process_status'=>'i']);//atualiza para ignorado, pois caso seja removido da lixeira, irá permanecer neste status por padrão
                $model->setData('error_msg',$code);
                $model->addLog('trash','Removido automaticamente. Motivo: '.$code.' '. $n);
                $model->delete();
                return ['success'=>false,'msg'=>$n,'code'=>$code];

            }elseif(in_array($code,['extr04'])){//Método de extração incompatível com o informado pela classe
                //este erro ocorre porque provavelmente o pdf está como imagem e ao analisar o texto seu modo de extração é outro
                //retorna para o erro de operador, pois isto está ocorrendo apenas quando ele envia manualmente
                $this->setProcessMakeDone($model,'none');//ignora a ação na área de seguradoras
                $model->update(['process_status'=>'c']);//atualiza para ignorado, pois caso seja removido da lixeira, irá permanecer neste status por padrão
                $model->addLog('status','Alterado automaticamente o status para (c) Erro de Operador. Motivo: '.$code);
                $model->setData('error_msg',$code);
                return ['success'=>false,'msg'=>$n,'code'=>$code];

            }elseif(in_array($code,['bro04'])){//corretor sem permissão
                $model->update(['process_status'=>'i']);
                $model->setData('error_msg',$code);
                $model->addLog('status',['msg'=>'Ingorado - '.$n]);
                $this->setProcessMakeDone($model,'none');//não deve tirar da área de seguradoras
                return ['success'=>false,'msg'=>$n,'code'=>$code];
            }

            //verifica e atualiza o status com base na mensagem de erro atual
            $st = $this->verifyStatusByError($model,$st,$extract['code'],$extract['msg']);
            //if(Auth::user() && Auth::user()->id==1)dd('x1',$st);
            if($st['status']=='i'){//ignorado
                $markdone_action='none';
            }elseif($st['status']=='c'){//erro do operador
                $markdone_action='on';//tira da área de seguradora, pois deve ser processado pelo robô
            }//else($st['status']=='e' || '1' )   //erro de sistema ou em análise (neste caso nenhuma ação é executada, pois deve ser analisado o erro para que somente na próxima indexação seja marcado corretamente)

            //???return $this->setStatus($model,$st['status'],$extract['code'],$st['next_at']);
            $arr_update['process_status']=$st['status'];

        }else{
            //Daqui por diante marca como concluído no quiver para todos os casos, pois se chegou até aqui precisa sempre ir no quiver para marcar
            //Obs: abaixo existem duas situações onde o respectivo registro é excluído por duplicidade. E como esté sendo executado o registro atual
            //     que pode ter vindo da área de seguradoras, deve ir para a tabela pr_seguradora_files para que o robô marque como concluído
            $markdone_action='on';
            //antes de prosseguir verifica se tem erros de corretor e seguradora
            if(empty($extract['insurer_id']) || empty($extract['broker_id'])){
                $n='c';//erro operador
                $extract['code']=empty($extract['insurer_id'])?'ins01':'bro01';
            }else{
                $n='p';
                $arr_update['updated_at']=date('Y-m-d H:i:s');//atualiza a data sempre que o status = Pronto para o robô
            }
            $arr_update['process_status']=$n;
        }

        //atualiza os dados já capturados
        $arr_update['insurer_id']=$extract['insurer_id']??null;
        $arr_update['process_ctrl_id']=$apolice_num;
        $n = $model->getData('broker_manual');
        if($n!='s')$arr_update['broker_id'] = $extract['broker_id'] ?? null;

        //dd('a1',$model->toArray(),$arr_update,$extract);
        //dd($arr_update);
        $model->update($arr_update);
        $model->setData('error_msg',$extract['code']);
        //dd($markdone_action);
        if($markdone_action)$this->setProcessMakeDone($model,$markdone_action);

        if($apolice_num){//verifica somente se houver número de apólice
                if($model->process_test){//é um registro de teste
                    //nenhuma ação
                }else{
                    //verifica se o número da apólice já existe no sistema para o processo, produto e seguradora atual (desconsidera status 'i' ignorado)
                    $modelCheckExists=$this->ProcessRobotModel
                            ->where([
                                'process_name'=>$model->process_name,
                                'process_prod'=>$model->process_prod,
                                'insurer_id'=>$model->insurer_id,
                                'broker_id'=>$model->broker_id,
                                'process_test'=>false
                            ])
                            ->whereRaw('TRIM(LEADING "0" FROM process_ctrl_id) = ?',[ltrim($apolice_num,'0')])
                            ->where('process_status','<>','i')
                            ->where('id','<>',$id)
                            ->first();
                            //dd($model->toArray(),$modelCheckExists);//,   \App\Services\DBService::getSqlWithBindings($modelCheckExists)      );
                    if($modelCheckExists){
                        //if(Auth::user()->id==1)dd($modelCheckExists,$apolice_num,(int)$apolice_num);
                        if(in_array($modelCheckExists->process_status,['w','f']) && ($modelCheckExists->getSegData()['data_type']??'')=='historico'){//quer dizer que o registro do upload principal é a apólice de um histórico, e por isto que o ID está repetido
                            //verifica se já existe outro registro de apólice associado ao registro de histórico
                            $model2 = $this->ProcessRobotModel
                                ->where([
                                    //'process_ctrl_id'=>ltrim($apolice_num,'0'),
                                    'process_name'=>$model->process_name,
                                    'process_prod'=>$model->process_prod,
                                    'insurer_id'=>$model->insurer_id,
                                    'process_test'=>false,
                                ])
                                ->whereRaw('TRIM(LEADING "0" FROM process_ctrl_id) = ?',[ltrim($apolice_num,'0')])
                                ->whereNotIn('process_status',['i'])
                                ->where('id','<>',$id)
                                ->wherePrSeg('dados',['data_type'=>$data_type])
                                ->first();
                            //dd($model2, $apolice_num, $data_type);
                            if($model2){//já existe registro associado
                                //altera o status para ignorado
                                $r=$this->setStatus($model,'i','file03');

                                //adiciona para ser marcado como concluído no quiver o item original
                                $this->setProcessMakeDone_item('a',$model2);//a - marcar como concluído no quiver

                                //manda o arquivo para a lixeira
                                $model->addLog('trash','Removido automaticamente. Motivo: já existe no DB - Ref '.$model2->id);
                                $this->setProcessMakeDone($model,'none');//marca como 'nenhuma ação', pois não deve atuar na área de seguradoras
                                $model->delete();
                                return $r;

                            }else{//nenhum registro adicional, portanto gera o vínculo
                                //aqui é criado apenas o vínculo do pdf atual com o registro de histórico, e a atualização do status de 'w' para 'f' será após ter concluído o processamento do robô
                                $model->setData('hist_id', $modelCheckExists->id);//atualiza informado que é este processo é complementar ao histórico
                                //... e deixa prosseguir normalmente
                            }

                        }else{
                            //adiciona para ser marcado como concluído no quiver o item original
                            //DESATIVADO (não mexe no registro que já existe): $this->setProcessMakeDone_item('a',$modelCheckExists);//a - marcar como concluído no quiver

                            //Atualização 28/12/2021
                            //Lógica: aqui o registro de $model será excluído pois existem outro igual em $modelCheckExists,
                            //        mas este outro registro pode ter sido enviado manualmente e neste caso os respectivos campos de controlers na tabela pr_seguradora_files não serão executados (por pertencer a um registro de envio manual)
                            //        portanto irá copiar o respectivo registro manual da tabela 'pr_seguradora_files' ($modelCheckExists) para o respectivo registro de 'pr_seguradora_files' percentente a este procesos principal ($model)
                            //        assim, este registro que foi ignorado/removido por ser duplicado, terá o registro $modelCheckExists copiado em 'pr_seguradora_files' garantindo que o processo de marcar como concluído funcione para este caso
                            $is_markdone=true;
                            if(in_array($modelCheckExists->process_status,['p','a','f','w'])){
                                //quer dizer que o registro que já existe, é um registro válido para marcar como concluído
                                $r=$this->cloneProcessMakeDone($modelCheckExists,$model);//clona o registro no processo da área de seguradora deste registro que está duplicado, para que possa voltar na área de seguradoras e marcar como concluído
                                if($r=='mark')$is_markdone=false;//quer dizer que já está marcado e portanto não precisa executar a função this->setProcessMakeDone()
                            }
                            //dd('okokok',$is_markdone);
                            $r=$this->setStatus($model,'i','file03');
                            $model->addLog('trash','Removido automaticamente. Motivo: já existe no DB - Ref '.$modelCheckExists->id);
                            if($is_markdone)$this->setProcessMakeDone($model,'none');//marca como 'nenhuma ação', pois não deve atuar na área de seguradoras
                            $model->delete();

                            return $r;
                        }
                    }else{//apólice único // não tem registros duplicados
                        //nenhuma ação....
                    }
                }
        }
        //dd('passou!!!');


        //limpa o campo para informar que todos os blocos devem ser processados
        if($isReprocess){
            $model->setData('block_names','');
            $model->delData('block_names_tmp');
        }

        //Salva os dados do log
        if(!$extract['success'] && in_array($extract['code'],['read02','read03','read04'])){
            $model->addLog('status',['msg'=>$code.' - '.self::getStatusCode($extract['code'])]);

        }else{
            $r = $this->getDataLogProcess($model,$data,$extract['code']);
            $model->addLog('process',['msg'=>'Indexação processada','data'=>$r]);
        }

        $model->delData('login_use');//remove a informação do login usado, pois isto tem conflitado com a distribuição de logins de quiver pela classe WSRobotController@get_process
        AccountPassService::setLoginNotBusyByProcess($model->id);//tira a trava do cadastro do login

        /*???if($isReprocess==false){
            return $this->setStatus($model,'p','');//seta status='p' - pronto para ser processado pelo robô

        }else{//é reprocessamento
           $s=$model->process_status;
           if($s=='p' || $s=='0' || $s=='e' || $s=='c' || $s=='1' || $s=='i'){//quer dizer que o status atual é: (p) pronto para o robô, (e)erro, (c)erro cliente, (1)análise, (i)ignorado - nestes casos atualiza para (p)pronto para o robô
               return $this->setStatus($model,'p','');
           }else{
               return ['success'=>true,'msg'=>''];
           }
        }*/

        return ['success'=>$extract['success'],'msg'=>self::getStatusCode($extract['code']),'code'=>$extract['code']];
    }

    /**
     * Seta os dados do log para a tabela process_robot
     * Return array datalog
     */
    private function getDataLogProcess($model,$data=null,$code=null){
        if(!$data)$data=$model->data_array;
        if(!is_array($data))$data=[];
        //Salva os dados do log
        $arr=[
            'code'=>$code.' - '.self::getStatusCode($code,false),
            'process_name'=>$model->process_name,
            'process_prod'=>$model->process_prod,
            'insurer'=>($model->insurer ? $model->insurer->insurer_name.' #'.$model->insurer_id:''),
            'broker'=>($model->broker ? $model->broker->broker_name.' #'.$model->broker_id:''),
            'apolice_num'=>$model->process_ctrl_id,
            'segurado_nome'=>$data['segurado_nome']??'',
            'segurado_doc'=>$data['segurado_doc']??'',
            'file_original_name'=>$data['file_original_name']??'',
        ];
        $r=[];
        foreach($arr as $f=>$v){
            if($v)$r[$f]=$v;
        }
        return $r;
    }


    /**
     * Reprocessa um registro para extrair os dados do pdf novamente.
     * Request esperados:
     *      int $id - id da tabela process_robot.id
     *      str $rd - redirecionamento após finalizar (opcional)
     * Return array[success,msg]
     */
    public function get_reprocessFile(Request $request){return $this->post_reprocessFile($request);}
    public function post_reprocessFile(Request $request){
        $id = $request->input('id');
        $model = $this->ProcessRobotModel->find($id);
        $userLoggedLevel = Auth::user()->user_level;

        if($userLoggedLevel!='dev' && $userLoggedLevel!='superadmin'){
            if($model->process_status=='f')return ['success'=>false,'msg'=>'Este processso já foi finalizado e não pode ser processado'];
        }

        //captura e processa os dados
        $r = $this->processFilePDF($model, $request->input('force')=='s');//se force=s - para indica que é um reprocessamento de dados a  partir do texto do pdf, caso contrário apenas faz a leitura sob o txt já processado

        $rd = $request->input('rd');
        if($rd){
            return \Redirect::to($rd)->send();
        }else{
            return $r;
        }
    }






    /**
     * Seta um novo status para a tabela process_robot
     * @param int|model id      - da tabela process_robot
     * @param string status     - valores de status: 0|a|p|e|f|1|i  - (consulte a documentação em xls - tabela process_robot.process_status)
     * @param string status_code- mensagem ou código do erro/retorno (um dos valores da classe \App\ProcessRobot\VarsProcessRobot::$statusCode)
     * @param string $next_at   - tempo futura para reprocessamento (somente se status=p), sintaxe: yyyy-mm-dd hh:mm:ss
     * return array [success,msg]
     */
    public function setStatus($id,$status,$status_code=null,$next_at=null){
        $model = is_object($id) ? $id : $this->ProcessRobotModel->find($id);

        $arr=['process_status'=>$status,'process_status_changed'=>'1'];
        if($status=='p' && $next_at)$arr['process_next_at']= (int)$next_at ? $next_at : null;
        if($status=='p' || $status=='a'){
            $arr['updated_at']=date('Y-m-d H:i:s');//atualiza a data sempre que o status = Pronto para o robô / Em andamento
            $arr['robot_id']=null;//limpa o id do robô para que qualquer robô possa processá-lo
        }
        //dd($arr);
        $model->update($arr);//process_status_changed=1 para indicar que foi alterado pelo usuário admin, seta index=>false para recalcular na tabela ProcessRobotResume

        if($status_code!==null)$model->setData('error_msg', $status_code);

        return ['success'=> ($status=='e' || $status=='c' || $status=='i'?false:true) ,'msg' => self::getStatusCode($status_code)];
    }

    /**
     * Gera uma cópia de registros na tabela pr_seguradora_files, duplicando o registro de $modelToCloned
     * @param $modelToCloned - model da tabela process_robot[cad_apolice] do qual contém o registro a ser clonado (este model pode ter vindo da área de seguradoras ou enviado manualmente)
     * @param $modelMain - model da tabela process_robot[cad_apolice] para captura do respectivo registro associado da área de seguradoras para vincular na clonagem do registro (este model precisa obrigatoriamente ter vindo da área de seguradoras)
     * @return null | 'mark'    = se 'mark' quer dizer que já efetuou a ação de marcar na tabela pr_seguradora_files e não precisa setar a função  $this->setProcessMakeDone()
     */
    private function cloneProcessMakeDone($modelToCloned, $modelMain){
        //verifica se foi enviado manualmente o registro principal $modelMain, e neste caso não precisa duplicar o registro,
        //pois somente o que é enviado pela área de seguradoras é que precisa ter este processo clonado para garantir que possa ser marcado como concluído.
        if(!$modelMain->process_auto)return null;

        $prBase = (new PrSeguradoraFiles);
        //->join('process_robot p', 'pr_seguradora_files.process_id', '=', 'p.id')
        //->whereNotNull('p.deleted_at');
        $prSegFMain = $prBase->where(['process_rel_id'=>$modelMain->id])->first();
        if(!$prSegFMain)return null;


        $prSegFCloned = $prBase->where(['process_rel_id'=>$modelToCloned->id])->first();
        if(!$prSegFCloned){
            //se chegou até aqui, é porque ocorreu algum erro que não existe o registro na tabela pr_seguradora_files
            //portanto precisa gerar um registro na tabela pr_seguradora_files de $modelMain para que possa ser baixado o registro na área de seguradoras
            //obs: abaixo gera apenas os campos necessários para que não seja gerado o erro nas linhas seguintes
            $prSegFCloned = (object)[
                'process_id'=> $modelMain->id,
                'process_rel_id'=> $modelToCloned->id,
            ];
        };

        //verifica se já não foi adicionado
        //dd('a3',['process_id' => $prSegFMain->process_id, 'process_rel_id' => $prSegFCloned->process_rel_id], $prBase->where(['process_id' => $prSegFMain->process_id, 'process_rel_id' => $prSegFCloned->process_rel_id])->exists());
        if( $prBase->where(['process_id' => $prSegFMain->process_id, 'process_rel_id' => $prSegFCloned->process_rel_id])->exists()){
            //dd('já existe');
            $prSegCurr = $prBase->where(['process_id' => $prSegFMain->process_id, 'process_rel_id' => $modelMain->id])->first();
            if($prSegCurr)$prSegCurr->update(['status'=>'a','process_clone_id'=>$modelToCloned->id,'is_clone_id_cad_apolice'=>true]);//seta para marcar como concluído

            //dd('e01',$prSegCurr, $modelToCloned->id);
            return 'mark';

        }else{
            $data = [
                'process_id' => $prSegFMain->process_id,
                'process_rel_id' => $prSegFCloned->process_rel_id,
                'quiver_id' =>  $prSegFMain->quiver_id,
                'created_at' => $prSegFMain->created_at,
                'status' => 'a', //ação de marcar como concluído
                'process_count' => 0, //sempre 0, pois este está como valor padrão para processos importados / clonados
                'process_clone_id' => $prSegFCloned->process_id,
            ];

            //dd($prSegFCloned->toArray(),$prSegFMain->toArray(), $data);
            $prBase->create($data);
        }

        return null;
    }

    private function setProcessMakeDone_item($st,$processsModel,$mPrRel=null,$mSegFiles=null){
        if($mPrRel && !$mPrRel instanceof \Illuminate\Database\Eloquent\Collection){
            $mPrRel = collect([$mPrRel]);
        }
        if(!$mSegFiles)$mSegFiles = $this->getProcessModel('seguradora_files');

        if(!$mPrRel || $mPrRel->count()==0)$mPrRel = (new PrSeguradoraFiles)->where(['process_rel_id'=>$processsModel->id])->get();//obs: se achou o registro, deve colocar para marcar novamente como concluído
        //dd($processsModel->toArray(),$mPrRel);
        if(!$mPrRel || $mPrRel->count()==0)return;//quer dizer que não achou o registro, motivo provável: o status na tabela pr_seguradora_files = a|b

        foreach($mPrRel as $regRel){
            if($mSegFiles->find($regRel->process_id)->process_ctrl_id=='manual'){//não deve processar este registro
                continue;
            }

            $regRel->update(['status'=>$st]);//seta que já deve marcar como concluído

            //descartado em 29/11/2021
            //seta o status que está pronto para o robô processar na área de seguradoras (obs: joga sempre alguns minutos para frente, para dar preferência para outro processo e deixar juntar alguns casos)
            /*$mSegFiles->find($regRel->process_id)->update([
                'process_status'=>'p',
                'updated_at'=>date('Y-m-d H:i:s'),
                'process_next_at'=>date('Y-m-d H:i:s', strtotime('15 min', strtotime(date('Y-m-d H:i:s'))) )
            ]);*/
            //dd($regRel,$mSegFiles);
        }
    }
    /**
     * Adiciona no registro de cadastro de apólice que deve ser marcardo como concluído ou não concluído no Quiver.
     * É inserido o registro na tabela pr_seguradora_files.
     * @param $action - valores: on - marcar como concluído, off - desmarca como concluído, none - nenhuma ação (marca como ignorado o registro em pr_seguradora_files)
     * @param $process_finais - se false não executa os processos finais referente a verificação de apólice que existe nesta função. Default true.
     * Sem retorno
     */
    private function setProcessMakeDone($id,$action,$process_finais=true){
        $model = is_object($id) ? $id : $this->ProcessRobotModel->find($id);
        if(!$model)return null;
        //verifica se esta conta está com o serviço de área de seguradoras ativo
        $account = AccountsService::get($model->account_id);
        if(($account->config['seguradora_files']['active']??null) !== 's' )return null; //não tem permissão para acessar esta configuração

        $mSegFiles = $this->getProcessModel('seguradora_files');

        //obs: por padrão, somente registros não finalizados (F) é que retornaram nesta lista
        //      $reg = (new PrSeguradoraFiles)->where(['process_rel_id'=>$model->id])->whereNotIn('status',['f','1'])->first(); //alterado em 2021-11-23 para ficar conforme linha abaixo (de ['f','1'] para ['f']).. analisando possível impacto
        //                  (até agora parece ser a melhor alternativa, pois se um registro é ignorado e depois enviado outro igual, o mesmo não está tirando da área de seguradoras por já estar =1 (nenhuma ação))
        $reg = (new PrSeguradoraFiles)->where(['process_rel_id'=>$model->id])->whereNotIn('status',['f'])->first();

        //dd('x01',$model);
        if($model->process_auto==false && empty($model->process_ctrl_id)){
            //quer dizer que foi enviando manualmente e não tem número de apólice
            if($reg)$reg->update(['status'=>'1']);//marca como ignorado o registro em pr_seguradora_files
            return;
        }
        //dd($id,$action,$reg);

        if($reg){
            if($reg->process_id){//está associado a um registro de pesquisa na área de seguradoras
                $mSegFiles=$mSegFiles->find($reg->process_id);

            }else{
                //foi enviado manualmente

                /**
                 * Atualização 28/12/2021:
                 *      Desabilitado para que os arquivos enviados manualmente não sejam marcados como concluído na área de seguradoras (processo mark_done)
                 *      Somente os que forem baixados da área de seguradoras é que terão o processo mark_done para voltar lá e marcar como concluído
                 */
                return false; //não tem permissão para acessar esta configuração

                /*!!! desabilitado
                //*** permite área de seguradoras manual ***
                //procura o primeiro registro para associado manual (será o primeiro registro com o campo locked=true)
                $mSegFiles=$mSegFiles->where('process_ctrl_id','manual')->first();
                */
            }
            if($mSegFiles){
                if($action=='none'){
                    $reg->update(['status'=>'1']);//marca como ignorado o registro em pr_seguradora_files
                }else{
                    $st=$action=='on'?'a':'b';
                    //desatvar linha: if($mSegFiles->status!==$st){//ocorreu a mudança de status
                        //dd($reg,$action,$st);
                        /*$reg->update(['status'=>$st]);//seta que já deve marcar como concluído
                        //seta o status que está pronto para o robô processar na área de seguradoras (obs: joga sempre alguns minutos para frente, para dar preferência para outro processo e deixar juntar alguns casos)
                        $mSegFiles->find($reg->process_id)->update([
                            'process_status'=>'p',
                            'updated_at'=>date('Y-m-d H:i:s'),
                            'process_next_at'=>date('Y-m-d H:i:s', strtotime('15 min', strtotime(date('Y-m-d H:i:s'))) )
                        ]);*/
                        $this->setProcessMakeDone_item($st,$model,$reg,$mSegFiles);
                    //}
                }
            }else{
                $reg->update(['status'=>'x']);//Seta erro de regitro base não encontrado
            }
        }


        if($process_finais && $action=='on'){
            //até aqui quer dizer que o registro é válido para ser processado pelo robô (e por isto foi marcado para ser retirado da área de seguradoras)
            //portanto também deve ser adicionado para ser verificado no site das seguradoras
            $r = $this->getSeguradoraDataController()->addProcessCheck($model,'apolice_check');

            if($r['code']=='ok' || $r['code']=='ok2'){//foi adicionado pelo seguradora_data
                $r=$this->servicePrCadApolice()->add($model,'apolice_check','p','add', false);//false para não setar o usuário logado (modo automático)
            }else{
                $r=$this->servicePrCadApolice()->add($model,'apolice_check','m','add', true);//true para setar o usuário logado (modo de edição manual)
            }
            //dd($r);
        }
    }


    /**
     * Função ao ser executada sempre que for finalizado um processo (alterado o status para 'f')
     */
    private function onStatusF($model,$check_execs=true){
        if($model->process_status!='f')return false;

        //verifica no status da última execução se não deu erro de proposta não localizada
        if($check_execs){//analisa a tabela process_robot_execs para saber se deve adicionar
            $exec = $model->execs->last();
            if(!$exec)return false;//não existe execuções do robô, e quer dizer que o robô ainda não processou/emitiu portanto não adiciona o processo de captura de boletos
            $s=$exec->status_code;
            if(!in_array($s,['ok','ok2']))return false;//quer dizer que a última execução tem que ser 'ok'
        }
        //adiciona o registro ser baixar os boletos no site das seguradoras
        $this->getSeguradoraDataController()->addProcessCheck($model,'boleto_seg');
    }



    /**
     * Verifica e adiciona o registro para vínculo de arquivos manuais com o registro de área de seguradoras.
     * Adiciona um registro na tabela process_robot com o campo process_ctrl_id='manual'
     * Sem retorno
     */
    private function addRegManual_SeguradoraFiles($account_id){
        $m=$this->getProcessModel('seguradora_files')->select('id')->where('process_ctrl_id','manual')->first();
        if(!$m){//não existe, portanto adiciona o registro
            $this->getProcessModel('seguradora_files')->create([
                'process_name'=>'seguradora_files',
                'process_prod'=>'down_apo',
                'process_ctrl_id'=>'manual',
                'process_status'=>'f',
                'process_date'=>date('Y-m-d'),
                'user_id'=>null,//seta null porque é uma inserção automática
                'account_id'=>$account_id,
            ]);
        }
    }




    /**
     * Altera os status de todos os ids informados
     * Função para o botão 'alterar status' da lista de processos
     */
    public function post_changeAllStatus(Request $request){
        $data   = $request->all();
        if(!is_array($data['ids']??null))return ['success'=>false,'msg'=>'Erro de parâmetro ID'];
        if($data['status']=='')return ['success'=>false,'msg'=>['status'=>'Status não definido']];

        foreach($data['ids'] as $id){
            $m = $this->ProcessRobotModel->find($id);
            if(!$m)continue;

            //Obs: aqui o campo block_names pode vir de duas formas:
            //  1 - ex: [dados,premio,...]
            //  2 - ex: automovel-block=>[dados,premio,...]
            $block_names = $data['block_names']??null;//forma 1
            if(!$block_names)$block_names = $data[$m->process_prod.'-blocks']??null; //forma 2

            $r=$this->changeProcessStatus($id,$data['status'],$data['next_at']??null, $block_names );
            if(!$r['success'])return $r;
        }
        return ['success'=>true];
    }
    private function changeProcessStatus($id,$new_status,$next_at=null,$block_names=null){//array $block_names - ex: [dados,premio,....]
        //obs: sempre que esta função for chamada, indica que a alteração de status foi realizada manualmente pelo usuário
        //dd($block_names);
        if($next_at && (int)$next_at){//se $next_at='00/00/0000 00:00', então irá limpar o campo process_next_at
            if(!ValidateUtility::isDate($next_at))return ['success'=>false,'msg'=>['next_at'=>'Data inválida']];
            if(ValidateUtility::ifDate(FormatUtility::convertDate($next_at,true), '<=', date('Y-m-d H:i:s')))return ['success'=>false,'msg'=>['next_at'=>'Data precisa ser maior que agora']];
            $next_at = FormatUtility::convertDate($next_at,true);
        }

        $userLogged = Auth::user();
        $userLoggedLevel = $userLogged->user_level;

        $st = $new_status;
        $model = is_object($id) ? $id : $this->ProcessRobotModel->find($id);
        if($model){
            //verifica se a conta não está cancelada, e neste caso não pode atualizar
            if($model->account->account_status!='a')return ['success'=>false,'msg'=>'Conta cancelada'];

            //se true indica que somente o superadmin tem permissão para editar o registro estar como suporte ou análise
            $is_change_status_data = (in_array($userLoggedLevel,['dev','superadmin'])) || (!in_array($userLoggedLevel,['dev','superadmin']) && !in_array($model->process_status,['e','1']));
            if(!$is_change_status_data){
                return ['success'=>false,'msg'=>'Registro não pode ser alterado (em análise pelo suporte)'];
            }

            if($st=='p'){//pronto para o robô
                //*** verifica se tem as informações necessárias para mudar para este status ***
                //verifica se está marcado req_fill_manual=s, e neste caso deve deixa alterar para este status, pois é obrigatório o preenchimento manual dos dados pelo operador
                if($model->getData('req_fill_manual')=='s'){
                    return ['success'=>false,'msg'=>'É necessário editar manualmente de dados para emitir'];
                }

                //verifica o corretor
                if(!$model->broker || !$model->insurer || !$model->process_ctrl_id){//não tem dados suficientes para prosseguir
                    //coloca para ser indexado novamente
                    $model->update(['process_status'=>'0','robot_id'=>null]);//0 = indexação de dados
                    //adiciona o log
                    $model->addLog('edit','Alteração de status para: '. self::$status[$st] .'('.$st.') negado. Dados insuficientes. Alterado automaticamente para '. self::$status['0'] .'(0)');
                    return ['success'=>true];
                }
            }


            $old_status = $model->process_status;
            $model->update(['process_order'=>null]);//limpa o campo ordem sempre ao alterar o status

            if($userLoggedLevel!='dev' && $userLoggedLevel!='superadmin'){//somente se for desenvolvedor que pode alterar
                //lógica: se já foi finalizado (status=f) e changed==0 - quer dizer que foi finalizado pelo robô e não pode ser alterado pelo usuário admin
                $is_change_status = $model->process_status=='f' && $model->process_status_changed==0?false:true;
                if(!$is_change_status)return ['success'=>false,'msg'=>'Permissão negada (1)'];
            }
            if($st=='p' || $st=='a'){//p - pronto para ser processado pelo robô, a - em andamento pelo robo
                //verifica se existem todas as informações necessárias para ser processado pelo robo
                $metadata = $model->getText('data');
                if($model->process_ctrl_id && $metadata){
                    $this->setProcessMakeDone($model,'on');//seta que este registro deve ser marcado como concluído no Quiver
                    $this->setStatus($model,$st,null,$next_at);
                }else{
                    return ['success'=>false,'msg'=>['status'=>'Registro #'.$id.' não tem dados para status = '.self::$status[$st] ]];
                }
            }else{
                $this->setStatus($model,$st);
                //seta que este registro deve ser marcado no Quiver
                if($st=='f' || $st=='w'){//finalizado ou pendente de apólice
                    $this->setProcessMakeDone($model,'on');//marcado como concluído
                    $this->onStatusF($model);//ações ao marcar como concluído
                    $this->histFinished($model->getData('hist_id'),$model);//caso tenha um histórico associado, finaliza também

                }elseif($st=='i'){//ignorado
                    $this->setProcessMakeDone($model,'none');//marcado como nenhuma ação no quiver
                }
            }

            //verifica se foi informado nomes dos blocos para processar
            if($st=='0' || $st=='p' || $st=='a'){
                $model->delData('login_use');//remove a informação do login usado, pois isto tem conflitado com a distribuição de logins de quiver pela classe WSRobotController@get_process
                AccountPassService::setLoginNotBusyByProcess($model->id);//tira a trava do cadastro do login

                //dd('*B',$model->toArray(),$model->getData('login_use'));
                if($block_names){
                    //verifica se foi informado o bloco 'premio' e neste caso, é obrigatório informar o bloco 'parcela'
                    if(in_array('premio',$block_names) && !in_array('parcelas',$block_names))array_push($block_names,'parcelas');
                }else{
                    //captura os blocos que ainda não foram processados
                    $block_names = explode(',',$model->getData('block_names')??'');
                    //captura os nomes dos blocos padrões
                    $blocks_defaults = VarsProcessRobot::$configProcessNames[$model->process_name]['products'][$model->process_prod]['blocks'];
                    $blocks_defaults = explode(',',$blocks_defaults);
                    //diferença entre as matrizes
                    $block_names = array_diff($blocks_defaults,$block_names);
                }
                //dd($block_names,'*');
                //atualiza no campo block_names_tmp que contém os blocos para o robô processar //obs: pode ocorrer de $block_names for vazio, e neste caso não tem problema pois irá reprocessar todos
                $model->setData('block_names_tmp', join(',',$block_names));
            }

            //adiciona o log
            $model->addLog('status','Status atualizado de: '. self::$status[$old_status] .'('.$old_status.')  - para: '. self::$status[$st] .'('.$st.')');

            if($st=='f' || $st=='w'){//f finalizado, w pendente de apólice
                //finalizado manualmente pelo usuário
                $model->setData('st_change_user',$st.'|'.$userLogged->id.'|'.date('Y-m-d H:i:s'));

                //!!Importante: está ok o código abaixo, mas foi desabiltiado pois este recurso de 'revisão' será concluído em outro momento
                //adiciona sempre um novo registro de revisão como ignorado
                //$this->servicePrCadApolice()->add($model,'review','i','add+',true);//true para setar o usuário logado
            }
        }

        return ['success'=>true];
    }



    /**
     * Retorna apenas a relação de ids a partir dos parâmetros querystring informados
     */
    public function post_getIdsByQs(Request $request){
        $qs = $request->input('qs_from');
        parse_str($qs,$qs);
        $request->replace($qs);//substitui os valores do request
        $ids = $this->get_list($request,'ids');
        return ['success'=>true,'ids'=>$ids];
    }




    /**
     * Testa se a extração está retornando com sucesso ou erro
     * @param array opt
     *      save_index     - (boolean) indica se irá salvar o resultado da indexação. Default false. Obs: dados da seguradora, corretor e satus não são alterados
     *      pdf_engine     - (opcional) nome do recurso que irá processar o pdf. Padrão 'pdfparser' (mais informações na classe em \App\Utilities\FilesUtility::readPDF).
     * Return array[success,msg,process_model]
     */
    public function test_extractTextFromPdf($id,$opt=[]){
        $opt = array_merge([
            'save_index'=>false,    //Salva o resultado da indexação (obs: este recurso apenas salva sem considerar o ca
            'pdf_engine'=>null,
        ],$opt);

        $extract=$this->extractTextFromPdf($id,null,$opt['pdf_engine'],true);

        $msg = $extract['msg'];
        $model = $extract['process_model']??null;

        if($extract['success'] && $opt['save_index']){//salva o resultado da indexação
            $msg = 'Indexado com sucesso';

            //salva os dados em php
            $model->update(['process_ctrl_id'=>array_get($extract['file_data'],'apolice_num')]);
            $model->setText('data', $extract['file_data']);

            //grava no db
            //obs: na função abaixo grava somente os dados que não foram alterados pelo usuário
            $PrSegService = new PrSegService;
            $r=$PrSegService->saveFromExtract($model->process_prod, $id, $extract['file_data']);
            if(!$r['success'])return ['success'=>false,'msg'=>$msg,'process_model'=>$model,'pdf_engine'=> ($extract['pdf_engine']??$opt['pdf_engine']) ,'code'=>'wbot01' ];
        }
        return ['success'=>$extract['success'],'msg'=>$msg,'process_model'=>$model,'pdf_engine'=> ($extract['pdf_engine']??$opt['pdf_engine']) ,'code'=>($extract['code']??''), 'data'=>$extract['file_data']  ];
    }


    /**
     * Faz apenas a extração do texto do pdf
     * @param int|object model $id - da tabela process_robot
     * @param object $file_info - (opcional) retorno da classe $this->getFileInfoPDF(). Aceita null para capturar automaticamente.
     * @param string $pdf_engine- (opcional)nome do recurso que irá processar o pdf. Padrão 'pdfparser' (mais informações na classe em \App\Utilities\FilesUtility::readPDF).
     * @param string $callback  - (opcional) deve ser informado o nome da classe com o callback retorno com o resultado das extrações offline (pelo AutoIt). Obs: mesmo informado, este callback é executado apenas nestes casos.
     * @return array - sucess, msg, file_text, engine, $file_info, process_model
     */
    public function extractOnlyText($id,$file_info=null,$pdf_engine=null,$callback=null){
        $model = is_object($id) ? $id : $this->ProcessRobotModel->find($id);

        if(empty($pdf_engine))$pdf_engine='auto';

        if(!$file_info){
            $file_info = $this->getFileInfoPDF($model);
            if(!$file_info['success'])return ['success'=>false,'msg'=>'Process File PDF: '.$file_info['msg'], 'process_model'=>$model,'pdf_engine'=>$pdf_engine];
        }

        $extract_count = $model->getData('extract_count');
        $extract_count = $extract_count ? (int)$extract_count : 0;

        $file_pass = $model->getData('file_pass');

        try{
            $n = FilesUtility::readPDF($file_info['file_path'], [
                'engine'=>$pdf_engine,
                'file_url'=>$file_info['file_url_notpriv'],
                'pass'=>$file_pass,
                'file_name'=>$file_info['file_original_name'],
            ]);
            //dd('x',$pdf_engine,$n);
            //if($pdf_engine!==$n['engine']){
                //$model->setData('pdf_engine_change','ws02_tmp');//adiciona o registro do pdf engine atual utilizado //obs: seta 'autoit_ws02' para que na linha ~ 1971, o comando "if($x!='' && $pdf_engine!='auto' && """$x!==$pdf_engine""")" seja verdadeiro...
            //};
            $pdf_engine=$n['engine'];
            $file_pass=$n['pass'];

            if(substr($pdf_engine,0,4)=='ait_'){//quer dizer que este será processado em outra máquina e deve aguardar o retorno da extração
                $model->delData('pdf_engine_change');//remove pois já existe um método de extração definido
                $this->addFileExtractText($model,$pdf_engine,$callback);//adiciona o registro para extração de arquivos
                return ['success'=>false,'msg'=>'Aguardando extração do texto offline', 'process_model'=>$model, 'process_wait'=>true,'pdf_engine'=>$pdf_engine];
            }

            $file_text = trim($n['text']);
            //verifica se foi carregado um texto válido (pois as vezes carrega o texto codificado e o método padrão pdfparser não consegue extrair)
            //e caso seja inválido tenta novamente usando o padrão java
            //ddx([$pdf_engine,$file_text]);exit;

            if(in_array($pdf_engine,['auto','pdfparser']) && (
                //precisa ter uma das strings abaixo para considerar válido
                stripos($file_text,'susep')===false ||
                stripos($file_text,'seguro')===false
            )){
                $model->delData('pdf_engine_change');//remove pois já existe um método de extração definido
                if($pdf_engine != 'ws02'){
                    $n = FilesUtility::readPDF($file_info['file_path'], [
                        'engine'=>'ws02',
                        'file_url'=>$file_info['file_url_notpriv'],
                        'pass'=>$file_pass,
                        'file_name'=>$file_info['file_original_name'],
                    ]);
                    $pdf_engine=$n['engine'];
                    $file_pass=$n['pass'];
                    //if(Auth::user() && Auth::user()->user_level=='dev')dd('c2a',$n);
                }

                if(substr($pdf_engine,0,4)=='ait_'){//quer dizer que este será processado em outra máquina e deve aguardar o retorno da extração
                    $this->addFileExtractText($model,$pdf_engine,$callback);//adiciona o registro para extração de arquivos
                    return ['success'=>false,'msg'=>'Aguardando extração do texto offline', 'process_model'=>$model, 'process_wait'=>true, 'pdf_engine'=>$pdf_engine];
                }
            }
            $file_text = trim($n['text']);

            if($file_text==''){//retorna para que seja extraído pelo modo ocr padrão, pois o arquivo pode ser uma imagem
                $pdf_engine='ait_ocr01';
                if($extract_count>1){//já realizou este processo pelo ait_ocr01, e se chegou até aqui é porque o texto ainda está vazio
                    return ['success'=>false,'msg'=>'Process File PDF: Texto do PDF vazio', 'process_model'=>$model, 'pdf_engine'=>$pdf_engine];
                }else{
                    $model->setData('pdf_extract_count',$extract_count+1);//adiciona o dado temporário para indicar que será extraído pelo ait_ocr01 porque o texto veio vazio (motivo: se o pdf de fato por vazio, este processo ficará em loop e este metadado é para evitar isto)
                    $model->setData('pdf_engine_change',$pdf_engine);//adiciona o registro do pdf engine atual utilizado
                    $this->addFileExtractText($model,$pdf_engine,$callback);//adiciona o registro para extração de arquivos
                    return ['success'=>false,'msg'=>'Aguardando extração do texto offline', 'process_model'=>$model, 'process_wait'=>true, 'pdf_engine'=>$pdf_engine];
                }
            }

        }catch(Exception $e){
            //dd($e->getMessage(),$e->getLine(),$e->getFile(),$e);
            return ['success'=>false,'msg'=>'Process File PDF: '.$e->getMessage() .' - Info: file: '.$e->getFile(). ', line '.$e->getLine(), 'process_model'=>$model, 'pdf_engine'=>$pdf_engine];
        }
        if(empty($file_text)){
            $model->delData('pdf_engine_change');//remove pois se chegou até aqui, é porque retornou a vazio em todas as verificações
            return ['success'=>false,'msg'=>'Process File PDF: Texto do PDF vazio', 'process_model'=>$model, 'pdf_engine'=>$pdf_engine];
        }
        //dd('e01',$file_pass,$pdf_engine,$file_text);
        if(is_array($file_pass))$file_pass = json_encode($file_pass);
        $model->setData('file_pass',$file_pass);

        return ['success'=>true,'msg'=>'Sucesso','file_text'=>$file_text,'pdf_engine'=>$pdf_engine,'file_info'=>$file_info,'process_model'=>$model];
    }

    /**
     * Extraí o texto do pdf e retorna ao conteúdo
     * @param int|object model $id - da tabela process_robot
     * @param booelan $force - ''|null xml já extraído, 'ok' processa sob o texto, 'all' extrai o texto e processa
     * @param string $pdf_engine - nome do recurso que irá processar o pdf. Padrão 'pdfparser' (mais informações na classe em \App\Utilities\FilesUtility::readPDF).
     * @param boolean $extract_test - se true indica que está apenas testando a extração e não deve efetuar alterações. Default false.
     * @return array - success, msg, process_model  //somente se success=true -> file_text, file_data, process_id,insurer_id, broker_id, insurer_model,insurer_model,process_model
     *                             , ignore         //(boolean) retornará a true para indicar que este registro deve vir marcado como ignorado (consulte o arquivo estrutura-sistema.xlsx para mais informações)
     */
    private function extractTextFromPdf($id,$force=null,$pdf_engine=null,$extract_test=false,$reprocessOpt=[]){
        //dd(FormatUtility::formatData(['meucampo'=>'123 abc'], ['meucampo'=>'type:int'], 'view'));
        /**
         * Lógica da extração do pdf:
         * 1) No upload é informado o nome do processo e produto, e depois é extraído por esta função
         * 2) Em seguida é extraído o texto do pdf pela funçao \App\Utilities\FilesUtility::readPDF(...,[engine=pdfparser])
         * 3) Nesta extração é identificado qual é o corretor e seguradora.
         * 4) Ao chamar a respectiva classe do processo em \App\ProcessRobot\{process_name}\Process{process_prodname}Class.php'
         *      verifica pela função $this->getPdfEngine() qual é o motor de extração do pdf e se for o caso repete todo este processo novamente (pois a extração padrão muitas vezes falha com alguns arquivos no recurso padrão)
         */

        $model = is_object($id) ? $id : $this->ProcessRobotModel->find($id);
        //prossegue somente se o registro tiver o status=0 - em processo de indexação de dados
        //captura o arquivo
        $file_info = $this->getFileInfoPDF($model);
        if(!$file_info['success']){
            return ['success'=>false,'msg'=>'Process File PDF: '.$file_info['msg'], 'process_model'=>$model,'code'=>'read07'];
        }

        $n = FilesUtility::numberPagesPDF($file_info['file_path']);
        if($n>200){//limite de páginas para leitura
            return ['success'=>false,'msg'=>'Process File PDF: '. self::getStatusCode('read20'), 'process_model'=>$model,'ignore'=>true,'code'=>'read20'];
        }

        if(empty($pdf_engine))$pdf_engine='auto';
        if($force===true)$force='all';
        if($force===false)$force='ok';

        //captura os metadados já salvos
        $metadata = $model->getData();//return array
        $pdf_engine_from_autoit = array_get($metadata,'pdf_engine')=='autoit_ws02';//indica que o campo $model->getText('text') já tem o texto extraído no padrão 'ws02' (java com pdfbox)
        //dump('----',[$metadata,$id,$force,$pdf_engine]);

        //captura o texto do pdf
        if($model->_tmp_reprocess===true){//quer dizer que foi chamado novamente esta função pela função re_extractTextFromPdf()
            $file_text='';
            unset($model->_tmp_reprocess);
        }else{
            $file_text = trim($model->getText('text'));
        }

        if(empty($file_text) || $force=='all'){//texto ainda não capturado do pdf
            if($extract_test)return ['success'=>false,'msg'=>'Teste de extração: texto vazio ou não encontrado'];

            $pdf_engine_from_autoit=false;//deixa false caso esteja como true
            $n=$this->extractOnlyText($model,$file_info,$pdf_engine);
            //dd('b',$n,$pdf_engine,$reprocessOpt,'-',$pdf_engine_from_autoit);
            if(!$n['success']){
                if($n['process_wait']??false){//quer dizer que o processo de extração é externo offline (pelo AutoIt)
                    $model->update(['process_status'=>'o']);    //Adiciona o registro do processo para o status='o' (ag. extração do texto) para execução de aplicação autoit
                }else{
                    if(stripos($n['msg'],'InvalidPasswordException')!==false || stripos($n['msg'],'Arquivo com senha')!==false){
                        $n['code'] = 'read06';//com senha
                    }else if(stripos($n['msg'],'permission')!==false){
                        $n['code'] = 'read18';//bloqueado
                    }else{
                        $n['code'] = 'read07';//erro de leitura
                    }
                }
                return $n;
            }
            $file_text = $n['file_text'];
            $pdf_engine = $n['pdf_engine'];

            /*
            Se $reprocessOpt quer dizer este método já foi reprocessado (pelo comando mais abaixo "return $this->extractTextFromPdf($id,$force,$x,$extract_test,['class_pdf_engine'=>$x]);")
                 por ter o processador do pdf diferente do que processou anteiormente, e neste novo reprocessamento o $pdf_engine do da extração
                 é diferente do pdf engine setado na classe da seguradora (class_pdf_engine).
            Isto ocorre pois o pdf da seguradora não pode ser convertido no formato engine informado na classe (incompatível).
                Basicamente somente irá ocorrer quando o pdf engine = 'pdfparser' e ao extrair não retornar a dados válidos
            Neste caso retorna a erro para o programador avaliar.
            */
            if($reprocessOpt && $reprocessOpt['class_pdf_engine']<>$pdf_engine){
                //dd($reprocessOpt['class_pdf_engine'],$pdf_engine);
                $model->setText('text','');//deixa o texto já capturado do pdf para que seja reprocessado no modo correto
                $model->delData('pdf_engine');//deleta este parâmetro pois só existia por causa da importação do txt já extraído do pdf automática pelo autoit
                return ['success'=>false,'msg'=>'PDF Engine incompatível - classe seguradora: '. $reprocessOpt['class_pdf_engine'] .' - conversão final: '.$pdf_engine, 'process_model'=>$model,'ignore'=>true,'code'=>'read19'];
            }

            if($pdf_engine_from_autoit)$model->delData('pdf_engine');//deleta este parâmetro pois só existe por causa da importação do txt já extraído do pdf automática pelo autoit
        }


        //verifica as strings que indica que o texto precisa ser extraído novamente
        if(in_array($pdf_engine,['ait_ocr01','ait_xpdfr','ait_ocr01_xpdfr','ait_ocr01_aws','ait_ocr01_tessrct','ait_aws','ait_tessrct'])){
            foreach(CadApoliceReadTextVar::$re_extract as $str){
                if(stripos($file_text, $str)!==false){
                    $model->update(['process_status'=>'o']);    //Adiciona o registro do processo para o status='o' (ag. extração do texto) para execução de aplicação autoit
                    $model->setText('text','');//deixa o texto já capturado do pdf para que seja reprocessado no modo correto
                    return ['success'=>false,'msg'=>'Aguardando extração do texto offline', 'process_model'=>$model, 'process_wait'=>true,'pdf_engine'=>$pdf_engine,'code'=>'read07'];
                }
            }
        }

        $tmp = FormatUtility::sanitizeBreakText($file_text);
        $tmp = str_replace(['  ','  ','  ','  ','  ','  ','  ','  ','  '],' ',$tmp);//tira os espaços extras


        //verifica as strings que indica que o texto deve ser ignorado
        foreach(CadApoliceReadTextVar::$is_ignore as $arr){
            $str = $arr['str'];
            if(substr($str,0,6)=='regex:'){//expressão regular
                $str=substr($str,6);
                //dd($str,$tmp,preg_match_all($str,$tmp));
                if(preg_match_all($str,$tmp)){
                    return ['success'=>false,'msg'=>self::getStatusCode($arr['code'],false), 'process_model'=>$model,'ignore'=>true,'code'=>$arr['code']];
                }
            }elseif(stripos($tmp, $str)!==false){
                return ['success'=>false,'msg'=>self::getStatusCode($arr['code'],false), 'process_model'=>$model,'ignore'=>true,'code'=>$arr['code']];
            }
        }

        //verifica as strings que indica que é uma seguradora que o robô não trabalha / deve ser ignorado
        foreach(CadApoliceReadTextVar::$ignore_insurer as $str){
            if(stripos($tmp, $str)!==false){
                return ['success'=>false,'msg'=>'Seguradora ignorada', 'process_model'=>$model,'ignore'=>true,'code'=>'ins05'];
            }
        }

        //dd('passou ');


        //teste
        //\App\ProcessRobot\DetectTypeProcessRobot::getRamo($file_text);

        $InsurerModel=null;
        $BrokerModel=null;

        //*** Verifica se o arquivo é do tipo histórico da apólice ***
                $n = '\\App\\ProcessRobot\\'.$model->process_name.'\\Process'.ucfirst($model->process_prod).'TypesClass';
                $detectFileType='apolice';
                if(class_exists($n)){
                    $n=$n::getTypes($file_text);

                    if($n['type']=='historico'){//é histórico
                        $detectFileType='historico';
                        if(!$n['success']){
                            //deu algum erro portanto reprocessa
                            if($pdf_engine_from_autoit){
                                if($extract_test)return ['success'=>false,'msg'=>'Teste de extração: erro no reprocessamento (1)'];
                                return $this->re_extractTextFromPdf($model,$force,$pdf_engine);
                            }else{
                                $n['process_model']=$model;
                                return $n;
                            }
                        }
                        //*** lógica abaixo: adiciona na string $file_text, o documento da seguradora e SUSEP do corretor para que passe pelas verificações abaixo ***
                            //procura a seguradora com base no basename de identificação
                            $InsurerModel = $this->InsurerModel->where('insurer_basename',$n['seguradora_nome'])->first();
                            if(!$InsurerModel){
                                if($pdf_engine_from_autoit){
                                    if($extract_test)return ['success'=>false,'msg'=>'Teste de extração: erro no reprocessamento (2)'];
                                    return $this->re_extractTextFromPdf($model,$force,$pdf_engine);
                                }else{
                                    return ['success'=>false,'msg'=>'Histórico: Seguradora não encontrada com o nome: '.$n['data']['seguradora_nome'], 'process_model'=>$model,'code'=>'ins01'];
                                }
                            }
                            $file_text.=chr(10).$InsurerModel->insurer_doc;//adiciona o número do cnpj da seguradora
                            //procura o corretor pelo cnpj
                            $doc = str_replace(['.','/','-'],['','',''],$n['corretor_cpf_cnpj']);
                            $doc = ltrim($doc,'0');
                            //dd($doc);
                            $BrokerModel = $this->BrokerModel->whereRaw('TRIM(LEADING "0" FROM REPLACE(REPLACE(REPLACE(broker_cpf_cnpj,"/",""),"-",""),".",""))=?',[$doc])->first();
                            if(!$BrokerModel){
                                if($pdf_engine_from_autoit){
                                    if($extract_test)return ['success'=>false,'msg'=>'Teste de extração: erro no reprocessamento (3)'];
                                    return $this->re_extractTextFromPdf($model,$force,$pdf_engine);
                                }else{
                                    return ['success'=>false,'msg'=>'Histórico: Corretor não encontrado para o documento: '.$n['corretor_cpf_cnpj'], 'process_model'=>$model,'code'=>'bro01'];
                                }
                            }
                            $file_text.=chr(10).$BrokerModel->broker_doc;//adiciona o número do susep do corretor
                    }
                }
        //end


        //*** faz a leitura das informações do texto do pdf ***
        //localiza qual é a seguradora
        $insurer_id=$model->insurer_id;
        if(!$InsurerModel){
            if(empty($insurer_id) || $force){
                $r=$this->checkExistsByDoc($model->account_id,$file_text,'Insurer');
                //dd($r);
                if(!$r['success'] && $r['code']=='ins01'){//quer dizer que nenhuma seguradora foi encontrada
                    //é provável a var $file_text esteja com o texto assim 'n o m e do ...' (extração padrão em ws02 (caso SOMPO)) e isto irá impedir que a função $this->checkExistsByDoc() faça identifique a seguradora pelo cnpj/supsep do corretor
                    //somente para esta situação, remove de $file_text todos os espaços para verificar se mesmo assim encontra o cnpj/susep
                    //obs: isto é aplicado somente neste IF pois se retirar os espaços na primeira verificação acima, é propávél que para números curtos (como SUSEP) acabe encontrando em trechos que não correspondem realmente a este número (ex SUSEP 1234, acaba encontrando em uma string qualquer Núm Controle: abc1234def...)
                    $r=$this->checkExistsByDoc($model->account_id,$file_text,'Insurer','doc','clean');
                }

                if(!$r['success']){
                    /*///verifica e remover este código
                    //verifica se é um tipo inválido, como: endosso
                    if(stripos($file_text,'ENDOSSO DE CANCELAMENTO')!==false){
                        return ['success'=>false,'msg'=>'Apólice do tipo Endosso - não processado (2)', 'process_model'=>$model,'file_text'=>$file_text,'ignore'=>true,'code'=>'read03'];
                    }*/

                    if($pdf_engine_from_autoit){
                        if($extract_test)return ['success'=>false,'msg'=>'Teste de extração: erro no reprocessamento (4)'];
                        return $this->re_extractTextFromPdf($model,$force,$pdf_engine);
                    }else{
                        $r['process_model']=$model;
                        $r['file_text']=$file_text;
                        if($r['code']!='ins03')$r['ignore']=true;
                        return $r;
                    }
                }else{
                    $insurer_id = $r['id'];
                }
            }

            //captura o registro da seguradora responsável para localizar seu processo/classe
            if($insurer_id)$InsurerModel = $this->InsurerModel->find($insurer_id);
            if(!$InsurerModel){
                return ['success'=>false,'msg'=>'Process File PDF: seguradora '.$model->insurer_id.' não localizado em process_robot', 'process_model'=>$model,'file_text'=>$file_text,'ignore'=>true,'code'=>'ins01'];
            }
        }
        if(!$insurer_id)$insurer_id=$InsurerModel->id;
        //dd('A',$pdf_engine,,$file_text,$pdf_engine_from_autoit,$model);
        //dd($insurer_id,$InsurerModel);
        if($InsurerModel->insurer_status!='a'){
            return ['success'=>false,'msg'=>'Process File PDF: seguradora '. $InsurerModel->insurer_alias .' ('. $InsurerModel->id .') não disponível para processamento', 'process_model'=>$model,'file_text'=>$file_text,'ignore'=>true,'code'=>'ins02'];
        }

        /* //em desenvolvimento. Analisando a real necessidade
        //bloqueio temporário por seguradora
            $arr = VarsProcessRobot::$configProcessNames[$model->process_name]['products'][$model->process_prod]??null;
            if($arr)$arr=$arr['insurers_stop']??null;
            if($arr && !in_array($InsurerModel->insurer_basename,$arr))return ['success'=>false,'msg'=>'PDF bloqueado para leitura para esta seguradora', 'process_model'=>$model,'file_text'=>$file_text,'ignore'=>true,'code'=>'ins06'];
        */


        //verifica se existe classe já programada para processar produto e seguradora
            $arr = VarsProcessRobot::$configProcessNames[$model->process_name]['products'][$model->process_prod]??null;
            if($arr)$arr=$arr['insurers_allow']??null;
            if($arr && !in_array($InsurerModel->insurer_basename,$arr))return ['success'=>false,'msg'=>'Seguradora ou produto não programado', 'process_model'=>$model,'file_text'=>$file_text,'ignore'=>true,'code'=>'ins04'];


        //captura o process/classe responsável para converter os dados em php
        $class_label1 = $model->process_name.'\\'.$model->process_prod.'\\'.$InsurerModel->insurer_basename;
        $class = '\\App\\ProcessRobot\\'.$model->process_name.'\\'.$model->process_prod.'\\'.$InsurerModel->insurer_basename . ($detectFileType!='apolice'?ucfirst($detectFileType):'') .'Class';

        if(!class_exists($class)){
            if($pdf_engine_from_autoit){
                if($extract_test)return ['success'=>false,'msg'=>'Teste de extração: erro no reprocessamento (5)'];
                return $this->re_extractTextFromPdf($model,$force,$pdf_engine);
            }else{
                return ['success'=>false,'msg'=>'Process File PDF: classe não encontrada '.$class, 'process_model'=>$model,'file_text'=>$file_text,'ignore'=>true,'code'=>'err'];
            }
        }

        $class = App($class,[
            'model'=>$model,
            'extract_test'=>$extract_test,
        ]);

        //verifica se o processador do pdf é o mesmo do padrão configurado para a respectiva classe, e se não executa esta função novamente
        $x = $class->getPdfEngine();
        $y = $model->getData('pdf_engine_change');
        //dd($x,$y,$force);
        //dd($class,$x,$pdf_engine,$y,$pdf_engine_from_autoit);
        if($y && $y!=$x){
            $n = $model->getData('extract_count2');
            $n = $n ? (int)$n : 0;
            if($n===0){
                $model->setData('extract_count2',$n+1);
                if($extract_test)return ['success'=>false,'msg'=>'Teste de extração: requer nova extração de texto (2) para '.$x,'pdf_engine'=>$x];
                return $this->re_extractTextFromPdf($model,$force,$x);
            }else{
                //quer dizer que o método de leitura foi alterado para algum modo ocr pois não foi possível ler a apólice no método padrão (pdfparser ou ws02)
                //e portanto como o novo método não é compatível com o método da classe da seguradora encontrada, retornar a erro
                $model->delData('extract_count2');
                return ['success'=>false,'msg'=>'Método de extração incompatível com o informado pela classe', 'process_model'=>$model,'file_text'=>$file_text,'code'=>'extr04'];
            }

        }elseif($pdf_engine_from_autoit && $x!='ws02'){//embora tenha processado tudo certo até aqui, o texto do pdf veio extraído pelo app local autoit no padrão java/ws02 (enviado junto com o pdf por FTP (pelo processo da classe ProcessSeguradoraFilesController@doExtract) e o processador programado desta classe é diferente de ws02 e por isto deve ser reprocessado
            if($extract_test)return ['success'=>false,'msg'=>'Teste de extração: requer nova extração de texto (1) para '.$x,'pdf_engine'=>$x];
            $model->delData('extract_count2');
            return $this->re_extractTextFromPdf($model,$force,$x);

        }else if($x!='' && $pdf_engine!='auto' && $x!==$pdf_engine){//quer dizer que o processador do pdf é diferente do que processou atualmente, e portanto refaz todo o processo
            //dd('f',$extract_test,$id,$force,$x,$extract_test,['class_pdf_engine'=>$x]);
            if($extract_test)return ['success'=>false,'msg'=>'Teste de extração: requer nova extração de texto (2) para '.$x,'pdf_engine'=>$x];
            $model->delData('extract_count2');
            return $this->extractTextFromPdf($id,$force,$x,$extract_test,['class_pdf_engine'=>$x]);
        }

        //passou: quer dizer que o metadado extract_count2 não é mais necessário
        $model->delData('extract_count2');

        //captura a configuração da apólice específica da conta atual
        $config_cad_apolice = AccountsService::getCadApoliceConfig($model->account);

        //processa o texto o pdf
        $file_pass_ok = $metadata['file_pass']??null;if($file_pass_ok)$file_pass_ok=json_decode($file_pass_ok);
        $data = $class->process($file_text,[
            'path'=>$file_info['file_path'],
            'pass'=>$file_pass_ok,
            'url'=>$file_info['file_url_notpriv'],
            'venc_1a_parc_cartao' => ($config_cad_apolice['venc_1a_parc_cartao']??null),
            'venc_1a_parc_debito' => ($config_cad_apolice['venc_1a_parc_debito']??null),
            'venc_1a_parc_boleto' => ($config_cad_apolice['venc_1a_parc_boleto']??null),
            'venc_1a_parc_1boleto_debito' => ($config_cad_apolice['venc_1a_parc_1boleto_debito']??null),
            'venc_1a_parc_1boleto_cartao' => ($config_cad_apolice['venc_1a_parc_1boleto_cartao']??null),
            'venc_ua_parc' => ($config_cad_apolice['venc_ua_parc']??null),
        ]);
        //if(Auth::user()->id==1)dd('x',$data);
        if(($data['code']??'')=='endosso')$data['code']='read03';//corrigi o valor do código

        if(empty($data['code'])){
            return ['success'=>false,'msg'=> ($data['msg']??'Parâmetro CODE não retornado na classe da seguradora') , 'process_model'=>$model,'code'=>'extr01','file_data'=>$data];
        }

        if(in_array($data['code'],[
            'read02',//ramo inválido
            'read03',//endosso
            'read04',//frota
        ])){
            return ['success'=>false,'msg'=>self::getStatusCode($data['code'],false), 'process_model'=>$model,'ignore'=>true,'code'=>$data['code']];
        }


        //retorno padrão para os casos abaixo
        $return = ['success'=>$data['success'],'msg'=>$data['msg'],'process_model'=>$model,'file_text'=>$file_text,'file_data'=>$data,'validate'=>($data['validate']??null),'code'=>($data['code']??'ok')];
        if($data['ignore']??false)$return['ignore']=true;
        if($data['code']??false)$return['code']=$data['code'];

        //*** verifica se as informações estão corretas ***
        if(!is_array($data))return array_merge($return,['msg'=>'Process File PDF: erro na leitura do texto do pdf pela classe '.$class_label1,'ignore'=>true,'code'=>'read07']);

        //???if(array_get($data,'success')==false)return array_merge($return,['msg'=>$data['msg']]);

        //retornou um array como esperado
        $data=$data['data'];
        $return['file_data']=$data;
        //dd($return);


        /*if(!$extract_test){//no teste, não precisa verificar se o corretor está cancelado
            if($BrokerModel->broker_status=='c'){
                return ['success'=>false,'msg'=>'Process File PDF: Corretor cancelado no sistema', 'process_model'=>$model,'file_text'=>$file_text, 'file_data'=>$data];
            }
        }*/
        if($detectFileType=='apolice'){//obs: faz somente se for apólice, pois se for histórico não tem como verificar (pois o documento da seguradora não consta no arquivo da apólice)
            //verifica se acha algum dos cnpjs das seguradoras registrado em $data['seguradora_doc'], para garantir a exatidão do processo
            $docs=explode(',',$InsurerModel->insurer_doc);
            $idFindInsurer=false;
            //if(Auth::user()->id==1)dd('x',$docs,$data);
            $n=array_get($data,'seguradora_doc');
            foreach($docs as $doc){
                if(FormatUtility::extractNumbers($n)==FormatUtility::extractNumbers($doc)){
                    $idFindInsurer=true;
                    break;
                }
            }
            if(!$idFindInsurer){
                if($return['ignore']??false){
                    return $return;
                }else{
                    return array_merge($return,['msg'=>'Process File PDF: CNPJ da Seguradora não compatível','code'=>'ins01']);
                }
            }
        }


        //*** verifica o corretor ***
        $broker_id=null;
        $tmp=null;
        if($model->getData('broker_manual')=='s'){//quer dizer que o corretor foi atualizado manualmente, portanto captura o que está no cadastro do corretor
            $BrokerModel = $this->BrokerModel->find($model->broker_id);
            //dd($BrokerModel);
        }
        if(!$BrokerModel){
            $n=$data['corretor_susep'];
            if($n){
                $r=$this->checkExistsByDoc($model->account_id,$n,'Broker');
                /*if(!$r['success']){
                    return ['success'=>false,'msg'=>'Process File PDF: ' . $r['msg'], 'process_model'=>$model,'file_text'=>$file_text, 'file_data'=>$data];
                }*/
                if($r['success']){
                    $broker_id = $r['id'];
                }else{
                    $tmp=$r['code'];
                }
            }/*else{
                return ['success'=>false,'msg'=>'Process File PDF: Corretor não encontrado pelo campo CORRETOR_SUSEP', 'process_model'=>$model,'file_text'=>$file_text, 'file_data'=>$data];
            }*/
            //captura o registro do corretor
            $BrokerModel = $broker_id ? $this->BrokerModel->find($broker_id) : null;
            /*if(!$BrokerModel){
                return ['success'=>false,'msg'=>'Process File PDF: Corretor '.$model->broker_id.' não localizado em process_robot', 'process_model'=>$model,'file_text'=>$file_text, 'file_data'=>$data];
            }*/
        }else{
            $broker_id = $BrokerModel->id;
        }
        if($return['code']=='ok' || $return['success']==true)
            if(!$broker_id){
                $return['success']=false;
                $return['code']=$tmp??'bro01';
            }

        //verifica se o corretor tem permissão para este produto
        if($BrokerModel){
            if($BrokerModel->broker_status=='c')return ['success'=>false,'msg'=>'Corretor cancelado', 'process_model'=>$model,'code'=>'bro02','ignore'=>true];
            if(!$this->allowBrokerProducts($BrokerModel,$model->process_prod))return ['success'=>false,'msg'=>'Process File PDF: Corretor sem permissão para o produto da apólice', 'process_model'=>$model,'code'=>'bro04','ignore'=>true];
        }


        if($detectFileType=='historico' && $BrokerModel){
            //atualiza os campos que não são possíveis de serem capturados
            $data['corretor_nome']=$BrokerModel->broker_name;
            $data['corretor_susep']=$BrokerModel->broker_doc;
            $data['seguradora_doc']=$InsurerModel->insurer_doc;
        }

        //até aqui deu tudo certo, portanto remove o parâmetro caso exista (de reprocessamento desta função)2
        if($pdf_engine_from_autoit)$model->delData('pdf_engine');


        if($return['code']=='read01' && array_get($return['validate'],'corretor_susep')!=''){//quer dizer que o corretor não foi encontrado
            $return['code']='bro01';//altera o código do erro
        }


        //***** atualiza os campos de acordo com a configuração do cadastro da apólice *****
            //atualiza o padrão do número da apólice para o padrão da respectiva classe
            //$broker_num_quiver=null;    //falta programar a respectiva customização por corretor

            $numQuiverConfig = array_get($config_cad_apolice,'num_quiver_'. $model->process_prod,[]);
            if(($numQuiverConfig['def']??false)==true){
                //captura a configuração padrão
                $numQuiverConfig = array_get($config_cad_apolice,'num_quiver.'.$InsurerModel->id);
                unset($numQuiverConfig['def']);
            }else{
                //captura a seguradora
                $numQuiverConfig=$numQuiverConfig[$InsurerModel->id]??[];
            }
            $numQuiverConfig = array_merge($class->numQuiverConfig(),FormatUtility::array_ignore_null($numQuiverConfig));
            $n = \App\ProcessRobot\cad_apolice\Classes\Data\NumQuiverData::process($data['apolice_num'], $numQuiverConfig );
            //dd($data['apolice_num'],$n);
            $data['apolice_num_quiver'] = substr($n,0,20);//limite de caracteres no banco



        //valida o padrão do número da apólice gerado
        $n = ValidateUtility::validateData(['apolice_num_quiver'=>$data['apolice_num_quiver']], array_only(\App\ProcessRobot\cad_apolice\Classes\Segs\SegDados::fields_rules_extract(), ['apolice_num_quiver']));
        if($n!==true){
            $return['success']=false;
            $return['msg']=self::getStatusCode('extr03',false);
            $return['code']='extr03';
            if(!$return['validate'])$return['validate']=[];
            $return['validate'] = $return['validate'] + $n;
            //dd($data['validate']);
            //$return = ['success'=>$data['success'],'msg'=>$data['msg'],'process_model'=>$model,'file_text'=>$file_text,'file_data'=>$data,'validate'=>($data['validate']??null),'code'=>($data['code']??'ok')];
        }
        //dd( $data['apolice_num_quiver'], $n, $data );


        //dd($return['code'],$return['validate'],$broker_id,$BrokerModel);
        return [
            'success'=>$return['success'],
            'code'=>$return['code'],
            'msg'=>$return['msg'] ?? 'Extraído com sucesso',
            'validate'=>$return['validate'],
            'process_id'=>$model->id,
            'insurer_id'=>$insurer_id,
            'broker_id'=>$broker_id,
            'file_data'=>$data,
            'file_text'=>$file_text,
            'insurer_model'=>$InsurerModel,
            'insurer_model'=>$BrokerModel,
            'process_model'=>$model,
            'pdf_engine'=>$pdf_engine
        ];
    }
    //auxiliar de extractTextFromPdf() - serve apenas para o caso de txts vindos do autoit (mais detalhes veja na lógica desta funçao extractTextFromPdf())
    private function re_extractTextFromPdf($model,$force=null,$pdf_engine=null){
        $model->setText('text','');//deixa o texto já capturado do pdf para que seja reprocessado no modo correto
        $model->delData('pdf_engine');//deleta este parâmetro pois só exisita por causa da importação do txt já extraído do pdf automática pelo autoit
        $model->_tmp_reprocess=true;
        return $this->extractTextFromPdf($model,$force,$pdf_engine);//faz o reprocessamento novamente
    }



    /**
     * Retorna ao caminho do arquivo da apólice
     * @param int|object model $id - da tabela process_robot
     */
    private function getFileInfoPDF($id){
        $file_ext='pdf';
        $model = is_object($id) ? $id : $this->ProcessRobotModel->find($id);

        if(!$model){
            if(in_array(Auth::user()->user_level,['dev','superadmin'])){//pode ocorrer de um dev|superadmin ter tentado acessar arquivo de outra conta
                $model = $this->ProcessRobotModel->withoutGlobalScope('account_user')->find($id);
                if(!$model)return ['success'=>false,'msg'=>'Arquivo não encontro ou permissão negada'];
            }else{//sem permissão
                return ['success'=>false,'msg'=>'Arquivo não encontro ou permissão negada'];
            }
        }

        $path = $model->baseDir();
        $data = $model->getData();

        //obs: ajusta o nome original do arquivo para um formato slug
        $filename = $model->id.'.'.$file_ext;
        $filename_original = substr(array_get($data,'file_original_name',$filename),0,strlen($file_ext)*-1);
        $tmp = str_slug(str_replace('.','-', $filename_original));
        if(empty($tmp)){//quer dizer que ocorreu algum erro (provavelmente codificação) e a variável ficou vazia
            $tmp = str_slug(str_replace('.','-', utf8_encode($filename_original)));
        }
        if(empty($tmp)){//se ainda estiver vazio, gera um nome padrão com o id do processo
            $tmp = $model->id;
        }
        $filename_original = $tmp.'.'.$file_ext;

        $f = $path['dir'].'/'.$path['date_dir'].'/'.$path['folder_id'];
        $p = $f.'/'.$filename;
        $u = route( (\Config::adminPrefix()=='super-admin'?'super-admin':'admin') .'.app.get',['process_cad_apolice','fileload',$model->id]);

        //$u_np = route('site.app.get',['process_cad_apolice','robot_file_load',base64_encode(serialize(['user'=>$model->user_id,'process'=>$model->id]))]);
        $u_np = route('process_robot_fileload',['cad_apolice',base64_encode(serialize(['user'=>$model->user_id,'process'=>$model->id,'token'=>($data['token']??null) ])), $filename_original]);


        //tira da área ssl (motivo: os webservices não estão compatíveis para ler remotamente arquivos dentro do https)
        $u_np = str_replace('https://','http://',$u_np);

        $n=$data['file_original_name']??'';
        if(substr($n, strrpos($n,'.')) != '.'.$file_ext){
            $n=str_replace('.',' ',$n);
            $n=substr($n,0,strlen($n)-strlen($file_ext)-1);
            $n.='.'.$file_ext;
        }
        $data['file_original_name']=$n;
        //dd($n,strlen($n));

        if(file_exists($p)){
            $r=[
                'success'=>true,
                'file_url'=>$u,             //autenticada
                'file_url_notpriv'=>$u_np,   //não autenticada
                'file_path'=>$p,
                'file_folder'=>$f,
                'file_mimetype'=>mime_content_type($p),
                'file_size'=>filesize($p),
                'file_name'=>$filename,
                'file_original_name'=>$data['file_original_name'],
                'model'=>$model
            ];
        }else{
            $r=['success'=>false,'msg'=>'Arquivo '. $path['relative_dir'].'/'.$path['date_dir'].'/'.$model->id.'.'.$file_ext .' não localizado'];
        }

        //dd($r,$p);
        return $r;
    }


    /**
     * Carrega a visualização do arquivo PDF
     * @param $process_name - será sempre 'cad_apolice'
     */
    public function get_fileload($process_id){
        $file = $this->getFileInfoPDF($process_id);

        if(!$file['success']){
            header('HTTP/1.0 404 Not Found');
            if(in_array(Auth::user()->user_level,['dev','superadmin'])){
                exit('Erro ao acessar arquivo - '.$file['msg']);
            }else{
                exit('Erro ao acessar arquivo');
            }
        }

        $pass = $file['model']->getData('file_pass');

        /*
        //if(\Request::input('test')=='ok')dd($file['file_path'],$file['file_mimetype'],$file['file_size'],filesize($file['file_path']));
        header('Content-type: '.$file['file_mimetype']);
        header("Content-Length: " . $file['file_size']);
        header('Content-Disposition:inline; filename="'.$file['file_original_name'].'"');
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        @readfile($file['file_path']);
        */
        return response()->file($file['file_path'], [
            'Content-Disposition' => 'inline; filename="'. $file['file_original_name'] .'"',
        ]);
    }


    /**
     * Verifica se existe o cadastro do corretor/seguradora a partir do documento no texto do pdf
     * @param string $text - texto do pdf
     * @param string $table - 'Insurer' ou 'Broker'
     * @param string $field - campo a ser procurado, valores 'doc', 'cpf_cnpj', ...
     * @parma string $method - método de verificação, valores:
     *                          default - limpa $text retirando apenas as quebras de linhas, espaços extras, etc, e o cpf/cnpj/susep deve estar separados por espaços laterais
     *                          clean   - o mesmo de 'default' + retira de $text todos os espaços entre as palavras e verifica o cpf/cnpj/susep apenas se existe no texto (pois irá existir espaços separadores)
     * @return array    - se error [success=>false,msg,code]
     *                  - se ok    [success=>true,id=>(table.id)]
     */
    private function checkExistsByDoc($account_id,$text,$table,$field='doc',$method='default'){
        $tablelr=strtolower($table);
        $mtd = $table.'Model';

        if($table=='Broker'){
            $model = $this->$mtd->where('account_id',$account_id)->get();
        }else{//insurer
            $model = $this->$mtd->get();//obs: não filtra por status, pois aqui precisa identificar que a apólice está cancelada (e marcar esta opção no registro deste processo)
        }

        $finds_model=[];
        $code_prefix=substr(strtolower($table),0,3);//ex: 'bro' ou 'ins'
        //formata caracteres que geralmente dão problema na comparação
        //retira todos os espaços, pois em alguns textos vem espaços extras por causa da extração em ocr
        //obs: trocar "'" por '/', pois em alguns textos o cnpj vem assim: 99.999.999'99
        $text0 = str_replace([chr(10),chr(13),chr(9),'  ','  '],[' ',' ',' ',' ',' '],$text);
        //$text = str_ireplace(['−',' ','o',"'",chr(10),chr(13),chr(9)],['-','','0','/','','',' '],$text);
        $text = str_ireplace(['−','o',"'",chr(10),chr(13),chr(9)],['-','0','/',' ',' ',' '],$text);
        $text = str_replace(['  ','  ','  ','  ','  ','  ','  ','  '],' ',$text);

        //para $method=clean retira todos os espaços e quebras de linha
        if($method=='clean')$text = str_ireplace([' ',chr(10),chr(13),chr(9)],'',$text);

        if($model->count()==0){
            return ['success'=>false,'msg'=>VarsProcessRobot::$tableNames[$tablelr]['singular_name'] .' não cadastrado no sistema', 'code'=>$code_prefix.'01'];
        }else{
            $field_doc = $tablelr.'_'.$field;

            if($table=='Broker'){
                //$text - já contém apenas susep para verificar
                foreach($model as $reg){//procura em todos os registros qual deles existe no documento
                    $docs = explode(',',$reg->$field_doc);//captura o campo de documento com formataÃ§Ã£o
                    foreach($docs as $doc){
                        $tmp = ltrim(trim($doc),'0');//retira os zeros da esquerda
                        if($tmp && strpos($text,$tmp)!==false){//achou
                            $finds_model[]=$reg;
                            break;
                        }
                    }
                }

            }else{//table = Insurer
                //if(Auth::user() && Auth::user()->id==1)dd($model,$text);
                //$text - contém todo o texto do pdf para verificar
                foreach($model as $reg){//procura em todos os registros qual deles existe no documento
                    $docs = explode(',',$reg->$field_doc);//captura o campo de documento com formatação
                    $t=false;
                    foreach($docs as $doc){
                        //$tmp = ltrim(trim($doc),'0');//retira os zeros da esquerda
                        $tmp = $doc;
                        if(!$tmp)continue;

                        if($method=='clean'){
                            //deve verificar somente pela função instr(), pois não existem espaços laterais no texto
                            if(stripos($text,$tmp)!==false){
                                $t=true;
                                if(!in_array((string)$reg->id,$finds_model))$finds_model[$reg->id]=$reg;
                            }

                        }elseif(is_numeric(str_replace(['-','.','/',' '],'',$tmp))){//quer dizer que o texto procurado é um CNPJ, SUSEP ou qualquer outro documento
                            //if($reg->id==4 && $tmp=='61.550.141/0016-59')dump($tmp,$text);
                                //lógica regx: deve coresponder ao valor, sem ter números grupados, mas aceita textos e quebras de linhas laterais
                                $n=str_replace(['/','.'],['\/','\.'],$tmp);
                                preg_match_all('/([^0-9]('.$n.')[^0-9])|([^0-9]'.$n.'$)|(^'.$n.'[^0-9])|(^'.$n.'$)/',$text,$matches);
                            //if($reg->id==4 && $tmp=='61.550.141/0016-59')dd($matches);
                                if(empty($matches[0]))continue;
                                foreach($matches[0] as $matc){//loop das datas encontradas no texto
                                    if($matc && strpos($matc,$tmp)!==false){//achou
                                        $t=true;
                                        if(!in_array((string)$reg->id,$finds_model))$finds_model[$reg->id]=$reg;
                                        //dump([$reg->insurer_basename,$tmp,$matc]);
                                        break;
                                    }
                                }
                                if($t)break;
                        }else{//quer dizer que o texto procurado é uma string
                            //portanto apenas verifica no texto original se existe a string em $tmp
                            if(stripos($text0,$tmp)!==false){
                                $t=true;
                                if(!in_array((string)$reg->id,$finds_model))$finds_model[$reg->id]=$reg;
                            }
                        }
                    }
                }
            }


            //if($table=='Broker')dd($finds_model,$text);
            if(count($finds_model)==1){
                return ['success'=>true,'id'=>array_first($finds_model)->id];
            }else if($table=='Insurer' && count($finds_model)>1){
                //verifica pelas regras adicionais
                $find=[];
                foreach($finds_model as $reg){
                    $n=$reg->insurer_find_rule;
                    if($n){
                        $allows=$denies=0;
                        $n=json_decode($n,true);
                        if(!empty($n['allows'])){//verifica as strings aceitas
                            foreach($n['allows'] as $a){
                                if(stripos($text0,$a)!==false)$allows++;
                            }
                            if(count($n['allows'])!=$allows)$allows=-1;//a quantidade de itens comparados tem que ser igual (operador AND)
                        }
                        if(!empty($n['denies'])){//verifica as strings negadas
                            foreach($n['denies'] as $a){
                                if(stripos($text0,$a)!==false)$denies++;
                            }
                        }
                        //dump([$reg->insurer_alias,$allows,$denies]);
                        if($denies>0 || $allows==-1)continue;//está negado ou não está aceito

                        $find[]=$reg;
                    }else{//não tem regras adicionais, portanto apenas adiciona como registro encontrado
                        $find[]=$reg;
                    }
                }
                //dd($find);
                //if(Auth::user() && Auth::user()->user_level=='dev')dd(Collect($find)->pluck('insurer_basename')->toArray());
                if(count($find)==1){
                    return ['success'=>true,'id'=>$find[0]->id];
                }else{
                    return ['success'=>false,'msg'=>'Mais de um '. VarsProcessRobot::$tableNames[$tablelr]['singular_name'] .' encontrado no texto do PDF ('. Collect($find)->pluck('insurer_basename')->implode(', ') .')', 'code'=>$code_prefix.'03'];
                }
            }else if(count($finds_model)>1){
                    return ['success'=>false,'msg'=>'Mais de um '. VarsProcessRobot::$tableNames[$tablelr]['singular_name'] .' encontrado no texto do PDF ('. Collect($finds_model)->pluck('insurer_basename')->implode(', ') .')', 'code'=>$code_prefix.'03'];
            }else{
                return ['success'=>false,'msg'=>VarsProcessRobot::$tableNames[$tablelr]['singular_name'].' não encontrado do texto do PDF', 'code'=>$code_prefix.'01'];
            }
        }
    }


    /**
     * Permite adicionar um texto de observação do administrador (dev ou superadmin) para o operador (admin ou user)
     */
    public function post_obsEdit(Request $req){
        $userLogged = Auth::user();
        if(!in_array($userLogged->user_level,['dev','superadmin']))return ['success'=>false,'msg'=>'Permissão negada'];
        $area = $req->area;
        if(!in_array($area,['admin','operator']))return ['success'=>false,'msg'=>'Parâmetro area inválida'];

        $m = $this->ProcessRobotModel->find($req->input('process_id'));
        $obs = $req->input('obs');
        \App\Services\MetadataService::set('process_robot', $m->id, 'obs_'.$area, $obs);
        $m->addLog('manual',"Alteração de Observação:\n".$obs);

        return ['success'=>true];
    }

    /**
     * Permite atualizar o texto base que é utilizado para extrair os campos da apólice.
     * Permitido apenas para usuaário nível desenvolvedor
     * @param Request $request
     */
    public function post_updFileText(Request $request){
        if(Auth::user()->user_level!='dev')return ['success'=>false,'msg'=>'Permissão negada'];
        $process_id = $request->input('process_id');

        $file_text = $request->input('file_text');
        if(empty($file_text))return  ['success'=>false,'msg'=>'Texto inválido'];

        $model = $this->ProcessRobotModel->find($process_id);
        $model->setText('text',$file_text);
        if($request->input('upd_status')=='s')$model->update(['process_status'=>'0']);//altera o status para indexação de dados
        $model->addLog('edit','Alterado texto base da apólice para: ('. strlen($file_text) .') '. str_limit($file_text,100));
        return ['success'=>true];
    }

    /**
     * Atualiza campos extras
     */
    public function post_updateCustomFields(Request $request){
        //if($user_level!='dev')return ['success'=>false,'msg'=>'Permissão negada'];
        $qid = $request->input('quiver_id');
        $model=$this->ProcessRobotModel->find($request->input('process_id'));
        if($qid=='remove'){
            $model->delData('quiver_id');
            $model->addLog('edit','Removido o quiver id');

        }else if(is_numeric($qid)){
            $model->setData('quiver_id',$qid);
            $model->addLog('edit','Alterado quiver id para: '.$qid );
        }
        return ['success'=>true];
    }


    /**
     * Permite atualizar manualmente o campo status_code
     */
    public function post_updateStatusCode(Request $req){
        if(!in_array(Auth::user()->user_level,['dev','superadmin']))return ['success'=>false,'msg'=>'Permissão negada'];
        $code = strtolower($req->code);
        if(!$code || strlen($code)!=6)return ['success'=>false,'msg'=>'Código incorreto'];

        $model=$this->ProcessRobotModel->find($req->input('process_id'));
        $code_old = $model->getData('error_msg');
        $model->setData('error_msg',$code);
        $model->addLog('manual','Informado novo código de erro de '. strtoupper($code_old) .' para '. strtoupper($code) );

        return ['success'=>true];
    }


    /**
     * Permite atualizar manualmente a senha do arquivo
     */
    public function post_updateFilePass(Request $req){
        $pass = trim($req->pass);
        if(!$pass)return ['success'=>false,'msg'=>'Senha incorreta'];

        $model=$this->ProcessRobotModel->find($req->input('process_id'));
        $model->setData('file_pass', json_encode([$pass]) );
        $model->addLog('manual','Informado a senha do arquivo pdf: "'. $pass .'"');

        return ['success'=>true];
    }


    /**
     * Atualiza os dados da apólice nas tabelas 'pr_seg_...'
     * O $request é feito a partir da página /process_cad_apolice/{id}/show, que indica que é alterado pelo usuário do sistema
     */
    public function post_updateFields(Request $request){
        $data = $request->all();
        $id = $data['id'];

        $model = $this->ProcessRobotModel->find($id);
        if(!$model || in_array($model->process_status,['f','w'])){//finalizados ou pendente de apólice
            return ['success'=>false,'msg'=>'Registro bloqueado. Não é possível alterar.'];
        }

        $user_manual_confirm = ($data['user_manual_confirm']??'')=='s';


        $PrSegService = new PrSegService;
        $PrSegService->setProcessClass($this);

        //captura os dados do request
        $parcelas_data = FormatUtility::filterPrefixArrayList($data,'parcela{N}|');
        $prod_data = FormatUtility::filterPrefixArrayList($data,'prod{N}|');

        //junta todos os cados em uma só var
        $data_all_split=['dados'=>$data, 'parcelas'=>$parcelas_data, $model->process_prod=>$prod_data];

        //validação
        $varClass = $PrSegService->getSegClass('dados');
        $validate = $varClass->fields_validate($data);
        if(!$user_manual_confirm){
            if($validate!==true)return ['success'=>false,'msg'=>$validate,'allow_manual_confirm'=>true];
                //validação extra
                $validate=$varClass::validateAll($data,$data_all_split,['processModel'=>$model]);
                if($validate!==true)return ['success'=>false,'msg'=>$validate,'allow_manual_confirm'=>true];
        }

        if(!$user_manual_confirm){
            foreach([
                    'parcelas' => ['data'=>$parcelas_data, 'prefix'=>'parcela{N}|'],
                    $model->process_prod => ['data'=>$prod_data, 'prefix'=>'prod{N}|']
                ]
                as $table=>$table_data
            ){
                //para cada $table_data será um array de valores
                $varClass = $PrSegService->getSegClass($table);
                foreach($table_data['data'] as $i=>$data2){
                    $prefix = str_replace('{N}','{'.$i.'}',$table_data['prefix']);
                    $validate = $varClass->fields_validate($data2);
                    //dd($i,FormatUtility::addPrefixArray($validate,$prefix),$data);
                    if($validate!==true)return ['success'=>false,'msg'=>FormatUtility::addPrefixArray($validate,$prefix),'allow_manual_confirm'=>true];
                        //validação extra
                        $validate=$varClass::validateAll($data2,$data_all_split);
                        if($validate!==true)return ['success'=>false,'msg'=>FormatUtility::addPrefixArray($validate,$prefix),'allow_manual_confirm'=>true];
                }
            }

            //validações adicionais
                if($data['fpgto_n_prestacoes']!=(string)count($parcelas_data))return ['success'=>false,'msg'=>['fpgto_n_prestacoes'=>'Número de prestações não corresponde as parcelas.'],'allow_manual_confirm'=>true];

            //validações de pgto
                //obs:  na função abaixo não precisa informar os parâmetros $marg_parc e $marg_iof, pois estes campos existem para verificação automática das parcelas
                //      e aqui são verificados os dados digitados pelo usuário, por isto tem apenas a margem padrão para todos os casos
                $n = \App\ProcessRobot\cad_apolice\Classes\Data\PgtoData::validateAll($data,$data_all_split['parcelas']);
                if(!$n['success'])return ['success'=>false,'data'=>$data,'msg'=>$n['msg'],'code'=>$n['code']??'read11','allow_manual_confirm'=>true];
        }

        //captura os campos alterados a partir dos campos controles do log
            $changed_dados = \App\Services\LogsService::prepareData($request->all());
            //dd('a',$changed_dados);
            if(!$changed_dados)//quer dizer não que ocorreram alterações, portanto não precisa prosseguir
                return ['success'=>true,'msg'=>'Dados atualizados com sucesso'];

        //atualiza a data que foram salvos os campos
            $model->setData('fields_dtupd_manual',date('Y-m-d H:i:s'));
            $model->setData('user_manual_confirm', ($user_manual_confirm?'s':'') );
            $model_data = $model->getData();
            $this->setRobotDataCtrlChanges($model,$model_data,'user');

        //captura a lista de blocos que foram alterados
            $block_names=[];
            //verifica na tabela de dados
            $blockCheck = $PrSegService->getSegClass('dados')::fields_layoutGroup();
            foreach($changed_dados as $f => $v){
                if(in_array($f, $blockCheck['dados']['fields']) )$block_names[]='dados';
                if(in_array($f, $blockCheck['premio']['fields']) )$block_names[]='premio';
                //obs: não é necessário verificar o anexo
            }
            //verifica na tabela de parcelas
            $blockCheck = array_keys($PrSegService->getSegClass('parcelas')::fields_labels());
            foreach($changed_dados as $f => $v){
                $f=explode('|',$f)[1]??'';
                if(in_array($f,$blockCheck))$block_names[]='parcelas';
            }
            //verifica na tabela de produtos
            $blockCheck = array_keys($PrSegService->getSegClass($model->process_prod)::fields_labels());
            foreach($changed_dados as $f => $v){
                $f=explode('|',$f)[1]??'';
                if(in_array($f,$blockCheck))$block_names[]=$model->process_prod;
            }
            //atualiza na lista de blocos já processados para retirar os blocos que foram alterados
            if($block_names){
                if($model_data['block_names']??false){
                    $r=[];
                    $n=explode(',',$model_data['block_names']);
                    foreach($n as $f){
                        if(!in_array($f,$block_names))$r[]=$f;
                    }
                    $model->setData('block_names', join(',',$r));
                    //salva a diferença do que deve ser processado
                    $r = array_diff(explode(',',VarsProcessRobot::$configProcessNames[$model->process_name]['products'][$model->process_prod]['blocks']) , $r);//captura todos os nomes dos blocos
                    $model->setData('block_names_tmp', join(',',$r));
                }//else //não existem nomes de blocos processados - nenhuma ação é necessária
            }

        //dd('passou',$data);
        //grava os dados no db
            $r=$PrSegService->saveAutoDataToDB($model->process_prod, $id, $data, $parcelas_data, $prod_data);


        //grava nas tabelas de controle (...__s) os campos alterados pelo usuário
            //filtra retornando em matriz os os dados de $data baseados no prefixo
            $data_parcelas = FormatUtility::filterPrefixArrayList($data,'parcela{N}|');
            $data_prod = FormatUtility::filterPrefixArrayList($data,'prod{N}|');

            //relação de campos alterados
            $arr_dados = $PrSegService->getCtrlFieldsChanged('dados',$model,$data,'user');
            $arr_parcelas = $PrSegService->getCtrlFieldsChanged('parcelas',$model,$data_parcelas,'user');
            $arr_prod = $PrSegService->getCtrlFieldsChanged($model->process_prod,$model,$data_prod,'user');
            //dd('xxx',$data_prod,$arr_prod);
            //armazena as alterações no db
            $PrSegService->setAutoCtrlStatus($model->process_prod, $id, 'user', 'user', $arr_dados, $arr_parcelas, $arr_prod);


       //limpa o campo caso tenha sido marcado
            $model->delData('req_fill_manual');

       //grava o log
            $model->addLog('save',$changed_dados);

       //grava o status pronto para o robô
            if($model->insurer_id && $model->broker_id){//somente se o corretor e seguradora estiver preenchidos
                 $model->update(['process_status'=>'p','updated_at'=>date('Y-m-d H:i:s')]);
                 $model->setData('error_msg','');
                 $this->setProcessMakeDone($model,'on');//marcado como concluído
            }

        //dd($r);
        return $r;
    }


    /**
     * Verifica e atualiza o status de acordo com a mensagem de retorno.
     * Até o momento está com a seguinte lógica:
     *      1) altera o status para 'c' (erro do cliente) somente se a mensagem de erro indicar que o cliente deve ser responsável
     *      2) altera o status para '1' (em análise) somente se a mensagem de erro indicar que o programadar deve ser responsável
     *      3) permite retornar ao status 'T' que indica que o registro deve ser processado novamente e não ter este valor gravado no status
     *      4) caso contrário irá retornar ao erro padrão
     * @param model $ProcessModel
     * @param string $current_status - valor do status atual (opcinoal)
     * @param string $status_code - código de retorno da mensagem
     * @return array - [status=>, time=>, arr_upd] - parâmetros:
     *                      status  - new status retornado
     *                      next_at - (string date Y-m-d H:i:s') com a data a ser atualizada se retornado status=T (para que seja atualizado o status=P e process_next_at=time). Default retornará a null.
     *                      arr_upd - (array) formato pronto para executar no sql, ex: ['process_status'=>'p','process_next_at'=>'2020-06-08 14:15:13']
     *
     */
    public function verifyStatusByError($ProcessModel,$current_status,$status_code,$msg=''){
        $s = $current_status ? $current_status : $ProcessModel->process_status;

        //estes status atuais não precisam serem verificados
        if(in_array($s,['f','a','w','t']))return ['status'=>$s,'next_at'=>null,'arr_upd'=>['process_status'=>$s]];

        //frases para status=c (erro cliente)
        $texts=[
            'c' => [
                //erros ao localizar proposta
                'quid01','quid02','quid04','quid05','quid09','quid15','quid16',
                //erros na verificação da proposta após localizar
                'quid08','quid11','quid12','quid13','quid14',
                //erros de login
                'quil04','quil03','quil02','quil06','quil07','quil08','quil09','quil10',
                //corretor não encontrado ou cancelado
                'bro01','bro02',
                //erros ao salvar
                'quiv27','quiv12','quiv01','quiv24','quiv13','quiv03','quiv02','quiv04','read09','quiv29','quiv05','quiv35','quif09','quiv36','quiv14',
                //erros de extração - valores do prêmio
                'read11','read23',
            ],
            //textos para status=1 (em análise)
            '1' => [
                //seguradora não encontrada
                'ins01',
            ],
            //textos para status=i (ignorado)
            'i' => [
                //número de páginas acima do limite
                'read20',
            ],
            //indica que ocorreram mesmo retornando com status='E' (por isto está passando por esta função), o status_code geral é igual a 'ok' ou 'ok2', e portanto considerado finalizado do mesmo modo
            //'f' => ['ok','ok2'],  //em análise
            //textos que indicaram que o registro deve ser reprocessado (status interno de sistema = T)
            't' => [
                'quivnn',//erro por excesso de tentativas
                'quiv08',//erro ao salvar (apenas tenta novamente)
                'quif05',//erro de permissão ao acessar o arquivo no momento de tentar anexar no quiver
                'quiv26',//falha ao atualizar as parcelas
                'quiblk',//erro ao salvar blocos de dados
                'quic01',//opção CHASSI ou C.P.F./C.N.P.J ausente na lista de pesquisa
                'robo01',//erro: nenhum bloco foi processado
                'quiv15',//Erro ao carregar a tela do bloco
            ]
        ];


        $new_s='';
        foreach($texts as $st => $arr_code){
            if(empty($arr_code))continue;
            foreach($arr_code as $xcode){
                if($status_code==$xcode){//achou o código
                    $new_s = $st;
                    break;
                }
            }
            if($new_s)break;
        }
        if(!$new_s)$new_s=$s;


        //quer dizer que neste caso, por uma falha na lógica (ainda não programado totalmente), a string abaixo não tem o respectivo status_code para comparar
        //mas este tipo de erro, deve considerar para tentar novamente
        //dd($status_code,$msg);
        if(stripos($msg,'O botão de INCLUIR do formulário não foi')!==false){
            $new_s='t';
        }

        $arr_upd=['process_status'=>$new_s];

        if($new_s=='t'){
            $count_err= $ProcessModel->getData('count_err_nn');
            $count_err = $count_err ? (int)$count_err : 0;
            if($count_err>=2){//já ultrapassou o limite de reagendamentos
                $ProcessModel->delData('count_err_nn');
                $next_at=null;
                $new_s='e';
                $arr_upd=['process_status'=>'e','process_next_at'=>$next_at];
            }else{
                //para este tipo de erro, precisa apenas tentar novamente
                $ProcessModel->setData('count_err_nn',$count_err+1);
                $delay = $this->ProcessRobotModel->process_repeat_delay_nn;
                $next_at = date('Y-m-d H:i:s', strtotime( $delay  .' min', strtotime(date('Y-m-d H:i:s'))) );
                $arr_upd=['process_status'=>'p','process_next_at'=>$next_at];
            }
        }else{
            $next_at=null;
        }
        //dd($arr_upd);
        return ['status'=>$new_s,'next_at'=>$next_at,'arr_upd'=>$arr_upd];
    }




    /**
     * Faz o envio automático de processos com status='i' (ignorados) para a lixeira (válido somente se o campo process_auto=true)
     * Este processo deve ser executado somente via agendamento automático no sistema.
     */
    public function get_sendProcessAutoTrash(){
        $ignore_auto_days=3;//número de dias após o registro ter sido cadastrado, que será enviado para a lixeira se estiver ignorado
        $dt=date('Y-m-d H:i:s', strtotime('-'.$ignore_auto_days.' day', time()));//obs: a partir do 8º dia é que o registro irá aparecer para ser excluído
        $model=$this->ProcessRobotModel->where('created_at','<=',$dt)->withoutGlobalScope('account_user')->where('process_status','i')->get();
        //dd($model->toArray());
        if($model){
            foreach($model as $reg){
                $reg->addLog('trash','Removido automaticamente. Motivo: registro Ignorado');
                $reg->delete();//manda para a lixeira
            }
        }
        echo 'Finalizado '. date("Y-m-d H:i:s");
    }


    /**
     * Adiciona o registro na tabela files_extract_text para ser extraído pelo robô
     * Return boolean
     */
    private function addFileExtractText($model,$engine,$callback=null){
        $file = $this->getFileInfoPDF($model);
        if($file['success']){
            //$pass = FilesUtility::getPassByName($file['file_original_name']);//captura a lista de senhas a partir do  nome do arquivo
            $pass = $model->getData('file_pass');

            if(!$callback)$callback='\\App\\Http\\Controllers\\Process\\ProcessCadApoliceController@cbFileExtractText';
            \App\Http\Controllers\WSFileExtractTextController::add($engine,$file['file_url_notpriv'], $file['file_path'], 'process_robot', $model->id, $pass, $callback);
            return true;
        }else{
            return false;
        }
    }



    /**
     * Callback do arquivo de extração (complementar da função acima addFileExtractText())
     * @param array $opt:
     *      area_id
     *      area_name
     *      success (boolean) -
     *      msg - mensagem de retorno (válido para success=false)
     *      file_text - texto retornado
     *      file_url
     *      file_path
     * @return array[success,msg]
     */
    public function cbFileExtractText($opt){
        $model = $this->ProcessRobotModel->find($opt['area_id']);
        if(!$model)return ['success'=>false,'msg'=>'Registro não localizado'];
        $model->update(['process_status'=> ($opt['success']?'0':'e') ]);//se deu certo, altera o status para '0' - em indexação
        if($opt['success']){
            $model->setText('text',$opt['file_text']);
        }else{
            $model->setData('error_msg','extr01');
            $model->addLog('error',$opt['msg']);
        }
        return ['success'=>true,'msg'=>'Extração de texto concluída'];
    }

    /**
     * Atualiza o campo token da tabela process_robot
     * Usado para liberar o acesso externo ao arquivo atualizado o token com a data e hora atual
     * @param int|model id - da tabela process_robot
     * Sem retorno
     * @obs: se for usar a função $this->getFileInfoPDF(), deve-se atualizar o token (this->updateToken()) antes caso queria utilizar a o parâmetro retornado 'file_url_notpriv'
     */
    private function updateToken($id){
        $model = is_object($id) ? $id : $this->ProcessRobotModel->find($id);
        $model->setData('token',date('Y-m-d H:i:s'));
    }


    /**
     * Atualiza o cadastro do corretor
     */
    public function post_updateBroker(Request $request){
        $id = $request->input('process_id');
        $broker_id = $request->input('broker_id');
        $account_id = $request->input('account_id');
        $userLogged = Auth::user();

        $broker = $this->BrokerModel->where('account_id',$account_id)->find($broker_id);
        if(!$broker)return ['sucess'=>false,'msg'=>'Erro de parâmetro 1'];

        $model = $this->ProcessRobotModel->find($id);
        if(!$model)return ['sucess'=>false,'msg'=>'Erro de parâmetro 2'];
        if($broker->broker_status=='c')return ['success'=>false,'msg'=>'Não é possível atualizar: corretor cancelado'];
        $old_model = $model->broker;

        $model->update(['broker_id'=>$broker_id]);
        $model->addLog('save','Alterado corretor '. ($old_model?'de #'. $old_model->id .' '. $old_model->broker_alias.' ':'')  .'para #'.$broker_id.' '.$broker->broker_alias);
        $model->setData('broker_manual','s');//indica que o cadastro do corretor foi alterado manualmente

        //faz a indexação de dados novamente (pois pode não ter feito todas as verificações devido ao corretor não ter sido encontrado)
        $r = $this->processFilePDF($model);//se force=s - para indica que é um reprocessamento de dados a  partir do texto do pdf, caso contrário apenas faz a leitura sob o txt já processado
        //dd('process',$r);

        if($r['success']){
            //Verifica se o registro está pronto para ser processado pelo.
            $r = (new PrSegService)->validateByModel($model,true);
            if($r===true)$this->setProcessMakeDone($model,'on');//marca como concluído
        }

        //independnete do resultado da indexação acima, processa retorna true do mesmo jeito, para prosseguir com a ação de atualização de corretor (pois será outra ação que irá verificar novamente os dados retornados da extração)
        return ['success'=>true];
    }


    /**
     * Permite visualizar arquivos o log/dump de arquivos relacionados ao processos do robô
     */
    public function get_ddView(Request $request){
        if(Auth::user()->user_level!='dev')exit('Permissão negada');
        $id = $request->input('id');
        $model = $this->ProcessRobotModel->find($id);
        if(!$model)exit('Erro de parâmetro 2');

        echo '<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>';

        echo '<h3>Tabela: process_robot</h3>';
        dump($model->toArray());

        echo '<h3>Tabela: process_robot_data</h3>';
        dump($model->getData());

        echo '<h3>Tabelas: seg_...</h3>';
        dump($model->getSegData(true));


        echo '<h3>Dados da PDF da Apólice</h3>';
        dump($this->getFileInfoPDF($model->id));

        echo '<h3>Tabela: process_robot_execs + arquivos .data</h3>';
        $execsModel = (new ProcessRobotExecs)->where('process_id',$id)->orderBy('id','asc')->get();
        if($execsModel->count()==0){
            echo 'Nenhum registro de processamento encontrado';
        }else{
            foreach($execsModel as $reg){
                dump(['Processamento '.$reg->id,$reg->toArray(), $reg->getText($model)]);
                //dd($reg->getText($model),json_encode($reg->getText($model)));
                echo '<a href="#" onclick="$(\'#textarea-'.$reg->id.'\').fadeToggle();return false;">Ver json</a><br><textarea onclick="this.select();" readonly style="display:none;width:800px;height:80px;margin-bottom:20px;" id="textarea-'.$reg->id.'">'. json_encode($reg->getText($model)) .'</textarea>';
            }
        }

        $r=$model->getText('apolice_check');
        if($r){echo '<h3>Verificação de Dados na Seguradora (apolice_check)</h3>';dump($r);}

        $r=$model->getText('boleto_seg');
        if($r){echo '<h3>Baixa de Boletos no Site da Seguradora (boleto seg)</h3>';dump($r);}

        echo '<br><br><br><br><br><br>';
    }


    /**
     * Atualiza o registro de histórico do status 'w' pendente de apólice para 'f' finalizado
     * @param $hist_id - id da tabela process_robot.id and com process_robot_data[meta_name=>'hist_id'] para completar o histórico já registrado no db
     * Sem retorno
     */
    private function histFinished($hist_id,$modeCadApolice){
        //verifica se este registro tem a informação que indica que deve atualizar um outro registro de histórico (verifica o campo process_robot_data.hist_id={this id})
        if($hist_id){//tem registro de histórico associado
            //portanto atualizado o registro
            $modelHist = $this->ProcessRobotModel->where(['process_status'=>'w','id'=>$hist_id])->first();
            if($modelHist){
                $modelHist->update(['process_status'=>'f']);
                $modelHist->addLog('status','Histórico - Status atualizado de: Pendente de Apólice(w) para Concluído(f) - pelo ID '.$modeCadApolice->id);
            }
        }
    }


    /**
     * Faz o processo de todos históricos para ver se já tem apólice associado e finaliza-os
     * @obs futuramente este processo poderá ser agendado no servidor, mas futuramente não precisa, pois na lógica não deve ter registors nesta situação,
     *      mas esta função existe para ser executada manualmente e realizar os possíveis ajustes
     */
    public function get_processFixHistorico(Request $request){
        $modelHist=$this->ProcessRobotModel->select('id')->where('process_status','w')->wherePrSeg('dados',['data_type'=>'historico'])->get();
        $count=0;
        if($modelHist){
            foreach($modelHist as $regHist){
                //verifica se tem apólice finalizada
                $modelApo = $this->ProcessRobotModel->where('process_status','f')->whereData(['hist_id'=>$regHist->id])->first();
                if($modelApo){
                    //atualiza o histórico para finalizado
                    $regHist->update(['process_status'=>'f']);
                    $regHist->addLog('status','Histórico - Status atualizado de: Pendente de Apólice(w) para Concluído(f) - pelo ID '.$modelApo->id);
                    $count++;
                }

            }
        }
        exit('Finalizado - ' . ($count ? $count.' registro(s) alterado(s)' : 'Nenhum registro alterado'));
    }


    /**
     * Verifica se a correta tem permissão para processar a apólice do respectivo produto
     * @param $brokerM - model broker
     * @param $prod  - nome do produto (ex automovel, residencial, ...)
     * @params $...M - models broker e process_robot
     */
    public function allowBrokerProducts($brokerM,$prod){
        $n = explode(',',$brokerM->getMetaData('products_allow'));
        return empty($n) || empty($n[0]) || in_array($prod,$n);//caso seja vazio ou encontre o produto considera true
    }


    /**
     * Retorna a lista de todos os erros
     * @param $request - valores:
     *    first_blank   - se 's', então o primeiro resultado será vazio
     *    mode          - modo de retorno da lista. Valores: json (default), select2
     */
    public function get_listStatusCode(Request $req){
        $r = self::getStatusCode();
        if($req->first_blank=='s')$r = [''=>''] + $r;
        foreach($r as $f=>&$v){
            $v= strtoupper($f).' - '.$v;
        }
        if($req->mode=='select2')$r = FormatUtility::toSelect($r);
        return $r;
    }


    /**
     * Reportar erro na apólice para o Operador Manual
     * Obs: altera o campo para process_robot.process_status='c'
     * @param $req - campos esperados: id, req_fill_manual, code(=status_code), obs_operator
     */
    public function post_sendToOperator(Request $req){
        if(!in_array(Auth::user()->user_level,['dev','superadmin']))exit('Acesso negado');

        $m = $this->ProcessRobotModel->find($req->id);
        $m->update(['process_status'=>'c']);
        $s = 'Reportado erro na apólice para o o operador (status=c)';
        if($req->req_fill_manual=='s'){
            $m->setData('req_fill_manual','s');
            $s.="\nBloqueado para emissão somente após alteração manual";
        }else{
            $m->delData('req_fill_manual');
        }
        if($req->code){
            $m->setData('error_msg',$req->code);
            $s.="\nAlteado para código ".$req->code;
        }
        if($req->obs_operator){
            $n = MetadataService::get('process_robot', $req->id, 'obs_operator') . "\n" . date('d/m/Y H:i') . " - " . $req->obs_operator;
            MetadataService::set('process_robot', $req->id, 'obs_operator', $n);
            $s.="\nObs: ".$n;
        }

        $m->addLog('status',$s);
        return ['success'=>true,'msg'=>'Alterado com sucesso'];
    }


    //** *************** webservice de ações para o app do robô ***********************
    /**
     * Esta classe só será iniciada a partir do controller App\Http\Controllers\wsrobotController@get_process, e é obrigatório este nome 'wsrobot_data_get_process'
     * Deve conter o restante dos comandos da solicitação do controller wsrobotController@get_process processando logo após a selação inicial dos registros a serem processados
     * @param array $params - com os mesmos parâmetros retornados do controller
     * @return array: [ProcessModel, DataModel]     //respectivos valores recebidos de $params     //veja + em wsrobotController@get_process
     *                  Obs: se repeat==true, então irá repetir recursivamente esta função
     * @obs: para retornar a nenhum registro, use: return ['status'=>'A|E','msg'=>'Nenhum registro disponível'];
     */
    public function wsrobot_data_getBefore_process($params){
        extract($params);
        $data = $ProcessModel->getText('data');
        if(empty($ProcessModel->process_ctrl_id) || empty($data) || empty($data['apolice_num'])){
            //até aqui não tem dados suficicientes para o robô
            if($only_view!==true){//no modo de visualização de dados, não precisa verificar e alterar os dados abaixo
                $ProcessModel->update(['process_status'=>'e']);
                $ProcessModel->setData('error_msg','wbot02');
            }
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
     * @param $params - com os mesmos parâmetros retornados do controller
     * @return array para montagem do xml final     //veja + em wsrobotController@get_process
     */
    public function wsrobot_data_getAfter_process($params){
        extract($params);

        //dados do pdf já preparados (formato final) para o robô
        $PrSegService = new PrSegService;
        $file_data = $PrSegService->getAllData($ProcessModel);

        if(!$file_data['fpgto_premio_total']){//como não tem este campo, indica que é um registro antigo, portanto captura os dados direto do arquivo .data
            //dd($ProcessModel->created_at,$file_data);
            try{
                $file_data = $ProcessModel->getText('data');
                $n = FormatUtility::filterNamesArrayList($file_data, $PrSegService->getTableModel($ProcessModel->process_prod)->getFillable());//captura somente os campos do veículo
                $n = FormatUtility::addPrefixArray($n,'_1',false,true);//adiciona o sufixo '_1' na chave do matriz
                $file_data = $file_data + $n;//adiciona a nova array

                if(isset($file_data['1_1'])){//se gerar um índice desta forma, é considerado um erro, pois significa que a extração está com erro / incompleta
                    if($only_view!==true){//no modo de visualização de dados, não precisa verificar e alterar os dados abaixo
                        $ProcessModel->update(['process_status'=>'e']);//e - erro suporte
                        $ProcessModel->setData('error_msg','wbot02');
                    }
                    return ['repeat'=>true];
                }

            }catch (Exception $e){
                //atualização em 10/02/2021
                //retorna a erro no robô, para que o programador avalie o caso.
                if($only_view!==true){//no modo de visualização de dados, não precisa verificar e alterar os dados abaixo
                    $ProcessModel->update(['process_status'=>'e']);//e - erro suporte
                    $ProcessModel->setData('error_msg','wbot02');
                }
                return ['repeat'=>true];
            }
        }

        //model do corretor e seguradora
        $broker = $ProcessModel->broker;
        $insurer = $ProcessModel->insurer;

        //libera o acesso externo ao arquivo atualizado o token com a data e hora atual
        $this->updateToken($ProcessModel);

        //captura dados do anexo
        $fileInfo = $this->getFileInfoPDF($ProcessModel->id);
        if(!$fileInfo['success'])return $fileInfo;

        //captura os dados gerais entre corretores e seguradoras
        if($broker && $insurer){
            $brokerInsurerData = $insurer->getBrokerData($broker->id);
        }else{
            $ProcessModel->update(['process_status'=>'e']);//e - erro suporte
            $ProcessModel->setData('error_msg','wbot02');
            return ['repeat'=>true];
            //return ['success'=>false,'msg'=>'Erro ao localizar cadastro - '. ($broker?'Corretor':'') .' - '. ($insurer?'Seguradora':'')  ];
        }

        //verifica os blocos que devem ser informados para robo processar
            //captura os nomes dos blocos que o robô deve processar (nomes separados por virgula)
            $blocks_defaults = VarsProcessRobot::$configProcessNames[$ProcessModel->process_name]['products'][$ProcessModel->process_prod]['blocks'];
            $blocks_defaults = explode(',',$blocks_defaults);
            $block_names = $data['block_names_tmp']??'';
            if($block_names){
                //monta a matriz - sintaxe: [block=>true] para ser processado
                $arr=[];
                foreach(explode(',',$block_names) as $n){
                    $arr[$n]=true;
                }
                foreach($blocks_defaults as $n){//seta os blocos que estão faltando como false
                    if(!isset($arr[$n]))$arr[$n]=false;
                }
                $block_names=$arr;
            }else{
                $block_names=array_fill_keys($blocks_defaults,true);
            }


        //campo quiver_id
            $quiver_id = $data['quiver_id']??'';
            if(!$quiver_id && ($file_data['data_type']??'')=='apolice' && ($data['hist_id']??false)){//indica que a apólice atual é referente a um histórico já enviado
                //captura o quiver_id do histórico já processado, caso contrário não conseguirá emitir no quiver
                $modelHist = $this->ProcessRobotModel->find($data['hist_id']);
                if($modelHist->account_id!=$ProcessModel->account_id)return ['success'=>false,'msg'=>'Erro de cruzamento de apólice/histórico'];
                $quiver_id = $modelHist->getData('quiver_id');
            }


        //ajusta a comissão
            $comissao_desc=$brokerInsurerData['cad_apolice_comissao_desc']??false;
            $comissao_desc = is_array($comissao_desc) ? ($comissao_desc[$ProcessModel->process_prod]??false) : false;

        //dados de login
            $corretor_user  = $login_use['user'];
            $corretor_login = $login_use['login'];
            $corretor_senha = $login_use['pass'];

        if($only_view!==true){//no modo de visualização de dados, não precisa verificar e alterar os dados abaixo
            //verifica se tem os dados de login necessários para prosseguir
            if(empty($corretor_user) || empty($corretor_user) || empty($corretor_user)){
                $ProcessModel->update(['process_status'=>'c']);//c - erro do cliente
                $ProcessModel->setData('error_msg','wbot03');
                return ['repeat'=>true];
            }
        }


        //monta a array final
            $r=$xmlDefault + [
                //dados adicionais do processo
                'has_process_quiver_id'=>$quiver_id?'s':'n',
                'process_block_names'=> json_encode($block_names),
                'process_quiver_id'=>$quiver_id,
                //dados de login do corretor
                'corretor_login_corretora'=>$corretor_user,
                'corretor_login_usuario'=>$corretor_login,
                'corretor_login_senha'=> ($corretor_senha ? ($method=='GET' ? '*******' : $corretor_senha) : '-- erro: senha em branco --'),//somente na requisição post (que vem do robo) é necessário exibir a senha
                //dados da seguradora
                'insurer_id'=>$insurer->insurer_id,
                'insurer_basename'=>$insurer->insurer_basename,
                'seguradora_nome_quiver'=>$insurer->insurer_name,
                'corretor_comissao_desconto'=> $comissao_desc?'s':'n',
                //dados do anexo
                'apolice_file_url'=>$fileInfo['file_url_notpriv'],
                'apolice_file_size'=>$fileInfo['file_size'],
                'apolice_file_pass'=>$ProcessModel->getData('file_pass'),
            ];

            $config_cad_apolice = AccountsService::getCadApoliceConfig($accountModel);
            $r['config_search_products'] = strtoupper(FormatUtility::removeAcents( $config_cad_apolice['search_products'][$ProcessModel->process_prod]??'' ));

            foreach(['names_fpgto','names_anexo'] as $f){
                $arr=[];
                //formata os valores
                foreach($config_cad_apolice[$f] as $a => $v){
                    $arr[strtoupper($a)] = strtoupper(FormatUtility::removeAcents($v));
                }
                $r['config_'. $f] = json_encode($arr);
            }

            $r = $r + $file_data;


        //corrige os valores dos campos
             if(($r['data_type']??'')=='apolice-hist')$r['data_type']='historico';

        return $r;
    }


    /**
     * Seta uma ordem de processamento dos registros
     */
    public function post_setOrder(Request $req){
        //dd($req->all());
        $i = $this->ProcessRobotModel->orderBy('process_order','desc')->value('process_order') ?? 0;
        foreach($req->ids as $id){
            $i++;
            $this->ProcessRobotModel->where('process_status','p')->where('id',$id)->update(['process_order'=> $req->action=='clear' ? null : $i]);
        }
        return ['success'=>true];
    }


    /**
     * Inicia os processos com erros a partir do status_code
     * @param $reg - model process_robot_errors
     * @param array $params - o mesmo setado mais abaixo em ...registerError()
     *      Campos esperados: login_use, status_code, msg
     * Sem retorno
     */
    public function startProcessErrors($reg, $params){
        extract($params);

        if($login_use){
           $n = explode(',',$login_use);//esperado no formato string: 0 conta, 1 login, 2 id (ex: GC,robo,****)
           AccountPassService::activePass($reg->account_id, $n[2]);
        }

        //lógica: captura todos os registros com erro que tenham o respectivo $status_code
        $model = $this->ProcessRobotModel
                    ->where(['process_name'=>self::$basename,'process_status'=>'c'])
                    ->whereData(['error_msg'=>$status_code,'login_use'=>$login_use])
                    ->update(['process_status'=>'p']);//p - pronto para o robô

        //obs: analisando a linha abaixo é necessária, pois neste ponto todos registro da tabela account_pass já devem estar liberados??? e também já existe o processo automático que libera os logins automaticamente a cada 5 min
        //(analisar se é necessário (precisa ser pelo foreach)AccountPassService::setLoginNotBusyByProcess($model->id);//tira a trava do cadastro do login
    }

    /**
     * Esta classe só será iniciada a partir do controller App\Http\Controllers\wsrobotController@set_process, e é obrigatório este nome 'wsrobot_data_set_process'
     * Esta classe deve conter o restante dos comandos da solicitação do controller wsrobotController@set_process
     * @param com os mesmos parâmetros retornados do controller
     * @return array com ['status'=>'...']      //veja + em wsrobotController@set_process
     */
    public function wsrobot_data_set_process($params){
        /* Alguns valores esperados:
         *      $status: f,e,t
         */

        extract($params);


        //Proposta já emitidida - verifica se o usuário que emitiu a proposta é o mesmo do cadastro de logins do robô
        //se for, grava no registro o erro: quid07 (erro)
        //se não, grava no registro o erro: quid03 (emitida ok)
        if($status_code=='quid03'){
            $modelExists=null;
            $quiv_user_emissao = array_get($data_robot,'error_msg.quiver_usuario_emissao');
            $quiv_user_tipo    = array_get($data_robot,'error_msg.quiver_usuario_tipo');
            $s='';$c='';

            if(strtolower($quiv_user_tipo)=='programa' && $quiv_user_emissao==''){//quer dizer que foi emitido automaticamente de um programa de terceiros para o quiver, portanto é considerado emitido
                $s = 'f';
                $c = 'quid03';

            }elseif($quiv_user_emissao!=''){//procura se este usuário está na lista de usuários cadastrados
                $loginExists = AccountPassService::loginExists($ProcessModel->account_id, $quiv_user_emissao);
                //dd($quiv_user_emissao, $loginExists);
                if($loginExists){
                    //é bem provável que exista uma outra proposta já emitida pelo robô, com os mesmos dados seguradora, produto, vigência, nº de apólice, etc... e por isto retornou como já emitida
                    //verifica se encontra outra proposta já emitida pelo robô com os mesmos parâmetros
                    $modelExists = $this->ProcessRobotModel
                            ->where([
                                'process_name'=>$ProcessModel->process_name,
                                'process_prod'=>$ProcessModel->process_prod,
                                'insurer_id'=>$ProcessModel->insurer_id,
                                'broker_id'=>$ProcessModel->broker_id,
                                'process_test'=>false,

                                'process_ctrl_id'=>$ProcessModel->process_ctrl_id,
                                'process_status'=>'f',
                            ])
                            ->where('id','<>',$ProcessModel->id)
                            ->first();
                    if($modelExists){//identifcou que já existe
                        //ignora este registro
                        $s = 'i';
                        $c = 'quid07';

                    }else{//não existe, retorna a erro para ser verificado
                        $s = 'e';
                        $c = 'quid07';
                    }
                }else{
                    //como está emitido ok, alterar o status para finalizado
                    $s = 'f';
                    $c = 'quid03';
                }
            }

            if($s & $c){
                //atualiza o registro
                $ProcessModel->update(['process_status'=>$s]);
                $ProcessModel->setData('error_msg',$c);
                $ProcessModel->addLog('status','Retorno do robô como proposta já emitida ('.$c.') por Usuário "'.$quiv_user_emissao .'" - tipo "'.$quiv_user_tipo.'" ' .  ($modelExists?'. ID Original '. $modelExists->id :'')  .'. Status alterado para '. (self::$status[$s]??null) .' ('. $s.')');

                //remove o token
                $ProcessModel->delData('token');

                //remove o campo de finalização de status pelo usuário
                $ProcessModel->delData('st_change_user');

                //ações ao marcar como concluído
                if($s=='f')$this->onStatusF($ProcessModel,false);

                return $params['return'];
            }
        }
        //dd('passou!!!',$status_code, $data_robot, $quiv_user_emissao);


        //verifica e atualiza o status com base na mensagem de erro atual
        $status_new = $this->verifyStatusByError($ProcessModel,$status,$status_code,$msg);
        if($status_new['status']!=$status){//quer dizer que ocorreu uma mudança de status automático
            $ProcessModel->update($status_new['arr_upd']);
        }


        //*** registra os erros que irão gerar pendências para o operador atuar manualmente ***
        $errors_register=[
            'quil04','quil03','quil02','quil06','quil07','quil08','quil09','quil10',//erros de login
        ];
        if(in_array($status_code,$errors_register)){//erros que devem tentar alterar o status para erro até que seja analisado pelo programador
            $msg = null;
            $tmp = explode(',',$ProcessModel->getData('login_use'));//esperado no formato string: 0 conta, 1 login, 2 id (ex: GC,robo,****)
            $pass_id = $tmp[2];
            if($tmp)$msg = ' ('. $tmp[0].', '.$tmp[1] .')';
            $callback=[
                'class'=>'\\App\\Http\\Controllers\\Process\\ProcessCadApoliceController@startProcessErrors',
                'params'=>[
                    'login_use'=>$ProcessModel->getData('login_use'),
                    'status_code'=>$status_code,
                    'msg'=>'Quiver - '. self::getStatusCode($status_code,false) . $msg
                ]
            ];
            \App::make('\\App\\Http\\Controllers\\Process\\ProcessErrorsController')->registerError($ProcessModel,$status_code,$callback,true); //,true - para sempre inserir o registro (pois somente deste forma é que a $msg será exibida na tela de pendências do operador)

            //traba o acesso ao login
            AccountPassService::disableLogin($ProcessModel->account_id,$pass_id);
        }


        //***** ajusta para o caso de histórico *****
        if($status=='f'){//somente se estiver finalizado
            $data_type = $ProcessModel->getSegData()['data_type']??'';
            //verifica se o processo é do tipo histórico
            if($data_type=='historico'){//quer dizer que o processo setado como finalizado é um histórico de apólice
                //se for finalizado, então para como 'w' (pendente de apólice), pois somente quando enviar um pdf de apólice é que irá marcar este registro como finalizado
                if($status=='f'){
                    $status='w';//pendente de atualização
                    $ProcessModel->update(['process_status'=>$status]);
                }

            }else if($data_type=='apolice'){
                //verifica se este registro tem a informação que indica que deve atualizar um outro registro de histórico (verifica o campo process_robot_data.hist_id={this id})
                $this->histFinished($data['hist_id']??null,$ProcessModel);
            }
        }

        //captura os nomes dos blocos
            $blocks_defaults = VarsProcessRobot::$configProcessNames[$ProcessModel->process_name]['products'][$ProcessModel->process_prod]['blocks'];
            $blocks_defaults = explode(',',$blocks_defaults);
            $block_names_tmp = [];//que foram processados agora através deste retorno do robô
            //inverte adicionando os valores como chaves
            if($data['block_names_tmp']??false){
                foreach(explode(',',$data['block_names_tmp']) as $n){
                    $block_names_tmp[$n]=false;
                };
            }else{//seta todos os blocks
                $block_names_tmp = array_fill_keys($blocks_defaults,false);
            }


        //****** atualiza os campos alterados nas tabelas controle pr_seg_.... *****
        //dd($status,$msg,$data_robot);
        if($data_robot){
            /*** Lógica ****
             * Espera receber em $data_robot um array, sintaxe: [dados, {prodname}, premio, parcelas, anexo, root]
             */
            $is_ctrl_change = false;
            $PrSegService = new PrSegService;
            //captura e salva a relação de campos alterados
            if(isset($data_robot['dados'])){
                if(in_array($data_robot['dados']['_status_code'],['ok','ok2']) && isset($block_names_tmp['dados']))$block_names_tmp['dados']=true;//seta true para informar que este bloco foi atualizado com sucesso
                unset($data_robot['dados']['_status_code']);

                $data_robot['dados'] = $PrSegService->getVarCodeByText('dados',$data_robot['dados']);
                if($data_robot['dados'])$params['data_robot']['dados'] = array_merge($params['data_robot']['dados'],$data_robot['dados']); //atualiza a var $data_robot, que é passado por referência nesta função

                $fields_changed = $this->wsrobot_data_set_process_x1fc($data_robot['dados']);
                //dd($fields_changed);
                $PrSegService->setTableCtrlStatus('dados',$ProcessModel->id,null,'robo','robo',$fields_changed);
                if($fields_changed)$is_ctrl_change=true;
            }
            if(isset($data_robot['premio'])){//atualiza os dados do prêmio junto na tabela 'dados'
                if(in_array($data_robot['premio']['_status_code'],['ok','ok2']) && isset($block_names_tmp['premio']))$block_names_tmp['premio']=true;//seta true para informar que este bloco foi atualizado com sucesso
                unset($data_robot['premio']['_status_code']);

                $data_robot['premio'] = $PrSegService->getVarCodeByText('dados',$data_robot['premio']);
                if($data_robot['premio'])$params['data_robot']['premio'] = array_merge($params['data_robot']['premio'], $data_robot['premio']); //atualiza a var $data_robot, que é passado por referência nesta função

                $fields_changed = $this->wsrobot_data_set_process_x1fc($data_robot['premio']);

                $PrSegService->setTableCtrlStatus('dados',$ProcessModel->id,null,'robo','robo',$fields_changed);
                if($fields_changed)$is_ctrl_change=true;
            }

            //parcelas e {prod}
            $arr_blocks=[];
            if(isset($data_robot['parcelas']))$arr_blocks['parcelas']=$data_robot['parcelas'];
            if(isset($data_robot[$ProcessModel->process_prod]))$arr_blocks[$ProcessModel->process_prod]=$data_robot[$ProcessModel->process_prod];
            if($arr_blocks){
                foreach($arr_blocks as $table => $tmp_data_robot){
                    if(in_array($tmp_data_robot['_status_code'],['ok','ok2']) && isset($block_names_tmp[$table]))$block_names_tmp[$table]=true;//seta true para informar que este bloco foi atualizado com sucesso
                    $i=1;
                    foreach($tmp_data_robot as $n => $arr){
                        if($n=='_status_code'){
                            if($arr=='ok' || $arr=='ok2'){}//indicador que está tudo atualizado, nenhuma ação neste if é necessário
                        }else{
                            $arr = $PrSegService->getVarCodeByText($table,$arr);
                            if($arr)$params['data_robot'][$table][$n] = array_merge($params['data_robot'][$table][$n], $arr); //atualiza a var $data_robot, que é passado por referência nesta função

                            if($arr){
                                unset($arr['_status_code']);
                                $fields_changed = $this->wsrobot_data_set_process_x1fc($arr);
                            }else{
                                $fields_changed = [];
                            }
                            $PrSegService->setTableCtrlStatus($table,$ProcessModel->id,$i,'robo','robo',$fields_changed);
                            $i++;
                            if($fields_changed)$is_ctrl_change=true;
                        }
                    }
                }
            }

            //anexo
            if(isset($data_robot['anexo'])){
                if(in_array($data_robot['anexo']['_status_code'],['ok','ok2']) && isset($block_names_tmp['anexo']))$block_names_tmp['anexo']=true;//seta true para informar que este bloco foi atualizado com sucesso
                $n=$data_robot['anexo'];
                if(in_array($n['_status_code'],['ok'])){//anexado com sucesso   //aqui é somente 'ok'
                    $fields_changed=['anexo_upl'];
                    $PrSegService->setTableCtrlStatus('dados',$ProcessModel->id,null,'robo','robo',$fields_changed);
                }
                unset($data_robot['anexo']['_status_code']);
                $fields_changed = $this->wsrobot_data_set_process_x1fc($data_robot['anexo']);
                if($fields_changed)$is_ctrl_change=true;
            }

            if($is_ctrl_change)$this->setRobotDataCtrlChanges($ProcessModel, $data, 'robo');
        }
        //dd($data_robot,$block_names_tmp);
        //dd('passou');

        //atualiza os nomes dos blocos que já foram processados com os que já estavam processados antes
            $block_names = isset($data['block_names']) ? explode(',',$data['block_names']) : [];//já processados antes
            foreach($block_names_tmp as $n=>$t){
                if($t){
                    if(!in_array($n,$block_names))$block_names[]=$n;
                }
            }
            $ProcessModel->setData('block_names', join(',',$block_names));
            $ProcessModel->delData('block_names_tmp');
            //dd($block_names,$block_names_tmp);


        if($status=='f'){//somente se estiver finalizado
            //ações ao marcar como concluído
            $this->onStatusF($ProcessModel,false);  //seta false para que na função onStatusF() não utilize a verificação da tabela process_robot_execs (pois nesta função wsrobot_data_set_process() ainda não foi finalizado a atualização na tabela ...execs)

            //!!Importante: está ok o código abaixo, mas foi desabiltiado pois este recurso de 'revisão' será concluído em outro momento
            //adiciona sempre um novo registro de revisão
            //$this->servicePrCadApolice()->add($ProcessModel,'review','p','add+',false);//false para não setar o usário logado
        }

        //Desativado em 09/04/2021. Motivo: o quiver id passou a ser atualizado pela função deste arquivo wsrobot_getData() > $field=quiver_id_register
        //$quiver_id = array_get($data_robot,'root.quiver_id');
        //if($quiver_id){//quer dizer que recebeu o Document_ID da proposta localizada
        //    //armazena o id somente se vazio
        //    if(empty($data['quiver_id']))$ProcessModel->setData('quiver_id', $quiver_id);
        //}

        //remove o token
        $ProcessModel->delData('token');

        //remove o campo de finalização de status pelo usuário
        $ProcessModel->delData('st_change_user');


        //seta que este registro deve ser marcado como concluído no Quiver somente se status=1 (nenhuma ação)
        //lógica: todo processo que passar por esta função wsrobot_data_set_process(), quer dizer que foi ou deveria ser emitido pelo robô, portanto seta para marcar como concluído no quiver
        //obs: este código abaixo é necessário principalmente para que os envios manuais que tem falhado em alguns momentos em marcar como concluído
        //$this->setProcessMakeDone($ProcessModel,'on',false);
        //Atualização 03/08/2021: foi desabilitado a linha abaixo, pois havia um erro na classe ProcessSeguradoraFilesController@wsrobot_data_set_process do qual foi corrigido posterior ao comando abaixo, e portanto provavelmente não é necessário este código
        //$this->setProcessMakeDone_item('a',$ProcessModel);//a - marcar como concluído no quiver //obs: esta função substitui a de 'setProcessMakeDone()', pois executa somente a ação obrigatório de marcar como concluído


        return $params['return'];
    }
        //auxiliar de wsrobot_data_set_process //captura a relação de campos alterados //param: [field1=>[from,to],...]  //return [field1,field2...]
        private function wsrobot_data_set_process_x1fc($arr){
            $r=[];
            foreach($arr as $f=>$v){//$v esperado: [from,to] e se 'to'<>null que é considerado alterado
                if(is_null($v[1]??null))continue;
                $r[]=$f;
            }
            return $r;
        }


     /**
      * Captura um dado para o robô.
      * Parâmetros $params esperados - campo 'field':
      *     quiver_id_exists    - verifica se existe outro process_robot com o mesmo quiver id na base de dados.
        *                         Campos esperados:
        *                             qid - valor do quiver id
        *                             process_id - id do processo
        *                         retorno: success (boolean), exists (s|n)
      *     quiver_id_register  - igual ao quiver_id_exists, mas registra o quiver_id ao process_id para evitar duas emissão simultâneas no mesmo arquivo (lógica: o primeiro pedido já registra o quiver_id e o segundo em diante nega o acesso..)
      *                             campos esperados e retorno: o mesmo de quiver_id_exists
      *     pass_data           - retorna aos dados de acesso
      *     insurer_data        - retorna a dados extraídos a partir do texto da seguradora
      *                             Parâmetros adicionais:
      *                                 insurer_basename    - nome base da seguradora
      *                                 text                - texto da proposta
      * Return array: success - boolean, msg - erro para success=false, ... demais campos para success=true
      */
    public function wsrobot_getData($params){
        $r=null;
        $field = $params['field']??'';
        if($field=='quiver_id_exists' || $field=='quiver_id_register'){
            $qid = $params['qid']??'';
            $process_id = $params['process_id']??'';
            if($qid && $process_id){
                $model = \App\Models\ProcessRobotData::whereRaw('process_id=(select p.id from process_robot p where p.id=process_id and p.account_id='. $params['account_id'] .')')
                            ->where(['meta_name'=>'quiver_id','meta_value'=>$qid])->where('process_id','<>',$process_id);
                $count=$model->count();

                if($field=='quiver_id_exists'){
                    return ['success'=>true,'exists'=>$count==0?'n':'s'];

                }else{//$field===quiver_id_register
                    if($count==0){//quiver_id não existe
                        \DB::beginTransaction();
                        $model = $this->ProcessRobotModel->whereIn('process_status',['p','a'])->lockForUpdate()->find($process_id);
                        if(!$model)return ['success'=>false,'msg'=>'Registro precisa estar com os status P, A'];
                        $model->setData('quiver_id',$qid);
                        \DB::commit();
                        return ['success'=>true,'exists'=>'n'];
                    }else{//quiver_id já existe
                        return ['success'=>true,'exists'=>'s'];
                    }
                }
            }

        }elseif($field=='pass_data'){
            $pass = AccountPassService::getLoginById($params['account_id'],$params['pass_id']??'review');
            return $pass ? ['success'=>true] + $pass->toArray() : ['success'=>false,'msg'=>'Registro não encontrado'];

        }elseif($field=='insurer_data'){
            $n = $params['insurer_basename'] ?? null;
            if($n){
                try{
                    $cls = '\\App\\ProcessRobot\\cad_apolice\\ClassesPropostas\\'. $params['insurer_basename'] .'Class';
                    $c = new $cls;
                    return ['success'=>true,'proposta_num'=>$c->process($params['text'])];
                } catch (Exception $e) {
                    return ['success'=>false,'msg'=>'get_data: '. $e->getMessage() ];
                }
            }
            return ['success'=>false,'msg'=>'get_data: Parâmetros inválidos(1)'];
        }
        return ['success'=>false,'msg'=>'get_data: Parâmetros inválidos(2)'];
    }

    /**
     * Seta se ocorreram alterações no registro da tabela process_robot pelo usuário ou robô
     * @param $ProcessModel
     * @param array $data - matriz da tabela process_robot_data associado
     * @param string $ctrl - valores: robo, user
     * Sem retorno
     */
    private function setRobotDataCtrlChanges($ProcessModel,$data,$ctrl){
        if(isset($data['ctrl_changes'])){
            $n=explode(',',$data['ctrl_changes']);//esperado os valores ex: 'robo, user'
            if(!in_array($ctrl,$n))$n[]=$ctrl;
            $n=join(',',$n);
        }else{
            $n=$ctrl;
        }
        $ProcessModel->setData('ctrl_changes',$n);
    }
}
