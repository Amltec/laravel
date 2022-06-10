<?php

namespace App\ProcessRobot\cad_apolice\Classes\Insurers;
use App\Utilities\TextUtility;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;
use App\Utilities\FilesUtility;

/**
 * Classe trait de funções funções gerais para leitura de apólices pdf de qualquer ramo da Seguradora Sompo
 * Deve ser incorporada a partir da uma classe de um ramo específico, como a classe ex: App\ProcessRobot\cad_apolice\automovel\alfaClass.php
 */
trait alfaInsurer{


    public function initInsurer(){
        $this->pdf_engine = 'ait_xpdfr'; //nome do interpretador de pdf (valores em  \App\Utilities\FilesUtility::readPDF)
    }


    private $text_ws02 = null;
    public function getTextWS02(){
        if(!$this->text_ws02){
            $this->text_ws02 = FilesUtility::readPDF($this->process_opt['path'], ['engine'=>'ws02','pass'=>$this->process_opt['pass']]);
        }
        return $this->text_ws02;
    }


      /**
     * Configuração para o padrão do número do quiver
     */
    function numQuiverConfig(){
        return [
            'ex_num'=>'01.0531.002304525',
            'last_dot'=>true,
            'not_zero_left'=>true
        ];
    }

    /**
     * Retorna os dados do segurado
     * @return error:   [success,msg,data,ignore,code]
     *         ok:      [sucess,data]
     */
    public function getDados_tipo1(){
        $data=[];
        //dd($this->text_ws02);
         //*** captura o tipo da apólice - para verificar se é do tipo automovel ***
         $data['apolice_prod_ref'] = $this->checkRamo();
         if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];


