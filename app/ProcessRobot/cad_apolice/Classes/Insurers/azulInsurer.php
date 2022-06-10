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
         $this->pdf_engine = 'ait_tessrct'; //nome do interpretador de pdf (valores em  \App\Utilities\FilesUtility::readPDF)
        $this->validate_premio_margem=21.5;//(R$) limite de diferença por causa de possíveis juros das parcelas

        //Campos obrigatórios adicionais para personalização na classe filha (obs: neste caso segue as regras existentes no validate da classe filha, ex ProcessAutomovelClass.php)
        $this->validate_required = ['segurado_pernoite_cep'=>false];//sintaxe field=>boolean
        $this->validate_iof_margem=1.70;

        //Validações da classe filha. Segue a mesma regra da var $fields_rules da classe de produto
        //Campos obrigatórios adicionais para personalização na classe filha (obs: neste caso segue as regras existentes no validate da classe filha, ex ProcessAutomovelClass.php)
        $this->validate_required = ['veiculo_cod_fipe'=>false];//sintaxe field=>boolean

        //define se os dados terão uma validação extra de compração de valores por outro método
        $this->extra_validate_data_values=['engine'=>'ws02','fields'=>'veiculo_placa_1','veiculo_chassi_1','veiculo_ci_1'];

        //$this->text;                //ocr01 google
       // $this->text;    //tessrct
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

        //dd($this->text_ws02);
        //captura o tipo da apólice - para verificar se é do tipo automovel
        $data['apolice_prod_ref'] = $this->checkRamo();
        if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];

        $n = $this->getX1(['start'=>'Azul Seguro Auto','end'=>'Nome','sanitize'=>true ]);
        if(strpos($n,'endosso')!==false){
            return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];
        }
        $n = $this->getX1(['start'=>'ENDOSSO','end'=>'DE','sanitize'=>false ]);
        if(strpos($n,'ENDOSSO')!==false){
            return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];
        }
        $n = $this->getX1(['start'=>'PRESENTE ENDOSSO','end'=>'FOI','sanitize'=>false ]);
        if(strpos($n,'ENDOSSO')!==false){
            return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];
        }

        //Dados da Seguradora
        $text_cnpj = TextUtility::getPartOfStr($this->text, ['start'=>'OUVIDORIA:']);
        $text_cnpj = TextUtility::getPartOfStr($text_cnpj, ['end'=>'Nr']);
        $data['seguradora_doc'] = TextUtility::getSearchText($text_cnpj,'CNPJ:','value',['side'=>'right']); //CNPJ Seguradora

        $text_ap = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'DE SEGURO','sanitize'=>true]);
        $text_ap = TextUtility::getPartOfStr($text_ap, ['end'=>'24h','sanitize'=>true]);
        $data['apolice_num'] = TextUtility::getSearchText($text_ap,'SEGURO','value',['side'=>'right']);//numero da apólice
        $data['apolice_re_num']='';//não tem número da apólice renovada na Azul

        $text_prop = $this->getX1(['start'=>'24h de','return_type'=>'next'],$this->text_ws02);
        $text_prop = TextUtility::getPartOfStr($text_prop,['start'=>'Proposta:']);
        $text_prop = str_replace(['Proposta:',' ','.'],'',$text_prop);
        $data['proposta_num'] = trim($text_prop);// Numero da Proposta

        $n=TextUtility::getPartOfStr($this->text_ws02,['start'=>'OUVIDORIA:']);
        $n = TextUtility::getSearchText($n,'OUVIDORIA:','datebr',['side'=>'right']);
        $data['data_emissao']=$n; //Data de Emissão

        $n=TextUtility::getPartOfStr($this->text_ws02,['start'=>'24h de','end'=>'Numero']);
        $n= TextUtility::getSearchText($n,'','datebr',['limit'=>false]);
        $data['inicio_vigencia']=$n[0]??'';
        $data['termino_vigencia']=$n[1]??'';

        //Dados do Corretor
        $n = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'(SUSEP:','sanitize'=>true]);
        $n = TextUtility::getSearchText($n,'SUSEP:','value',['side'=>'right']);//Susep Corretor
        $n = str_replace(['.',')','-'],['','',''], $n);
        $data['corretor_susep'] = ltrim(ltrim(strtolower($n),'o'),'0');

        //Dados do segurado
        $text_nome = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'Nnw','end'=>'CASCO','sanitize'=>false]);
        $del1 = TextUtility::getSearchText($text_nome,'','number',['side'=>'right']);
        $del2 = TextUtility::getSearchText($text_nome,$del1,'value',['side'=>'left']);
        $text_nome = trim(str_replace([$del1,$del2],'',$text_nome));
        $doc_segurado = TextUtility::getSearchText($text_nome,'','document',['side'=>'right']);
        $text_nome = TextUtility::getPartOfStr($text_nome,['end'=>$doc_segurado,'sanitize'=>false]);
        $text_nome = trim(str_replace([$doc_segurado],'',$text_nome));

        $data['segurado_nome'] = $text_nome;//nome do segurado
        $data['segurado_doc'] = $doc_segurado;//doc do segurado

        if(strlen($data['segurado_doc'])<=12){
            $n = 'fisica';
        }else{
            $n = 'juridica';
        }
        $data['tipo_pessoa'] = $n;// tipo pessoa
        //dd($data);
        return ['success'=>true,'data'=>$data];
        //dd($data,$this->text);
    }

    public function getDados1(){
        $data=[];
        $data['data_type']='apolice';

        $data['apolice_prod_ref'] = $this->checkRamo();
        if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];

        $n = $this->getX1(['start'=>'Azul Seguro Auto','end'=>'Nome','sanitize'=>true ]);
        if(strpos($n,'endosso')!==false){
            return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];
        }
        $n = $this->getX1(['start'=>'ENDOSSO','end'=>'DE','sanitize'=>false ]);
        if(strpos($n,'ENDOSSO')!==false){
            return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];
        }
        $n = $this->getX1(['start'=>'PRESENTE ENDOSSO','end'=>'FOI','sanitize'=>false ]);
        if(strpos($n,'ENDOSSO')!==false){
            return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];
        }

        //Dados da Seguradora
        $text_cnpj = TextUtility::getPartOfStr($this->text, ['start'=>'OUVIDORIA:']);
        $text_cnpj = TextUtility::getPartOfStr($text_cnpj, ['end'=>'Nr']);
        $data['seguradora_doc'] = TextUtility::getSearchText($text_cnpj,'CNPJ:','value',['side'=>'right']); //CNPJ Seguradora

        $text_ap = TextUtility::getPartOfStr($this->text, ['start'=>'DE SEGURO','sanitize'=>true]);
        $text_ap = TextUtility::getPartOfStr($text_ap, ['end'=>'24h','sanitize'=>true]);
        $data['apolice_num'] = TextUtility::getSearchText($text_ap,'apolice:','value',['side'=>'right']);//numero da apólice
        $data['apolice_re_num']='';//não tem número da apólice renovada na Azul

        $text_prop = $this->getX1(['start'=>'24h de','return_type'=>'next'],$this->text);
        $text_prop = TextUtility::getPartOfStr($text_prop,['start'=>'Proposta:']);
        $text_prop = str_replace(['Proposta:',' ','.'],'',$text_prop);
        $data['proposta_num'] = trim($text_prop);// Numero da Proposta

        $n=TextUtility::getPartOfStr($this->text,['start'=>'OUVIDORIA:']);
        $n = TextUtility::getSearchText($n,'OUVIDORIA:','datebr',['side'=>'right']);
        $data['data_emissao']=$n; //Data de Emissão

        $n=TextUtility::getPartOfStr($this->text,['start'=>'Seguro:','end'=>'Processo ']);
        $n= TextUtility::getSearchText($n,'','datebr',['limit'=>false]);
        $data['inicio_vigencia']=$n[0]??'';
        $data['termino_vigencia']=$n[1]??'';


        //Dados do Corretor
        $n = TextUtility::getPartOfStr($this->text, ['start'=>'(SUSEP:','sanitize'=>true]);
        $n = TextUtility::getSearchText($n,'SUSEP:','value',['side'=>'right']);//Susep Corretor
        $n = str_replace(['.',')','-'],['','',''], $n);
        $data['corretor_susep'] = ltrim(ltrim(strtolower($n),'o'),'0');

        //Dados do segurado
        $text_nome = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'APÓLICE DE SEGURO','end'=>'Nome:','sanitize'=>false]);
        $nome_segurado = $this->getX1(['start'=>'DE SEGURO','return_type'=>'next'],$text_nome);

        $doc_segurado = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'APÓLICE DE SEGURO','end'=>'Coberturas','sanitize'=>false]);
        $doc_segurado = str_replace('CPF',' CPF',$doc_segurado);
        $doc_segurado = TextUtility::getSearchText($doc_segurado,'','document',['side'=>'right']);
        $data['segurado_nome'] = trim($nome_segurado);//nome do segurado
        $data['segurado_doc'] = $doc_segurado;//doc do segurado
        //dd($data['segurado_nome'],$data['segurado_doc'], $text_nome,$this->text_ws02);
        if(strlen($data['segurado_doc'])<=12){
            $n = 'fisica';
        }else{
            $n = 'juridica';
        }
        $data['tipo_pessoa'] = $n;// tipo pessoa
        //dd($data);
        return ['success'=>true,'data'=>$data];
        //dd($data,$this->text);
    }


    /**
     * Retorna os dados do premio
     */
    public function getPremio($data){
         //forma de pagamento
         $block_text = $this->getX1(['start'=>'Coberturas','end'=>'SUSEP:','sanitize'=>true,'split'=>false],$this->text);
       // dd($this->text_ws02);
         //dd($block_text,$this->text);
         $block_text = str_replace(['debitО ','debІТo'], ['debito ','debito '], $block_text);
         $block_text = str_replace([', ',',  ',',   ',' ,','  ,'],',',$block_text);

         //pagamento: tipo
         $n=PgtoData::getPgtoTipo($block_text);
          $data['fpgto_tipo'] = $n;
        if(empty($data['fpgto_tipo'])){
            $block_text = $this->getX1(['start'=>'COBRANÇA EM:','end'=>'Coberturas','sanitize'=>false,'split'=>false],$this->text);
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

             $block_text = $this->getX1(['start'=>'COBRANCA EM','sanitize'=>true,'split'=>false],$this->text);
             $block_text = str_replace([', ',',  ',',   ',' ,','  ,'],',',$block_text);
             if(!$block_text){
                 $block_text = $this->getX1(['start'=>'valor vencimento','sanitize'=>true,'split'=>false],$this->text);
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
                 //dd($premio);
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
                     //dd($r,$blocktext_ws02,$this->text_ws02);
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
            //EXTRAÇÃO VIA JAVA *******************************************************************************************************************************************************************************
            $block_text = $this->getX1(['start'=>'COBRAN','end'=>'CONSULTE','sanitize'=>false,'split'=>false],$this->text_ws02);
            $block_text = str_replace([', ',',  ',',   ',' ,','  ,'],',',$block_text);
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
             //dd($valores,$datavenc,$block_text);

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
                            if(is_numeric($v) && strlen($v)>1){//é um número inteiro
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
                        //dd($num_parc,$valor_parc,$date_parcOK);
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
                     $block_text = $this->getX1(['start'=>'valor vencimento','end'=>'condicoes ','sanitize'=>true,'split'=>false],$this->text);
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




         //dd($blocktext_ws02);

        $blocktext_ws02 = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'CASCO','sanitize'=>false]);
        $blocktext_ws02 = TextUtility::getPartOfStr($blocktext_ws02, ['end'=>'Azul','sanitize'=>false]);

       if(strpos($blocktext_ws02, 'COBRAN')!==false){
            $premio_casco_cl_ad= FormatUtility::nDecimal(TextUtility::getSearchText($blocktext_ws02,'ADICIONAIS','number_formated',['side'=>'right']));
            $premio_rcfv= FormatUtility::nDecimal(TextUtility::getSearchText($blocktext_ws02,'RCFV','number_formated',['side'=>'right']));
            $premio_app= FormatUtility::nDecimal(TextUtility::getSearchText($blocktext_ws02,'APP','number_formated',['side'=>'right']));
            $servicos= TextUtility::getSearchText($blocktext_ws02,'SERVI','number_formated',['side'=>'right']);
            $adicional = TextUtility::getSearchText($blocktext_ws02,'%)','number_formated',['side'=>'right']);
            $custo = TextUtility::getSearchText($blocktext_ws02,'CUSTO DE','number_formated',['side'=>'right']);
            $iof= TextUtility::getSearchText($blocktext_ws02,'I.O.F. ','number_formated',['side'=>'right']);
            $iof_ws02= $iof;
            $data['fpgto_premio_total'] = TextUtility::getSearchText($blocktext_ws02,$iof,'number_formated',['side'=>'right']);
        }else{

          $valores= str_replace('.','',$blocktext_ws02);
          $valores = TextUtility::getSearchText($valores,'','number_formated',['limit'=>false]);
          //dd($valores,$blocktext_ws02);
          $premio_casco_cl_ad= str_replace(',','.',$valores[0]);
          $premio_casco_cl_ad= number_format($premio_casco_cl_ad,2);
          $premio_casco_cl_ad= str_replace(',','',$premio_casco_cl_ad);
          //dd($premio_casco_cl_ad);
          $premio_rcfv= str_replace(',','.',$valores[1]);
          $premio_rcfv= number_format($premio_rcfv,2);
          $premio_rcfv= str_replace(',','',$premio_rcfv);

          $premio_serv = str_replace(',','.',$valores[3]);
          $premio_serv= number_format($premio_serv,2);
          $premio_serv = str_replace(',','',$premio_serv);

          $premio_app= str_replace(',','.',$valores[2]);
          $premio_app= number_format($premio_app,2);

          $adicional = str_replace(',','.',$valores[5]);
          $adicional= number_format($adicional,2);

          $custo = str_replace(',','.',$valores[6]);
          $custo= number_format($custo,2);

          $iof= str_replace(',','.',$valores[4]);
          $iof= number_format($iof,2);
          $iof= str_replace(',','',$iof);

          //dd($premio_casco_cl_ad);
          $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'CASCO','sanitize'=>false]);
          $blocktext = str_replace(['COBRANÇA','COBRANCA','cobrança'],'cobranca',$blocktext);
          $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'cobranca','sanitize'=>false]);
          $data['fpgto_premio_total'] = TextUtility::getSearchText($blocktext,'Total','number_formated',['side'=>'right']);


            $n = PgtoData::checkPremioTotal($data['fpgto_premio_total'],$this->text_ws02,0,00);//verifica se o premio total existe no texto em JAVA
            if(!$n){
                $text = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'CUSTO DE','sanitize'=>false]);
                $data['fpgto_premio_total'] = TextUtility::getSearchText($text,'COBRA','number_formated',['side'=>'left']);
            }
        }


        if(empty($adicional)){
            $adicional = '0,00';
        }

        //dd($premio_casco_cl_ad,$premio_rcfv,$premio_app);
        $premio_liquido = ($premio_casco_cl_ad+ $premio_rcfv+ $premio_app);
       // dd( $premio_liquido);
        $premio_liquido = FormatUtility::numberFormat($premio_liquido);


        $data['fpgto_premio_liquido']= $premio_liquido;
        $data['fpgto_custo']= $custo;
        $data['fpgto_adicional']= $adicional;
        $data['fpgto_iof']= $iof;
        $data['fpgto_juros']= '0,00';
        $data['fpgto_juros_md']= '0,00';
        $data['fpgto_premio_liq_serv']= '0,00';
        //}
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

