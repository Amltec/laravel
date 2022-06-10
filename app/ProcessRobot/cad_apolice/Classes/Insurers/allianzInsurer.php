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
trait allianzInsurer{

    //método de inicialização
    public function initInsurer(){
        $this->pdf_engine = 'ws02'; //nome do interpretador de pdf (valores em  \App\Utilities\FilesUtility::readPDF)

        //relação de campos obrigatórios ou não
        $this->validate_required['veiculo_ci']=false;
        $this->validate_required['veiculo_cod_fipe']=false;
        $this->validate_required['segurado_pernoite_cep']=false;
    }

    /**
     * Configuração para o padrão do número do quiver
     */
    function numQuiverConfig(){
        return [
            'ex_num'=>'5177202179312041844',
            'len'=>12,
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

        //captura o tipo da apólice - para verificar se é do tipo automovel
        $data['apolice_prod_ref'] = $this->checkRamo();

        if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];

        if(strpos($this->text,'Sua frota está protegida')!==false){
            return ['success'=>false,'msg'=>'Ignorado apólice do tipo frota','data'=>[],'ignore'=>true,'code'=>'read04'];
        }

        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Proposta:','sanitize'=>true]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'informacoes']);

        if(strpos($blocktext,'esta e a proposta')!==false){
            return ['success'=>false,'data'=>[],'msg'=>'É uma proposta - não processado','ignore'=>true,'code'=>'read03'];
        }

        $tmp = TextUtility::getSearchText($this->text,' Endosso:','number',['side'=>'right']);
         // dd($tmp);
         if($tmp>=1){

             return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];
         }

        if(stripos($this->text,'RESUMO DO SEU SEGURO')!==false){//achou o texto
            return ['success'=>false,'data'=>[],'msg'=>'É resumo da apólice','ignore'=>true,'code'=>'read14'];
        }

        //dados do corretor
        $n = $this->getX1(['start'=>'CORRETORA','return_type'=>'next']);//nome do corretor
        $data['corretor_nome'] = trim($n);//nome do corretor

        $n = $this->getX1(['start'=>'SUSEP:','remove'=>'SUSEP:']);//susep do corretor
        $n = explode(' ', $n);
        $n = $n[0];
        $data['corretor_susep'] = trim($n);//susep do corretor


        //dados da apólice
         $data['data_type'] = $this->getX1(['start'=>'Apólice de Seguro'])=='Endosso' ? 'endosso' : 'apolice';

         $n = $this->getX1(['start'=>'SAC 24','return_type'=>'next','cb'=>function($v){ return explode(" ",$v)[1];  }]);//CNPJ Seguradora
         $n = substr($n,0);
         //$n = str_replace(['-','.','/'], ['','',''], $n);
         if(strlen($n)==19){
                $n=substr($n, 1);
         }
         $data['seguradora_doc'] = $n;//CNPJ Seguradora
         //dd( $data['seguradora_doc']);

         $n = $this->getX1(['start'=>'Proposta:','cb'=>function($v){ return explode(":",$v)[1];  }]);//Numero da Proposta Seguradora
         $n = trim($n);
         $data['proposta_num'] = $n;//Numero da Proposta Seguradora

         $n = $this->getX1(['start'=>'Apólice:','cb'=>function($v){ return explode(":",$v)[1];  }]);//Número da Apólice na Seguradora

         $data['apolice_num'] = trim($n);//Número da Apólice na Seguradora
        // $data['apolice_num'] = str_replace(['V','Q','A'], '',  $data['apolice_num']);
         $n = substr($data['apolice_num'], 12);
        // dd(ltrim($n,0),$data['apolice_num']);

         //atualizado para ser capturado pela função numQuiverConfig()
         //$data['apolice_num_quiver'] =ltrim($n,0) ;//Número da Apólice Quiver

         $data['data_emissao'] =trim($this->getX1(['start'=>'EMISSÃO:','remove'=>'EMISSÃO:']));//Data de Emissão

