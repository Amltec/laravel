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
trait mitsuiInsurer{

    //método de inicialização
    public function initInsurer(){

         $this->pdf_engine = 'ait_tessrct'; //nome do interpretador de pdf (valores em  \App\Utilities\FilesUtility::readPDF)
         //margem de diferença de centavos entre o cálculo do iof com o iof capturado em $data para o PgtoData::validateAll(). Obs: por padrão deveria ser igual, mas na prática existe diferença de centávos entre as apólices.
         //$this->validate_iof_margem=0.11;
    }


    /**
     * Configuração para o padrão do número do quiver
     */
    function numQuiverConfig(){
        return [
            'ex_num'=>'01312627309',
            'not_dot_traits'=>true,
            'not_zero_left'=>true
        ];
    }

    /**
     * Retorna os dados do segurado
     * @return error:   [success,msg,data,ignore,code]
     *         ok:      [sucess,data]
     */
    public function getDados_tipo1(){
        //$this->text = FormatUtility::sanitizeBreakText($this->text);//retira somente as quebras de linha
        //dd($this->text);
        $data=[];
        //*** captura o tipo da apólice - para verificar se é do tipo automovel ***
        $data['apolice_prod_ref'] = $this->checkRamo();
        //dd($data['apolice_prod_ref']);
        if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];

        $n= $this->text;//verifica se é frota
        if(strpos($n,'AUTO FROTA')!==false){
            return ['success'=>false,'msg'=>'Ignorado apólice do tipo frota','data'=>[],'ignore'=>true,'code'=>'read04'];
        }

