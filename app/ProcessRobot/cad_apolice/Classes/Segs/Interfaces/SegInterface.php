<?php
namespace App\ProcessRobot\cad_apolice\Classes\Segs\Interfaces;

/**
 * Classe interface para as classes de tabelas de seguros
 */
interface SegInterface{
    //relação de campos para ignorar na exibição do log da baixa
    //@return array [hide_admin (exibe somente para o superadmin)=>[field1,...], not_quiver (não usados no quiver)=>[field1,...]]
    //caso não exista nenhum campo, pode retornar a [] ou false
    public static function fields_ignore_show();

    //regras para validação e ajustes dos campos antes para inserir no DB (mas tabelas pr_seg_...)
    //Obs: parâmetros conforme padrão da classe \App\Utilities\ ValidateUtility::validateData() ou FormatUtility::adjuteData()
    public static function fields_rules();

    //o mesmo de fields_rules(), mas considerando a validação para no momento da extração do texto do pdf
    //(usados no arquivo App\ProcessRobot\cad_apolice\Process{Prod}Class.php)
    //@param array $opt - valores: dados, parcelas, {prodname}, processModel,...
    public static function fields_rules_extract($opt=null);

    //regras para comparação de valores no modo de visualização de dados
    //ex: fab_code=FIAT na visualização, mas para inserir no DB é fab_code=123, portanto a regra considera a string FIAT para comparação
    //ex de ajuste: array_merge(self::fields_rules(),[ 'fab_code'=>'' ]);   //setando vazio já será considerando considerado comparação no modo de texto
    //utlizado pela classe PrSegService->getCtrlFieldsChanged()
    public static function fields_rules_view();

    //Executa a validação de campos antes de inserir no banco de dados
    //@param action - nome da ação para personalização do validate - valores: add, edit, null
    //@return true | array msg
    public static function fields_validate($data,$action);

    //labels dos campos //sintaxe: field=>label ou field=>[label,label_short]
    public static function fields_labels();

    //formato de exibição dos campos
    //Obs: parâmetros conforme padrão da classe \App\Utilities\FormatUtility::formatData()
    //@param $mode - valores: view - visualização em tela, form - visualização em formulário (para ficar compatível com self::fields_html())
    public static function fields_format($mode);

    //formata os dados antes de inserir no db
    //@param array $data
    //obs: por padrão antes de inserir no db, os dados já são formatados de acordo com a função self::fields_rules(), mas pode ser ajustada também de forma adicional por esta função
    public static function fields_format_db_before($data,$opt=null);//antes de class::fileds_rules()
    public static function fields_format_db_after($data,$opt=null);//depois de class::fileds_rules()

    //formato de exbibição dos dados que vierem do quiver para o robô (deve ser aplicado aos campos de Models\ProcessRobot arquivos execs)
    //@param e @return - os mesmos de fields_format()
    public static function fields_format_quiver();

    //parâmetros para geração de campos html
    //Obs: os parâmetros precisam ser compatíveis com o templates.ui.auto_fields
    public static function fields_html();

    //posíveis ajustes finais nos campos para preparar os dados enviados para exibição
    //@param $data [field=>value,...] //return new $data
    public static function fields_show($data);

    //função personalizada de validação extra executado ao gerar/salvar os dados e/ou  após funções validates acima: fields_rules, fields_validate
    //Obs: pode-se alterar o $data dentro desta função
    //@param array $data_all_split - [dados=>, parcelas=> {prod}=>]
    //@param array $opt - opções gerais (que variam) para cada classe
    //@return true || [field1=>msg1,... ]
    public static function validateAll(&$data,$data_all_split=null,$opt=[]);
}
