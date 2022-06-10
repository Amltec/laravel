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
trait tokioInsurer{

    //método de inicialização
    public function initInsurer(){
        $this->pdf_engine = 'ws02'; //nome do interpretador de pdf (valores em  \App\Utilities\FilesUtility::readPDF)

        //margem de diferença de centavos entre o cálculo do iof com o iof capturado em $data para o PgtoData::validateAll(). Obs: por padrão deveria ser igual, mas na prática existe diferença de centávos entre as apólices.
        $this->validate_iof_margem=0.48;
    }


      /**
     * Configuração para o padrão do número do quiver
     */
    function numQuiverConfig(){
        return [
            'ex_num'=>'80426119',
            'not_dot_traits'=>true,
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


        //captura o tipo da apólice - para verificar se é do tipo automovel
        $data['apolice_prod_ref'] = $this->checkRamo();
        if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];

        if(strpos($this->text,'Tokio Marine Auto Frota')!==false){
            return ['success'=>false,'msg'=>'Ignorado apólice do tipo frota','data'=>[],'ignore'=>true,'code'=>'read04'];
        }

        //dd($data);
        $text_cnpj = '';
        //dados do corretor

        $text_corretor = TextUtility::getPartOfStr($this->text, ['start'=>'Nome Corretor:','end'=>'CNPJ:','remove'=>['Nome Corretor:','CNPJ:']]);
        $data['corretor_nome'] = trim($text_corretor);//nome do corretor

        if(empty( $data['corretor_nome'])){
            $n = $this->getX1(['start'=>'Corretor','end'=>'Código:','split'=>false,'remove'=>'Nome:']);//nome do corretor
            $n = str_replace(['Corretor','Código:'], ['',''], $n);
            $n = FormatUtility::sanitizeAllText($n);
            $data['corretor_nome'] = trim($n);//nome do corretor
        }
        if(strpos($data['corretor_nome'],'cnpj:')!=false){
            $n = explode('cnpj:',$data['corretor_nome']);
            $n = str_replace([':'], [''], $n);
            $data['corretor_nome'] = trim($n[0]);//nome do corretor

        }



        $n = $this->getX1(['start'=>'SUSEP:','remove'=>'SUSEP:']);//susep do corretor
        $n = str_replace(['.'], [''], $n);
        $n = FormatUtility::sanitizeAllText($n);
        $data['corretor_susep'] = trim($n);//susep do corretor


        //dados da apólice
        // $data['data_type'] = $this->getX1(['start'=>'Apólice de Seguro']);
         $data['data_type'] = stripos($this->getX1(['start'=>'Apólice de Seguro']),'Endosso')!==false ? 'endosso' : 'apolice';
       //dd( $data['data_type']);

         $textEndosso = TextUtility::getPartOfStr($this->text, ['start'=>'Endosso:'],['sanitize'=>true]);
         $textEndosso = TextUtility::getPartOfStr($textEndosso, ['end'=>'Dados ']);
         $textEndosso = TextUtility::getSearchText($textEndosso,'endosso:','number',['side'=>'right']);
         if($textEndosso==''){
            $textEndosso = TextUtility::getPartOfStr($this->text, ['start'=>'Endosso:'],['sanitize'=>true]);
            $textEndosso = TextUtility::getPartOfStr($textEndosso, ['end'=>'Data da']);
            $textEndosso = TextUtility::getSearchText($textEndosso,'endosso:','number',['side'=>'right']);
         }
         //dd($textEndosso);
         if($textEndosso>=1){
             return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];
         }


