<?php

namespace App\ProcessRobot\cad_apolice;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;
use App\Services\PrSegService;
use Exception;

/**
 * Classe de utiliztários gerais para a leitura do pdf no arquiver
 * Deve ser extendida por cada /Process{produto}Class.php
 */


class QuiverClass{
    //texto do pdf
    protected $text='';

    /**
     * Opções gerais vindas da classe App\ProcessRobot\cad_apolice\{process_prod}\{insurer_name}Class()->process($text,$opt).
     * Campos esperados:
     *      path    - caminho do arquivo pdf atual
     *      url     - url do arquivo pdf atual
     *      venc_1a_parc_cartao - considerar data de vencimento da primeira parcela quando não informado, valores: vigencia, emissao, 30d_vigencia, 30d_emissao
     *      venc_1a_parc_debito - idem acima
     *      venc_1a_parc_boleto - idem acima
     *      venc_1a_parc_1boleto_debito - idem acima
     *      venc_1a_parc_1boleto_cartao - idem acima
     *      venc_ua_parc        - Quando a última parcela estiver como paga na apólice, valores: 1parc (Vencimento da primeira), 30d_u (30 dias após a penúltima parcela)
     */
    protected $process_opt=[];

    //model com o respectivo registro da tabela process_robot
    protected $process_model = null;

    //nome do recurso que processa o pdf (valores estão em \App\Utilities\FilesUtility::readPDF).
    protected $pdf_engine = 'pdfparser';

    //Validações da classe filha. Segue a mesma regra da var $fields_rules da classe de produto
    protected $validate_rules=[];

    //Campos obrigatórios adicionais para personalização na classe filha (obs: neste caso segue as regras existentes no validate da classe filha, ex ProcessAutomovelClass.php)
    protected $validate_required = [];//sintaxe field=>boolean

    //R$ margem de diferença aceita entre na verificação das parcelas com o prêmio total do seguro. Aceita o null ou false para desativar
    protected $validate_premio_margem=0.9;

    //R$ margem de diferença de centavos entre o cálculo do iof com o iof capturado em $data para o PgtoData::validateAll(). Obs: por padrão deveria ser igual, mas na prática existe diferença de centávos entre as apólices.
    protected $validate_iof_margem=0.05;

    //variável de teste
    protected $extract_test = false;

    //limite de caracteres considerados para a extração do texto
    protected $limite_text = 23000;

    //define se será alterado os valores já capturados dentro da função PgtoData::validateAll(..., $change_data)
    protected $pgto_data_change_data = true;

    //define se os dados terão uma validação extra de compração de valores por outro método
    //lógica: será validado na função validateBase() extraindo o pdf por um segundo método (que não seja ocr) e comparando cada valor se existe nesta nova extração
    //sintaxe: false ou array[ engine=>ws02, fields=>true(todos)|arr[field1,field2,...]|str(field1,field2,...)  ]
    protected $extra_validate_data_values = false;


    /**
     * @param ProcessRobot $model
     * @param boolean $extract_test - se true indica que está apenas testando a extração e não deve efetuar alterações. Default false.
     */
    public function __construct($model=null,$extract_test=false){
        $this->process_model = $model;
        $this->extract_test = $extract_test;
        if(method_exists($this,'initInsurer'))$this->initInsurer();//inicialização das classes trait insurers
    }


    public function getPdfEngine(){
        return $this->pdf_engine;
    }

    /**
     * Separa o texto com o divisor '<--extract:split-->' e seta os textos nas respectivas variáveis:
     *      $this->text             - texto principal
     *      $this->text_{pdfengine} - texto secundário com o nome do engine extrator. Ex: $this->text_ait_xpdfr=...
     * Sem retorno
     */
    protected function splitThisText($text_all){
        $n = explode('<--extract:split-->',$text_all);
        $this->text = $this->limitText( $n[0]??'' );

        if($this->pdf_engine=='ait_ocr01_xpdfr'){
            $this->{'text_ait_xpdfr'} = $this->limitText( $n[1]??'' );

        }elseif($this->pdf_engine=='ait_ocr01_aws'){
            $this->{'text_ait_aws'} = $this->limitText( $n[1]??'' );

        }elseif($this->pdf_engine=='ait_ocr01_tessrct'){
            $this->{'text_ait_tessrct'} = $this->limitText( $n[1]??'' );
        }
    }

