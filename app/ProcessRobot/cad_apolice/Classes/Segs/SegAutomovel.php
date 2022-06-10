<?php

namespace App\ProcessRobot\cad_apolice\Classes\Segs;
use Illuminate\Database\Eloquent\Model;
use App\Utilities\ValidateUtility;
use App\Utilities\FormatUtility;
use App\ProcessRobot\cad_apolice\Classes\Vars\QuiverAutomovelVar;

class SegAutomovel implements Interfaces\SegInterface{

    //relação de campos para ignorar na exibição do log da baixa
    public static function fields_ignore_show(){
        //relação de campos para ignorar na exibição do log da baixa. Parâmetro $type: hide_admin (ocultados para o admin), not_quiver (não usados no quiver)
        return [
            'not_quiver'=>['veiculo_tipo'],
        ];
    }

    //regras para validação e ajustes dos campos antes para inserir no DB (mas tabelas pr_seg_...)
    //Obs: parâmetros conforme padrão da classe \App\Utilities\ ValidateUtility::validateData() ou FormatUtility::adjuteData()
    public static function fields_rules(){
        return [
            'prop_nome'                 =>'max:50',
            'veiculo_tipo'              =>'size:1,values:a|m|c',
            'veiculo_fab_code'          =>'type:int,max:5',
            'veiculo_modelo'            =>'max:50',
            'veiculo_ano_fab'           =>'type:int,size:4',
            'veiculo_ano_modelo'        =>'type:int,size:4',
            'veiculo_chassi'            =>'min:16,max:17',
            'veiculo_cod_fipe'          =>'type:intstr,min:4,max:8',
            'veiculo_placa'             =>'min:6,max:7',
            'veiculo_combustivel_code'  =>'type:int,max:2',
            'veiculo_n_portas'          =>'type:int',
            'veiculo_n_lotacao'         =>'type:int,vmin:1,vmax:28',
            'veiculo_ci'                =>'type:intstr,size:14',
            'veiculo_classe'            =>'type:int,max:2,vmin:0,vmax:20',
            'veiculo_zero'              =>'values:s|n|1|0,values_to:1|0|1|0',
            'veiculo_data_saida'        =>'type:datebr,min:10',
            'veiculo_nf'                =>'max:15',
            'segurado_pernoite_cep'     =>'type:intstr,min:4,max:9',
        ];
    }
    //o mesmo de fields_rules(), mas considerando a validação para no momento da extração do texto do pdf  (usados no arquivo App\ProcessRobot\cad_apolice\Process{Prod}Class.php)
    public static function fields_rules_extract($opt=null){
        $r=[];
        /* Adiciona as opções:
         *      exists  - precisa existir na matriz
         *      *       - obrigatório
         */
        $optional=['prop_nome','veiculo_fab_code','veiculo_combustivel_code','veiculo_n_portas','veiculo_n_lotacao','veiculo_data_saida','veiculo_nf','veiculo_zero','veiculo_placa'];
        if(in_array(array_get($opt,'automovel.1.veiculo_tipo'),['m','c'])){//o veículo é do tipo moto ou caminhão
            //tira a obrigatoriedade do campo fipe
            $optional[]='veiculo_cod_fipe';
        }

        foreach(self::fields_rules() as $f=>$v){
            $t = !in_array($f,$optional);//campos: true obrigatório, false opcional
            $r[$f]=is_array($v) ? array_merge(($t?['exists','*']:['exists']) ,$v) : (is_callable($v)?$v: ($t?'exists,*,':'exists,').$v);
        };
        unset($r['num']);//este campo não existe na extração

        return $r;
    }

