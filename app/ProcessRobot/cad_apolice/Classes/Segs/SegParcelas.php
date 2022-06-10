<?php

namespace App\ProcessRobot\cad_apolice\Classes\Segs;
use Illuminate\Database\Eloquent\Model;
use App\Utilities\ValidateUtility;
use App\Utilities\FormatUtility;


class SegParcelas implements Interfaces\SegInterface{
    //relação de campos para ignorar na exibição do log da baixa
    public static function fields_ignore_show(){return [];}

    //regras para validação e ajustes dos campos antes para inserir no DB (mas tabelas pr_seg_...)
    //Obs: parâmetros conforme padrão da classe \App\Utilities\ ValidateUtility::validateData() ou FormatUtility::adjuteData()
    public static function fields_rules(){
        return [
            'num'                       =>'type:int,vmin:1,vmax:12',
            'fpgto_datavenc'            =>'type:datebr',
            'fpgto_valorparc'           =>'type:decimalbr,vmin:1',
        ];
    }
    //o mesmo de fields_rules(), mas considerando a validação para no momento da extração do texto do pdf  (usados no arquivo App\ProcessRobot\cad_apolice\Process{Prod}Class.php)
    public static function fields_rules_extract($opt=null){
        $r=[];
        /* Adiciona as opções:
         *      exists  - precisa existir na matriz
         *      *       - obrigatório
         */
        $optional=[];
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
        return self::fields_rules();
    }

    //Executa a validação de campos antes de inserir no banco de dados
    //@param action - nome da ação para personalização do validate - valores: add, edit, null
    //@return true | array msg
    public static function fields_validate($data,$action=null){
        $required=['fpgto_datavenc','fpgto_valorparc'];//num - não precisa informar
        $validate = ValidateUtility::validateData($data, self::fields_rules(), $required );
        return $validate;
    }



    //labels dos campos //sintaxe: field=>label ou field=>[label,label_short]
    public static function fields_labels(){
        return [
            'num'                       =>'Nº Prestação',
            'fpgto_datavenc'            =>'Data Vencimento',
            'fpgto_valorparc'           =>'Valor da Parcela',
        ];
    }

    //formato de exibição dos campos
    //Obs: parâmetros conforme padrão da classe \App\Utilities\FormatUtility::formatData()
    //@param $mode - valores: view - visualização em tela, form - visualização em formulário (para ficar compatível com self::fields_html())
    public static function fields_format($mode='view'){
        return [
            'num'                       =>'',
            'fpgto_datavenc'            =>'type:datebr',
            'fpgto_valorparc'           =>'type:decimalbr',
        ];
    }

    //formata os dados antes de inserir no db //antes de class::fileds_rules()
    public static function fields_format_db_before($data,$opt=[]){
        /*$dateYearRef = $opt['model']->process_date??null;
        foreach([
            'fpgto_datavenc',
        ] as $f){
            if(isset($data[$f]))$data[$f] = FormatUtility::fixYearDateBr($data[$f], $dateYearRef );
        }*/
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
        return [
            //'num'                     =>['label'=>'Prestação','type'=>'select','list'=>['','1','2','3','4','5','6','7','8','9','10','11','12']],    //não precisa exibir este campo
            'fpgto_datavenc'            =>['label'=>'Vencimento','type'=>'date'],
            'fpgto_valorparc'           =>['label'=>'Valor','type'=>'currency'],
        ];
    }

    //posíveis ajustes finais nos campos para preparar os dados enviados para exibição
    //@param $data [field=>value,...] //return new $data
    public static function fields_show($data){
        return $data;
    }

    //função personalizada de validação extra executado ao gerar/salvar os dados e/ou  após funções validates acima: fields_rules, fields_validate
    //Obs: pode-se alterar o $data dentro desta função
    public static function validateAll(&$data, $data_all_split=null,$opt=[]){
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
}