         $n = $this->getX1(['start'=>'ANTERIOR:']);//Numero da apólice renovada
         if(strpos($n,'ANTERIOR:')!==false){
             $n = $this->getX1(['start'=>'ANTERIOR:','remove'=>'ANTERIOR:','cb'=>function($v){ return explode(" ",$v)[0];  }]);//Numero da apólice renovada
             $n = str_replace(['-','.','/'], [''], $n);
            $data['apolice_re_num'] = $n;

         }else{
            $data['apolice_re_num']='';
         }


        $data['inicio_vigencia'] = $this->getX1(['start'=>'24H de ','remove'=>'24H de ','cb'=>function($v){  $n=explode(' ',$v); return $n[0];  }]);//Data Início de vigência

        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'das 24h de','sanitize'=>true]);
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

         //Dados do Segurado


         $n = $this->getX1(['start'=>'SEGURADO:','remove'=>'SEGURADO:','cb'=>function($v){  $n=explode(':',$v); return $n[0];  }]);//Nome Segurado

         if(!$n){
             $n = $this->getX1(['start'=>'PROPONENTE:','remove'=>'PROPONENTE:','cb'=>function($v){  $n=explode(':',$v); return $n[0];  }]);//Nome Segurado
         }

         $data['segurado_nome'] = trim($n);//Nome Segurado
         $data['segurado_nome'] = str_replace('CPF','',$data['segurado_nome']);
        // dd($n);