    //regras para comparação de valores no modo de visualização de dados
    //ex: fab_code=FIAT na visualização, mas para inserir no DB é fab_code=123, portanto a regra considera a string FIAT para comparação
    //ex de ajuste: array_merge(self::fields_rules(),[ 'fab_code'=>'' ]);   //setando vazio já será considerando considerado comparação no modo de texto
    //utlizado pela classe PrSegService->getCtrlFieldsChanged()
    public static function fields_rules_view(){
        return array_merge(self::fields_rules(), [
            'veiculo_fab_code'          =>'',
            'veiculo_combustivel_code'  =>'',
        ]);
    }

    //Executa a validação de campos antes de inserir no banco de dados
    //@param action - nome da ação para personalização do validate - valores: add, edit, null
    //@return true | array msg
    public static function fields_validate($data,$action=null){
        $required=['veiculo_modelo','veiculo_ano_fab','veiculo_ano_modelo','veiculo_chassi','veiculo_cod_fipe','veiculo_ci','veiculo_classe','veiculo_zero','segurado_pernoite_cep'];
        $validate = ValidateUtility::validateData($data, self::fields_rules(), $required );
        return $validate;
    }


    //labels dos campos //sintaxe: field=>label ou field=>[label,label_short]
    public static function fields_labels(){
        return [
            'prop_nome'                 =>'Nome do Proprietário',
            'veiculo_tipo'              =>'Tipo',
            'veiculo_fab_code'          =>'Fabricante',
            'veiculo_modelo'            =>'Modelo',
            'veiculo_ano_fab'           =>'Ano de Fabricação',
            'veiculo_ano_modelo'        =>'Ano Modelo',
            'veiculo_chassi'            =>'Chassi',
            'veiculo_cod_fipe'          =>'Código Fipe',
            'veiculo_placa'             =>'Placa',
            'veiculo_combustivel_code'  =>'Combustível',
            'veiculo_n_portas'          =>'Nº Portas',
            'veiculo_n_lotacao'         =>'Nº Locação',
            'veiculo_ci'                =>'Cód. Identificação',
            'veiculo_classe'            =>'Classe Bônus',
            'veiculo_zero'              =>'Veículo zero',
            'veiculo_data_saida'        =>'Data de Saída',
            'veiculo_nf'                =>'Nota Fiscal',
            'segurado_pernoite_cep'     =>'CEP Pernoite',
        ];
    }

    //formato de exibição dos campos
    //Obs: parâmetros conforme padrão da classe \App\Utilities\FormatUtility::formatData()
    //@param $mode - valores: 'view' - visualização em tela, 'form' - visualização em formulário (para ficar compatível com self::fields_html())
    public static function fields_format($mode='view'){
        return [
            'prop_nome'                 =>'',
            'veiculo_tipo'              =>function($v) use($mode){return $mode=='form' ? $v : (QuiverAutomovelVar::$tipos_code[$v]??'') ;},
            'veiculo_fab_code'          =>function($v) use($mode){return $mode=='form' ? $v : QuiverAutomovelVar::getFabricante($v) ;},
            'veiculo_modelo'            =>'',
            'veiculo_ano_fab'           =>'',
            'veiculo_ano_modelo'        =>'',
            'veiculo_chassi'            =>'',
            'veiculo_cod_fipe'          =>function($v){ return str_replace('-','',$v); },//remove o traço deixando apenas os números
            'veiculo_placa'             =>'',
            'veiculo_combustivel_code'  =>function($v) use($mode){return $mode=='form' ? (int)$v : QuiverAutomovelVar::$combustivel_code[(int)$v]??'';},    //obs: (int)$v para tirar os zeros a esquerda
            'veiculo_n_portas'          =>'',
            'veiculo_n_lotacao'         =>'',
            'veiculo_ci'                =>'',
            'veiculo_classe'            =>'',
            'veiculo_zero'              =>$mode=='form' ? '' : 'values:s|n|1|0|True|False,values_to:SIM|NÂO|SIM|NÂO|SIM|NÂO',
            'veiculo_data_saida'        =>'type:datebr',
            'veiculo_nf'                =>'',
            'segurado_pernoite_cep'     =>'',
        ];
    }