    /**
     * Verifica se o texto extraído com dados de duas apólices diferentes
     * Válido apenas para os modos de extração $this->pdf_engine=='ait_ocr01_xpdfr|ait_ocr01_aws'
     * @param array $data - dados atuais extraídos do pdf
     * @return [success,msg,code]
     */
    private function checkThisTextDiff($data){
        if($this->pdf_engine=='ait_ocr01_xpdfr' || $this->pdf_engine=='ait_ocr01_aws'){//modo de extração com texto de 2 apólices
            $v=[
                'n' => $data['apolice_num']??'',
                //'q' => $data['apolice_num_quiver']??'',
                'c' => $data['corretor_susep']??'',
                't1'=> $this->text,
                't2'=> '' //$this->pdf_engine=='ait_ocr01_aws' ? $this->text_ait_aws : $this->text_ait_xpdfr,
            ];
            foreach([
                'ait_ocr01_aws'     => 'text_ait_aws',
                'ait_ocr01_tessrct' => 'text_ait_tessrct',
                'ait_ocr01_xpdfr'   => 'text_ait_xpdfr',
            ] as $f => $prop){
                if($f == $this->pdf_engine){
                    $v['t2'] = $this->$prop;
                    break;
                }
            }


            if(!$v['n'])return ['success'=>true];//retorna a true pois ainda não tem número da apólice para verificar
            if(!$v['t2'])return ['success'=>true];//retorna a true pois não existe o segundo texto, portanto somente o primeiro já será considerado ok


            //obs: o código abaixo foi desativado pois o campo 'apolice_num_quiver' não é mais gerado na classe que utilizada esta função, e portanto não tem como utilizá-lo
            //para isto é foi ajustado para utilizar o próprio campo 'apolice_num'
                //lógica: procura primeiro pelo texto do número da apólice do quiver (que é uma parte do número da apólice) e depois pega os 20 caracteres ao redor para comparar
                //$i = strpos($v['t1'],$v['q']);
                //$v['t1_n'] = $i!==false ? substr($v['t1'], $i-20,40) : '';
                //$i = strpos($v['t2'],$v['q']);
                //$v['t2_n'] = $i!==false ? substr($v['t2'], $i-20,40) : '';


            //retira a formatação e espaços de números no texto
            $v['n'] = str_replace(['.','-',' '],'',$v['n']);
            $v['t1'] = str_replace(['.','-',' '],'',$v['t1']);
            $v['t2'] = str_replace(['.','-',' '],'',$v['t2']);

            //lógica: procura primeiro pelo texto do número da apólice depois pega os 40 caracteres ao redor para comparar
            $i = strpos($v['t1'],$v['n']);
            $v['t1_n'] = $i!==false ? substr($v['t1'], $i-40,80) : '';
            $i = strpos($v['t2'],$v['n']);
            $v['t2_n'] = $i!==false ? substr($v['t2'], $i-40,80) : '';


            //lógica: procura primeiro pelo texto do número da supesp quiver (que pode ser uma parte do número completo) e depois pega os 20 caracteres ao redor para comparar
            $v['t1']=str_replace(['.','-'],'',$v['t1']); $v['t2']=str_replace(['.','-'],'',$v['t2']); //aqui já retira os pontos e traços para comparar a susep
            //dd($v['t2'], $v['c'],$data  );
            $i = strpos($v['t1'],$v['c']);
            $v['t1_c'] = $i!==false ? substr($v['t1'], $i-20,40) : '';
            $i = strpos($v['t2'],$v['c']);
            $v['t2_c'] = $i!==false ? substr($v['t2'], $i-20,40) : '';

            unset($v['t1'],$v['t2']);

            //formata retirando espaços e números
            foreach($v as $f=>$a){
                $v[$f]=str_replace(['.','-','/'], '', $v[$f]); //remove caracteres de formatação de números
                $v[$f]=str_replace([chr(13),chr(10),chr(9),'(',')',':',' '], '', $v[$f]); //remove as quebras de linhas e espaços
            }

            $find_n = $find_c = 0;
            //dd($v['n'],$v['c'],$v['t1_c'],$v['t2_c']);

            //lógica: verifica se o número da apólice é igual em ambos os textos
            if(strpos($v['t1_n'],$v['n'])!==false)$find_n++;
            if(strpos($v['t2_n'],$v['n'])!==false)$find_n++;

            //lógica: verifica se do corretor é igual em ambos os textos //obs: abaixo não separa por espaços à esquerda $v[c] pois o número do corretor pode estar assim ex: c='123' para '00000123'
            if(strpos($v['t1_c'],$v['c'])!==false)$find_c++;
            if(strpos($v['t2_c'],$v['c'])!==false)$find_c++;

            if($find_n==2 && $find_c==2){//encontrou o mesmo número de apólice nos dois textos
                return ['success'=>true];
            }else{
                return ['success'=>false,'msg'=>'Texto extraído com duas apólices divergentes','code'=>'extr02'];
            }

        }else{//modo de extração apenas com texto de 1 apólice
            return ['success'=>true];
        }
    }