         if($data['data_type']=='')return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];

         $n = $this->getX1(['start'=>'- CNPJ','return_type'=>'next','cb'=>function($v){ return explode(" ",$v)[0];  }]);//CNPJ Seguradora
         //dd($n);
         if($n==false || $n=='Processo'){
            $n = $this->getX1(['start'=>'CNPJ ','cb'=>function($v){ return explode(" ",$v)[1];  }]);//CNPJ Seguradora
         }

         if($n=='Participação'){
            $textCNPJ = TextUtility::getPartOfStr($this->text, ['start'=>'Código SUSEP:'],['sanitize'=>false]);
            $textCNPJ = TextUtility::getPartOfStr($textCNPJ, ['end'=>'Processo']);
            $textCNPJ = str_replace(['.','-','/'], '', $textCNPJ);
            $n = TextUtility::getSearchText($textCNPJ,'','cnpj',['side'=>'right']);//CNPJ Seguradora
         }else{
            $n = substr($n,0,-1);

            //$n = str_replace(['-','.','/'], ['','',''], $n);
            if(strlen($n)==19){
                   $n=substr($n, 1);
            }
         }

         $data['seguradora_doc'] = $n;//CNPJ Seguradora

         if(empty($data['seguradora_doc'])){
            $text_cnpj = TextUtility::getPartOfStr($this->text, ['start'=>' S.A. - CNPJ'],['sanitize'=>false]);
            $text_cnpj = TextUtility::getPartOfStr($text_cnpj, ['end'=>'Código'],['sanitize'=>false]);
            $text_cnpj = str_replace('033.','33.',$text_cnpj);

            $data['seguradora_doc'] = TextUtility::getSearchText($text_cnpj,'S.A.','cnpj',['side'=>'right']);//CNPJ Seguradora
         }

         if(empty($data['seguradora_doc']) || $data['seguradora_doc']=='Correntista'){
            $text_cnpj = TextUtility::getPartOfStr($this->text, ['start'=>'Tokio Marine Seguradora S.A.'],['sanitize'=>false]);
            $text_cnpj = TextUtility::getPartOfStr($text_cnpj, ['end'=>'Processo SUSEP'],['sanitize'=>false]);
            $text_cnpj = str_replace('033.','33.',$text_cnpj);

            $data['seguradora_doc'] = TextUtility::getSearchText($text_cnpj,'S.A.','cnpj',['side'=>'right']);//CNPJ Seguradora
         }
         if(strpos($text_cnpj,'###########')!==false ){
            $text_cnpj = TextUtility::getPartOfStr($this->text, ['start'=>'Processo SUSEP'],['sanitize'=>false]);
            $text_cnpj = TextUtility::getPartOfStr($text_cnpj, ['end'=>'cuja'],['sanitize'=>false]);
            $data['seguradora_doc'] = TextUtility::getSearchText($text_cnpj,'Nº','value',['side'=>'right']);//CNPJ Seguradora
           // dd( $data['seguradora_doc'],$text_cnpj);
         }



         $n = $this->getX1(['start'=>'Proposta:','cb'=>function($v){ return explode(":",$v)[1];  }]);//Numero da Proposta Seguradora
         $n = trim($n);
         $n = TextUtility::getSearchText($n,'','number',['side'=>'right']);
        // dd($n);
         $data['proposta_num'] = $n;//Numero da Proposta Seguradora

         $n = $this->getX1(['start'=>'Apólice:','end'=>'Negócio','remove'=>'Apólice:','cb'=>function($v){ return explode(" ",$v)[0];  }]);//Número da Apólice na Seguradora
         $data['apolice_num'] = trim($n);//Número da Apólice na Seguradora

         //atualizado para ser capturado pela função numQuiverConfig()
         //$data['apolice_num_quiver'] =ltrim($data['apolice_num'],0);//Número da Apólice Quiver

         $data['data_emissao'] = TextUtility::getSearchText($this->text,'da Emissão:','datebr',['side'=>'right']);
         if(empty($data['data_emissao'] )){
            $data['data_emissao'] =trim($this->getX1(['start'=>'Emissão:','remove'=>'Emissão:']));//Data de Emissão
         }


         $n = $this->getX1(['start'=>'Tipo de Seguro:']);//Numero da apólice renovada
         if(strpos($n,'Apólice Renovada:')!==false){
            $data['apolice_re_num'] = $this->getX1(['start'=>'Apólice Renovada:','remove'=>'Apólice Renovada:','cb'=>function($v){ return explode(".",$v)[1];  }]);//Numero da apólice renovada
         }else{
            $data['apolice_re_num']='';
         }


        $data['inicio_vigencia'] = $this->getX1(['start'=>'24 horas do dia ','remove'=>'24 horas do dia ','cb'=>function($v){  $n=explode(' ',$v); return $n[0];  }]);//Data Início de vigência

        $data['inicio_vigencia'] = str_replace(',', '', $data['inicio_vigencia']);

        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'24 horas do dia ','sanitize'=>true]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'data da']);
        $n=[];
        TextUtility::execFncInStr($blocktext,10,function($v) use(&$n){
            if(ValidateUtility::isDate($v) && strlen(trim($v))==10){
                $n[]=$v;
            }
        });
        $data['termino_vigencia'] = $n[1];
        //dd($data['termino_vigencia'], $blocktext);
        //dd($blocktext,$n);


         //Dados do Segurado
         $text_nome = TextUtility::getPartOfStr($this->text, ['start'=>'Segurado:','end'=>':','remove'=>['Segurado:'],'sanitize'=>false]);
         $text_nome = TextUtility::getPartOfStr($text_nome, ['end'=>':','remove'=>['CNPJ:','CPF:'],'sanitize'=>false]);
         $data['segurado_nome'] = $text_nome;
         if(empty($data['segurado_nome'])){
            $n = $this->getX1(['start'=>'Nome:','remove'=>'Nome:','cb'=>function($v){  $n=explode(':',$v); return $n[0];  }]);//Nome Segurado
            $data['segurado_nome'] = trim($n);//Nome Segurado
         }

         $n= $this->getX1(['start'=>'Endereço:','return_type'=>'prev']);
         $n=explode(':',$n);
         $n=str_replace(['-','.','/'], ['','',''], $n);

         $n=str_replace('Cód Cliente', '',$n);
         $n = trim($n[1]);
         if(strlen($n)==15)$n=substr($n,1,strlen($n));

         $text_doc = TextUtility::getPartOfStr($this->text, ['start'=>'Segurado:','end'=>'Endere']);

         $data['segurado_doc'] = TextUtility::getSearchText($text_doc,'','document',['side'=>'right']);
         //dd($text_doc);
         if(empty($data['segurado_doc']) && strpos($text_doc,'CNPJ:')!=false){
            $n = TextUtility::getSearchText($text_doc,'CNPJ:','value',['side'=>'right']);
            $n = substr($n, 1);
            $data['segurado_doc'] = $n;//documento segurado
         }
         if(empty($data['segurado_doc'])){
            $data['segurado_doc'] = $n;//documento segurado
         }
         $n=str_replace(['-','.','/'], ['','',''], $data['segurado_doc']);
        // dd(strlen($n),$data['segurado_doc']);
         if(strlen($n )==11){$n='FISICA';}else{$n='JURIDICA';}
         $data['tipo_pessoa'] = $n;//tipo segurado

        return ['success'=>true,'data'=>$data];
    }

    public function getDados2(){//empresarial
        $data=[];
        //dd(123);

        //captura o tipo da apólice - para verificar se é do tipo automovel
        $data['apolice_prod_ref'] = $this->checkRamo();
        if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];

        //dados do corretor
        $n = $this->getX1(['start'=>'Nome Corretor:','end'=>'Código:','split'=>false,'remove'=>'Nome:']);//nome do corretor
        $n = str_replace(['Nome Corretor:','Código:'], ['',''], $n);
        $n = FormatUtility::sanitizeAllText($n);
        $data['corretor_nome'] = trim($n);//nome do corretor


        $n = $this->getX1(['start'=>'SUSEP:','remove'=>'SUSEP:']);//susep do corretor
        $n = str_replace(['.'], [''], $n);
        $n = FormatUtility::sanitizeAllText($n);
        $data['corretor_susep'] = trim($n);//susep do corretor


        //dados da apólice
        // $data['data_type'] = $this->getX1(['start'=>'Apólice de Seguro']);
         $data['data_type'] = stripos($this->getX1(['start'=>'Apólice de Seguro']),'Endosso')!==false ? 'endosso' : 'apolice';

         if($data['data_type']=='')return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];

         $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'SUSEP: 6190','sanitize'=>true]);
         $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'O registro']);
         //dd($blocktext);
         $blocktext = str_replace(['cnpj 033.'], ['33.'], $blocktext);

         $data['seguradora_doc'] = TextUtility::getSearchText($blocktext,'SUSEP:','cnpj',['side'=>'right']);;//CNPJ Seguradora
         //dd( $data['seguradora_doc']);
         //dd($data,$blocktext);
         $n = $this->getX1(['start'=>'Proposta:','cb'=>function($v){ return explode(":",$v)[1];  }]);//Numero da Proposta Seguradora
         $n = trim($n);
        // dd($n);
         $data['proposta_num'] = $n;//Numero da Proposta Seguradora

         $n = TextUtility::getSearchText($this->text,'Apólice:','number',['side'=>'right']);//Número da Apólice na Seguradora
         $data['apolice_num'] = trim($n);//Número da Apólice na Seguradora

         //atualizado para ser capturado pela função numQuiverConfig()
         //$data['apolice_num_quiver'] =ltrim($data['apolice_num'],0);//Número da Apólice Quiver

         $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'assinam este documento.','sanitize'=>true]);
         $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Atenciosamente,']);

         $data['data_emissao'] = TextUtility::getDateExtenso($blocktext,'datebr');//Data de Emissão

         $n = $this->getX1(['start'=>'Tipo de Seguro:']);//Numero da apólice renovada
         if(strpos($n,'Apólice Renovada:')!==false){
            $data['apolice_re_num'] = TextUtility::getSearchText($this->text,'Apólice Renovada:','number',['side'=>'right']);//Numero da apólice renovada
         }else{
            $data['apolice_re_num']='';
         }


        $data['inicio_vigencia'] = $this->getX1(['start'=>'24 horas do dia ','remove'=>'24 horas do dia ','cb'=>function($v){  $n=explode(' ',$v); return $n[0];  }]);//Data Início de vigência

        $data['inicio_vigencia'] = str_replace(',', '', $data['inicio_vigencia']);

        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'ate as 24 ','sanitize'=>true]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'segurado','sanitize'=>true]);
        $blocktext = str_replace('.','', $blocktext);
        $data['termino_vigencia'] = TextUtility::getSearchText($blocktext,'dia','datebr',['side'=>'right']);

         //Dados do Segurado
         if(strpos($this->text,'Tokio Marine Empresarial')!=false){
            $n = $this->getX1(['start'=>'Razão Social:','remove'=>'Razão Social:','cb'=>function($v){  $n=explode(':',$v); return $n[0];  }]);//Nome Segurado
            $data['segurado_nome'] = trim($n);//Nome Segurado
         }else{
            $n = $this->getX1(['start'=>'Nome:','remove'=>'Nome:','cb'=>function($v){  $n=explode(':',$v); return $n[0];  }]);//Nome Segurado
            $data['segurado_nome'] = trim($n);//Nome Segurado
         }
         if(empty($data['segurado_nome'])){
            $textName = TextUtility::getPartOfStr($this->text, ['start'=>'Nome:'],['sanitize'=>true]);
            $textName = TextUtility::getPartOfStr($textName, ['end'=>'CPF:']);
            $data['segurado_nome'] = trim(str_replace(['Nome:','CPF:'],'',$textName));
         }
         //dd($textName);
         $n= $this->getX1(['start'=>'Endereço:','return_type'=>'prev']);
         $n=explode(':',$n);
         $n=str_replace(['-','.','/'], ['','',''], $n);

         $n=str_replace('Cód Cliente', '',$n);
         $n = trim($n[1]);
         if(strlen($n)==15)$n=substr($n,1,strlen($n));
         $data['segurado_doc'] = $n;//documento segurado

         if(strlen($n)==11){$n='FISICA';}else{$n='JURIDICA';}
         $data['tipo_pessoa'] = $n;//tipo segurado

        return ['success'=>true,'data'=>$data];
    }

    public function getDados3(){//usado para auto layout junho/2021
        $data=[];

        //captura o tipo da apólice - para verificar se é do tipo automovel
        $data['apolice_prod_ref'] = $this->checkRamo();
        if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];



        //dados do corretor
        $n = TextUtility::getPartOfStr($this->text, ['start'=>'Corretor:'],['sanitize'=>true]);
        $n = TextUtility::getPartOfStr($n, ['end'=>'CNPJ:']);
        $n = str_replace(['Corretor:','CNPJ:'], ['',''], $n);
        $n = FormatUtility::sanitizeAllText($n);
        $data['corretor_nome'] = trim($n);//nome do corretor


        $n = $this->getX1(['start'=>'SUSEP:','remove'=>'SUSEP:']);//susep do corretor
        $n = str_replace(['.'], [''], $n);
        $n = FormatUtility::sanitizeAllText($n);
        $data['corretor_susep'] = trim($n);//susep do corretor


        //dados da apólice
        // $data['data_type'] = $this->getX1(['start'=>'Apólice de Seguro']);
         $data['data_type'] = stripos($this->getX1(['start'=>'Apólice de Seguro']),'Endosso')!==false ? 'endosso' : 'apolice';
       //dd( $data['data_type']);

         $textEndosso = TextUtility::getPartOfStr($this->text, ['start'=>'Endosso:'],['sanitize'=>true]);
         $textEndosso = TextUtility::getPartOfStr($textEndosso, ['end'=>'Dados ']);
         $textEndosso = TextUtility::getSearchText($textEndosso,'endosso:','number',['side'=>'right']);
         if($textEndosso>=1){
             return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];
         }
         //dd($textEndosso);

         if($data['data_type']=='')return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];

         $textCnpj = TextUtility::getPartOfStr($this->text, ['start'=>'S.A. - CNPJ 033'],['sanitize'=>false]);
         $textCnpj = TextUtility::getPartOfStr($textCnpj, ['end'=>'SUSEP']);
         $textCnpj = str_replace('CNPJ 033', 'CNPJ 33', $textCnpj);
         $n = TextUtility::getSearchText($textCnpj,'CNPJ','cnpj',['side'=>'right']);
         $data['seguradora_doc'] = $n;//CNPJ Seguradora


         $n = $this->getX1(['start'=>'Proposta:','cb'=>function($v){ return explode(":",$v)[1];  }]);//Numero da Proposta Seguradora
         $n = trim($n);
        // dd($n);
         $data['proposta_num'] = $n;//Numero da Proposta Seguradora

         $n = $this->getX1(['start'=>'Apólice:','end'=>'Negócio','remove'=>'Apólice:','cb'=>function($v){ return explode(" ",$v)[0];  }]);//Número da Apólice na Seguradora
         $data['apolice_num'] = trim($n);//Número da Apólice na Seguradora


         //atualizado para ser capturado pela função numQuiverConfig()
         //$data['apolice_num_quiver'] =ltrim($data['apolice_num'],0);//Número da Apólice Quiver

         $textEmissao = TextUtility::getPartOfStr($this->text, ['start'=>'Data da Emissão:'],['sanitize'=>false]);
         $textEmissao = TextUtility::getPartOfStr($textEmissao, ['end'=>'Data da Versão']);
         $data['data_emissao'] =TextUtility::getSearchText($textEmissao,'Data da Emissão:','datebr',['side'=>'right']);//Data de Emissão

         $n = $this->getX1(['start'=>'Tipo de Seguro:']);//Numero da apólice renovada
         if(strpos($n,'Apólice Renovada:')!==false){
            $data['apolice_re_num'] = $this->getX1(['start'=>'Apólice Renovada:','remove'=>'Apólice Renovada:','cb'=>function($v){ return explode(".",$v)[1];  }]);//Numero da apólice renovada
         }else{
            $data['apolice_re_num']='';
         }


        $data['inicio_vigencia'] = $this->getX1(['start'=>'24 horas do dia ','remove'=>'24 horas do dia ','cb'=>function($v){  $n=explode(' ',$v); return $n[0];  }]);//Data Início de vigência

        $data['inicio_vigencia'] = str_replace(',', '', $data['inicio_vigencia']);

        $textVigFin = TextUtility::getPartOfStr($this->text, ['start'=>'até às 24 horas do'],['sanitize'=>false]);
        $textVigFin = TextUtility::getPartOfStr($textVigFin, ['end'=>'Ramo']);
        $data['termino_vigencia'] = TextUtility::getSearchText($textVigFin,'24 horas do','datebr',['side'=>'right']);

         //Dados do Segurado
         $n = $this->getX1(['start'=>'Segurado:','remove'=>'Segurado:','cb'=>function($v){  $n=explode(':',$v); return $n[0];  }]);//Nome Segurado
         //dd($n);
         $data['segurado_nome'] = trim($n);//Nome Segurado

         $textDoc = TextUtility::getPartOfStr($this->text, ['start'=>'Segurado:'],['sanitize'=>false]);
         $textDoc = TextUtility::getPartOfStr($textDoc, ['end'=>'Endereço']);
         $n = TextUtility::getSearchText($textDoc,'Segurado:','document',['side'=>'right']);
         if($n==''){
             $textDoc = str_replace('CNPJ: 0', '', $textDoc);
             $n = TextUtility::getSearchText($textDoc,'Segurado:','document',['side'=>'right']);
         }
         $n = str_replace(['.','-','/'], '', $n);
         $data['segurado_doc'] = $n;//documento segurado

         if(strlen($n)==11){$n='FISICA';}else{$n='JURIDICA';}
         $data['tipo_pessoa'] = $n;//tipo segurado

        return ['success'=>true,'data'=>$data];
    }



    /**
     * Retorna os dados do premio
     */
    public function getPremio($data){
        //premio_liquido
        //premio_servico
        //premio_custo
        //premio_adicional
        //premio_iof???
        //premio_total

         //forma de pagamento
        //$this->getData_formaPgto(true,$data);
       PgtoData::getPgtoAuto(true,$data,$this->validate_premio_margem,['full_text'=>$this->text,'thisClass'=>$this]);


        //dd($data);
        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'dados de pagamento','end'=>'historico de pagamento','sanitize'=>true]);
        if($blocktext==''){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Prêmio Líquido Total:','end'=>'Cobrança:','sanitize'=>false]);
        }
        //dd($blocktext);
        $r=PgtoData::getFielsPremioAdd($blocktext,$data,['tem_juros'=>true]);
        if($r['fpgto_premio_liquido']==''){
             $r['fpgto_premio_liquido']= TextUtility::getSearchText($blocktext,'Prêmio Líquido Total:','number_formated',['side'=>'right']);
        }

        // dd($data);
        $data = $data + $r;

        return $data;
    }

    private static function clearText($text){
        $text =  preg_replace('/[^\d\-]/', '',$text);
        return $text;
    }

}