         //verifica se é endosso
         $tmp = trim(TextUtility::getSearchText($this->text,'endosso:','number',['max_words'=>1]));
         $data['data_type'] = trim($tmp,'0')?'endosso':'apolice';
         if($data['data_type']=='endosso')return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];


         //*** dados do corretor ***
         $corretor_text = TextUtility::getPartOfStr($this->text, ['start'=>'Dados da Apólice','end'=>'Informações Cadastrais']);
         $corretor = TextUtility::getPartOfStr($corretor_text, ['start'=>'Corretor:']);
         $corretor = TextUtility::getPartOfStr($corretor, ['end'=>'Apólice']);
         $corretor = str_replace(['Corretor:','Apólice'],'',$corretor);

         if(empty($corretor)){
            $corretor = TextUtility::getPartOfStr($corretor_text, ['start'=>'Corretor:']);
            $corretor = TextUtility::getPartOfStr($corretor, ['end'=>'Informações']);
            $corretor = trim(str_replace(['Corretor:','Informações'],'',$corretor));
         }
         //dd($corretor,$corretor_text);

         $data['corretor_nome'] = $corretor;
         $data['corretor_susep'] = ltrim(TextUtility::getSearchText($corretor_text,'susep:','value'),'0');

         if($data['corretor_susep']=='Informações'){
            $data['corretor_susep'] = ltrim(TextUtility::getSearchText($corretor_text,'susep:','value'),'0');
         }
         //dd($data['corretor_susep'],$this->text);

         //*** dados da apólice ***
         $apolice_text = TextUtility::getPartOfStr($this->text, ['start'=>'Dados da Apólice','end'=>'Informações Cadastrais']);

         if(empty($apolice_text)){
            $apolice_text = TextUtility::getPartOfStr($this->text, ['start'=>'Proposta','end'=>'Informações Cadastrais']);
         }
         //dd($apolice_text,$this->text);
         $text_seguradora = TextUtility::getPartOfStr($this->text, ['start'=>'ALFA SEGURADORA S.A. - CNPJ:']);
         $text_seguradora = TextUtility::getPartOfStr($text_seguradora, ['end'=>'SUSEP:']);
         //dd($text_seguradora,$this->text);
         $data['seguradora_doc'] = TextUtility::getSearchText($text_seguradora,'CNPJ:','cnpj');

         $data['proposta_num'] = TextUtility::getSearchText($apolice_text,'Proposta:','value');
         //dd($apolice_text,$this->text);
         $data['apolice_num'] = TextUtility::getSearchText($apolice_text,'Apólice:','value');
         $n= explode('.', $data['apolice_num']);

         $data['apolice_num_quiver'] = ltrim($n[2],0);
         $data['apolice_re_num'] = TextUtility::getSearchText($apolice_text,'Apólice Anterior:','value',['max_words'=>1]);
             $data['apolice_re_num'] = str_replace('Item:','',$data['apolice_re_num']);//retira o texto que irá aparecer caso não encontre

         $data['inicio_vigencia'] = TextUtility::getSearchText($apolice_text,'Vigência do Seguro','datebr');


         $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Das 24:00h de','sanitize'=>true]);
         $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'emissao']);
         $n=[];
         TextUtility::execFncInStr($blocktext,10,function($v) use(&$n){
             if(ValidateUtility::isDate($v) && strlen(trim($v))==10){
                 $n[]=$v;
             }
         });
         $data['termino_vigencia'] = $n[1];
         //dd($data['termino_vigencia'], $blocktext);
         //dd($blocktext,$n);


         $n=TextUtility::getPartOfStr($this->text, ['start'=>'O segurado poderá consultar a situação cadastral de seu corretor de seguros, no']);
         $data['data_emissao'] = TextUtility::getDateExtenso($n,'datebr');

         if(empty($data['data_emissao'])){
             $n=TextUtility::getPartOfStr($this->text, ['start'=>'nome completo, CNPJ ou CPF.']);
             $data['data_emissao'] = TextUtility::getDateExtenso($n,'datebr');
         }
         //dd($data['data_emissao']);

         //*** dados do segurado ***
         $segurado_text = TextUtility::getPartOfStr($this->text, ['start'=>'Informações Cadastrais','end'=>'Informações do Veículo']);

         $data['segurado_nome'] = TextUtility::getPartOfStr($segurado_text,['start'=>'Segurado:','end'=>'Endere','remove'=>['Segurado:','Endere']]);
         //dd($segurado_text,$this->text);
         $n = TextUtility::getSearchText($this->text,'CPF:','cpf');
         if(!$n)$n = TextUtility::getSearchText($this->text,'CNPJ:','cnpj');
         $data['segurado_doc'] = $n;
         $data['tipo_pessoa'] = ValidateUtility::isCPF($n) ? 'FISICA' : (ValidateUtility::isCNPJ($n)?'JURIDICA':'');

         if($data['tipo_pessoa']=='JURIDICA'){
             $data['segurado_nome'] = TextUtility::getPartOfStr($this->text,['start'=>'Segurado:','end'=>'cnpj','remove'=>['Segurado:','cnpj']]);
            // dd($data['segurado_nome']);
         }
         if(strpos($data['segurado_nome'],'Endereço:')!==FALSE){
            $data['segurado_nome'] = TextUtility::getPartOfStr($data['segurado_nome'], ['end'=>'Endere']);
            $data['segurado_nome'] = str_replace('Endere','',$data['segurado_nome']);
        }
         return ['success'=>true,'data'=>$data];
    }


    /**
     * Retorna os dados do premio
     */
    public function getPremio_tipo1($data){
        //*** dados do pagamento ***
        // $pgto_text = TextUtility::getPartOfStr($this->text, ['start'=>'Valores do Seguro e Pagamento','end'=>' Dados Bancários para Débito','remove'=>' Dados Bancários para Débito']);
        //dd($pgto_text);
        // if(empty($pgto_text)){
            $pgto_text = TextUtility::getPartOfStr($this->text, ['start'=>'Valores do Seguro e Pagamento','end'=>'Produto elaborado','remove'=>' Produto elaborado']);
            if(empty($pgto_text)){
                $pgto_text = TextUtility::getPartOfStr($this->text, ['start'=>'Valores do Seguro e Pagamento','end'=>' Dados Bancários para Débito','remove'=>' Dados Bancários para Débito']);
            }
            if(strpos($pgto_text,'Boleto')!==false){
                $data['fpgto_tipo']=PgtoData::getPgtoTipo('Boleto');
                $data['fpgto_tipo_code']=PgtoData::getPgtoCode( $data['fpgto_tipo'] )[1];
            }else{
                $data['fpgto_tipo']=PgtoData::getPgtoTipo($pgto_text);
                $data['fpgto_tipo_code']=PgtoData::getPgtoCode( $data['fpgto_tipo'] )[1];
            }
            if($data['fpgto_tipo']=='boleto' || strpos($pgto_text,'Banco:  Juros')!==false){
                $data['fpgto_tipo']=PgtoData::getPgtoTipo('Boleto');
                $data['fpgto_tipo_code']=PgtoData::getPgtoCode( $data['fpgto_tipo'] )[1];
                $n = TextUtility::getPartOfStr($pgto_text, ['start'=>'Total do Seguro','end'=>'Titular']);
                $data['fpgto_premio_total'] = TextUtility::getSearchText($n,'Titular','number_formated',['side'=>'left']);
            }else{
                $n= TextUtility::getPartOfStr($pgto_text, ['start'=>'Valor Total do Seguro','end'=>'Dados Bancários']);
                if(strpos($n,'Dados Bancários')!==false){
                    //$data['fpgto_premio_total']=PgtoData::getPremioTotal($pgto_text);
                    if(empty($data['fpgto_premio_total'])){// usa o texto em java
                        $n=$this->getTextWS02();
                        $n= TextUtility::getPartOfStr($n['text'], ['start'=>'Total do Seguro','end'=>'Dados Bancários']);
                        $data['fpgto_premio_total']=TextUtility::getSearchText($n,'Seguro','value',['side'=>'right']);
                        //dd($data['fpgto_premio_total'],$n);
                    }
                }else{
                    $n = TextUtility::getPartOfStr($pgto_text, ['start'=>'Conta:','end'=>'Titular ']);
                    $data['fpgto_premio_total']= TextUtility::getSearchText($n,'Titular','number_formated',['side'=>'left']);
                    //dd($n,$pgto_text);
                }
            }
            $pgto_text_parc = TextUtility::getPartOfStr($pgto_text, ['start'=>'Forma','end'=>'Apólice']);
            $pgto_text_parc = str_replace('.','',$pgto_text_parc);
            if(empty($r)){// usa o texto em java
                $n=$this->getTextWS02();
                $n=$n['text'];
                $pgto_text_parc = TextUtility::getPartOfStr($n, ['start'=>'Parcela Forma Valor','end'=>'Telefones']);
                if(empty($pgto_text_parc)){
                    $pgto_text_parc = TextUtility::getPartOfStr($n, ['start'=>'Titular da Conta']);
                    //$pgto_text_parc = TextUtility::getPartOfStr($pgto_text_parc, ['end'=>'Forma']); antes estava assim
                    $pgto_text_parc = TextUtility::getPartOfStr($pgto_text_parc, ['end'=>'Informações']);
                    //dd($pgto_text_parc,$n);
                }
                $pgto_text_parc = str_replace(['.',' 0,00'],'',$pgto_text_parc);
            }
            $r=PgtoData::getTableVencParc_mixed($pgto_text_parc);
            //dd($r,$pgto_text_parc,$n);
            $venc_parcelas = PgtoData::getArrayFromData($r);
            $venc_parcelas = PgtoData::getTableOrderDate($venc_parcelas['datavenc'],$venc_parcelas['valor']);
            $r = PgtoData::makeTable(count($venc_parcelas['0']),$venc_parcelas['0'],$venc_parcelas['1'],1,'');

            //dd($r);
            if(empty($r)){
                $pgto_text1 = TextUtility::getPartOfStr($this->text, ['start'=>'Valores do Seguro e Pagamento ','end'=>'Adicionais','remove'=>'Adicionais']);
                $pgto_text1 = str_replace('Titular da Conta:','debito',$pgto_text1);

                $parcelas_trext = TextUtility::getPartOfStr($pgto_text1 , ['start'=>'Forma','end'=>'Informações','sanitize'=>false]);
                $r=PgtoData::getTableVencParc_mixed($parcelas_trext);
                dd($parcelas_trext,$r);
                if(strpos( $pgto_text1,'bito')!==false){
                    $data['fpgto_tipo']=PgtoData::getPgtoTipo('debito');
                    $data['fpgto_tipo_code']=PgtoData::getPgtoCode( $data['fpgto_tipo'] )[1];
                }
            }

            if(empty($data['fpgto_tipo'])){
                $pgto_text1 = TextUtility::getPartOfStr($this->text, ['start'=>'Valores do Seguro e Pagamento ','end'=>'Adicionais','remove'=>'Adicionais']);
                $pgto_text1 = str_replace('Titular da Conta:','debito',$pgto_text1);
                $parcelas_trext = TextUtility::getPartOfStr($pgto_text1 , ['start'=>'Forma','end'=>'Informações','sanitize'=>false]);
                //dd($parcelas_trext,$r);
                if(strpos( $pgto_text1,'bito')!==false){
                    $data['fpgto_tipo']=PgtoData::getPgtoTipo('debito');
                    $data['fpgto_tipo_code']=PgtoData::getPgtoCode( $data['fpgto_tipo'] )[1];
                }
            }


            if(empty($r)){
                $pgto_text_parc = TextUtility::getPartOfStr($pgto_text_parc, ['start'=>'Valor','end'=>'Juros']);
                $r=PgtoData::getTableVencParc_mixed($pgto_text_parc);
            }
            if($r)$data = $data + $r;
            //dd($r,$pgto_text_parc);
            $data = $data + PgtoData::addFields1($data);



            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'liquido das coberturas','end'=>'produto elaborado','sanitize'=>true]);

            $n=$this->getTextWS02();
            $n=$n['text'];
            $blocktext = TextUtility::getPartOfStr($n, ['start'=>'Demonstrativo dos Prêmios','end'=>'Dados Bancários','sanitize'=>false]);
            $blocktext = str_replace('.','',$blocktext);
            //dd($blocktext);

            $data['fpgto_premio_liq_serv'] = TextUtility::getSearchText($blocktext,'servicos adicionais','value',['side'=>'right']);
            if(empty($data['fpgto_premio_liq_serv'])){
                $data['fpgto_premio_liq_serv']='0,00';
            }
            $data['fpgto_premio_liquido'] = trim(TextUtility::getSearchText($blocktext,'Coberturas','value',['side'=>'right']));
            $data['fpgto_juros']= trim(TextUtility::getSearchText($blocktext,'Juros de Parcelamento','value',['side'=>'right']));
            $data['fpgto_iof']= trim(TextUtility::getSearchText($blocktext,'(7,38%)','value',['side'=>'right']));
            $data['fpgto_adicional']=trim(TextUtility::getSearchText($blocktext,'custo dos servicos adicionais','value',['side'=>'right']));
            $data['fpgto_adicional']='0,00';
            if(empty($data['fpgto_adicional'])){
                $data['fpgto_adicional']='0,00';
            }
            $data['fpgto_custo']='0,00';
            $data['fpgto_juros_md']='0,00';

            if(empty($data['fpgto_premio_total'])){
                $data['fpgto_premio_total']=trim(TextUtility::getSearchText($blocktext,'Total do Seguro','value',['side'=>'right']));
            }
            //dd($data['fpgto_premio_total']);
            //dd($data);
            /*
            if(empty($blocktext)){
                $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>' Demonstrativo dos','end'=>'dados bancarios para','sanitize'=>true]);
            }
             if(empty($blocktext)){
                $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'das Coberturas','end'=>'Dados Banc','sanitize'=>true]);
            }
            $perc_iof = TextUtility::getSearchText($blocktext,'     ','value',['side'=>'right']);
            $blocktext = str_replace(['vencimento juros',$perc_iof], '', $blocktext);
           // dd($blocktext,$this->text);

            $data['fpgto_premio_liq_serv'] = TextUtility::getSearchText($blocktext,'servicos adicionais','number_formated',['side'=>'right']);
            //dd( $data['fpgto_premio_liq_serv']);
            $r=PgtoData::getFielsPremioAdd($blocktext,$data,['tem_juros'=>false]);
            //dd($r);
            $data['fpgto_premio_liquido']=TextUtility::getPartOfStr($blocktext, ['start'=>'coberturas','end'=>'juros','sanitize'=>true]);
            $data['fpgto_premio_liquido'] = TextUtility::getSearchText($blocktext,'coberturas','number_formated',['side'=>'right']);

            if($data['fpgto_premio_liq_serv']==''){
                $data['fpgto_premio_liq_serv']='0,00';
            }

            $n = TextUtility::getSearchText($pgto_text,'Banco:','value',['side'=>'right']);

            if($n!='Juros'){
                $data['fpgto_juros']= TextUtility::getSearchText($n,'','number_formated',['side'=>'right']);
            }

            if(empty($data['fpgto_juros'])){
                $n = TextUtility::getSearchText($this->text,'Juros de Parcelamento','value',['side'=>'right']);
                if($n=='IOF'){
                    $data['fpgto_juros']='0,00';
                }else{
                    $data['fpgto_juros']=$n;
                }
            }
            if($data['fpgto_juros']==$data['fpgto_premio_liquido']){
                $n = TextUtility::getPartOfStr($this->text, ['start'=>'banco:','end'=>'agencia:','sanitize'=>true]);
                $data['fpgto_juros']=TextUtility::getSearchText($n,'banco:','number_formated',['side'=>'right']);
            }
            */
            if(strpos($blocktext, 'custo dos servicos adicionais')){
                $data['fpgto_adicional']= '0,00';
            }
             //dd($data);

            $data = $data + $r;
            // dd($data,$blocktext,$this->text);
             return $data;
    }

    private static function clearText($text){
        $text =  preg_replace('/[^\d\-]/', '',$text);
        return $text;
    }

}