    //formata os dados antes de inserir no db //antes de class::fileds_rules()
    public static function fields_format_db_before($data,$opt=[]){
        /*$dateYearRef = $opt['model']->process_date??null;
        foreach([
            'veiculo_data_saida',
        ] as $f){
            if(isset($data[$f]))$data[$f] = FormatUtility::fixYearDateBr($data[$f], $dateYearRef );
        }*/
        return $data;
    }
    //formata os dados antes de inserir no db //depois de class::fileds_rules()
    public static function fields_format_db_after($data,$opt=[]){
        if(isset($data['veiculo_placa']))$data['veiculo_placa']=mb_strtoupper($data['veiculo_placa']);//converte em maiúsculo
        return $data;
    }

    //formato de exbibição dos dados que vierem do quiver para o robô
    public static function fields_format_quiver(){
        return self::fields_format('view');
    }

    //parâmetros para geração de campos html
    //Obs: os parâmetros precisam ser compatíveis com o templates.ui.auto_fields
    public static function fields_html(){

        $r=[
            'prop_nome'                 =>['maxlength'=>50],
            'veiculo_tipo'              =>['type'=>'select2','list'=>[''=>''] + QuiverAutomovelVar::$tipos_code ],
            'veiculo_fab_code'          =>['type'=>'select2','list'=>[''=>''] + self::getFabCodeList() ],
            'veiculo_modelo'            =>['maxlength'=>50],
            'veiculo_chassi'            =>['maxlength'=>17],
            'veiculo_ano_fab'           =>['type'=>'number','maxlength'=>4],
            'veiculo_ano_modelo'        =>['type'=>'number','maxlength'=>4],
            'veiculo_cod_fipe'          =>['maxlength'=>8],
            'veiculo_placa'             =>['maxlength'=>7,'class_field'=>'text-uppercase'],
            'veiculo_combustivel_code'  =>['type'=>'select2','list'=>[''=>''] + QuiverAutomovelVar::$combustivel_code ],
            'veiculo_n_portas'          =>['maxlength'=>2],
            'veiculo_n_lotacao'         =>['maxlength'=>2],
            'veiculo_ci'                =>['maxlength'=>14],
            'veiculo_classe'            =>['type'=>'select','list'=>[''=>'','0'=>'0','1'=>'1','2'=>'2','3'=>'3','4'=>'4','5'=>'5','6'=>'6','7'=>'7','8'=>'8','9'=>'9','10'=>'10','11'=>'11','12'=>'12','13'=>'13','14'=>'14','15'=>'15','16'=>'16','17'=>'17','18'=>'18','19'=>'19','20'=>'20']],
            'veiculo_zero'              =>['type'=>'select','list'=>[''=>'','0'=>'Não','1'=>'Sim']],
            'veiculo_data_saida'        =>['type'=>'date'],
            'veiculo_nf'                =>['maxlength'=>15],
            'segurado_pernoite_cep'     =>['type'=>'cep','maxlength'=>9],
        ];

        //mescla com o label dos campos
        $labels=self::fields_labels();
        foreach($r as $field=>&$opt){
            if(!isset($opt['label']))$opt['label']=$labels[$field]??null;
        }

        return $r;
    }


    //posíveis ajustes finais nos campos para preparar os dados enviados para exibição
    //@param $data [field=>value,...] //return new $data
    public static function fields_show($data){
        $data['veiculo_tipo__v']            = QuiverAutomovelVar::$tipos_code[$data['veiculo_tipo']]??'';
        $data['veiculo_fab_code__v']        = strtoupper(FormatUtility::removeAcents( QuiverAutomovelVar::getFabricante(ltrim($data['veiculo_fab_code'],'0')) ));

        //obs: cada item de QuiverAutomovelVar::$fabricante_code retorna a uma string ou array, portanto monta a lista abaixo considerando estes casos
        $r='';
        foreach(QuiverAutomovelVar::$fabricante_code as $c => $f){
            if(ltrim($data['veiculo_fab_code'],'0') == $c){
                $r = is_array($f) ? join('|',$f) : $f;
                break;
            }
        }
        $data['veiculo_fab_code__vs']       = strtoupper(FormatUtility::removeAcents($r));

        $data['veiculo_combustivel_code__v']= QuiverAutomovelVar::$combustivel_code[ltrim($data['veiculo_combustivel_code'],'0')]??'';
        return $data;
    }