    //Retorna ao número da primaira página
    protected function getPagina1(){
    	$text = TextUtility::getPartOfStr($this->text,['end'=>'cnpj']);
    	$i = strrpos($text, '{page-start:');
    	$pag = substr($text,$i,strlen($text));
    	$pag = TextUtility::getPartOfStr($pag,['split'=>chr(10),'start'=>'{page-start:','remove'=>['{page-start:','}']]);
    	if(is_numeric($pag)){
    		$pag=(int)$pag;
    	}else{
    		$pag=1;
    	}
    	return $pag;
    }

    //Função  complementar para reduzir a escrita do código na função process
    protected function getX1($opt1,$text=null){
        $optInit=['split'=>chr(10)];
        if(isset($opt1['split']) && $opt1['split']===false)unset($optInit['split']);
        return TextUtility::getPartOfStr($text??$this->text, array_merge($optInit,$opt1));
    }


    /**
     * Verifica / captura o tipo de seguro de acordo com as strings abaixo.
     * @oaram $prod - nome do produto/ramo, ex: automovel
     * Return string ao respectivo código encontrado, ou '' se não encontrado
     */
    protected function checkAllRamo($prod){
        //dd(\App\ProcessRobot\DetectTypeProcessRobot::getRamo($this->text));

        $text = FormatUtility::sanitizeBreakText($this->text);

        //String permitidas. Sintaxe: 'text1' ou ['text1','text2'] (neste caso ambas strings precisam existir)
        $list=[
            'automovel'=>[
                    '0520 - Automóvel',
                    '0524 - Automóvel',
                    '0525 - Automóvel',
                    '0526 - Automóvel',
                    '0531 - Automóvel',
                    '0542 - Automóvel',
                    '0553 - Automóvel',
                    '0588 - Automóvel',
                    '31 - AUTOMÓVEIS',
                    '31 Automoveis',
                    '31 Veiculos',
                    'Ramo 53 R.C.F. - Veiculos',
                    'Tokio Marine Auto',
                    'Tokio Marine Caminhão',
                    'SulAmérica Auto',
                    'Allianz Auto',
                    'Azul Seguro Auto',
                    'Auto azul',
                    '.0531.',
                    'Porto Seguro Auto',
                    'Porto Seguro Moto',
                    'Itaú Seguro Auto',
                    '0531-AUTOMÓVEL',
                    'Ramo: AUTOMOVEL',
                    'RAMO: 0531',
                    'Ramo: 31',
                    'Ramo: 53',
                    'Alfa Auto',
                    '0531 Automóvel',
                    '31 - Casco',
                    '53 - RCF Veículos',
                    'APÓLICE DE SEGURO DE AUTOMÓVEL',
                    'Ramo: 05.31',
                    'Ramo 31',
                    'MS AUTO',
                    'Ramo 53 R.C.F. - Auto',
            ],
            'residencial'=>[
                    'Ramo: 0114',
                    'Ramo: 01.14',
                    'susep 0114',
                    'Ramo 14',
                    'Ramo: 114',
                    'Ramo: 14',
                    'Ramo: 014',
                    'Ramo14',
                    'Alfa Residencia',
                    'Ramo:0114',
            ],
            'empresarial'=>[
                    'Ramo: 18',
                    'Ramo: 01.18',
                    'Ramo: 0118',
                    'Ramo: 118',
                    'Ramo 18',
                    'Ramo: 018',
                    'Ramo SUSEP 0118',
                    'Alfa Empresa',
                    'Ramo 0118',
                    'Ramo: 0118',
                    'Ramo: 118/141/351',
            ],
            'condominio'=>[
                    '',
            ]
        ];
        $text = str_replace(' ','',$text);//tira os espaços por possíveis problemas na extração
        $n='';
        foreach($list[$prod] as $item){
            $item = str_replace(' ','',$item);
            if(stripos($text,$item)!==false){
                $n=$item;
                break;
            }
        }
        return $n;
    }

