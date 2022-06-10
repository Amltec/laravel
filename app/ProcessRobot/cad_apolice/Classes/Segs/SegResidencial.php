<?php

namespace App\ProcessRobot\cad_apolice\Classes\Segs;
use Illuminate\Database\Eloquent\Model;
use App\Utilities\ValidateUtility;
use App\Utilities\FormatUtility;
use App\ProcessRobot\cad_apolice\Classes\Vars\QuiverResidencialVar;


class SegResidencial implements Interfaces\SegInterface{

    protected static $seg_name = 'residencial';


    //relação de campos para ignorar na exibição do log da baixa
    public static function fields_ignore_show(){
        //relação de campos para ignorar na exibição do log da baixa. Parâmetro $type: hide_admin (ocultados para o admin), not_quiver (não usados no quiver)
        return false;
    }

    //regras para validação e ajustes dos campos antes para inserir no DB (mas tabelas pr_seg_...)
    //Obs: parâmetros conforme padrão da classe \App\Utilities\ ValidateUtility::validateData() ou FormatUtility::adjuteData()
    public static function fields_rules(){
        return [
            static::$seg_name . '_endereco'      =>'max:50',
            static::$seg_name . '_numero'        =>'max:5',
            static::$seg_name . '_compl'         =>'max:20',
            static::$seg_name . '_bairro'        =>'max:20',
            static::$seg_name . '_cidade'        =>'max:20',
            static::$seg_name . '_uf'            =>'size:2,values:AC|AL|AP|AM|BA|CE|DF|ES|GO|MA|MT|MS|MG|PA|PB|PR|PE|PI|RJ|RN|RS|RO|RR|SC|SP|SE|TO',
            static::$seg_name . '_cep'           =>'type:intstr,min:4,max:9',
        ];
    }
    //o mesmo de fields_rules(), mas considerando a validação para no momento da extração do texto do pdf  (usados no arquivo App\ProcessRobot\cad_apolice\Process{Prod}Class.php)
    public static function fields_rules_extract($opt=null){
        $r=[];
        /* Adiciona as opções:
         *      exists  - precisa existir na matriz
         *      *       - obrigatório
         */
        $optional=[static::$seg_name . '_compl',static::$seg_name . '_cep'];
        foreach(self::fields_rules() as $f=>$v){
            $t = !in_array($f,$optional);//campos: true obrigatório, false opcional
            $r[$f]=is_array($v) ? array_merge(($t?['exists','*']:['exists']) ,$v) : (is_callable($v)?$v: ($t?'exists,*,':'exists,').$v);
        };

        return $r;
    }

    //regras para comparação de valores no modo de visualização de dados
    //ex: fab_code=FIAT na visualização, mas para inserir no DB é fab_code=123, portanto a regra considera a string FIAT para comparação
    //ex de ajuste: array_merge(self::fields_rules(),[ 'fab_code'=>'' ]);   //setando vazio já será considerando considerado comparação no modo de texto
    //utlizado pela classe PrSegService->getCtrlFieldsChanged()
    public static function fields_rules_view(){
        return self::fields_rules();
    }

    //Executa a validação de campos antes de inserir no banco de dados
    //@param action - nome da ação para personalização do validate - valores: add, edit, null
    //@return true | array msg
    public static function fields_validate($data,$action=null){
        $required=[static::$seg_name . '_endereco',static::$seg_name . '_numero',static::$seg_name . '_bairro',static::$seg_name . '_cidade',static::$seg_name . '_uf'];
        $validate = ValidateUtility::validateData($data, self::fields_rules(), $required );
        return $validate;
    }


    //labels dos campos //sintaxe: field=>label ou field=>[label,label_short]
    public static function fields_labels(){
        return [
            static::$seg_name . '_endereco'      =>'Endereço',
            static::$seg_name . '_numero'        =>'Número',
            static::$seg_name . '_compl'         =>'Complemento',
            static::$seg_name . '_bairro'        =>'Bairro',
            static::$seg_name . '_cidade'        =>'Cidade',
            static::$seg_name . '_uf'            =>'Estado',
            static::$seg_name . '_cep'           =>'CEP',
        ];
    }

