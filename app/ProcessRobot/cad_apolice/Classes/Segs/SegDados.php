<?php

namespace App\ProcessRobot\cad_apolice\Classes\Segs;
use Illuminate\Database\Eloquent\Model;
use App\Utilities\ValidateUtility;
use App\Utilities\FormatUtility;
use App\ProcessRobot\cad_apolice\Classes\Vars\QuiverVar;
use App\ProcessRobot\VarsProcessRobot;

class SegDados implements Interfaces\SegInterface{
     //dias limite após início da vigência em que esta apólice possa ser processada (depois do limite será considerado erro)
    private static $vigencia_limit_days=365;

    //dias limite entre a vigência e emissão
    private static $emissao_vigencia_limit_days=76;



    //retorna a relação de campos organizados em grupos para melhor visualização do frontend
    public static function fields_layoutGroup(){
        return [
            'dados'=>[
                'label'=>'Dados',
                'fields'=>['data_type','proposta_num','apolice_num','apolice_num_quiver','data_emissao','apolice_re_num','inicio_vigencia','termino_vigencia','segurado_nome','segurado_doc','tipo_pessoa'],
            ],
            'premio'=>[
                'label'=>'Prêmio',
                'fields'=>['fpgto_tipo_code','fpgto_n_prestacoes','fpgto_1_prestacao_valor','fpgto_1_prestacao_venc','fpgto_venc_dia_2parcela','fpgto_avista','fpgto_premio_total','fpgto_premio_liquido','fpgto_premio_liq_serv','fpgto_custo','fpgto_adicional','fpgto_iof','fpgto_juros','fpgto_juros_md','fpgto_desc','comissao_premio'],
            ],
            'anexo'=>[
                'label'=>'Anexo',
                'fields'=>['anexo_upl'],
            ]
        ];
    }

    //relação de campos para ignorar na exibição do log da baixa. Parâmetro $type: hide_admin (ocultados para o admin), not_quiver (não usados no quiver)
    public static function fields_ignore_show(){
        return [
            'hide_admin'=>['apolice_re_num'],
            'not_quiver'=>['data_type','apolice_num','apolice_re_num','segurado_nome','tipo_pessoa','segurado_doc'],
        ];
    }


    //regras para validação e ajustes dos campos antes para inserir no DB (mas tabelas pr_seg_...)
    //Obs: parâmetros conforme padrão da classe \App\Utilities\ ValidateUtility::validateData() ou FormatUtility::adjuteData()
    public static function fields_rules(){
        return [
            'data_type'                 =>'max:12,values:apolice|historico|endosso',
            'proposta_num'              =>'max:20',
            'apolice_num'               =>'max:30',
            'apolice_num_quiver'        =>'type:intstr,max:20',     //obs: esta regra é global para todos os casos, e cada classe em \App\ProcessRobot\cad_apolice\Classes\NumQuiver\{insurer}... pode ter suas próprias regras
            'data_emissao'              =>'type:datebr',
            'apolice_re_num'            =>'max:20',
            'inicio_vigencia'           =>'type:datebr',
            'termino_vigencia'          =>'type:datebr',
            'segurado_nome'             =>'max:50',
            'segurado_doc'              =>'max:20',
            'tipo_pessoa'               =>'max:1,values:FISICA|JURIDICA|f|j,values_to:f|j|f|j',
            'fpgto_tipo_code'           =>'type:int,max:6',
            'fpgto_n_prestacoes'        =>'type:int,values:1|2|3|4|5|6|7|8|9|10|11|12|24|36',
            'fpgto_1_prestacao_valor'   =>'type:decimalbr,empty:0',
            'fpgto_1_prestacao_venc'    =>'type:datebr',
            'fpgto_venc_dia_2parcela'   =>'type:int,vmin:1,vmax:31',
            'fpgto_avista'              =>'type:int,vmin:0,vmax:30,values:avista|30dias|0|30,values_to:0|30|0|30',
            'fpgto_premio_total'        =>'type:decimalbr,empty:0',
            'fpgto_premio_liquido'      =>'type:decimalbr,empty:0',
            'fpgto_premio_liq_serv'     =>'type:decimalbr,empty:0',
            'fpgto_custo'               =>'type:decimalbr,empty:0',
            'fpgto_adicional'           =>'type:decimalbr,empty:0',
            'fpgto_iof'                 =>'type:decimalbr,empty:0',
            'fpgto_juros'               =>'type:decimalbr,empty:0',
            'fpgto_juros_md'            =>'type:decimalbr,empty:0',
            'fpgto_desc'                =>'type:decimalbr,empty:0',

            //'comissao_premio'         =>'',//este campo não precisa ser validado, pois existe apenas no retorno do robô
        ];
    }