    /**
     * Função base de validações dentro do método ValidateData() da classe filha (ex ProcessAutomovelClass)
     * @param $prod_name - valores: automovel, residencial...
     * @param $opt - valores:
     * @return [success=>, msg=>, data=>, validate=>, code=>, (opcional)ignore=>]
     *
     */
    protected function validateBase(&$data,$prod_name){
        $return=['validate'=>[]];

        //verifica se a apólice tem textos extraídos de duas ou mais apólices diferentes
            $r=$this->checkThisTextDiff($data);
            if(!$r['success'])return ['success'=>false,'data'=>$data,'msg'=>$r['msg'],'code'=>$r['code']];

        //obs: se for endosso não precisa verificar nenhuma informação, e apenas CPF/CNP //return false
            if($data['data_type']=='endosso')return ['success'=>false,'data'=>$data,'msg'=>'Endosso - não processado','ignore'=>true,'code'=>'read03'];        //validações implementadas pela classe filha

         //validações implementadas pela classe filha (cada classe em ProcessRobot\cad_apolice\{prod_name}\{insurer}Class.php
            if($this->validate_rules){
                $validate=ValidateUtility::validateData($data,$this->validate_rules);
                if($validate!==true)$return =['success'=>false,'msg'=>'Campos inválidos','data'=>$data,'validate'=>array_merge($return['validate'],$validate),'code'=>'read01'];
            }

        //validações adicionais antes da principal
            //regras para preenchimento dos campos em $data
            $fields_rules=[
                //'corretor_nome'             =>'exists,*',
                'corretor_susep'            =>'exists,*,ignore:{auto},min:8,type:int',
                //Dados da apólice
                'apolice_prod_ref'          =>'exists,*',
            ];
            //como este campo pode não ser especificado, então seta um valor padrão caso vazio
            if(empty($data['fpgto_desc']))$data['fpgto_desc']='0,00';


            //prossegue com a validação
            $validate=ValidateUtility::validateData($data,$fields_rules,$this->validate_required,['required_all'=>false]);
            if($validate!==true){
                if($validate['apolice_prod_ref']??false){
                    return ['success'=>false,'msg'=>'Ramo inválido','data'=>$data,'ignore'=>true,'code'=>'read02'];
                }else{
                    $return = ['success'=>false,'msg'=>'Campos inválidos','data'=>$data,'validate'=>array_merge($return['validate'],$validate),'code'=>'read01'];
                }
            }

        //divide os campos de $data pelos grupos: dados, parcelas, {prod}
            $PrSegService = new PrSegService;
            $data_split = $PrSegService->splitData($data,$prod_name);



         //prossegue com a validação por grupo
            $r = $PrSegService->validateAll($data, $data_split['parcelas'], $data_split[$prod_name], $prod_name, [
                    //opções de \App\ProcessRobot\cad_apolice\Classes\Segs\SegDados.php
                    'extract_text'=>$this->extract_test,
                    'processModel'=>$this->process_model,
                    'source'=>'extract',

                    //Campos obrigatórios para personalização adicional do respectivo validate já executado para tabela PrSeg...
                    'validate_required'=>$this->validate_required,//sintaxe field=>boolean
                    'validate_bro_ins'=>false, //nesta validação, não é necessário verificar o corretor e seguradora, pois esta função é responsável pela momento da extração do texto em campos, e portanto o corretor é verificado somente em outro process (analise as funções: ProcessCadApoliceController->processFilePDF(),extractTextFromPdf())

                    //opções...
                    'check_pgto'=>false,    //indica se deve verificar a compatibilidade do pgto
                    'allow_change'=>true,   //deve permitir que as funções de validação dentro de validateAll() corrijam as respectivas vars arr_dados|parcelas|prod
                ]);
            if($r!==true){
                $r['validate'] = array_merge($return['validate'],$r['validate']);
                $return = $r;
            }


            //retira todas as quebras de linhas e tabs
            foreach($data as $field => $value){
                $data[$field] = FormatUtility::sanitizeBreakText($value);
            }

            //valida todos os campos de pgto
            $n = PgtoData::validateAll($data,$data_split['parcelas'],$this->validate_premio_margem,$this->validate_iof_margem,$this->pgto_data_change_data);
            if(!$n['success'])$return = ['success'=>false,'data'=>$data,'msg'=>$n['msg'],'code'=>$n['code']??'read11','validate'=> $return['validate'] ];

            //é bem provável que as funções $PrSegService->validateAll e PgtoData::validateAll() tenha realizado modificações em $data
            //e portanto deve juntar os dados destas vars novamente
            $data_all = $PrSegService->joinData($data, $data_split['parcelas'], $data_split[$prod_name], $prod_name);


        if($return['validate'] || (isset($return['success']) && $return['success']==false)){
            if(!isset($return['data']))$return['data']=$data;
            return $return;
        }else{

            //validação extra
            if($this->extra_validate_data_values){
                $n=$this->extra_validate_data_values['engine']??null;
                $fields=$this->extra_validate_data_values['fields']??null;
                if($n && $fields){
                    if($fields===true)$fields=array_keys($data_all);
                    if(!is_array($fields))$fields=explode(',',$fields);
                    $new_text='';
                    if($n=='ws02'){
                        $new_text =  \App\Utilities\FilesUtility::readPDF($this->process_opt['path'],['engine'=>$n])['text'];
                    }
                    if(!$new_text){
                        return ['success'=>false,'data'=>$data_all,'msg'=>'Erro na extração do arquivo no modo de verificação extra','code'=>'extr05'];
                    }

                    //remove os campos ignorados
                    $ignore_fields=['apolice_prod_ref','data_type'];

                    //limpa e junta todo o texto
                    //como é apenas para comparar se o valor está correto, limpa os caracteres não alfanuméricos para ficar compatível no loop abaixo
                    $new_text=FormatUtility::removeAcents($new_text,true);
                    $new_text = str_replace([chr(13),chr(10),chr(9),'.','-',' '], '', $new_text);
                    //compara os valores
                    $r=[];
                    try{
                        foreach($fields as $f2){
                            if(in_array($f2,$ignore_fields))continue;
                            foreach($data_all as $f => $v){
                                if($f==$f2){
                                    if(is_string($v) & $v!=''){
                                        $v=FormatUtility::removeAcents($v,true);
                                        $v = str_replace([chr(13),chr(10),chr(9),'.','-',' '], '', $v);
                                        $r[$f] = stripos($new_text, (string)$v)!==false;
                                    }
                                    break;
                                }
                            }
                        }
                    } catch (Exception $e) {
                        dd('Erro na extração do arquivo no modo de verificação extra: ', $e);
                    }
                    //verifica os valores que não bateram
                    $fields=$r;
                    $r=[];
                    foreach($fields as $f=>$v){
                        if(!$v)$r[]=$f;
                    }
                    //dd($r, $data_all, $new_text);
                    if($r){
                        return ['success'=>false,'data'=>$data_all,'msg'=>'Um ou mais campos não compatíveis na verificação extra dos dados: '. join(',',$r),'code'=>'read25'];
                    }
                    //dd('compara',$r,$data_all,$new_text);
                }
            }

            return ['success'=>true,'data'=>$data_all,'msg'=>'','code'=>'ok'];
        }
    }