/*
         $n= $this->getX1(['start'=>'ENDEREÇO:','cb'=>function($v){  $n=explode(':',$v); return $n[2];  }]);
         $n=str_replace(['-','.','/'], ['','',''], $n);

         $data['segurado_doc'] = trim($n);//documento segurado
        */


         $doc_segurado = TextUtility::getPartOfStr($this->text, ['start'=>'Segurado:'] );
         $doc_segurado = TextUtility::getPartOfStr($doc_segurado, ['end'=>'CEP:']);

         if(!$doc_segurado){
             $doc_segurado = TextUtility::getPartOfStr($this->text, ['start'=>'PROPONENTE:'] );
             $doc_segurado = TextUtility::getPartOfStr($doc_segurado, ['end'=>'E-MAIL:']);
         }

         //dd($doc_segurado);

         if(strpos($doc_segurado, 'CPF')!==false){
            $n=TextUtility::execFncInStr($doc_segurado,14,function($v){
                if(ValidateUtility::isCPF($v))return true;
            });
           // dd($n[0]);
            $data['segurado_doc'] = trim($n[0]);//documento segurado
            $data['tipo_pessoa'] = 'FISICA';//tipo segurado
            //dd($n);
         }elseif (strpos($doc_segurado, 'CNPJ')!==false) {

            $n=TextUtility::execFncInStr($doc_segurado,18,function($v){
                if(ValidateUtility::isCNPJ($v))return true;
            });
            //dd($n,$doc_segurado);
            $data['segurado_doc'] = trim($n[0]);//documento segurado
            $data['tipo_pessoa'] = 'JURIDICA';//tipo segurado
            //dd($doc_segurado);
         }


        return ['success'=>true,'data'=>$data];
    }


    /**
     * Retorna os dados do premio
     */
    public function getPremio1($data){
        //premio_liquido
        //premio_servico
        //premio_custo
        //premio_adicional
        //premio_iof???
        //premio_total

        //forma de pagamento
         $blocktext = substr(FormatUtility::sanitizeAllText( $this->getX1(['start'=>'INFORMAÇÕES DE PAGAMENTO', 'split'=>false]) ),0,100);
         $pgto_tipo = PgtoData::getPgtoTipo($blocktext);

         $blocktext = FormatUtility::sanitizeAllText( $this->getX1(['start'=>'INFORMAÇÕES DE PAGAMENTO', 'split'=>false]) );

        // dd($pgto_tipo,$blocktext);
         if($pgto_tipo=='cartao'){
             $data['fpgto_tipo']=$pgto_tipo;
             $data['fpgto_tipo_code']= PgtoData::getPgtoCode($pgto_tipo)[1];

             //pega as parcelas, vencimentos e prêmio
             $blocktext2 = FormatUtility::sanitizeText( TextUtility::getPartOfStr($blocktext, ['start'=>'Parc.','end'=>'Total']) );
             if($blocktext2==''){//usado no residencial
                 $blocktext2 = FormatUtility::sanitizeText( TextUtility::getPartOfStr($blocktext, ['start'=>'valor da parcela','end'=>'clausulas']) );
             }
             $blocktext2 = trim(str_replace(['parc. vencimento valor','a vista','fatura cartao','total'], '', $blocktext2));

             if(strpos($blocktext2,'pagina')!=false){
                $blocktext2 = FormatUtility::sanitizeText( TextUtility::getPartOfStr($blocktext2, ['start'=>'Parc','end'=>'pagina']) );
                //dd($blocktext2);
             }

             $num_parc=[];
             $valor_parc=[];
             $nx=array_map('trim',explode(' ',trim($blocktext2)));
             foreach($nx as $v){

                //armazena todos os valores encontrados
                if(is_numeric($v)){//é um número inteiro
                    $num_parc[]=$v;
                }

                if(TextUtility::isNumberFormated($v)){//é um número formatado
                    $valor_parc[]=$v;
                }
            }
            //dd($num_parc,$valor_parc);
            array_multisort($num_parc,$valor_parc);
            $text_parc='';
            for($i=0;$i<count($num_parc);$i++){
               $text_parc = $text_parc.' '.$num_parc[$i].' '.$valor_parc[$i].' ';
            }

            // $r = $this->getData_formaPgto_tableVencParc_noDate($blocktext2,$data['inicio_vigencia'],null,'auto');
            //dd($this->getDate1aParc('cartao',$data));
             $r = PgtoData::getTableVencParc_noDate($text_parc,$this->getDate1aParc('cartao',$data),null,'auto',$this->text,false,$this->validate_premio_margem);
             if($r)$data = $data + $r;

             $data = PgtoData::addFields1($data);
         }else{
             //dd($blocktext);
             $r= PgtoData::getPgtoAuto($blocktext,$data,$this->validate_premio_margem,[
                 'pgto_tipo'=>$pgto_tipo,
                 'full_text'=>$this->text
             ]);
            // dd('**1',$r,$data);
         }
        // dd($data);
        //dd($data['apolice_prod_ref']);

        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'informacoes de pagamento','end'=>'parc. vencimento','sanitize'=>true]);
        if($blocktext=='' or $data['apolice_prod_ref']=='Ramo:14'){
            //dd($this->text);
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'INFORMAÇÕES DE PAGAMENTO','end'=>'Parcelas Vencimento','sanitize'=>false]);
            $blocktext = str_replace(['Preço líquido:','preco liquido:'],'Premio líquido:', $blocktext);

        }
        //dd($blocktext);
        $r=PgtoData::getFielsPremioAdd($blocktext,$data,['tem_juros'=>true]);
        if($r['fpgto_premio_liquido']==''){//usado no residencial
            $r['fpgto_premio_liquido']= TextUtility::getSearchText($blocktext,'Premio líquido:','number_formated',['side'=>'right']);//Premio Liquido
        }
        // dd($data);
        $data = $data + $r;

        return $data;
    }

    public function getDados2(){
        $data=[];
        //dd($this->text);

        //captura o tipo da apólice - para verificar se é do tipo automovel
        $data['apolice_prod_ref'] = $this->checkRamo();
        if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'not_product'];

        if(strpos($this->text,'Allianz Auto Frota')!==false || strpos($this->text,'allianz.com.br/frota')!==false){
            return ['success'=>false,'msg'=>'Ignorado apólice do tipo frota','data'=>[],'ignore'=>true,'code'=>'read04'];
        }

        //dados do corretor
        $n = $this->getX1(['start'=>'Telefone:','return_type'=>'prev']);//nome do corretor
        $data['corretor_nome'] = trim($n);//nome do corretor


        $n = $this->getX1(['start'=>'SUSEP:','remove'=>'SUSEP:']);//susep do corretor
        $n = explode(' ', $n);
        $n = $n[0];
        $data['corretor_susep'] = trim($n);//susep do corretor


        //dados da apólice
         $data['data_type'] = $this->getX1(['start'=>'Apólice de Seguro'])=='Endosso' ? 'endosso' : 'apolice';

         $n = $this->getX1(['start'=>'Processo SUSEP','return_type'=>'prev3','cb'=>function($v){ return explode(" ",$v)[1]??'';  }]);//CNPJ Seguradora
        // dd($n);
         $n = substr($n,0);
         $n = str_replace(['-','.','/'], ['','',''], $n);
         if(strlen($n)==19){
                $n=substr($n, 1);
         }
         $data['seguradora_doc'] = $n;//CNPJ Seguradora

         $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'sac 24 cnpj:','sanitize'=>true]);
         $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'ie:']);

         $n = TextUtility::getSearchText($blocktext,'cnpj:','value',['side'=>'right']);
         $n = str_replace(['-','.','/'], ['','',''], $n);
         if(strlen($n)==15){
                $n=substr($n, 1);
         }

         $data['seguradora_doc'] = $n;//CNPJ Seguradora
         //dd( $blocktext,$data['seguradora_doc']);

         $n = $this->getX1(['start'=>'Proposta:','cb'=>function($v){ return explode(":",$v)[1]??'';  }]);//Numero da Proposta Seguradora
         $n = trim($n);
         $data['proposta_num'] = $n;//Numero da Proposta Seguradora

         $n = $this->getX1(['start'=>'Nº Apólice:','return_type'=>'next']);//Número da Apólice na Seguradora


         $data['apolice_num'] = trim($n);//Número da Apólice na Seguradora
         //$data['apolice_num'] = str_replace('V', '',  $data['apolice_num']);
         //atualizado para ser capturado pela função numQuiverConfig()
         //$n = substr($data['apolice_num'], 12);
        // dd(ltrim($n,0),$data['apolice_num']);
         //$data['apolice_num_quiver'] =ltrim($n,0) ;//Número da Apólice Quiver

         $data['data_emissao'] =trim($this->getX1(['start'=>'Emissão:','remove'=>'Emissão:']));//Data de Emissão

         $n = $this->getX1(['start'=>'ANTERIOR:']);//Numero da apólice renovada
         if(strpos($n,'ANTERIOR:')!==false){
            $data['apolice_re_num'] = $this->getX1(['start'=>'ANTERIOR:','remove'=>'ANTERIOR:','cb'=>function($v){ return explode(" ",$v)[0];  }]);//Numero da apólice renovada
         }else{
            $data['apolice_re_num']='';
         }


        $data['inicio_vigencia'] = $this->getX1(['start'=>'24H de ','remove'=>'24H de ','cb'=>function($v){  $n=explode(' ',$v); return $n[0];  }]);//Data Início de vigência

        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'das 24h de','sanitize'=>true]);
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


         //Dados do Segurado
         $n = $this->getX1(['start'=>'SEGURADO:','remove'=>'SEGURADO:','cb'=>function($v){  $n=explode(':',$v); return $n[0];  }]);//Nome Segurado
         $data['segurado_nome'] = trim($n);//Nome Segurado
         $data['segurado_nome'] = trim(str_replace('CPF/CNPJ', '',$data['segurado_nome']));

         $n= $this->getX1(['start'=>'Segurado:']);
         if(strpos($n,'CPF')!==false){
            $n = substr($n,-14);
         }else{
            $n = substr($n,-19);
         }
         //dd($n);
         $n=str_replace(['-','.','/'], ['','',''], $n);

         $data['segurado_doc'] = trim($n);//documento segurado


         $n = $this->getX1(['start'=>'ENDEREÇO:']);
         if(!$n){//apólice de aminhão ex: id 5802
             $n = $this->getX1(['start'=>'Segurado:']);
         }

         if(strpos($n,'CPF')!==false){$n='FISICA';}else{$n='JURIDICA';}
         $data['tipo_pessoa'] = $n;//tipo segurado

        return ['success'=>true,'data'=>$data];
    }


    /**
     * Retorna os dados do premio
     */
    public function getPremio2($data){
        //premio_liquido
        //premio_servico
        //premio_custo
        //premio_adicional
        //premio_iof???
        //premio_total
        //Forma de Pagamento
         $blocktext = FormatUtility::sanitizeAllText( $this->getX1(['start'=>'INFORMAÇÕES DE PAGAMENTO', 'split'=>false]) );

         $apolice_caminhao = 'nao';
         if(!$blocktext){//apólice de aminhão ex: id 5802
             $blocktext = FormatUtility::sanitizeAllText( $this->getX1(['start'=>'Demonstração do Prêmio', 'split'=>false]) );
             $apolice_caminhao = 'sim';
         }

         $pgto_tipo = PgtoData::getPgtoTipo($blocktext);
         if($pgto_tipo==false){
               if(strpos($blocktext, 'debito')!==false){
                 $pgto_tipo='debito';
               }
         }
         $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'dados do produ']);


         if($pgto_tipo=='cartao' || $apolice_caminhao=='sim'){
             $data['fpgto_tipo']=$pgto_tipo;
             $data['fpgto_tipo_code']= PgtoData::getPgtoCode($pgto_tipo)[1];

             //pega as parcelas, vencimentos e prêmio

             $blocktext2 = FormatUtility::sanitizeAllText( TextUtility::getPartOfStr($blocktext, ['start'=>'vencimento valor','end'=>'dados do prod']) );
             $r = PgtoData::getTableVencParc($blocktext);

             if(empty($r)){
                $blocktext2 = FormatUtility::sanitizeAllText( TextUtility::getPartOfStr($blocktext, ['start'=>'Parc.','end'=>'Total']) );
                $r = PgtoData::getTableVencParc_noDate($blocktext2,$this->getDate1aParc($data['fpgto_tipo'],$data),null,'auto',$this->text,false,$this->validate_premio_margem);
             }

             if(empty($r)){
                  $blocktext2 = FormatUtility::sanitizeAllText( TextUtility::getPartOfStr($blocktext, ['start'=>'Parc.','end'=>'dados do prod']) );

                  $r = PgtoData::getTableVencParc_noDate($blocktext2,$this->getDate1aParc($data['fpgto_tipo'],$data),null,'auto',$this->text,false,$this->validate_premio_margem);
             }


             if($r)$data = $data + $r;

             $data = PgtoData::addFields1($data);

         }else{
             PgtoData::getPgtoAuto(true,$data,$this->validate_premio_margem,['pgto_tipo'=>$pgto_tipo]);
         }

         $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'demonstracao do premio','end'=>'dados do produto','sanitize'=>true]);
         if($blocktext==''){
             $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Demonstração do Prêmio','end'=>'Cláusulas','sanitize'=>false]);
             $blocktext = str_replace('(R$):', 'R$', $blocktext);
         }

        $r=PgtoData::getFielsPremioAdd($blocktext,$data,['tem_juros'=>true]);

        if($r['fpgto_premio_liquido']==''){
            $r['fpgto_premio_liquido'] = TextUtility::getSearchText($blocktext,'Prêmio líquido','number_formated',['side'=>'right']);
        }

        if(empty($data['fpgto_premio_total'])){
            $data['fpgto_premio_total'] = PgtoData::getPremioTotal($blocktext);

        }

        $data = $data + $r;
        //dd($data,$blocktext,$this->text);
        return $data;
    }

    /**
     * Retorna os dados do segurado
     * @return error:   [success,msg,data,ignore,code]
     *         ok:      [sucess,data]
     */
    public function getDados3(){
        $data=[];

        //captura o tipo da apólice - para verificar se é do tipo automovel
        $data['apolice_prod_ref'] = $this->checkRamo();

        if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];

        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Proposta:','sanitize'=>true]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'informacoes']);

        if(strpos($blocktext,'esta e a proposta')!==false){
            return ['success'=>false,'data'=>[],'msg'=>'É uma proposta - não processado','ignore'=>true,'code'=>'read03'];
        }

        $tmp = TextUtility::getSearchText($this->text,' Endosso:','number',['side'=>'right']);
         // dd($tmp);
         if($tmp>=1){

             return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];
         }

        if(stripos($this->text,'RESUMO DO SEU SEGURO')!==false){//achou o texto
            return ['success'=>false,'data'=>[],'msg'=>'É resumo da apólice','ignore'=>true,'code'=>'read14'];
        }

        //dados do corretor
        $n = $this->getX1(['start'=>'CORRETOR','return_type'=>'next']);//nome do corretor
        $data['corretor_nome'] = trim($n);//nome do corretor

        $n = TextUtility::getSearchText($this->text,'SUSEP Nº:','number',['side'=>'right']);//susep do corretor
        $data['corretor_susep'] = trim($n);//susep do corretor

        //dados da apólice
         $data['data_type'] = $this->getX1(['start'=>'Apólice de Seguro'])=='Endosso' ? 'endosso' : 'apolice';

         $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Allianz Seguros S.A.: Código:','sanitize'=>false]);
         $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'IE:']);
         $blocktext = str_replace('CNPJ: 061', 'CNPJ: 61', $blocktext);
         //dd($blocktext);
         $n = TextUtility::getSearchText($blocktext,'Allianz Seguros S.A.: Código:','cnpj',['side'=>'right']);//CNPJ Seguradora
         $data['seguradora_doc'] = $n;//CNPJ Seguradora
         //dd( $data['seguradora_doc']);

         $data['proposta_num'] = TextUtility::getSearchText($this->text,'Proposta Nº:','number',['side'=>'right']);//Numero da Proposta Seguradora

         $n = $this->getX1(['start'=>'Apólice:','cb'=>function($v){ return explode(":",$v)[1];  }]);//Número da Apólice na Seguradora
         //dd($n);
         //$n = str_replace('V','',$n);
         $data['apolice_num'] = trim($n);//Número da Apólice na Seguradora
         //dd($data['apolice_num']);
        //atualizado para ser capturado pela função numQuiverConfig()
        // $n = substr($data['apolice_num'], 12);
        // $data['apolice_num_quiver'] =ltrim($n,0) ;//Número da Apólice Quiver
         $data['data_emissao'] =TextUtility::getSearchText($this->text,'Data de emissão:','datebr',['side'=>'right']);//Data de Emissão
         $n = $this->getX1(['start'=>'ANTERIOR:']);//Numero da apólice renovada
         if(strpos($n,'ANTERIOR:')!==false){
             $n = $this->getX1(['start'=>'ANTERIOR:','remove'=>'ANTERIOR:','cb'=>function($v){ return explode(" ",$v)[0];  }]);//Numero da apólice renovada
             $n = str_replace(['-','.','/'], [''], $n);
            $data['apolice_re_num'] = $n;

         }else{
            $data['apolice_re_num']='';
         }
        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Vigência:','sanitize'=>false]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'LMGA:']);
        $data['inicio_vigencia'] = TextUtility::getSearchText($blocktext,'das 24H','datebr',['side'=>'right']);//Data Início de vigência
        $data['termino_vigencia'] = TextUtility::getSearchText($blocktext,'às 24H de','datebr',['side'=>'right']);

         //Dados do Segurado
         $n = $this->getX1(['start'=>'Nome:','remove'=>'Nome:','cb'=>function($v){  $n=explode(':',$v); return $n[0];  }]);//Nome Segurado

         if(!$n){
             $n = $this->getX1(['start'=>'Proponente:','remove'=>'Proponente:','cb'=>function($v){  $n=explode(':',$v); return $n[0];  }]);//Nome Segurado
         }
         $data['segurado_nome'] = trim($n);//Nome Segurado
        // dd($n);

         $doc_segurado = TextUtility::getPartOfStr($this->text, ['start'=>'CPF/CNPJ:'] );
         $doc_segurado = TextUtility::getPartOfStr($doc_segurado, ['end'=>'Tel:']);
        $n = TextUtility::getSearchText($doc_segurado,'CPF/CNPJ:','cpf');
        if(!$n)$n = TextUtility::getSearchText($doc_segurado,'CPF/CNPJ:','cnpj');

        $data['segurado_doc'] = $n;
        $data['tipo_pessoa'] = ValidateUtility::isCPF($n) ? 'FISICA' : (ValidateUtility::isCNPJ($n)?'JURIDICA':'');

        return ['success'=>true,'data'=>$data];
    }


    public function getPremio3($data){// usado no empresarial


        //Forma de Pagamento
         $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Demonstração do Prêmio','sanitize'=>false]);
         $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Cláusulas']);

         $pgto_tipo = PgtoData::getPgtoTipo($blocktext);
         if($pgto_tipo==false){
               if(strpos($blocktext, 'debito')!==false){
                 $pgto_tipo='debito';
               }
         }

         if($pgto_tipo=='cartao'){

             $data['fpgto_tipo']=$pgto_tipo;
             $data['fpgto_tipo_code']= PgtoData::getPgtoCode($pgto_tipo)[1];

             //pega as parcelas, vencimentos e prêmio

             $blocktext2 = FormatUtility::sanitizeAllText( TextUtility::getPartOfStr($blocktext, ['start'=>'vencimento valor','end'=>'dados do prod']) );
             $r = PgtoData::getTableVencParc($blocktext);
             //dd($r);
             if(empty($r)){
                $blocktext2 = FormatUtility::sanitizeAllText( TextUtility::getPartOfStr($blocktext, ['start'=>'Parc.','end'=>'Total']) );

                $r = PgtoData::getTableVencParc_noDate($blocktext2,$this->getDate1aParc($data['fpgto_tipo'],$data),null,'auto',$this->text,false,$this->validate_premio_margem);
             }
             if(empty($r)){
                  $blocktext2 = FormatUtility::sanitizeAllText( TextUtility::getPartOfStr($blocktext, ['start'=>'Parc.','end'=>'dados do produto']) );
                  $r = PgtoData::getTableVencParc_noDate($blocktext2,$this->getDate1aParc($data['fpgto_tipo'],$data),null,'auto',$this->text,false,$this->validate_premio_margem);
             }

             if($r)$data = $data + $r;

             $data = PgtoData::addFields1($data);

         }else{
             $data['fpgto_tipo']=$pgto_tipo;
             $data['fpgto_tipo_code']= PgtoData::getPgtoCode($pgto_tipo)[1];

             $blocktext2 = TextUtility::getPartOfStr($blocktext, ['start'=>'Parc.','sanitize'=>false]);
             $blocktext2 = TextUtility::getPartOfStr($blocktext2, ['end'=>'Cláusulas']);
             //$r = PgtoData::getPgtoAuto($blocktext2,$data,$this->validate_premio_margem,['pgto_tipo'=>$pgto_tipo]);
             $r = PgtoData::getTableVencParc($blocktext2);
             if($r)$data = $data + $r;

             $data = PgtoData::addFields1($data);
              //dd($r,$blocktext2);
         }

         $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'demonstracao do premio','end'=>'dados do produto','sanitize'=>true]);
         if($blocktext==''){
             $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Demonstração do Prêmio','end'=>'Cláusulas','sanitize'=>false]);
             $blocktext = str_replace('(R$):', 'R$', $blocktext);
         }
        if($blocktext==''){
             $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Demonstração do Prêmio','sanitize'=>false]);
             $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Comp','sanitize'=>false]);
             $blocktext = str_replace('(R$):', 'R$', $blocktext);
         }

        $r=PgtoData::getFielsPremioAdd($blocktext,$data,['tem_juros'=>true]);

        if($r['fpgto_premio_liquido']==''){
            $r['fpgto_premio_liquido'] = TextUtility::getSearchText($blocktext,'Prêmio líquido','number_formated',['side'=>'right']);
        }

        if(empty($data['fpgto_premio_total'])){
            $data['fpgto_premio_total'] = PgtoData::getPremioTotal($blocktext);

        }

        $data = $data + $r;
       // dd($data,$blocktext,$this->text);
        return $data;
    }


    /**
     * Retorna os dados do premio
     */
    public function getPremio4($data){ // layout 2022 com dados do veículo no bloco "Texto Complementar"
        //premio_liquido
        //premio_servico
        //premio_custo
        //premio_adicional
        //premio_iof???
        //premio_total
        //Forma de Pagamento
         $blocktext = FormatUtility::sanitizeAllText( $this->getX1(['start'=>'Demonstração do Prêmio', 'split'=>false]) );
         $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'proposta:']);
         $pgto_tipo = PgtoData::getPgtoTipo($blocktext);

         if($pgto_tipo=='cartao' ){
             $data['fpgto_tipo']=$pgto_tipo;
             $data['fpgto_tipo_code']= PgtoData::getPgtoCode($pgto_tipo)[1];

             //pega as parcelas, vencimentos e prêmio

             $blocktext2 = FormatUtility::sanitizeAllText( TextUtility::getPartOfStr($blocktext, ['start'=>'vencimento valor','end'=>'dados do prod']) );
             $r = PgtoData::getTableVencParc($blocktext);

             if(empty($r)){
                $blocktext2 = FormatUtility::sanitizeAllText( TextUtility::getPartOfStr($blocktext, ['start'=>'Parc.','end'=>'Total']) );
                $r = PgtoData::getTableVencParc_noDate($blocktext2,$this->getDate1aParc($data['fpgto_tipo'],$data),null,'auto',$this->text,false,$this->validate_premio_margem);
             }

             if(empty($r)){
                  $blocktext2 = FormatUtility::sanitizeAllText( TextUtility::getPartOfStr($blocktext, ['start'=>'Parc.','end'=>'dados do prod']) );

                  $r = PgtoData::getTableVencParc_noDate($blocktext2,$this->getDate1aParc($data['fpgto_tipo'],$data),null,'auto',$this->text,false,$this->validate_premio_margem);
             }


             if($r)$data = $data + $r;

             $data = PgtoData::addFields1($data);

         }else{
            $data['fpgto_tipo']=$pgto_tipo;
            $data['fpgto_tipo_code']= PgtoData::getPgtoCode($pgto_tipo)[1];
             PgtoData::getPgtoAuto(true,$data,$this->validate_premio_margem,['pgto_tipo'=>$pgto_tipo]);
         }

         $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'demonstracao do premio','end'=>'proposta:','sanitize'=>true]);
         if($blocktext==''){
             $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Demonstração do Prêmio','end'=>'Cláusulas','sanitize'=>false]);
             $blocktext = str_replace('(R$):', 'R$', $blocktext);

         }

        $r=PgtoData::getFielsPremioAdd($blocktext,$data,['tem_juros'=>true]);

        if($r['fpgto_premio_liquido']==''){
            $r['fpgto_premio_liquido'] = TextUtility::getSearchText($blocktext,'Prêmio líquido','number_formated',['side'=>'right']);
        }

        if(empty($data['fpgto_premio_total'])){
            $data['fpgto_premio_total'] = PgtoData::getPremioTotal($blocktext);

        }
        $data = $data + $r;

        $n =  PgtoData::getTableVencParc($blocktext);
        $data = $data + $n;
        $data = PgtoData::addFields1($data);
        //dd($n , $data);

        return $data;
    }

    private static function clearText($text){
        $text =  preg_replace('/[^\d\-]/', '',$text);
        return $text;
    }

}
