<?php

namespace App\ProcessRobot\cad_apolice\Classes\Insurers;
use App\Utilities\TextUtility;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;



/**
 * Classe trait de funções funções gerais para leitura de apólices pdf de qualquer ramo da Seguradora Sompo
 * Deve ser incorporada a partir da uma classe de um ramo específico, como a classe ex: App\ProcessRobot\cad_apolice\automovel\hdiClass.php
 */
trait azulInsurer{

    //método de inicialização
    public function initInsurer(){
         $this->pdf_engine = 'ait_ocr01_tessrct'; //nome do interpretador de pdf (valores em  \App\Utilities\FilesUtility::readPDF)
        $this->validate_premio_margem=21.5;//(R$) limite de diferença por causa de possíveis juros das parcelas

        //Campos obrigatórios adicionais para personalização na classe filha (obs: neste caso segue as regras existentes no validate da classe filha, ex ProcessAutomovelClass.php)
        $this->validate_required = ['segurado_pernoite_cep'=>false];//sintaxe field=>boolean
        $this->validate_iof_margem=1.70;

        //Validações da classe filha. Segue a mesma regra da var $fields_rules da classe de produto
        //Campos obrigatórios adicionais para personalização na classe filha (obs: neste caso segue as regras existentes no validate da classe filha, ex ProcessAutomovelClass.php)
        $this->validate_required = ['veiculo_cod_fipe'=>false];//sintaxe field=>boolean

        //$this->text;                //ocr01 google
       // $this->text_ait_tessrct;    //tessrct
    }


      /**
     * Configuração para o padrão do número do quiver
     */
    function numQuiverConfig(){
        return [
            'ex_num'=>'19.21.0531.047013.000',
            'between_dots'=>true,
            'not_zero_left'=>true
        ];
    }
/**
     * Retorna os dados do segurado
     * @return error:   [success,msg,data,ignore,code]
     *         ok:      [sucess,data]
     */
    public function getDados(){
        $data=[];
        $data['data_type']='apolice';
        //dd($this->text);
        //captura o tipo da apólice - para verificar se é do tipo automovel
        $data['apolice_prod_ref'] = $this->checkRamo();
        if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];