    /**
     * Limite o texto da extração a uma quantidade específica para comparação nas respectivas classes das seguradoras
     */
    public function limitText($text){
        return $text = substr($text,0,$this->limite_text);
    }


    /**
     * Gera a data da primeira parcela conforme parâmetros, considerando os parâmetros da var $process_opt[venc_1a_parc_cartao|debito|...]
     * @param $pgto_tipo - valores: cartao, debito, ...
     * @param $data - matriz de campos
     * @return string datebr | null
     * Ex de uso: $new_date = $this->getDateOpt('cartao',$data);
     */
    public function getDate1aParc($pgto_tipo,$data){
        //dd($pgto_tipo,$data);
        $d=null;
        if($pgto_tipo=='carne')$pgto_tipo='boleto'; //assume carne como boleto
        if(in_array($pgto_tipo,['cartao','debito','boleto','1boleto_debito','1boleto_cartao'])){
            $n = $this->process_opt['venc_1a_parc_' . $pgto_tipo];
            //dd($n);
            if($n=='vigencia' || $n=='30d_vigencia'){
                $d=$data['inicio_vigencia'];
            }elseif($n=='emissao' || $n=='30d_emissao'){
                $d=$data['data_emissao'];
            }
            if($n=='30d_vigencia' || $n=='30d_emissao'){
                $d=FormatUtility::addDate($d,'m',1,'datebr');
            }
        }
        //dd($d);
        return $d;
    }