    //formato de exibição dos campos
    //Obs: parâmetros conforme padrão da classe \App\Utilities\FormatUtility::formatData()
    //@param $mode - valores: 'view' - visualização em tela, 'form' - visualização em formulário (para ficar compatível com self::fields_html())
    public static function fields_format($mode='view'){
        return [
            static::$seg_name . '_endereco'      =>'',
            static::$seg_name . '_numero'        =>'',
            static::$seg_name . '_compl'         =>'',
            static::$seg_name . '_bairro'        =>'',
            static::$seg_name . '_cidade'        =>'',
            static::$seg_name . '_uf'            =>'',
            static::$seg_name . '_cep'           =>'',
        ];
    }

    //formata os dados antes de inserir no db //antes de class::fileds_rules()
    public static function fields_format_db_before($data,$opt=[]){
        return $data;
    }
    //formata os dados antes de inserir no db //depois de class::fileds_rules()
    public static function fields_format_db_after($data,$opt=[]){
        return $data;
    }

    //formato de exbibição dos dados que vierem do quiver para o robô
    public static function fields_format_quiver(){
        return self::fields_format('view');
    }

    //parâmetros para geração de campos html
    //Obs: os parâmetros precisam ser compatíveis com o templates.ui.auto_fields
    public static function fields_html(){
        $ufs=explode('|','AC|AL|AP|AM|BA|CE|DF|ES|GO|MA|MT|MS|MG|PA|PB|PR|PE|PI|RJ|RN|RS|RO|RR|SC|SP|SE|TO');

        $r=[
            static::$seg_name . '_endereco'      =>['maxlength'=>50],
            static::$seg_name . '_numero'        =>['maxlength'=>5],
            static::$seg_name . '_compl'         =>['maxlength'=>20],
            static::$seg_name . '_bairro'        =>['maxlength'=>20],
            static::$seg_name . '_cidade'        =>['maxlength'=>20],
            static::$seg_name . '_uf'            =>['type'=>'select2','list'=> array_combine($ufs,$ufs)],
            static::$seg_name . '_cep'           =>['type'=>'cep','maxlength'=>9],
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
        return $data;
    }

    //função personalizada de validação extra executado ao gerar/salvar os dados e/ou  após funções validates acima: fields_rules, fields_validate
    //Obs: pode-se alterar o $data dentro desta função
    public static function validateAll(&$data,$data_all_split=null,$opt=[]){
        $opt=array_merge([
            'sufix'=>null,   //se informado é utilizado paneas para completar o retorno do nome do campo. Ex: se $num=2 então retorna 'meucampo_2'
        ],$opt);
        $sufix = !is_null($opt['sufix']) ? (is_numeric($opt['sufix'])?'_'.$opt['sufix']:$opt['sufix']) : '';

        //valida o cep
        $r = FunctionsSeg::cepValidate($data[static::$seg_name . '_cep'],($data_all_split['dados']??null));
        if(!$r['success'])return [static::$seg_name . '_cep'.$sufix=>$r['msg']];
        if($r['ignore'])$data['_ignore_fields_cad'][]=static::$seg_name . '_cep';           //???? precisa analisar este comando... não tenho certeza se está ok

        return true;
    }


    /**
     * Retorna aos códigos utilizados para conversão do texto retornado do robô do quiver em respectivo código padrão desta aplicaçao.
     * As variáveis com códigos estão em \App\ProcessRobot\cad_apolice\Vars\....
     */
    public static function getVarsCodeFromText(){
        //obs: é obrigatório que o campo abaixo (ex fpgto_tipo) tenha seu respectivo campo de código com '_code' (ex fpgto_tipo_code)
        return []; //nenhum parâmetro necessário aqui
    }


    //**** campos personalizados ****

    //Campos necessários para revisão manual do usuário (existe apenas para o classe do ramo/produto)
    public static function fields_review_manual(){
        return [static::$seg_name . '_endereco',static::$seg_name . '_numero',static::$seg_name . '_compl',static::$seg_name . '_bairro',static::$seg_name . '_cidade',static::$seg_name . '_uf'];
    }

}

