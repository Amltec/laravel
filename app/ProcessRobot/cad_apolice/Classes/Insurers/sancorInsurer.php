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
trait sancorInsurer{

    //método de inicialização
    public function initInsurer(){
        $this->pdf_engine = 'ait_xpdfr'; //nome do interpretador de pdf (valores em  \App\Utilities\FilesUtility::readPDF)
        $this->process_opt=[];
    }

      /**
     * Configuração para o padrão do número do quiver
     */
    function numQuiverConfig(){
        return [
            'ex_num'=>'2021310108782',
            'not_dot_traits'=>true,
            'not_zero_left'=>true
        ];
    }


    /**
     * Retorna os dados do segurado
     * @return error:   [success,msg,data,ignore,code]
     *         ok:      [sucess,data]
     */
    public function getDados1(){
        $data=[];

        //*** captura o tipo da apólice - para verificar se é do tipo automovel ***
        $data['apolice_prod_ref'] = $this->checkRamo();
        if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];


        //verifica se é endosso
        $tmp = trim(TextUtility::getSearchText($this->text,'Endosso:','number',['max_words'=>1]));
        $data['data_type'] = $tmp?'endosso':'apolice';
        //dd($data['data_type']);
        if($data['data_type']=='endosso')return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];


        //*** dados do corretor ***
        $corretor_text = TextUtility::getPartOfStr($this->text, ['start'=>'Registro Susep Telefone','end'=>'DADOS DO SEGURO','remove'=>['Registro Susep Telefone','DADOS DO SEGURO']]);
        $data['corretor_nome'] = trim(preg_replace('/[0-9]+/', '', $corretor_text));
        $data['corretor_susep'] = TextUtility::getSearchText($corretor_text,$data['corretor_nome'],'number',['side'=>'right']);


        //verifica se é a corretora 365 ADMINISTRADORA E CORRETORA DE SEGUROS S/S
        if(strpos($this->text, '365 ADMINISTRADORA')){
            $this->text = str_replace('365 ADMINISTRADORA', 'ADMINISTRADORA', $this->text);

        }


        //dd($corretor_text);
        if(!$data['corretor_susep']){

            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'registro susep','end'=>'dados do seguros','sanitize'=>true]);
            $blocktext = str_replace('telefone 365', '', $blocktext);
             //dd($blocktext);
            $data['corretor_susep'] = TextUtility::getSearchText($this->text,'registro susep','number',['side'=>'right']);
        }

        //*** dados da apólice ***
        $apolice_text = TextUtility::getPartOfStr($this->text, ['start'=>' Brasil CNPJ','end'=>'Telefone:']);


        $data['seguradora_doc'] = TextUtility::getSearchText($apolice_text,'CNPJ:','value',['side'=>'right']);


        $text0 = FormatUtility::sanitizeAllText($this->text);

        $proposta_text = TextUtility::getPartOfStr($text0, ['start'=>'parcela apolice','remove'=>['parcela apolice']]);
        //dd($text0);
        $proposta_text = TextUtility::getPartOfStr($proposta_text, ['end'=>'premio']);


        $data['proposta_num'] = TextUtility::getSearchText($proposta_text,'proposta','value',['side'=>'right']);

        if($data['proposta_num']==''){
            $proposta_text = TextUtility::getPartOfStr($text0, ['start'=>'ramo automovel apolice','remove'=>['apolice endosso']]);
            $proposta_text = TextUtility::getPartOfStr($proposta_text, ['end'=>'premio']);
           // dd($proposta_text);

            $data['proposta_num'] = TextUtility::getSearchText($proposta_text,'proposta','value',['side'=>'right']);
        }


        $apolice_text = TextUtility::getPartOfStr($this->text, ['start'=>'31 - Casco','remove'=>['31 -']]);
        $apolice_text = TextUtility::getPartOfStr($apolice_text, ['end'=>'SEGURADO']);

        if(!$apolice_text){
            $apolice_text = TextUtility::getPartOfStr($this->text, ['start'=>'53 - RCF Veículos','remove'=>['RCF Veículos']]);
            $apolice_text = TextUtility::getPartOfStr($apolice_text, ['end'=>'SEGURADO']);
            $apolice_text = str_replace('53 -', 'Casco', $apolice_text);
        }
        //dd($apolice_text);
        $data['apolice_num'] = TextUtility::getSearchText($apolice_text,'Casco','number',['side'=>'right']);


        //atualizado para ser capturado pela função numQuiverConfig()
       // $data['apolice_num_quiver'] = ltrim($data['apolice_num'],0);

        $data['apolice_re_num'] = '';// não tem informação sobre apólice renovada

        $data['inicio_vigencia'] = TextUtility::getSearchText($this->text,'Das 24 horas de','datebr',['side'=>'right']);

        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'24 horas de','sanitize'=>true]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'premio']);
        $n=[];
        TextUtility::execFncInStr($blocktext,10,function($v) use(&$n){
            if(ValidateUtility::isDate($v) && strlen(trim($v))==10){
                $n[]=$v;
            }
        });
        $data['termino_vigencia'] = $n[1];
        //dd($data['termino_vigencia'], $blocktext);
        //dd($blocktext,$n);


        $data['data_emissao'] = TextUtility::getSearchText($this->text,'SEGURADO / PROPONENTE','datebr',['side'=>'left']);



        //*** dados do segurado ***
        $text0 = str_replace('/CNP)', '/ CNPJ', $this->text);
        if(!strpos($text0, 'CPF / CNPJ')){
            $text0 = str_replace('CPF/)', 'CPF /', $this->text);
        }
        if(!strpos($text0, 'CPF / CNPJ')){
             $text0 = str_replace('/CNPJ', '/ CNPJ', $this->text);
        }
        if(!strpos($text0, 'CPF / CNPJ')){
            $text0 = str_replace('/CNP)', '/ CNPJ', $this->text);
        }
        if(!strpos($text0, 'CPF / CNPJ')){
             $text0 = str_replace('CPF /CNPJ', 'CPF / CNPJ', $this->text);
        }
        if(!strpos($text0, 'CPF / CNPJ')){
            $text0 = str_replace('CPF/ CNPJ', 'CPF / CNPJ', $this->text);
        }
        if(!strpos($text0, 'CPF / CNPJ')){
            $text0 = str_replace('CPF/CNPJ', 'CPF / CNPJ', $this->text);
        }


        $segurado_text = TextUtility::getPartOfStr($text0, ['start'=>'Tipo de Pessoa','remove'=>['Tipo de Pessoa','CPF / CNPJ']]);
        $segurado_text = TextUtility::getPartOfStr($segurado_text, ['end'=>'Telefone']);

        // dd($segurado_text);
        $r=TextUtility::execFncInStr($segurado_text,3,function($v){//return array: 0 find, 1 left, 2 right
            if($v===' F ' || $v===' M ')return true;
        });


        if($r[0]){//encontrou
            //dd($r);
            $data['segurado_nome'] = trim($r[1]);

        }else{
            if(strpos($segurado_text, 'Física')!==false){
                //dd(123  );
                $segurado_text = str_replace(['Física','Telefone'], '', $segurado_text);
                $data['segurado_nome'] = trim(substr($segurado_text, 15));

               /*
                if(strpos($data['segurado_nome'], 'Complemento')){
                     //dd($data['segurado_nome']);
                    $segurado_text = TextUtility::getPartOfStr($text0, ['start'=>'Nome','remove'=>['Nome','CPF / CNPJ']]);
                    $segurado_text = TextUtility::getPartOfStr($segurado_text, ['end'=>'Telefone']);
                    $segurado_text = trim(str_replace('Telefone', '', $segurado_text));
                    $data['segurado_nome'] = $segurado_text;
                }
               */
            }else{
               $r=TextUtility::execFncInStr($segurado_text,9,function($v){//return array: 0 find, 1 left, 2 right
                if($v==='Jurídica')return true;
                });
                $data['segurado_nome'] = trim($r[1]);
            }


        }
        $data['segurado_doc']  = TextUtility::getSearchText($this->text,'CPF / CNPJ','document',['side'=>'right']);

        //dd($text0);

        $n=$data['segurado_doc'];
        $data['tipo_pessoa'] = ValidateUtility::isCPF($n) ? 'FISICA' : (ValidateUtility::isCNPJ($n)?'JURIDICA':'');

        return ['success'=>true,'data'=>$data];
    }


    /**
     * Retorna os dados do premio
     */
    public function getPremio($data){


         //*** dados do pagamento ***
       // $text_pgto = TextUtility::getPartOfStr($this->text, ['start'=>'AUTORIZAÇÃO DE PAGAMENTO']);
        //$text_pgto = TextUtility::getPartOfStr($text_pgto, ['end'=>'Assinatura do SEGURADO']);
        $text_pgto = TextUtility::getPartOfStr($this->text, ['start'=>'Forma de Pagamento']);
        $text_pgto = TextUtility::getPartOfStr($text_pgto, ['end'=>'Adicional']);
        $tipo_pgto = PgtoData::getPgtoTipo($text_pgto);
        $tipo_pgto = PgtoData::getPgtoCode($tipo_pgto);


        $data['fpgto_tipo'] = $tipo_pgto[0];
        $data['fpgto_tipo_code'] = $tipo_pgto[1];




        $text_parcelas = TextUtility::getPartOfStr($this->text, ['start'=>'FRACIONAMENTO DO PRÊMIO']);
        $text_parcelas = TextUtility::getPartOfStr($text_parcelas, ['end'=>'Sancor']);
        $text_parcelas = str_replace(['R$', 'R$ '],'', $text_parcelas);

        $text_remove = TextUtility::getSearchText($text_parcelas,'Data emissão','datebr',['side'=>'right']);
        $text_remove = 'Data emissão '.$text_remove;

        $text_parcelas = str_replace($text_remove,'', $text_parcelas);

        $valores = TextUtility::getSearchText($text_parcelas,'','number_formated',['limit'=>false]);
        $datavenc = TextUtility::getSearchText($text_parcelas,'','datebr',['limit'=>false]);
        $parcelas = count($datavenc);

        $valores = array_slice($valores, -$parcelas);
        //dd($text_remove,$text_parcelas,$datavenc);
        $r = PgtoData::makeTable('', $datavenc, $valores);
        $text_premio = TextUtility::getPartOfStr($this->text, ['start'=>'Adicional de fracionamento','end'=>'ITEM SEGURADO']);
        $premio = PgtoData::getPremioTotalParcela($text_premio, $valores, $this->validate_premio_margem);

        if(!$premio){
            $text_premio = TextUtility::getPartOfStr($this->text, ['start'=>'Forma de Pagamento','end'=>'ITEM SEGURADO']);
            $premio = PgtoData::getPremioTotalParcela($text_premio, $valores, $this->validate_premio_margem);
        }

        if(!$premio){
            $text_premio = TextUtility::getPartOfStr($this->text, ['start'=>'Forma de Pagamento','end'=>'ITEM SEGURADO']);
            //dd($text_premio);
            $premio = TextUtility::getSearchText($text_premio,'Prêmio das','value',['side'=>'left']);
            $premio = str_replace('R$', '', $premio);
            $data['fpgto_valorparc_1'] = TextUtility::getSearchText($text_premio,'IOF','value',['side'=>'right']);   ;
            $data['fpgto_valorparc_1'] =str_replace('R$', '', $data['fpgto_valorparc_1']);
        }


        //dd($premio);
        $data['fpgto_premio_total'] = $premio;
        if($r)$data = $data + $r;

        $data = $data + PgtoData::addFields1($data);


        //dd($data);
        $demais_par = 0;
         $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'premio liquido','end'=>'premio total','sanitize'=>true]);
         $blocktext = str_replace('r$', 'r$ ', $blocktext);


         $demais_par = TextUtility::getSearchText($blocktext,'iof',function($v,$left,$right){
         //dd($demais_par);

             if($v=='demais')return $v;
         },['side'=>'left']);
         if($demais_par){
            $blocktext =  str_replace('iof', '', $blocktext);
            $blocktext =  str_replace('de parcelas', 'iof', $blocktext);
            $iof=0;
         }else{
            // $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'de parcelas','end'=>'Prêmio','sanitize'=>true]);
             $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'horas de','end'=>'Prêmio','sanitize'=>true]);
              $blocktext = str_replace('r$', 'r$ ', $blocktext);
             //dd($blocktext);
            $iof = TextUtility::getSearchText($blocktext,'de parcelas','number_formated',['limit'=>true]);
         }
         //dd($iof);
         $blocktext =  str_replace($data['fpgto_1_prestacao_valor'], '', $blocktext);
         $blocktext = str_replace(' 0 ', ' 0,00 ', $blocktext);
        //$n=TextUtility::getPartOfStr($blocktext, ['end'=>'iof']);
        //dump($n);
       //dd($blocktext);
        //dd($iof);
         if($iof){

             $r=PgtoData::getFielsPremioAdd($blocktext,$data,['tem_juros'=>false,'iof'=>$iof]);
            // dd($r);
         }else{

             $r=PgtoData::getFielsPremioAdd($blocktext,$data,['tem_juros'=>false]);
         }

       // dd($r);
        $data = $data + $r;

       if($data['fpgto_premio_liquido']==$data['fpgto_adicional']){
           $adicional= TextUtility::getSearchText($blocktext,'Forma de Pagamento','number_formated',['side'=>'right']);
           $data['fpgto_adicional'] = $adicional;
           //dd($adicional);
       }
        //dd($data['fpgto_premio_liquido'],$data['fpgto_adicional']);

        return $data;
    }

    public function getDados2(){
        $data=[];

        //*** captura o tipo da apólice - para verificar se é do tipo automovel ***
        $data['apolice_prod_ref'] = $this->checkRamo();
        if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];


        //verifica se é endosso
        $tmp = trim(TextUtility::getSearchText($this->text,'Endosso:','number',['max_words'=>1]));
        $data['data_type'] = $tmp?'endosso':'apolice';
        //dd($data['data_type']);
        if($data['data_type']=='endosso')return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];


        //*** dados do corretor ***
        $text0 = FormatUtility::sanitizeBreakText($this->text);

        $corretor_text = TextUtility::getPartOfStr($text0, ['start'=>'Susep Telefone','end'=>'COSSEGURO','remove'=>['Susep Telefone','COSSEGURO'],'sanitize'=>false]);
        $data['corretor_nome'] = trim(preg_replace('/[0-9]+/', '', $corretor_text));
        //dd($data['corretor_nome'],$text0);
        $data['corretor_susep'] = TextUtility::getSearchText($corretor_text,$data['corretor_nome'],'number',['side'=>'right']);


        //verifica se é a corretora 365 ADMINISTRADORA E CORRETORA DE SEGUROS S/S
        if(strpos($text0, '365 ADMINISTRADORA')){
            $this->text = str_replace('365 ADMINISTRADORA', 'ADMINISTRADORA', $text0);

        }


        //dd($corretor_text);
        if(!$data['corretor_susep']){

            $blocktext = TextUtility::getPartOfStr($text0, ['start'=>'registro susep','end'=>'dados do seguros','sanitize'=>true]);
            $blocktext = str_replace('telefone 365', '', $blocktext);
             //dd($blocktext);
            $data['corretor_susep'] = TextUtility::getSearchText($this->text,'registro susep','number',['side'=>'right']);
        }

        //*** dados da apólice ***
        $apolice_text = TextUtility::getPartOfStr($this->text, ['start'=>' S.A. - CNPJ','end'=>'MATRIZ']);


        $data['seguradora_doc'] = TextUtility::getSearchText($apolice_text,'CNPJ','value',['side'=>'right']);


        $text0 = FormatUtility::sanitizeAllText($this->text);

        $proposta_text = TextUtility::getPartOfStr($text0, ['start'=>'proposta']);
        //dd($text0);
        $proposta_text = TextUtility::getPartOfStr($proposta_text, ['end'=>'filial']);


        $data['proposta_num'] = TextUtility::getSearchText($proposta_text,'proposta','number',['side'=>'right']);

        $apolice_text = TextUtility::getPartOfStr($this->text, ['start'=>'14 - COMPREENSIVO']);
        $apolice_text = TextUtility::getPartOfStr($apolice_text, ['end'=>'Endosso']);

        if(empty($apolice_text)){
            $apolice_text = TextUtility::getPartOfStr($this->text, ['start'=>'EMPRESARIAL  Apólice']);
            $apolice_text = TextUtility::getPartOfStr($apolice_text, ['end'=>'Endosso']);
        }

        //dd($apolice_text,$this->text);
        $data['apolice_num'] = TextUtility::getSearchText($apolice_text,'Apólice','number',['side'=>'right']);

        //atualizado para ser capturado pela função numQuiverConfig()
        //$data['apolice_num_quiver'] = ltrim($data['apolice_num'],0);

        $data['apolice_re_num'] = '';// não tem informação sobre apólice renovada

        $data['inicio_vigencia'] = TextUtility::getSearchText($this->text,'Das 24 horas de','datebr',['side'=>'right']);

        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'24 horas de','sanitize'=>true]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'premio']);
        $n=[];
        TextUtility::execFncInStr($blocktext,10,function($v) use(&$n){
            if(ValidateUtility::isDate($v) && strlen(trim($v))==10){
                $n[]=$v;
            }
        });
        $data['termino_vigencia'] = $n[1];
        //dd($data['termino_vigencia'], $blocktext);
        //dd($blocktext,$n);


        $data['data_emissao'] = TextUtility::getSearchText($this->text,'Data Emissão','datebr',['side'=>'right']);



        //*** dados do segurado ***

        $segurado_text = TextUtility::getPartOfStr($this->text, ['start'=>'Nome']);
        $segurado_text = TextUtility::getPartOfStr($segurado_text, ['end'=>'Número']);



        $data['segurado_nome'] = trim(TextUtility::getPartOfStr($segurado_text,['start'=>'Nome ', 'end'=>'CEP ','remove'=>['Nome','CEP ']]));
        $data['segurado_doc']  = TextUtility::getSearchText($segurado_text,'CPF/CNPJ','document',['side'=>'right']);

        $n=$data['segurado_doc'];
        $data['tipo_pessoa'] = ValidateUtility::isCPF($n) ? 'FISICA' : (ValidateUtility::isCNPJ($n)?'JURIDICA':'');

        return ['success'=>true,'data'=>$data];
    }

    private static function clearText($text){
        $text =  preg_replace('/[^\d\-]/', '',$text);
        return $text;
    }

}