    /**
     * Gera a data da última parcela conforme parâmetros, considerando os parâmetros da var $process_opt[venc_ua_parc]
     * @param $pgto_tipo - valores: cartao, debito, ...
     * @param $data - matriz de campos. Campos esperados
     *                  Se $pgto_tipo = 1parc     - considerar apenas o campo: fpgto_datavenc_1
     *                  Se $pgto_tipo = 30d_u     - considerar os campo: fpgto_n_prestacoes, fpgto_datavenc_{...}
     * @return string datebr | null
     * Ex de uso: $new_date = $this->getDateUaParc('cartao',$data);
     * Obs: esta função deve ser chamada sempre após a função getDate1aParc() dentro das respectivas classes, pois poderá ser usado o resultado da primeira parcela gerado por esta função getDate1aParc()
     */
    public function getDateUaParc($pgto_tipo,$data){
        // dd($pgto_tipo,$data);
         $d=null;
         if($pgto_tipo=='carne')$pgto_tipo='boleto'; //assume carne como boleto
         if(in_array($pgto_tipo,['cartao','debito','boleto','1boleto_debito','1boleto_cartao'])){
             $n = $this->process_opt['venc_ua_parc'];
             //dd($n);
             if($n=='1parc'){
                 $d=$data['fpgto_datavenc_1'];
             }
             if($n=='30d_u'){
                 $x= $data['fpgto_n_prestacoes']-1;
                 $d=$data['fpgto_datavenc_'.$x];
                 $d=FormatUtility::addDate($d,'m',1,'datebr');
             }
         }
         //dd($d);
         return $d;
    }

}