    //o mesmo de fields_rules(), mas considerando a validação para no momento da extração do texto do pdf (usados no arquivo App\ProcessRobot\cad_apolice\Process{Prod}Class.php)
    public static function fields_rules_extract($opt=null){
        $r=[];
        /* Adiciona as opções:
         *      exists  - precisa existir na matriz
         *      *       - obrigatório
         */
        $optional=['apolice_re_num'];
        foreach(self::fields_rules() as $f=>$v){
            if($f=='apolice_num_quiver')continue;//atualizar esta linha
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
        $required=['data_type','proposta_num','apolice_num','apolice_num_quiver','data_emissao','inicio_vigencia','termino_vigencia','segurado_nome','segurado_doc','tipo_pessoa','fpgto_tipo_code','fpgto_n_prestacoes','fpgto_1_prestacao_valor','fpgto_1_prestacao_venc','fpgto_venc_dia_2parcela','fpgto_avista','fpgto_premio_total','fpgto_premio_liquido','fpgto_iof'];
        $validate = ValidateUtility::validateData($data, self::fields_rules(), $required);
        return $validate;
    }

    //labels dos campos //sintaxe: field=>label ou field=>[label,label_short]
    public static function fields_labels(){
        return [
            'data_type'                 =>'Tipo',
            'proposta_num'              =>'Nº da Proposta',
            'apolice_num'               =>'Nº da Apólice',
            'apolice_num_quiver'        =>'Nº da Apólice Quiver',
            'data_emissao'              =>'Data de Emissão',
            'apolice_re_num'            =>'Nº da Renovação',
            'inicio_vigencia'           =>'Início da Vigência',
            'termino_vigencia'          =>'Final da Vigência',
            'segurado_nome'             =>'Nome do Segurado',
            'tipo_pessoa'               =>'Tipo do Documento',
            'segurado_doc'              =>'Documento do Segurado',
            'fpgto_tipo_code'           =>'Forma de Pagamento',
            'fpgto_n_prestacoes'        =>'Número de Prestações',
            'fpgto_1_prestacao_valor'   =>'Valor da 1ª Prestação',
            'fpgto_1_prestacao_venc'    =>'Data da primeira prestação',
            'fpgto_venc_dia_2parcela'   =>'Dia das demais prestações',
            'fpgto_avista'              =>'À vista ou 30 dias',
            'fpgto_premio_total'        =>'Prêmio Total',
            'fpgto_premio_liquido'      =>'Prêmio Líquido',
            'fpgto_premio_liq_serv'     =>'Prêmio de Serviço',
            'fpgto_custo'               =>'Custo da Apólice',
            'fpgto_adicional'           =>'Custos Adicionais',
            'fpgto_iof'                 =>'IOF',
            'fpgto_juros'               =>'Juros ',
            'fpgto_juros_md'            =>'Juros Melhor Data',
            'fpgto_desc'                =>'Descontos',
            'comissao_premio'           =>'Comissão do Prêmio %',

            //obs: este campo anexo, só deve existir nesta função '...labels()', pois não é editável por formulário (tem valor fixo)
            'anexo_upl'                 =>'Apólice',

            //campos adicionais que só existem aqui no label
            'insurer_id'                =>'Seguradora',
            'broker_id'                 =>'Corretora',
        ];
    }

    //formato de exibição dos campos
    //Obs: parâmetros conforme padrão da classe \App\Utilities\FormatUtility::formatData()
    //@param $mode - valores: 'view' - visualização em tela,
    //                        'form' - visualização em formulário (para ficar compatível com self::fields_html())
    public static function fields_format($mode='view'){
        $QuiverVar = \App\ProcessRobot\cad_apolice\Classes\Vars\QuiverVar::class;
        return [
            'data_type'                 =>function($v) use($mode){return $mode=='form' ? $v : VarsProcessRobot::$typesApolices[$v]??$v ;},
            'proposta_num'              =>'',
            'apolice_num'               =>'',
            'apolice_num_quiver'        =>'',
            'data_emissao'              =>'type:datebr',
            'apolice_re_num'            =>'',
            'inicio_vigencia'           =>'type:datebr',
            'termino_vigencia'          =>'type:datebr',
            'segurado_nome'             =>'',
            'tipo_pessoa'               =>$mode=='form' ? '' : 'values:f|j,values_to:FISICA|JURIDICA',
            'segurado_doc'              =>'',
            'fpgto_tipo_code'           =>function($v) use($QuiverVar,$mode){return $mode=='form' ? $v : $QuiverVar::$pgto_all_codes[$v]??$v ;},
            'fpgto_n_prestacoes'        =>'',
            'fpgto_1_prestacao_valor'   =>'type:decimalbr',
            'fpgto_1_prestacao_venc'    =>'type:datebr',
            'fpgto_venc_dia_2parcela'   =>'',
            'fpgto_avista'              =>$mode=='form' ? '' : 'values:0|30|30dias,values_to:A VISTA|30 DIAS|30 DIAS',
            'fpgto_premio_total'        =>'type:decimalbr',
            'fpgto_premio_liquido'      =>'type:decimalbr',
            'fpgto_premio_liq_serv'     =>'type:decimalbr',
            'fpgto_custo'               =>'type:decimalbr',
            'fpgto_adicional'           =>'type:decimalbr',
            'fpgto_iof'                 =>'type:decimalbr',
            'fpgto_juros'               =>'type:decimalbr',
            'fpgto_juros_md'            =>'type:decimalbr',
            'fpgto_desc'                =>'type:decimalbr',
            'comissao_premio'           =>'type:decimalbr',
        ];
    }

    //formata os dados antes de inserir no db //antes de class::fileds_rules()
    public static function fields_format_db_before($data,$opt=[]){
        /*$dateYearRef = $opt['model']->process_date??null;
        foreach([
            'data_emissao',
            'inicio_vigencia',
            'termino_vigencia',
            'fpgto_1_prestacao_venc',
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
        $r = self::fields_format('view');
        $r['fpgto_avista'] = 'values:1|2,values_to:A VISTA|30 DIAS';
        return $r;
    }

    //parâmetros para geração de campos html
    //Obs: os parâmetros precisam ser compatíveis com o templates.ui.auto_fields
    public static function fields_html(){
        $QuiverVar = \App\ProcessRobot\cad_apolice\Classes\Vars\QuiverVar::class;
        $r=[
            'data_type'                 =>['maxlength'=>12,'type'=>'select','list'=>[''=>'','apolice'=>'Apólice','historico'=>'Histórico','endosso'=>'Endosso']],
            'proposta_num'              =>['maxlength'=>35],
            'apolice_num'               =>['maxlength'=>30],
            'apolice_num_quiver'        =>['maxlength'=>20],
            'data_emissao'              =>['type'=>'date'],
            'apolice_re_num'            =>['maxlength'=>20],
            'inicio_vigencia'           =>['type'=>'date'],
            'termino_vigencia'          =>['type'=>'date'],
            'segurado_nome'             =>['maxlength'=>50],
            'tipo_pessoa'               =>['type'=>'select','list'=>[''=>'','f'=>'FISICA','j'=>'JURIDICA']],
            'segurado_doc'              =>['maxlength'=>50],
            'fpgto_tipo_code'           =>['maxlength'=>2,'type'=>'select',
                'list'=> [''=>''] + array_intersect_key($QuiverVar::$pgto_all_codes, array_flip(array_keys($QuiverVar::$pgto_codes_types)))
            ],
            'fpgto_n_prestacoes'        =>['type'=>'select','list'=>[''=>'','1'=>'1','2'=>'2','3'=>'3','4'=>'4','5'=>'5','6'=>'6','7'=>'7','8'=>'8','9'=>'9','10'=>'10','11'=>'11','12'=>'12','24'=>'24','36'=>'36']],
            'fpgto_1_prestacao_valor'   =>['type'=>'currency'],
            'fpgto_1_prestacao_venc'    =>['type'=>'date'],
            'fpgto_venc_dia_2parcela'   =>['attr'=>'min="1" max="31"'],
            'fpgto_avista'              =>['type'=>'select','list'=>[''=>'','0'=>'A VISTA','30'=>'30 dias']],
            'fpgto_premio_total'        =>['type'=>'currency'],
            'fpgto_premio_liquido'      =>['type'=>'currency'],
            'fpgto_premio_liq_serv'     =>['type'=>'currency'],
            'fpgto_custo'               =>['type'=>'currency'],
            'fpgto_adicional'           =>['type'=>'currency'],
            'fpgto_iof'                 =>['type'=>'currency'],
            'fpgto_juros'               =>['type'=>'currency'],
            'fpgto_juros_md'            =>['type'=>'currency'],
            'fpgto_desc'                =>['type'=>'currency'],

            //'comissao_premio'         =>'',//este campo não precisa estar no formulário, pois é gerado automaticamente e existe apenas no retorno do robô
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
        $data['fpgto_tipo_code__text'] = QuiverVar::$pgto_codes_types[$data['fpgto_tipo_code']]??'';
        $data['fpgto_avista__text'] = ['0'=>'AVISTA','30'=>'30DIAS'][$data['fpgto_avista']]??'';
        return $data;
    }


    //função personalizada de validação extra executado ao gerar/salvar os dados e/ou  após funções validates acima: fields_rules, fields_validate
    //Obs: pode-se alterar o $data dentro desta função
    public static function validateAll(&$data,$data_all_split=null,$opt=[]){
        $opt=array_merge([
            'extract_test'=>false,
            'processModel'=>null,
            'source'=>null  //null | extract (indica que é uma validação da extração do pdf)
        ],$opt);


        //valida a data de emissão que não pode ter muita diferença da data de vigência
        if(isset($data['inicio_vigencia']) && isset($data['data_emissao'])){
            $n=(int)FormatUtility::dateDiffFull($data['inicio_vigencia'], $data['data_emissao'],'d1');
            if($n<0)$n*=-1;
            if($n>self::$emissao_vigencia_limit_days){
                return ['data_emissao'=>'Emissão fora do limite da vigência ('. $n .' dias)','code'=>'read08'];
                //dd($n,$data['inicio_vigencia'], $data['data_emissao']);
            }
        }else{
            return ['data_emissao'=>'Vigência ou emissão inválidos','code'=>'read08'];
        }
        //if(1 || env('APP_ENV')!='local'){//em local não precisa verificar por causas dos testes
            if(!$opt['extract_test']){//verifica apenas se não for extração de teste
                //*** validações para todos os casos ***
                $d=$opt['processModel']->created_at->diff(new \DateTime(FormatUtility::convertDate($data['data_emissao'])));
                if($d->days>self::$vigencia_limit_days)return ['data_emissao'=>'Emissão fora do prazo permitido ('. ($d->days) .' dias)','code'=>'read09'];
            }
        //}


        if($opt['source']=='extract'){
                //verifica se o campo seguradora_doc é um cnpj um um número de susep
                if($data['seguradora_doc']!='{auto}'){
                    if(!ValidateUtility::isCNPJ($data['seguradora_doc'])){//não é um cnpj
                        $n=explode('.',str_replace(['/','-'],'.',$data['seguradora_doc']));//troca de 99999.999999/999-99 para 99999.999999.999.99
                        if(strlen($n[0]??'')!=5 || strlen($n[1]??'')!=6 || strlen($n[2]??'')!=4 || strlen($n[3]??'')!=2){
                            return ['seguradora_doc'=>'Número de CNPJ ou SUSEP inválido'];
                        }
                    }
                }

                //em alguns casos vem uma letra no número da apólice, e neste caso segue a lógica:
                //  pode existir apenas uma letra, até dois traço, pode conter pontos separadores e o restante deve ser obrigatoriamente número
                $count_letter=0;
                $count_trace=0;
                $num_original = str_replace('.','',$data['apolice_num']);
                $num_only='';
                for($i=0; $i<strlen($num_original); $i++){
                    $n = substr($num_original,$i,1);
                    if($n=='-'){
                        $count_trace++;
                    }elseif(!is_numeric($n)){
                        $count_letter++;
                    }else{
                        $num_only.=$n;
                    }
                }
                //dd($num_only,$num_original,$count_trace, $count_letter);
                if($count_trace>2 || $count_letter>1){
                    return ['apolice_num'=>'Número de apólice inválido'];
                }elseif(is_numeric($num_only)==false){
                    return ['apolice_num'=>'Número de apólice inválido'];
                }

                //verificação 2
                $n = str_replace(['0'],'',$num_only);
                if($n=='' || !is_numeric($n)){
                    return ['apolice_num'=>'XXXNúmero de apólice inválido'];
                }
        }

        //a data final da vigência tem que ser maior que a data inicial
        $a=$data['inicio_vigencia'];$b=$data['termino_vigencia'];
        $diff=(int)FormatUtility::dateDiffFull($a,$b,'d1');//diferença de dias entre as datas
        //dd($a,$b,$diff);
        if(ValidateUtility::ifDate($a, '>', $b) || ($diff<29 || $diff>366)){//diferença entre 1 mês a 1 ano
            //verifica se a apólice é de 2 anos ou 3 anos
            if(in_array($diff,[730,731,1095,1096])){//quer dizer que tem 2 ou 3 anos (considera a variação por causa do ano bicesto)
                //nenhuma ação
            }else{
                return ['termino_vigencia'=>'Data início e término da vigência incompatíveis','code'=>'read10'];
            }
        }

        return true;
    }


    /**
     * Retorna aos códigos utilizados para conversão do texto retornado do robô do quiver em respectivo código padrão desta aplicaçao.
     * As variáveis com códigos estão em \App\ProcessRobot\cad_apolice\Vars\....
     */
    public static function getVarsCodeFromText(){
        //obs: é obrigatório que o campo abaixo (ex fpgto_tipo) tenha seu respectivo campo de código com '_code' (ex fpgto_tipo_code)
        return [
            'fpgto_tipo' => QuiverVar::$pgto_all_codes
        ];
    }
}

