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
trait libertyInsurer{

    //método de inicialização
    public function initInsurer(){
        $this->pdf_engine = 'ws02'; //nome do interpretador de pdf (valores em  \App\Utilities\FilesUtility::readPDF)
    }


    /**
     * Configuração para o padrão do número do quiver
     */
    function numQuiverConfig(){
        return [
            'ex_num'=>'3134207174',
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
        //dd($data['apolice_prod_ref']);
        if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];


         //dados do corretor
        $n = $this->getX1(['start'=>'Corretor CPF/CNPJ','return_type'=>'next']);//nome do corretor
        $n = explode(' ',$n);
        unset($n[ count($n)-1 ]);
        $n = join(' ',$n);

        if($n==''){
            $n = $this->getX1(['start'=>'Corretor CPF/CNPJ','remove'=>'Corretor CPF/CNPJ']);//nome do corretor
            $n = explode(' ',$n);
            unset($n[ count($n)-1 ]);
             $n = join(' ',$n);

        }


        //Corretor - Susep
        $n=$this->getX1(['start'=>'Cód SUSEP','remove'=>'Cód SUSEP Cód LibertyEstab.%PARTTelefone']);//neste caso pode retornar a uma sequencia númerica que já corresponde ao código susep
        if(!is_numeric($n))$n=$this->getX1(['start'=>'Cód SUSEP','return_type'=>'next','cb'=>function($v){ return explode(" ",$v)[0];  }]);
        $data['corretor_susep'] = $n;
        if(strlen($data['corretor_susep'])>9){
            $data['corretor_susep'] = substr($data['corretor_susep'],1);
        }
        //Corretor - Nome
        $data['corretor_nome'] = $n;


        //*** dados da apólice ***
        //$data['data_type'] = $this->getX1(['start'=>'DADOS DO ENDOSSO'])=='DADOS DO ENDOSSO' || $this->getX1(['start'=>'Endosso - Ramo'])=='Endosso - Ramo' ||$this->getX1(['start'=>'Endosso - Ramo'])=='Endosso - Ramo 31 Veiculos'? 'endosso' : 'apolice';
        // if($data['data_type']=='endosso')return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'endosso'];
        $n = $this->getX1(['start'=>'Apólice - Ramo']);
        if($n==''){
            $n = $this->getX1(['start'=>'Apólice -  Ramo']);
        }
        //dd(strpos( $this->text, 'Ramo 18 '));
       if(strpos( $this->text, 'Ramo 14')==false && strpos( $this->text, 'Ramo 18')==false){
            $n = str_replace(['Ramo 31 Veiculos','-'],'',$n);
            //dd(123);
            if($n==''){
                return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];
            }

            $data['data_type']=$n;

            if(strpos( $this->text, 'ITEM 002')!==false){
                return ['success'=>false,'data'=>[],'msg'=>'Apólice de frota','ignore'=>true,'code'=>'read04'];
            }

            $n = $this->getX1(['start'=>'ITEM 001']);
            if($n=='')return ['success'=>false,'data'=>[],'msg'=>'Apólice de frota','ignore'=>true,'code'=>'read04'];
            //dd($n);
        }else{

            if($n==''){
                return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];
            }
            $data['data_type']=$n;
        }
        $data['data_type'] = 'apolice';

        //cnpj seguradora

        $n=$this->getX1(['start'=>'Liberty Seguros S/A CNPJ','remove'=>'Liberty Seguros S/A CNPJ']);
        if(empty($n)){
            $n=$this->getX1(['start'=>'Liberty Seguros S/A - CNPJ','remove'=>'Liberty Seguros S/A - CNPJ']);
        }
        //dd($n);
        $n=explode(' ',$n)[0];

        if(empty($n)){
            $n = FormatUtility::sanitizeAllText($this->text);
            $n = FormatUtility::sanitizeText($n);
            $n = str_replace(' ','',$n);
            $n = TextUtility::getPartOfStr($n, ['start'=>'seguross/acnpj','sanitize'=>false]);
            $n = TextUtility::getPartOfStr($n, ['end'=>'-cod.susep','sanitize'=>false]);
            $n = TextUtility::getSearchText($n,'CNPJ','cnpj',['side'=>'right']);
            $n = str_replace('-cod.susep','',$n);
        }

        if(empty($n)){
            $n = FormatUtility::sanitizeAllText($this->text);
            $n = FormatUtility::sanitizeText($n);
            $n = str_replace(' ','',$n);
            $n = TextUtility::getPartOfStr($n, ['start'=>'seguross/acnpj','sanitize'=>false]);
            $n = TextUtility::getPartOfStr($n, ['end'=>'codigosusep','sanitize'=>false]);
            $n = TextUtility::getSearchText($n,'cnpj','cnpj',['side'=>'right']);
            $n = str_replace('codigosusep','',$n);
            //dd($n);
        }
        //dd($n);
        /*if(!$n){//faz isso para pegar o CNPJ na lateral do PDF
            $n = FormatUtility::sanitizeAllText($this->text);
            $n = str_replace(' ','',$n)FormatUtility::sanitizeAllText($this->text);
            dd($n);
            $n = TextUtility::getPartOfStr($n, ['start'=>'c n p j','end'=>'c od']);
            $n = str_replace(' ', '', $n);
            $n = substr($n, 4,18);
        }*/


        if(empty($n)){//não achou o texto na margem na vertical da página
            //procura pelo texto da filiam
            //$n=$this->getX1(['start'=>'DADOS DA FILIAL','end'=>'Endereço','xremove'=>'Liberty Seguros S/A CNPJ','split'=>false]);
            $n=TextUtility::getPartOfStr($this->text,['start'=>'DADOS DA FILIAL']);
            $n=TextUtility::getPartOfStr($n,['start'=>'CNPJ','split'=>chr(10),'return_type'=>'next']);
            $n=explode(' ',$n);
            foreach($n as $numbers){//percorre a array até encontrar o cpf ou cnpj
                if(ValidateUtility::isCNPJ($numbers) || ValidateUtility::isCPF($numbers)){
                    $n=$numbers;
                    break;
                }
            }
            if(is_array($n))$n=join(' ',$n);
        }
       // dd($n);
        $cnpj2=$this->getX1(['start'=>'Liberty Seguros S/A CNPJ','remove'=>'Liberty Seguros S/A CNPJ']);


        $data['seguradora_doc'] = TextUtility::fixCPFCNPJ($n);//CNPJ Seguradora

        //Numero da Proposta Seguradora
        $n=trim($this->getX1(['start'=>'proposta','return_type'=>'next']));
        $n=explode(' ',$n);
        $n=$n[count($n)-1];//pega o último índice
        $data['proposta_num'] = $n;

        //Número da Apólice na Seguradora
        $data['apolice_num'] = $this->getX1(['start'=>'DADOS DA APÓLICE','return_type'=>'next2','cb'=>function($v){ return explode(" ",$v)[0];  }]);

        if($data['apolice_num']==''){

            $data['apolice_num'] = TextUtility::getSearchText($this->text,'Apólice N°','value',['side'=>'right']);
        }
        //dd($data['apolice_num']);
        $n = $data['apolice_num'];

         //atualizado para ser capturado pela função numQuiverConfig()
        //$n = str_replace(['-','.'],['',''], $n);//Número da Apólice Quiver
        //$data['apolice_num_quiver'] =$n;//Número da Apólice Quiver
        $data['apolice_num'] = $n;//armazena somente o número

        //Data de Emissão
        $n=$this->getX1(['start'=>'Data de Emissão','return_type'=>'next','cb'=>function($v){  $n=explode(' ',$v); return $n[0];  }]);
        if(!ValidateUtility::isDate($n)){
            $n=$this->getX1(['start'=>'Data de Emissão','return_type'=>'next']);//formato esperado 'Das 24:00hs de 08/04/2020 às 24:00hs de 08/04/2021 "{dd/mm/aaaa}" 00001'    //data a captura dd/mm/aaaa
            $n=explode(' ',$n);
            $n=$n[count($n)-2];
        }
        $data['data_emissao'] = $n;

        $data['apolice_re_num'] = $this->getX1(['start'=>'Renovação da Apólice','return_type'=>'next','cb'=>function($v){  $n=explode(' ',$v); return $n[0];  }]);//Numero da apólice renovada$da
        $data['apolice_re_num'] = str_replace(['-','.'],['',''], $data['apolice_re_num']);
       // dd($data['apolice_re_num']);
        if($data['apolice_re_num']==""){
            $data['apolice_re_num']="";
        }else{
            if(ctype_alpha($data['apolice_re_num'][0])){ //verifica se o número da apólice é número ou texto
                $data['apolice_re_num']='';
            }
        }


        $data['inicio_vigencia'] = $this->getX1(['start'=>'Das 24:00hs de ','remove'=>'Das 24:00hs de ','cb'=>function($v){  $n=explode(' ',$v); return $n[0];  }]);//Data Início de vigência


        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Das 24:00hs de ','sanitize'=>true]);
        $n = TextUtility::getPartOfStr($blocktext, ['end'=>'Data de']);
        if(!$n)$n = TextUtility::getPartOfStr($blocktext, ['end'=>' as 24:00hs de','side_len'=>[0,7]]);//obs: side_len'=>[0,7] - pois captura somente o texto a direita da base encontrada (é '7' pois está dobrando a qtde de caracteres informados)
        $blocktext=$n;

        $n = TextUtility::getSearchText($blocktext,'','datebr',['limit'=>false]);

        $data['termino_vigencia'] = $n[1]??'';
       if($data['inicio_vigencia']==''){
           $data['inicio_vigencia'] = TextUtility::getSearchText($this->text,'Das 24 horas','datebr',['side'=>'right']);
           $data['termino_vigencia'] = TextUtility::getSearchText($this->text,'às 24 horas','datebr',['side'=>'right']);
       }

         //*** Dados do Segurado ***
        //Nome Segurado
        $n=$this->getX1(['start'=>'Nome do(a) Segurado(a)','end'=>'CPF/','return_type'=>'next','remove'=>'CPF/']);
        if($n==''){
            $n=$this->getX1(['start'=>'Segurado(a)','end'=>'CPF','return_type'=>'next','remove'=>'CPF']);
        }

        if(empty($n)){
            $textName = FormatUtility::sanitizeAllText($this->text);
            $n=TextUtility::getPartOfStr($textName, ['start'=>'nome do(a) segurado(a)','sanitize'=>false]);
            $n=TextUtility::getPartOfStr($n, ['end'=>'cpf','sanitize'=>false]);
            $n= str_replace(['nome do(a) segurado(a)','cpf'],'',$n);

        }

        $n= str_replace('CNPJ', '', $n);

        $n=explode(' ',$n);

        $r='';
        foreach($n as $word){//percorre a array e ignora os números nos nomes
            if(!is_numeric(str_replace('-','',$word)))$r.=$word.' ';
        }

        $data['segurado_nome'] = trim($r);

        if($data['segurado_nome']=='/'){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Ramo 14']);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'CPF']);
            $n=$this->getX1(['start'=>'Ramo 14','return_type'=>'next']);
            $n = str_replace('Nome do(a) Segurado(a) ', '', $n);
            $data['segurado_nome'] = trim($n);
        }
        //documento segurado
        $n= $this->getX1(['start'=>'CPF/CNPJ','return_type'=>'next']);

        if(!$n){
            $n= $this->getX1(['start'=>'CPFCNPJ','return_type'=>'next']);
            $n=explode(' ',$n);
            foreach($n as $number){//percorre a array até achar um número
                if(ValidateUtility::isDocument($number)){
                    $n=$number;break;
                }
            }
        }else{
           $n= explode(' ',$n)[0];
        }

       // dd($n);

        if(!ValidateUtility::isDocument($n)){//não é um cnpj o cnpj válido
            $n= $this->getX1(['start'=>'CPF/CNPJ','return_type'=>'next']);

            $n=explode(' ',$n);
            foreach($n as $number){//percorre a array até achar um número
                if(ValidateUtility::isDocument($number)){
                    $n=$number;break;
                }
            }
        }
        //remove do nome do segurado o documento, pois algumas vezes vem junto
        $data['segurado_nome'] = str_replace($n,'',$data['segurado_nome']);
        $n = str_replace(['-','.','/'],['','',''], $n);
        $data['segurado_doc'] = $n;
        //dd($n);

        //tipo pessoa
        $n = $data['segurado_doc'];
        //dd($n);

        $n= strlen($n);
        if($n==11){$n='FISICA';}else{$n='JURIDICA';}
        $data['tipo_pessoa'] = $n;//tipo segurado

        return ['success'=>true,'data'=>$data];
    }


    /**
     * Retorna os dados do premio
     */
    public function getPremio($data){
        //forma de pagamento
        //$this->getData_formaPgto(true,$data);
        $text = TextUtility::getPartOfStr($this->text, ['start'=>'DEMONSTRATIVO','end'=>'ATENÇÃO:']);
        $text = str_replace('Nº', chr(10).'Nº', $text);
        //dd($text);
        PgtoData::getPgtoAuto($text,$data,$this->validate_premio_margem,['full_text'=>$this->text,'thisClass'=>$this]);

        //verifica se existe o prêmio total a partir da soma das parcelas
        $n=PgtoData::getArrayFromData($data);
        //dd($n);
        $text = TextUtility::getPartOfStr($this->text, ['start'=>'Prêmio Total','end'=>'FORMA DE']);
        $data['fpgto_premio_total'] = PgtoData::getPremioTotalParcela($text,$n['valor'],$this->validate_premio_margem);//captura o valor do prêmio exatamente como consta no texto, procurando a partir da soma das parcelas


        $n= PgtoData::addFields1($data);
        $data = $data + $n ;


        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'demonstrativo de','end'=>'forma de pagamento','sanitize'=>true]);
        $blocktext= str_replace('bquido', ' premio liquido', $blocktext);

        $n = TextUtility::getSearchText($blocktext,'(r$)','value',['side'=>'right']);

        //dd($blocktext);

        if($n=='adic'){
            $r = TextUtility::getSearchTextInColumns(
                $blocktext,
                'premio liquido (r$) adic. frac. (r$) custo apolice (r$) iof (r$) premio total (r$) juros (%)',
                ['fpgto_premio_liquido','fpgto_adicional','fpgto_custo','fpgto_iof','premio_total','fpgto_juros']
            );
           $data['fpgto_premio_liq_serv'] = '0,00';
            if($r)unset($r['premio_total']);

            //adiciona o campo de juros melhor data para constar na matriz $r (mas este campo não existe na apólice)
            $r['fpgto_juros_md']='0,00';

        }else{
            //dd(123);
            $r=PgtoData::getFielsPremioAdd($blocktext,$data,['tem_juros'=>false]);
        }
        //dd($r);


        $data = $data + $r;
        $data['fpgto_juros'] = '0,00';

        return $data;
    }

    private static function clearText($text){
        $text =  preg_replace('/[^\d\-]/', '',$text);
        return $text;
    }

}