    //função personalizada de validação extra executado ao gerar/salvar os dados e/ou  após funções validates acima: fields_rules, fields_validate
    //Obs: pode-se alterar o $data dentro desta função
    public static function validateAll(&$data,$data_all_split=null,$opt=[]){
        $opt=array_merge([
            'sufix'=>null,              //se informado é utilizado paneas para completar o retorno do nome do campo. Ex: se $num=2 então retorna 'meucampo_2'
            'validate_required'=>[],    //dados da classe App\ProcessRobot\cad_apolice\{process_prod}\{insurer}Class()->$validate_required
        ],$opt);
        $sufix = !is_null($opt['sufix']) ? (is_numeric($opt['sufix'])?'_'.$opt['sufix']:$opt['sufix']) : '';

        //$ignore_fields = $data_all_split['dados']['_ignore_fields_cad']??[];

        //modelo do veículo
        $n=strlen($data['veiculo_modelo']);
        if((strtolower($data['veiculo_tipo']??false))=='c'){//'caminhao' é provável que o modelo tenha apenas números
            if($n<3)return ['veiculo_modelo'.$sufix=>'Mínimo de 3 caracteres'];
        }else{
            if($n<5)return ['veiculo_modelo'.$sufix=>'Mínimo de 5 caracteres'];
        }

        //verifica e atualiza o chassi para 17 digitos caso tenha apenas 16
        //verifica e atualiza o chassi para 17 digitos caso tenha apenas 16
        $n=$data['veiculo_chassi'];
        if(strlen($n)==16)$n='0'.$n;
        if(strlen($n)!=17)return ['veiculo_chassi'.$sufix=>'Chassi inválido'];
        $data['veiculo_chassi']=$n;

        if(array_get($opt,'validate_required.veiculo_ci')!==false || $data['veiculo_ci']!=''){
            //Campo CI
            $n=str_replace(['-','.'],['',''],$data['veiculo_ci']);
            if(strlen($n)!=14)return ['veiculo_ci'.$sufix=>'Campo C.I. inválido'];
        }


        //tabela fipe - retira o traço
        $data['veiculo_cod_fipe']=str_replace(['-',' ','.'],['','',''],$data['veiculo_cod_fipe']);

        //verifica se o ano fabrição é menor que o ano modelo
        $n=(int)$data['veiculo_ano_fab'];
        $n2=(int)$data['veiculo_ano_modelo'];
        if($n>$n2)return ['veiculo_ano_fab'.$sufix=>'Ano de fabricação é maior que o ano modelo'];
        if(($n2-$n)>2)return ['veiculo_ano_fab'.$sufix=>'Ano de modelo maior que 2 anos do que o ano fabricação'];

        //placa
        if($data['veiculo_zero']!='s' && $data['veiculo_placa']=='')return ['veiculo_placa'.$sufix=>'Placa do veículo obrigatória'];

        //cep de pernoite
        /*if($data['segurado_pernoite_cep']!=''){
            $n=str_replace('-','',$data['segurado_pernoite_cep']);//deixa apenas números

            if(strlen($n)==4 && substr($n,-1)=='*'){//este é um padrão de cep oculto, onde vem com 3 digitos e * no final (ex: '489*')
                //Lógica: deixa passar
                if(!is_numeric( str_replace('*','',$n) ))return ['segurado_pernoite_cep'.$sufix=>'CEP de pernoite inválido'];
                //seta que deve ignorar o campo // obs: este campo gerado só tem validade nas informações enviadas para o robô
                $data['_ignore_fields_cad'][]='segurado_pernoite_cep';

            }else{
                //deixa obrigatório apenas para data-type=apolices
                if($data_all_split && $data_all_split['dados']['data_type']=='apolice' && empty($n))return ['segurado_pernoite_cep'.$sufix=>'Campo cep de pernoite inválido'];
                //valida o campo cep com 8 digitos ou 5 digitos (5 dig padrão liberty)
                $n = str_replace('*','',$n);//remove os '*'
                if($n && (strlen($n)!=8 && strlen($n)!=5 && strlen($n)!=4))return ['segurado_pernoite_cep'.$sufix=>'CEP de pernoite inválido - precisa conter 8, 5 ou 4 caracteres'];
                if($n && !is_numeric($n))return ['segurado_pernoite_cep'.$sufix=>'CEP de pernoite inválido'];
            }
        }*/
        //valida o cep de pernoite
        $r = FunctionsSeg::cepValidate($data['segurado_pernoite_cep'],($data_all_split['dados']??null));
        if(!$r['success'])return ['segurado_pernoite_cep'.$sufix=>$r['msg']];
        if($r['ignore'])$data['_ignore_fields_cad'][]='segurado_pernoite_cep';  //???? precisa analisar este comando... não tenho certeza se está ok


        $d1=$data['veiculo_data_saida'];
        $d2=$data_all_split['dados']['data_emissao']??'';
        if($d1 && strlen($d1)!=10)return ['veiculo_data_saida'.$sufix=>'Data com menos de 10 caracateres'];
        if($d1 && $d2 && ValidateUtility::isDate($d1)){
            //d1 & d2 estão no format dd/mm/aaaa
            $d1 = (int)(explode('/',$d1)[2]??0);
            $d2 = (int)(explode('/',$d2)[2]??0);
            if($d1 && $d2){
                //if(\Auth::user() && \Auth::user()->id==1)dd('x1',$d1,$d2,($d2+1));
                if($d1==$d2 || $d1==($d2-1) || $d1==($d2+1)){//lógica: o ano da data do veículo deve ser igual ao ano da emissão, ou pelo menos menor um ano apenas, ou igual a data da emissão + 1 ano
                    //passou
                }else{
                    return ['veiculo_data_saida'.$sufix=>'Data inválida'];
                }
            }else{
                return ['veiculo_data_saida'.$sufix=>'Data de saída do veículo ou data de emissão inválidos'];
            }
        }
        //dd('xxxx',$n,strlen($n));
        return true;
    }


    //**** campos personalizados ****

    //Campos necessários para revisão manual do usuário (existe apenas para o classe do ramo/produto)
    public static function fields_review_manual(){
        return ['veiculo_chassi','veiculo_classe'];
    }


    /**
     * Retorna aos códigos utilizados para conversão do texto retornado do robô do quiver em respectivo código padrão desta aplicaçao.
     * As variáveis com códigos estão em \App\ProcessRobot\cad_apolice\Vars\....
     */
    public static function getVarsCodeFromText(){
        //obs: é obrigatório que o campo abaixo (ex fpgto_tipo) tenha seu respectivo campo de código com '_code' (ex fpgto_tipo_code)
        return [
            'veiculo_fab' => QuiverAutomovelVar::$fabricante_code
        ];
    }


    /**
     * Retorna a um array da lista de veículos.
     * Sintaxe [code => text]   //obs: se text for um array, retorna ao primeiro índice
     */
    public static function getFabCodeList(){
        $r=[];
        foreach(QuiverAutomovelVar::$fabricante_code as $c => $f){
            $r[$c] = is_array($f) ? $f[0] : $f;
        }
        return $r;
    }
}