        $n = $this->getX1(['start'=>'Azul Seguro Auto','end'=>'Nome','sanitize'=>true ]);
        //dd($n);
        if(strpos($n,'endosso')!==false){
                    return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];
         }

        $text_ap = TextUtility::getPartOfStr($this->text, ['start'=>'apólice:','end'=>'Vigência','remove'=>['Vigência','apólice:']]);
        $n = $text_ap;//numero da apólice
        $n = str_replace([' '],[''], $n);
        //$apolice_num= str_replace('.', '', $n);
        $data['apolice_num'] = $n;//numero da apólice

        //numero da apólice Quiver
        //$data['apolice_num_quiver'] = ltrim($n,0);//numero da apólice Quiver

        $data['apolice_re_num']='';//não tem número da apólice renovada na Azul


        $n = $this->getX1(['start'=>'Proposta','cb'=>function($v){ return explode(".",$v)[1]??'';  }]);//número da proposta
        $n = str_replace([' ','.'],['',''], $n);
        $data['proposta_num'] = $n;

        $tmp = str_replace(['2020O','202o'], ['2020','2020'], $this->text);
        $tmp = TextUtility::getPartOfStr($tmp,['start'=>'nr. registro susep:']);
        $n = TextUtility::getSearchText($tmp,'nr. registro susep:','datebr',['side'=>'right']);
        $data['data_emissao'] = $n;//data de emissão

        $data['inicio_vigencia']='';
        $data['termino_vigencia']='';
        $dt_vigencia = $this->getX1(['start'=>'Nr. Apolice:','end'=>'Processo SUSEP','sanitize'=>true,'split'=>false ]); //data de inicio da vigencia
        $n='';
        TextUtility::execFncInStr($dt_vigencia,10,function($v) use(&$n){
            $v=trim($v);
            if(ValidateUtility::isDate($v) && strlen($v)==10){
                $n=$v;
                return true;
            }
        });
        if(!$n){
            $n=TextUtility::getPartOfStr($this->text_ait_tessrct,['start'=>'Vigência do Seguro:','end'=>'Processo ']);
            $n= TextUtility::getSearchText($n,'','datebr',['limit'=>false]);
            $data['inicio_vigencia']=$n[0]??'';
            $data['termino_vigencia']=$n[1]??'';

        }else{
            $data['inicio_vigencia'] = $n;//data de inicio da vigencia

            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'24h de','sanitize'=>true]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Processo']);
            $n=[];

            TextUtility::execFncInStr($blocktext,10,function($v) use(&$n){
                if(ValidateUtility::isDate($v) && strlen(trim($v))==10){
                    $n[]=$v;
                }
            });
            $data['termino_vigencia'] = $n[1];
        }


        if( $data['inicio_vigencia'] == $data['termino_vigencia']){
            $data['inicio_vigencia'] = TextUtility::getSearchText($blocktext,'24h','datebr',['side'=>'right']);
            $n = TextUtility::getPartOfStr($blocktext, ['start'=>'vigencia','sanitize'=>true]);
            //dd($data['inicio_vigencia']);
            $data['termino_vigencia'] = TextUtility::getSearchText($n,'vigencia','datebr',['side'=>'right']);
        }

        //Dados do Corretor
        $n = $this->getX1(['start'=>'SUSEP:','cb'=>function($v){ return explode(" ",$v)[1]??'';  }]);//Susep Corretor
        $n = str_replace(['.',')','-'],['','',''], $n);
        $data['corretor_susep'] = ltrim(ltrim(strtolower($n),'o'),'0');

        //$data['corretor_nome'] = $n;

        //Dados do segurado
        $data['segurado_nome'] = $this->getX1(['start'=>'Proposta:','return_type'=>'next']);//nome do segurado
        if(empty($data['segurado_nome'])){
            $text_nome = $this->getX1(['start'=>'Nome:','end'=>'Endereço:','sanitize'=>false,'split'=>false ]);
            $text_nome = trim(str_replace(['Nome:','Endereço:'],'', $text_nome));
            $data['segurado_nome'] = $text_nome;//nome do segurado
        }
        //dd($text_nome);
        $doc_segurado = $this->getX1(['start'=>'Nome:','end'=>'Endereço:','sanitize'=>true,'split'=>false ]);//doc do segurado

        TextUtility::execFncInStr($doc_segurado,18,function($v) use(&$n){
            $v=trim($v);
            if(ValidateUtility::isCPF($v) || ValidateUtility::isCNPJ($v)){
                $n=$v;
                return true;
            }
        });
       $n =  preg_replace("/[^0-9]/", "", $n);

        if(strpos($n,'cnpj:')!==false){
            $n = explode(' ', $n);
            $data['segurado_doc'] = $n[1];//doc do segurado
        }else{
            $data['segurado_doc'] = $n;//doc do segurado
        }


        if(strlen($data['segurado_doc'])<=12){
            $n = 'fisica';
        }else{
            $n = 'juridica';
        }
        $data['tipo_pessoa'] = $n;// tipo pessoa

        return ['success'=>true,'data'=>$data];
        //dd($data,$this->text);
    }



    /**
     * Retorna os dados do premio
     */
    public function getPremio_bkp($data){
         //forma de pagamento
         $block_text = $this->getX1(['start'=>'Coberturas','end'=>'SUSEP:','sanitize'=>true,'split'=>false],$this->text_ait_tessrct);

         //dd($block_text,$this->text_ait_tessrct);
         $block_text = str_replace(['debitО ','debІТo'], ['debito ','debito '], $block_text);
         $block_text = str_replace([', ',',  ',',   ',' ,','  ,'],',',$block_text);

         //pagamento: tipo
         $n=PgtoData::getPgtoTipo($block_text);
          $data['fpgto_tipo'] = $n;
        if(empty($data['fpgto_tipo'])){
            $block_text = $this->getX1(['start'=>'COBRANÇA EM:','end'=>'Coberturas','sanitize'=>false,'split'=>false],$this->text_ait_tessrct);
            $block_text = str_replace(['debitО ','debІТo'], ['debito ','debito '], $block_text);
            $block_text = str_replace([', ',',  ',',   ',' ,','  ,'],',',$block_text);
            //dd($block_text);
            $n=PgtoData::getPgtoTipo($block_text);
            $data['fpgto_tipo'] = $n;
        }

         //dd( $data['fpgto_tipo']);
         $data['fpgto_tipo_code'] = PgtoData::getPgtoCode($data['fpgto_tipo'])[1];


         //dd( $data['fpgto_tipo']);
         //pagamento: tabela

         if($data['fpgto_tipo']=='cartao' || $data['fpgto_tipo']=='boleto' || $data['fpgto_tipo']=='1boleto_debito'){

             $block_text = $this->getX1(['start'=>'COBRANCA EM','sanitize'=>true,'split'=>false],$this->text_ait_tessrct);
             $block_text = str_replace([', ',',  ',',   ',' ,','  ,'],',',$block_text);
             if(!$block_text){
                 $block_text = $this->getX1(['start'=>'valor vencimento','sanitize'=>true,'split'=>false],$this->text_ait_tessrct);
                 $block_text = str_replace([', ',',  ',',   ',' ,','  ,'],',',$block_text);
             }

             if(
                    (strpos($block_text,'proposta:')!=false || $data['fpgto_tipo']=='boleto')
                    ||
                    (strpos($block_text,'proposta:')!=false && $data['fpgto_tipo']=='cartao')
                    ||
                    (strpos($block_text,'seguros as 24h')!=false && $data['fpgto_tipo']=='cartao')
                ){
                $block_text = $this->getX1(['start'=>'COBRANCA EM','sanitize'=>true,'split'=>false],$this->text);
                $block_text = str_replace([', ',',  ',',   ',' ,','  ,'],',',$block_text);
                if(!$block_text){
                    $block_text = $this->getX1(['start'=>'valor vencimento','sanitize'=>true,'split'=>false],$this->text);
                    $block_text = str_replace([', ',',  ',',   ',' ,','  ,'],',',$block_text);
                }
             }

             $n = FormatUtility::getPartOfStr($block_text,['end'=>'CONSULTE AS CONDICOES']);
             //dd($n,$block_text);
             //reavaliar trecho abaixo caso surja novos casos
             //if(strpos($n,'valores em reais')!==false){
                //$n = FormatUtility::getPartOfStr($n,['end'=>'valores em reais']);
            // }

             if(!$n)$n = FormatUtility::getPartOfStr($block_text,['end'=>'CONSULTE AS SEGURO']);

             if($n)$block_text=$n;
             $block_text = str_replace(['_','-', '  '], ['','',' '], $block_text);
             //dd($block_text);

             if(strpos($block_text,'cartao outras emissoras')!=false || strpos($block_text,'cartao porto seguro')!=false){
                $block_text = $this->getX1(['start'=>'COBRANCA EM','sanitize'=>true,'split'=>false],$block_text);
                $block_text = str_replace([', ',',  ',',   ',' ,','  ,'],',',$block_text);
                if(strpos($block_text,'cartao porto seguro')!=false){
                    $block_text = $this->getX1(['start'=>'parcela valor vencimento','sanitize'=>true,'split'=>false],$block_text);
                    $block_text = str_replace([', ',',  ',',   ',' ,','  ,'],',',$block_text);
                    //dd($block_text);
                }


             }
             //verifica o número de valores e datas disponíveis

             $block_text = FormatUtility::sanitizeAllText($block_text);
             $block_text = str_replace([', ',',  ',',   ',' ,','  ,'],',',$block_text);
             if(strpos($block_text,'preco do seguro')!=false && strpos($block_text,'ficha de comp')!=false){
                $block_text = FormatUtility::getPartOfStr($block_text,['end'=>'preco do seguro']);
                $block_text = str_replace([', ',',  ',',   ',' ,','  ,'],',',$block_text);
                //dd(123);
             }


             $block_text = str_replace([', ,'], [','], $block_text);
             $block_text = str_replace([', '], [','], $block_text);
             $block_text = str_replace([' ,'], [','], $block_text);
             $block_text = str_replace(['202o'], ['2020'], $block_text);
             $block_text = preg_replace('/(\d+)\.\s/', '$1.', $block_text);//substitui toda string com "digito+'.'+' '" por "digito + '.'". Ex de '1. 234,56' por '1.234,56'
             //dd($block_text);
             //procura todos os valores que possam ter ponto '.' e troca por virgula (ex de 123.45 para 123,45)
             foreach(explode(' ',$block_text) as $n){
                 //procura todos os números assim: '999.99'
                 if(is_numeric($n) && strlen(explode('.',substr($n,-3))[1]??'')==2){
                     $block_text = str_replace($n, FormatUtility::numberFormat($n),$block_text);
                 }
             }

             //dd($block_text);
             $block_text = str_replace(', ',',',$block_text);
             $block_text = str_replace([', ',',  ',',   ',' ,','  ,'],',',$block_text);
             $valores = TextUtility::getSearchText($block_text,'','number_formated',['limit'=>false]);
             $datavenc = TextUtility::getSearchText($block_text,'','datebr',['limit'=>false]);

             //dd($valores, $datavenc,$block_text);
             if(count($datavenc)==1 && count($valores)==1){
                $block_text = $this->getX1(['end'=>'valores','sanitize'=>true,'split'=>false],$block_text);
                $block_text = str_replace([', ',',  ',',   ',' ,','  ,'],',',$block_text);
             }
             $block_text = str_replace(['conf.fat.cartao'], [' conf.fat.cartao'], $block_text);
             $block_text = str_replace(['conf.fat.carta'], [' conf.fat.cartao'], $block_text);
             $block_text = str_replace(['conf.fat .cartao'], [' conf.fat.cartao'], $block_text);
             $block_text = str_replace(['conf.fat.cartaoo'], [' conf.fat.cartao'], $block_text);
             $block_text = str_replace(['. '], ['.'], $block_text);
             $block_text = str_replace([' .'], ['.'], $block_text);

             //dd($block_text);
             $datavenc_texto = TextUtility::getSearchText($block_text,'',function($v){//procura por descrições no lugar da data como o texto 'paga'
                 $v=strtolower(trim($v));

                 if($v=='paga' || $v=='conf.fat.cartao') return $v;

             },['limit'=>false]);

            //dd($block_text,$valores, $datavenc,$datavenc_texto);
            //dd(count($datavenc),count($datavenc_texto),count($valores));

             //if(count($datavenc)+count($datavenc_texto) == count($valores)){//quer dizer que existe uma tabela de valores com datas
            if(
                (count($datavenc_texto)>0 || count($datavenc)+count($datavenc_texto) == count($valores))
                &&
                (count($datavenc)>0)
                ){//quer dizer que existe uma tabela de valores com datas
               //dd($datavenc_texto);

                 if(count($datavenc_texto)>0){//quer dizer que existe um texto no lugar do vencimento, ex 'paga'

                     if(count($datavenc_texto)>1){
                         //dd(123);
                         $block_text = str_replace('conforme fatura do cartao','conf.fat.cartao',$block_text);//substitui o texto para ficar compatível com o código abaixo
                         $valores = TextUtility::getSearchText($block_text,'','number_formated',['limit'=>false]);
                         $datavenc = TextUtility::getSearchText($block_text,'',function($v){
                            if(strtolower($v)=='conf.fat.cartao')return $v;
                         },['limit'=>false]);
                         //dd($valores);
                         if(count($datavenc)==count($valores)){
                             //altera o texto da var $datavenc para as respectivas datas
                            //dd(123);
                             $i=1;
                             foreach($datavenc as $f=>$v){
                                 if($i==1){
                                     $datavenc[$f] = $this->getDate1aParc($data['fpgto_tipo'],$data);
                                 }else{
                                     $datavenc[$f] = FormatUtility::addDate( FormatUtility::convertDate($this->getDate1aParc($data['fpgto_tipo'],$data)), 'm', $i-1,'datebr');
                                 }
                                 $i++;
                             }

                             $data_order = $this->orderArrayDate($datavenc);
                             $r=PgtoData::makeTable(count($valores), $data_order, $valores);
                             //dd($data_order);
                             $premio = PgtoData::getPremioTotalParcela($this->text, $valores, $this->validate_premio_margem);
                            // dd($premio,$valores,$this->validate_premio_margem);
                         }
                     }
                    //dd(count($datavenc_texto));
                    if(count($datavenc_texto)>=1){//por enquanto não está programado para mais de 1 texto no lugar da data (falta calcular a ordem das datas corretamente) - aparentemente resolvido ok

                     if(count($valores)==1){// faz esse bloco se for apenas 1 parcela
                         //dd($valores[0]);
                         $r['fpgto_datavenc_1'] = $this->getDate1aParc($data['fpgto_tipo'],$data);
                         $r['fpgto_valorparc_1'] = $valores[0];
                         //$r['fpgto_1_prestacao_venc'] = $data['inicio_vigencia'];
                         $r['fpgto_n_prestacoes'] = '1';
                         $r = $r + ['fpgto_premio_total'=>PgtoData::getPremioTotal($this->text)];
                         $data = $data + $r;

                     }else{
                         //adiciona como a primeira parcela a vigência
                         //$data_order = $this->orderArrayDate($datavenc);
                         //dd($datavenc);
                         if(count($valores)!=count($datavenc)){// só irá adicionar a data na matriz caso o número de valores seja diferente do número de datas
                            array_unshift($datavenc, $this->getDate1aParc($data['fpgto_tipo'],$data));//adiciona a data da vigência no início da matriz
                         }
                         $data_order = $this->orderArrayDate($datavenc);
                         //dd($datavenc);
                         //monta a tabela
                         $r = PgtoData::makeTable(count($datavenc), $data_order, $valores);
                         //dd($valores,$data_order);
                         //captura o prêmio total
                         $all_text = $this->text;
                         $all_text = str_replace([', ',',  ',',   ',' ,','  ,'],',',$all_text);
                         $r = $r + ['fpgto_premio_total'=>PgtoData::getPremioTotal($all_text)];
                         $data = $data + $r;
                         //dd($r);
                     }

                    }
                 }else{
                     //dd(123);
                     $r = PgtoData::getTableVencParc_mixed($block_text,$this->validate_premio_margem);
                     $all_text = $this->text;
                     $all_text = str_replace([', ',',  ',',   ',' ,','  ,'],',',$all_text);
                     if($r)$r = $r + ['fpgto_premio_total'=>PgtoData::getPremioTotal($all_text)];

                     if(!$r){
                         $data_order = $this->orderArrayDate($datavenc);
                         $r = PgtoData::makeTable('', $data_order, $valores);
                         //dd($r);
                     }

                     $r['fpgto_premio_total']=PgtoData::getPremioTotalParcela($this->text, $valores, $this->validate_premio_margem);

                     //dd($r,$this->text);
                     $data = $data + $r;
                     // dd(123);

                 }

             }else{//existe apenas valores sem data

                 $text_premio = FormatUtility::getPartOfStr($this->text,['start'=>'CUSTO DE APÓLICE']);
                 $text_premio = FormatUtility::getPartOfStr($text_premio,['end'=>'Valores']);
                 $text_premio = str_replace(', ',',',$text_premio);
                 $premio = TextUtility::getSearchText($text_premio,'Total','number_formated',['side'=>'right']);

                 if(empty($premio)){
                    $r = PgtoData::getTableVencParc_noDate($block_text, $this->getDate1aParc($data['fpgto_tipo'],$data), null, 'auto',$this->text, false,$this->validate_premio_margem);
                 }else{
                    $r = PgtoData::getTableVencParc_noDate($block_text, $this->getDate1aParc($data['fpgto_tipo'],$data), null, $premio,$this->text, false,$this->validate_premio_margem);
                 }

                 if(!$r){
                     //tenta procurar no texto $this->text_ws02
                     $blocktext_ws02 = FormatUtility::getPartOfStr($this->text_ws02,['start'=>'COBRAN§A EM','end'=>'CONSULTE  AS']);
                     $blocktext_ws02 = str_replace([', ',',  ',',   ',' ,','  ,'],',',$blocktext_ws02);
                     $r = PgtoData::getTableVencParc_noDate($blocktext_ws02, $this->getDate1aParc($data['fpgto_tipo'],$data), null, 'auto',$this->text, false,$this->validate_premio_margem);

                 }


             }

             if(!$r){
                 $r = PgtoData::getTableVencParc_mixed($block_text,$this->validate_premio_margem);
                 //dd($r,$block_text);
             }

             if(!$r){//não achou a tabela de valores

                 //procura pela mesma lógica da função getTableVencParc_mixed(), mas utiliza os valores e o texto 'Conf.Fat.Cartao'
                 $block_text = str_replace('conforme fatura do cartao','conf.fat.cartao',$block_text);//substitui o texto para ficar compatível com o código abaixo

                 $valores = TextUtility::getSearchText($block_text,'','number_formated',['limit'=>false]);
                 $datavenc = TextUtility::getSearchText($block_text,'',function($v){
                         if(strtolower($v)=='conf.fat.cartao')return $v;
                     },['limit'=>false]);
                 if(count($datavenc)==count($valores)){
                     //altera o texto da var $datavenc para as respectivas datas
                     $i=1;
                     foreach($datavenc as $f=>$v){
                         if($i==1){
                             $datavenc[$f] = $this->getDate1aParc($data['fpgto_tipo'],$data);
                         }else{
                             $datavenc[$f] = FormatUtility::addDate( FormatUtility::convertDate($this->getDate1aParc($data['fpgto_tipo'],$data)), 'm', $i-1,'datebr');
                         }
                         $i++;
                     }

                     $r=PgtoData::makeTable(count($valores), $datavenc, $valores);

                     $n = TextUtility::getPartOfStr($this->text, ['start'=>'Prêmio Total','end'=>'COBRANÇA EM:']);
                     $n = FormatUtility::sanitizeAllText($n);

                     //dd($n,$this->text);
                     $r['fpgto_premio_total']= TextUtility::getSearchText($n,'total','number_formated',['side'=>'right'],['sanitize'=>false]);
                     //dd($n,$r['fpgto_premio_total'],$block_text,$valores,$datavenc,$r);
                 }
             }

            if(empty($r['fpgto_premio_total'])){
                $n = TextUtility::getPartOfStr($this->text, ['start'=>'Prêmio Total','end'=>'COBRANÇA EM:']);
                $n = FormatUtility::sanitizeAllText($n);
                $n = str_replace(', ',',',$n);
                $r['fpgto_premio_total']= TextUtility::getSearchText($n,'total','number_formated',['side'=>'right'],['sanitize'=>false]);
                $data['fpgto_premio_total']=$r['fpgto_premio_total'];
                $data = $data + $r + PgtoData::addFields1($data);
             }

             if($r['fpgto_premio_total']){
                $data['fpgto_premio_total']=$r['fpgto_premio_total'];
                $data = $data + $r + PgtoData::addFields1($data);
             }

             if($r && $data['fpgto_premio_total']==''){

                 $premio=0;
                 $valores = PgtoData::getArrayFromData($r)['valor'];
                  //dd($valores);
                 foreach($valores as $p){
                     if(TextUtility::isNumberFormated($p))$premio += FormatUtility::nDecimal($p);
                     //dump($p);
                 }
                 $premio=FormatUtility::numberFormat($premio);

                 //dd($premio);
                 $data['fpgto_premio_total'] = $premio;
                 $data = $data + $r;
                 $r='';

             }

             //dd($this->text,$this->text_ws02);
             $text_premio = TextUtility::getPartOfStr($this->text, ['start'=>'ADICIONAL DE FRACIONAMENTO','end'=>'CONSULTE AS']);
             //dd($data[$text_premio]);
             //$premio = PgtoData::getPremioTotalParcela(str_replace(', ',',',$text_premio), $valores, $this->validate_premio_margem);
             //$data['fpgto_premio_total'] = $premio;
             $text_premio = TextUtility::getPartOfStr($text_premio, ['start'=>'Prêmio Total','end'=>'COBRANÇA']);
             $text_premio = str_replace([', ',',  ',',   ',' ,','  ,'],',',$text_premio);
             $premio = TextUtility::getSearchText($text_premio,'Prêmio Total','number_formated',['side'=>'right']);
             if($premio){
                 $data['fpgto_premio_total'] = $premio;
             }

             $data = PgtoData::addFields1($data);
             //dd($data);
         }else{//débito
            //dd(123);
             $block_text = $this->getX1(['start'=>'COBRANCA EM','end'=>'CONSULTE AS CONDICOES','sanitize'=>true,'split'=>false],$this->text);
             if($block_text==''){
                 $block_text = $this->getX1(['start'=>'СОBRANGA ','end'=>'CONSULTE AS CONDICOES','sanitize'=>true,'split'=>false],$this->text);
             }
             if($block_text==''){
                 $block_text = $this->getX1(['start'=>'PARCELA VALOR','end'=>'CONSULTE AS CONDI','sanitize'=>true,'split'=>false],$this->text);
             }

             if(strpos($block_text,'condicoes gerais do')!==false){
                $block_text = $this->getX1(['end'=>'condicoes gerais do','sanitize'=>false],$block_text);
            }
            $block_text = str_replace([', ',',  ',',   ',' ,','  ,'],',',$block_text);
             //dd($block_text);
             //adiciona espaço entre as datas - motivo: algumas vezes a data e valor vem grudados no texto
             TextUtility::execFncInStr($block_text,10,function($v) use(&$block_text){
                 if(strlen(trim($v))==10 && ValidateUtility::isDate($v)){
                     $block_text=str_replace($v,' '.$v.' ',$block_text);
                 };
             });

             //verifica o número de valores e datas disponíveis
             $valores = TextUtility::getSearchText($block_text,'','number_formated',['limit'=>false]);
             $datavenc = TextUtility::getSearchText($block_text,'','datebr',['limit'=>false]);
             $datavenc_texto = TextUtility::getSearchText($block_text,'',function($v){//procura por descrições no lugar da data como o texto 'paga'
                 $v=strtolower(trim($v));
                 if($v=='paga') return $v;
             },['limit'=>false]);
             //dd($valores,$datavenc,$block_text,$this->text);

             //valor do prêmio
             $n = $this->getX1(['start'=>'Premio Total','end'=>'COBRANCA EM','remove'=>'Premio Total','sanitize'=>true,'split'=>false ],$this->text);
             $n = str_replace(', ', ',', $n);
             //dd($n);
             $n = explode(' ', $n);
             $premio = $n[0];
            //dd($premio);
             if(PgtoData::checkPremioTotal($premio,$this->text,$this->validate_premio_margem)==false)return ['success'=>false,'data'=>$data,'msg'=>'Valor do prêmio não encontrado pela verificação no texto'];
             $data['fpgto_premio_total'] = $premio;

             //vencimentos e parcelas
             // dd($datavenc);

             if(count($datavenc)+count($datavenc_texto) == count($valores)){//quer dizer que existe uma tabela de valores com datas

                 if(count($datavenc_texto)>0){//quer dizer que existe um texto no lugar do vencimento, ex 'paga'
                   // dd(123);
                     if(count($datavenc_texto)>1)//por enquanto não está programado para mais de 1 texto no lugar da data (falta calcular a ordem das datas corretamente)
                         return ['success'=>false,'data'=>$data,'msg'=>'Erro ao capturar parcelas. Texto de vencimento inválido.'];

                     //adiciona como a primeira parcela a vigência
                     array_unshift($datavenc, $this->getDate1aParc($data['fpgto_tipo'],$data));//adiciona a data da vigência no início da matriz

                     //monta a tabela
                     $r = PgtoData::makeTable(count($datavenc), $datavenc, $valores);

                 }else{
                         $num_parc=[];
                         $date_parc=[];
                         $valor_parc=[];
                         $nx=array_map('trim',explode(' ',trim($block_text)));
                         foreach($nx as $v){

                            //armazena todos os valores encontrados
                            if(is_numeric($v)){//é um número inteiro
                                $num_parc[]=$v;
                            }

                            if(TextUtility::isNumberFormated($v)){//é um número formatado
                                $valor_parc[]=$v;
                            }
                            $v=TextUtility::getSearchText($v,'','datebr',['side'=>'right']);
                            if($v!=''){//é uma data formatada
                                $date_parc[]=$v;
                            }
                        }
                         $date_parcOK = $this->orderArrayDate($date_parc);
                        //sort($date_parc);
                        array_multisort($num_parc,$valor_parc,$date_parcOK);
                        $text_parc='';
                        for($i=0;$i<count($num_parc);$i++){
                           $text_parc = $text_parc.' '.$num_parc[$i].' '.$valor_parc[$i].' '.$date_parcOK[$i].' ';
                        }
                     //dd($text_parc);
                     $r = PgtoData::getTableVencParc_mixed($text_parc,false);

                 }

             }else{//existe apenas valores sem data
                 $r = PgtoData::getTableVencParc_noDate($block_text, $this->getDate1aParc($data['fpgto_tipo'],$data), null, $premio,$this->text,false,$this->validate_premio_margem);

             }

             if($r){

                 $data = $data + $r;
             }else{//não conseguiu montar a tabela de valores
                 //tenta pegar somente as datas e valores sem considerar a sequencia em que estão
                 $text_all = $this->text;
                 $text_all = str_replace([', ',',  ',',   ',' ,','  ,'],',',$text_all);
                 $premio = PgtoData::getPremioTotal($text_all);
                 $block_text = str_replace([', ',';'],',',$block_text);
                 $block_text = str_replace([', ',',  ',',   ',' ,','  ,'],',',$block_text);
                 $r=PgtoData::getTableVencParc_mixed($block_text,false,$this->validate_premio_margem);
                 if(!$r){
                     $block_text = $this->getX1(['start'=>'valor vencimento','end'=>'condicoes ','sanitize'=>true,'split'=>false],$this->text_ait_tessrct);
                     $block_text = str_replace([', ',';'],',',$block_text);
                     //correção para a situação de números espaços pela virgula, ex: '123, 45' deve ficar '123,45'
                     $block_text = str_replace([', ',',  ',',   ',' ,','  ,'],',',$block_text);
                     //dd($block_text);
                     $r=PgtoData::getTableVencParc_mixed($block_text,false,$this->validate_premio_margem);
                 }
                 if($r)$data = $data + $r;
                // dd($r,$block_text);
                 $total=0;
                 foreach($r as $p){
                     if(TextUtility::isNumberFormated($p))$total += FormatUtility::nDecimal($p);
                 }

                 $total=FormatUtility::numberFormat($total);
                 if($data['fpgto_premio_total']<>''){
                     $premio = $data['fpgto_premio_total'];
                 }

                 if(!PgtoData::checkPremioTotal($premio,$total,$this->validate_premio_margem))return ['success'=>false,'data'=>$data,'msg'=>'Divergência no valor - Prêmio Total: '.$premio.' - Soma das parcelas: '.$total];
             }


             $data = $data + PgtoData::addFields1($data);




         }

         if($r){
             $data = array_merge($data , $r);
         }


         //dd($data['fpgto_premio_total'],$data,$r);

         $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'coberturas','end'=>'total','sanitize'=>true]);
         $blocktext = str_replace(['cus to','i.0.f','1.o.f.',', ','0,00%','I.0.F.','о,00'], ['custo','i.o.f','i.o.f',',','','i.o.f ','0,00'],$blocktext);
         $blocktext = str_replace([', ',',  ',',   ',' ,','  ,'],',',$blocktext);
         $arr_words_ok = TextUtility::existsWordsOrder($blocktext,['premio casco','premio rcfv','premio','servicos','adicional de fracionamento','custo']);
         //dd($arr_words_ok);

         if($arr_words_ok==true){
             $valores_matriz = TextUtility::getSearchText($blocktext,'','number_formated',['limit'=>false]);
             if($valores_matriz){
                 $premio_casco_cl_ad= FormatUtility::nDecimal($valores_matriz[0]);
                 $premio_rcfv= FormatUtility::nDecimal($valores_matriz[1]);
                 $premio_app= FormatUtility::nDecimal($valores_matriz[2]);
                 $servicos= $valores_matriz[3];
                 $adicional = $valores_matriz[4];
                 $custo = $valores_matriz[5];
                 $iof= $valores_matriz[6]??'';
             }else{
                 $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'coberturas','end'=>'Cliente','sanitize'=>true]);
                 $blocktext = str_replace([', ',',  ',',   ',' ,','  ,'],',',$blocktext);
                 $blocktext = str_replace(['cus to','i.0.f','1.o.f.',', ','0,00%','I.0.F.','о,00'], ['custo','i.o.f','i.o.f',',','','i.o.f ','0,00'],$blocktext);
                 $valores_matriz = TextUtility::getSearchText($blocktext,'','number_formated',['limit'=>false]);

                 $premio_casco_cl_ad= FormatUtility::nDecimal($valores_matriz[0]);
                 $premio_rcfv= FormatUtility::nDecimal($valores_matriz[1]);
                 $premio_app= FormatUtility::nDecimal($valores_matriz[2]);
                 $servicos= $valores_matriz[3];
                 $adicional = $valores_matriz[4];
                 $custo = $valores_matriz[5];
                 $iof= $valores_matriz[6]??'';
                 $data['fpgto_premio_total'] = $valores_matriz[7]??'';
             }

             //dd($valores_matriz);
             // dd($blocktext,$iof);
             $premio_liquido = ($premio_casco_cl_ad + $premio_rcfv + $premio_app);

             $premio_liquido = FormatUtility::numberFormat($premio_liquido);


             $data['fpgto_premio_liquido']= $premio_liquido;
             $data['fpgto_custo']= $custo;
             $data['fpgto_adicional']= $adicional;
             $data['fpgto_iof']= $iof;
             $data['fpgto_juros']= '0,00';
             $data['fpgto_juros_md']= '0,00';
             $data['fpgto_premio_liq_serv']= '0,00';
         }
         $data['fpgto_desc'] = '0,00';
        //dd($data['fpgto_desc']);
         //dd($data);

         return $data;
    }

    private static function clearText($text){
        $text =  preg_replace('/[^\d\-]/', '',$text);
        return $text;
    }

}