        if(strpos($n,'ENDOSSO DE SEGURO')!==false){
            return ['success'=>false,'msg'=>'Apólice do tipo Endosso - não processado','data'=>[],'ignore'=>true,'code'=>'read03'];
        }
        //verifica se é endosso
        $tmp = trim(TextUtility::getSearchText($this->text,'ENDOSSO Nº','number',['max_words'=>1]));
        $data['data_type'] = $tmp?'endosso':'apolice';
        //dd($data['data_type']);
        if($data['data_type']=='endosso')return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];

        //*** dados do corretor ***
        //$corretor_text = TextUtility::getPartOfStr($this->text, ['start'=>'dados do corretor','end'=>'E por ser verdade','remove'=>'dados do corretor']);

        $susep_text = TextUtility::getPartOfStr($this->text, ['start'=>'Susep Corretor','end'=>'Resseguro']);
        $data['corretor_susep'] = TextUtility::getSearchText($susep_text,'Susep','number',['side'=>'right']);
        //dd($data['corretor_susep'],$this->text);
        $corretor_text = TextUtility::getPartOfStr($this->text, ['start'=>$data['corretor_susep'],'end'=>'Resseguro']);
        $n=str_replace([$data['corretor_susep'],' S ',' N ','Resseguro'],'',$corretor_text);
        $data['corretor_nome'] = trim(substr($n,0,-2));
        //dd($data['corretor_susep'],$data['corretor_nome'],$this->text);

        //*** dados da apólice ***
        $data['seguradora_doc'] = TextUtility::getSearchText($this->text,'MITSUI SUMITOMO SEGUROS S/A','cnpj',['sanitize'=>false]);
        //dd($data['seguradora_doc'],$this->text);


        $apolice_text = TextUtility::getPartOfStr($this->text, ['start'=>'MS AUTO','end'=>'EMISSÃO']);
        $data['apolice_num'] = TextUtility::getSearchText($apolice_text,'MS AUTO','number',['side'=>'right']);

        $data['apolice_num'] = TextUtility::getSearchText($apolice_text,'MS AUTO','number',['side'=>'right']);

        $n = TextUtility::getPartOfStr($this->text, ['start'=>'Proposta','end'=>'Apólice Anterior']);
        $n = TextUtility::getSearchText($n,'Apólice','number',['side'=>'left']);
        $data['proposta_num'] = $n;

        $n = TextUtility::getPartOfStr($this->text, ['start'=>'Apólice Anterior','end'=>'Proprietário']);
        $data['apolice_re_num'] = TextUtility::getSearchText($n,'Apólice Anterior','numberstr');

        $vig_text = TextUtility::getPartOfStr($this->text, ['start'=>'Inicio:']);
        $vig_text = TextUtility::getPartOfStr($vig_text, ['end'=>'Seguro']);
        //dd($vig_text);
        $data['inicio_vigencia'] = TextUtility::getSearchText($vig_text,'partir','datebr',['side'=>'right']);
        $data['termino_vigencia'] = TextUtility::getSearchText( $vig_text,'Seguro','datebr',['side'=>'left']);

        $n = TextUtility::getPartOfStr($this->text, ['start'=>'Proposta','end'=>'Apólice Anterior']);
        $n = TextUtility::getSearchText($n,'Apólice','datebr',['side'=>'left']);
        $data['data_emissao'] = $n;

        //*** dados do segurado ***
        $segurado_text = $this->getX1(['start'=>'Nome','return_type'=>'next']);
        $doc = trim(TextUtility::getSearchText($segurado_text,'','document',['side'=>'right']));
        $nome = trim(str_replace($doc,'',$segurado_text));
        $data['segurado_nome'] = $nome;
        $data['segurado_doc']= $doc;
        $n=$data['segurado_doc'];
        $data['tipo_pessoa'] = ValidateUtility::isCPF($n) ? 'FISICA' : (ValidateUtility::isCNPJ($n)?'JURIDICA':'');
        return ['success'=>true,'data'=>$data];
    }

    public function getDados_tipo2(){// usado no empresarial
        //$this->text = FormatUtility::sanitizeBreakText($this->text);//retira somente as quebras de linha
        //dd($this->text);
        $data=[];
        //*** captura o tipo da apólice - para verificar se é do tipo automovel ***
        $data['apolice_prod_ref'] = $this->checkRamo();
        if(empty($data['apolice_prod_ref'])){
            if(strpos($this->text,'MS RESIDÊNCIA')!==false){
                $data['apolice_prod_ref'] = 'Ramo 14';
            }
        }
       // dd($data['apolice_prod_ref']);
        if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];

        $n= $this->text;//verifica se é frota
        if(strpos($n,'AUTO FROTA')!==false){
            return ['success'=>false,'msg'=>'Ignorado apólice do tipo frota','data'=>[],'ignore'=>true,'code'=>'read04'];
        }

        //verifica se é endosso
        $tmp = trim(TextUtility::getSearchText($this->text,'ENDOSSO Nº','number',['max_words'=>1]));
        $data['data_type'] = $tmp?'endosso':'apolice';
        //dd($data['data_type']);
        if($data['data_type']=='endosso')return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];

        //*** dados do corretor ***
        //$corretor_text = TextUtility::getPartOfStr($this->text, ['start'=>'dados do corretor','end'=>'E por ser verdade','remove'=>'dados do corretor']);

        $data['corretor_susep'] = TextUtility::getSearchText($this->text_ws02,'Informações','number',['side'=>'left']);
        //dd($this->text_ws02);
        $corretor_text = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'Susep Nº']);
        $corretor_text = TextUtility::getPartOfStr( $corretor_text, ['end'=>'- TEL']);
        $n=str_replace(['- TEL','Susep Nº','Corretor '],'',$corretor_text);
        //dd($n,$this->text_ws02);
        $data['corretor_nome'] = 'não capturado';
        //dd($data, $corretor_text);

        //*** dados da apólice ***
        $data['seguradora_doc'] = TextUtility::getSearchText($this->text,'MITSUI SUMITOMO SEGUROS S/A','cnpj',['sanitize'=>false]);
        //dd($data['seguradora_doc'],$this->text);

        //dd($data);
        $apolice_text = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'Apólice nº','end'=>'Ramo']);
        $data['apolice_num'] = TextUtility::getSearchText($apolice_text,'Apólice','number',['side'=>'right']);

        if(empty($data['apolice_num'])){
            $apolice_text = TextUtility::getPartOfStr($this->text, ['start'=>'Apólice nº','end'=>'Ramo']);
            $data['apolice_num'] = TextUtility::getSearchText($apolice_text,'Apólice','number',['side'=>'right']);
        }

        $n = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'Proposta','end'=>'EMISSÃO']);
        $n = TextUtility::getSearchText($n,'Proposta','number',['side'=>'right']);
        $data['proposta_num'] = $n;

        $n = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'Apólice Anterior','end'=>'Proprietário']);
        $data['apolice_re_num'] = TextUtility::getSearchText($n,'Apólice Anterior','numberstr');

        $data['inicio_vigencia'] = TextUtility::getSearchText($this->text_ws02,'A partir das 24h','datebr',['side'=>'right']);
        $data['termino_vigencia'] = TextUtility::getSearchText($this->text_ws02,$data['inicio_vigencia'],'datebr',['side'=>'right']);

        $n = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'Vice']);
        $n = TextUtility::getPartOfStr($n, ['end'=>'Processo']);
        $n = TextUtility::getDateExtenso($n,'datebr');
        //$n = TextUtility::getSearchText($n,'Apólice','datebr',['side'=>'left']);
        $data['data_emissao'] = $n;

        //*** dados do segurado ***
        $segurado_text = TextUtility::getPartOfStr($this->text, ['start'=>'Segurado','end'=>'Vigência']);
        $data['segurado_nome'] = TextUtility::getPartOfStr($segurado_text,['start'=>'Nome / Razão Social','end'=>'Endereço','remove'=>['Nome / Razão Social','Endereço']]);
        $data['segurado_nome'] = $this->getX1(['start'=>'CPF','return_type'=>'next']);

        $tmp = TextUtility::getPartOfStr($this->text_ws02,['start'=>'CNPJ/CPF']);
        $tmp = TextUtility::getPartOfStr($tmp,['end'=>'CEP']);

        $tmp = TextUtility::getSearchText($tmp,'CPF','document',['side'=>'right']);
        if(strlen($tmp)>18){
            $tmp = substr($tmp,-18);
        }

        $data['segurado_doc']= TextUtility::getSearchText($tmp,'','document');
        $n=$data['segurado_doc'];
        $data['tipo_pessoa'] = ValidateUtility::isCPF($n) ? 'FISICA' : (ValidateUtility::isCNPJ($n)?'JURIDICA':'');

        if($data['tipo_pessoa']=='JURIDICA'){
            $segurado_text = TextUtility::getPartOfStr($this->text, ['start'=>'Segurado','end'=>'Vigência']);
            $data['segurado_nome'] = TextUtility::getPartOfStr($segurado_text,['start'=>'Nome / Razão Social','end'=>'Endereço','remove'=>['Nome / Razão Social','Endereço']]);
            $data['segurado_nome'] = $this->getX1(['start'=>'CNPJ','return_type'=>'prev']);
        }
       // dd($data['segurado_nome']);
        return ['success'=>true,'data'=>$data];
    }

    /**
     * Retorna os dados do premio
     */
    public function getPremio_tipo1($data){

        //*** dados do pagamento ***
        $pgto_text = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'Condições de Pagamento']);
        $pgto_text = TextUtility::getPartOfStr($pgto_text, ['start'=>'Vencimento']);
        $pgto_text = TextUtility::getPartOfStr($pgto_text, ['end'=>'Forma Cobrança']);
        //dd( $pgto_text,$this->text);

        $pgto_text = str_replace(' 0,00 ','',$pgto_text);
        $pgto_text = str_replace('QUITADA', $data['data_emissao'],$pgto_text);

        $form_pgto = TextUtility::getPartOfStr($this->text, ['start'=>'Forma Cobrança','end'=>'Susep ']);
        $data['fpgto_tipo']=PgtoData::getPgtoTipo($form_pgto);
        $data['fpgto_tipo_code']=PgtoData::getPgtoCode( $data['fpgto_tipo'] )[1];


        //$valores = TextUtility::getSearchText($pgto_text,'','number_formated',['limit'=>false]);
        $datavenc = TextUtility::getSearchText($pgto_text,'','datebr',['limit'=>false]);
        $parcelas = count($datavenc);
        //dd($datavenc,$pgto_text);
        $valores = [];
        for($i=0;$i<$parcelas;$i++){
            $valores[$i] = TextUtility::getSearchText($pgto_text,$datavenc[$i],'number_formated',['side'=>'left']);
        }
       $n= PgtoData::getTableOrderDate($datavenc, $valores);
       $datavenc=$n[0];
       $valores = $n[1];
       //dd($n[0]);
        $r = PgtoData::makeTable($parcelas,$datavenc,$valores,$inicia_parcela=1,$data_add=null);
        //dd($valores,$pgto_text);

        if($r)$data = $data + $r;

        $data = $data + PgtoData::addFields1($data);

        $text_premio = TextUtility::getPartOfStr($this->text, ['start'=>'SUBTOTAL']);
        $text_premio = TextUtility::getPartOfStr($text_premio, ['end'=>'CNPJ']);
        $text_premio = str_replace(['LO.F.','.O.F.','1.O.F.','L.O.F.'],'I.O.F',$text_premio);
        //dd($text_premio,$this->text);
        $data['fpgto_premio_total'] = PgtoData::getPremioTotal($text_premio);
        $data['fpgto_premio_liquido'] = TextUtility::getSearchText($text_premio,'SUBTOTAL','number_formated',['side'=>'right']);
        $data['fpgto_premio_liq_serv'] = '0,00';
        $data['fpgto_desc'] = '0,00';
        $data['fpgto_iof'] = TextUtility::getSearchText($text_premio,'I.O.F','number_formated',['side'=>'right']);
        $data['fpgto_adicional'] = TextUtility::getSearchText($text_premio,'FRACIONAMENTO','number_formated',['side'=>'right']);
        $data['fpgto_custo'] = TextUtility::getSearchText($text_premio,'CUSTO DA','number_formated',['side'=>'right']);
        $data['fpgto_juros'] = '0,00';
        $data['fpgto_juros_md'] = '0,00';
        //dd($data,$text_premio);

       return $data;
    }

    /**
     * Retorna os dados do premio
     */
    public function getPremio_tipo2($data){// usado no empresarial

        //*** dados do pagamento ***
        $form_pgto = TextUtility::getPartOfStr($this->text, ['start'=>'Forma de Cobrança']);

        $form_pgto = TextUtility::getSearchText($form_pgto,'Forma de Cobrança','value',['side'=>'right']);        //dd($form_pgto);
        $data['fpgto_tipo']=PgtoData::getPgtoTipo($form_pgto);
        $data['fpgto_tipo_code']=PgtoData::getPgtoCode( $data['fpgto_tipo'] )[1];

        $pgto_text = TextUtility::getPartOfStr($this->text, ['start'=>'Condições de Pagamento']);
        $pgto_text = TextUtility::getPartOfStr($pgto_text, ['start'=>'Condições de Pagamento','end'=>'Corretor']);

        $pgto_text = str_replace('0,00','',$pgto_text);

        $pgto_text = str_replace('QUITADA', $this->getDate1aParc($data['fpgto_tipo'],$data),$pgto_text);

        //$valores = TextUtility::getSearchText($pgto_text,'','number_formated',['limit'=>false]);
        $datavenc = TextUtility::getSearchText($pgto_text,'','datebr',['limit'=>false]);
        $parcelas = count($datavenc);
        //dd($datavenc,$pgto_text);
        $valores = [];
        for($i=0;$i<$parcelas;$i++){
            $valores[$i] = TextUtility::getSearchText($pgto_text,$datavenc[$i],'number_formated',['side'=>'left']);
        }
        $r = PgtoData::makeTable($parcelas,$datavenc,$valores,$inicia_parcela=1,$data_add=null);


        if($r)$data = $data + $r;

        $data = $data + PgtoData::addFields1($data);

        $text_premio = TextUtility::getPartOfStr($this->text, ['start'=>'PRÊMIO LÍQUIDO']);
        $text_premio = TextUtility::getPartOfStr($text_premio, ['end'=>'Outros Seguros']);
        $text_premio = str_replace(['1.O.F','1.0.F','I.0.F','L.O.F'],'I.O.F',$text_premio);
         // dd($text_premio,$this->text);
        $data['fpgto_premio_total'] = $r = PgtoData::getPremioTotal($text_premio);
        $data['fpgto_premio_liquido'] = TextUtility::getSearchText($text_premio,'FINAL','number_formated',['side'=>'right']);
        $data['fpgto_premio_liq_serv'] = '0,00';
        $data['fpgto_desc'] = '0,00';
        $data['fpgto_iof'] = TextUtility::getSearchText($text_premio,'I.O.F','number_formated',['side'=>'right']);
        $data['fpgto_adicional'] = TextUtility::getSearchText($text_premio,'FRACIONAMENTO','number_formated',['side'=>'right']);
        $data['fpgto_custo'] = TextUtility::getSearchText($text_premio,'CUSTO DE','number_formated',['side'=>'right']);
        $data['fpgto_juros'] = '0,00';
        $data['fpgto_juros_md'] = '0,00';
       // dd($data,$text_premio);

       return $data;
    }

}

