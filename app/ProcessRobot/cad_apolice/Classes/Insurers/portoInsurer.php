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
trait portoInsurer{

    //método de inicialização
    public function initInsurer(){

        $this->pdf_engine = 'ait_tessrct'; //nome do interpretador de pdf (valores em  \App\Utilities\FilesUtility::readPDF)

        $this->text_no_space='';

        //Campos obrigatórios adicionais para personalização na classe filha (obs: neste caso segue as regras existentes no validate da classe filha, ex ProcessAutomovelClass.php)
        $this->validate_required = ['segurado_pernoite_cep'=>false,'veiculo_cod_fipe'=>false];//sintaxe field=>boolean

        //margem de diferença de centavos entre o cálculo do iof com o iof capturado em $data para o PgtoData::validateAll(). Obs: por padrão deveria ser igual, mas na prática existe diferença de centávos entre as apólices.
        $this->validate_iof_margem=0.06;

        //define se os dados terão uma validação extra de compração de valores por outro método
        $this->extra_validate_data_values=['engine'=>'ws02','fields'=>'veiculo_chassi_1','veiculo_ci_1'];

        //limite de caracteres considerados para a extração do texto
        $this->limite_text = 30000;
    }


    /**
     * Configuração para o padrão do número do quiver
     */
    function numQuiverConfig(){
        return [
            'ex_num'=>'05310617961430',
            'last_dot'=>true,
            'len'=>'6',
            'not_zero_left'=>true
        ];
    }


     public function process($text,$opt=[]){
        $this->process_opt = $opt;
        $this->splitThisText($text);

        $this->text = $this->limitText($text);

        //junta os textos da var $text para que não quebre de modo estranho
        $this->text_no_space = str_replace([chr(10),chr(13)],'',$text);  //TextUtility::getPartOfStr($this->text, ['start'=>'Forma de Pagamento'] );/


        if($this->pdf_engine!='ws02'){
            //verifica se já existe senha no arquivo
            $pass = $this->process_model->getData('file_pass');

            try{
                $this->text_ws02 = \App\Utilities\FilesUtility::readPDF($opt['path'],['engine'=>'ws02','pass'=>$pass])['text'];
            }catch(\Exception $e){
                $m=(string)$e;
                if(strpos($m,'You do not have permission to extract text')!==false){//arquivo protegido contra leitura, novo padrão //utiliza apenas o ocr
                    //nenhuma ação
                    $this->text_ws02='padrao2';
                }
            }
        }

        if($this->text_ws02=='padrao2'){

            $r = $this->processTipo02();
        }else{

            $r = $this->processTipo01();
        }

        if(stripos($this->text,'Caminhoes leves')!==false || stripos($this->text,'Caminhões pesados')!==false){//quer dizer que esta apólice é caminhão
            $this->validate_required['segurado_pernoite_cep_1']=false;
        }

    	return $this->ValidateData($r);
    }

    /**
     * Retorna os dados do segurado
     * @return error:   [success,msg,data,ignore,code]
     *         ok:      [sucess,data]
     */
    public function getDados1(){

        $data=[];
        //dd(123);
        //captura o tipo da apólice - para verificar se é do tipo automovel
        $data['apolice_prod_ref'] = $this->checkRamo();
        if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];


        //dados do corretor
        $n = $this->getX1(['start'=>'Nome:','remove'=>'Nome:']);//nome do corretor
        $data['corretor_nome'] = trim($n);//nome do corretor


        $n = $this->getX1(['start'=>'SUSEP Oficial: ','remove'=>'SUSEP Oficial: ']);//susep do corretor
        $n = str_replace(['.'], [''], $n);
        $n = FormatUtility::sanitizeAllText($n);
        $data['corretor_susep'] = trim($n);//susep do corretor


        //dados da apólice

        $n = $this->getX1(['start'=>'emissão da apólice']);
        $n = explode(" ", $n);
        $n = $n[2]??'';
        $n = str_replace([":","apólice","APOLICE"], ["","apolice","apolice"], $n);

        if(empty($n)){
            if(strpos($this->text,'Esta é a apólice')!=false){
                $n = 'apolice';
            }

        }
        if(strpos($n,'apolice')!==false){
            $n = 'apolice';
        }
        //dd($n);
        $data['data_type'] = $n;
        if($data['data_type']=='')return ['success'=>false,'data'=>[],'msg'=>'endosso','ignore'=>true,'code'=>'read03'];

        $n= $this->getX1(['start'=>'Apólice:','return_type'=>'next']);
        //dd($n);
        if(strpos($n,'Endosso')!==false){
            return ['success'=>false,'data'=>[],'msg'=>'endosso','ignore'=>true,'code'=>'read03'];
        }



         $n = $this->getX1(['start'=>'SUSEP N']);
         //dd($n,$this->text_ws02);
         $n = explode(' ', $n);
         $data['seguradora_doc'] =$n[2]??'';//CNPJ Seguradora.

         if(!ValidateUtility::isCNPJ($data['seguradora_doc'])){
            $data['seguradora_doc'] = TextUtility::getSearchText($this->text,'Auto - CNPJ:','cnpj',['side'=>'right']);
           // dd($data['seguradora_doc']);
         }

         if($data['seguradora_doc']==''){
              $data['seguradora_doc'] =$n[2]??'';//CNPJ Seguradora.
         }
        // dd($data['seguradora_doc']);

        if(empty($data['seguradora_doc'])){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Processo SUSEP','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'DADOS']);
            $blocktext = TextUtility::getSearchText($blocktext,'Nº.','value',['side'=>'right']);
            $data['seguradora_doc'] = $blocktext;
        }
       //dd( $data['seguradora_doc'],$this->text);

         $n = $this->getX1(['start'=>'Proposta:','cb'=>function($v){ return explode(":",$v)[1];  }]);//Numero da Proposta Seguradora
         $n = trim($n);
         $n = str_replace(" ", "", $n);
         $n = preg_replace("/[^0-9]/", "", $n);
         $data['proposta_num'] = $n;//Numero da Proposta Seguradora

         if(empty($data['proposta_num'])){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Proposta:','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Vig']);
            $blocktext = str_replace(['Proposta:','Vig',' '],'',$blocktext);
            $n = TextUtility::getSearchText($blocktext,'','number',['side'=>'right']);//Numero da Proposta Seguradora
            $data['proposta_num'] = $n;//Numero da Proposta Seguradora
         }
         //dd($n);


         $n = $this->getX1(['start'=>'Apólice:','remove'=>'Apólice:']);//Número da Apólice na Seguradora
         $n = explode(" ", $n);
         $numApolice = $n[0].($n[1]??'').($n[2]??'');
         $data['apolice_num'] = $numApolice;//Número da Apólice na Seguradora
         if(strpos( $data['apolice_num'],'/')!==false){
            $blocktext = str_replace('emissão da apólice:','',$this->text);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['start'=>'Apólice:','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Código']);
            $blocktext = trim(str_replace(['Apólice:','Código',' '],'',$blocktext));
            $data['apolice_num'] = $blocktext;//Número da Apólice na Seguradora
            //dd($data['apolice_num'],$blocktext,$this->text);
         }

         //atualizado para ser capturado pela função numQuiverConfig()
         //$data['apolice_num_quiver'] =$n[2]??'';//Número da Apólice Quiver

         $data['data_emissao'] =TextUtility::getSearchText($this->text,'Data de emissão','datebr',['side'=>'right']);;//Data de Emissão,


         if($data['apolice_num']==$data['data_emissao']){

            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'DADOS DA SUA APÓLICE','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Vigência:']);
            //dd($blocktext);
            $n = $this->getX1(['start'=>'Apólice:','remove'=>'Apólice:'],$blocktext);//Número da Apólice na Seguradora
            $n = explode(" ", $n);
            $numApolice = $n[0].($n[1]??'').($n[2]??'');

            $data['apolice_num'] = $numApolice;//Número da Apólice na Seguradora
         }
         $data['apolice_num'] = str_replace('Item:','',$data['apolice_num']);
         //dd( $data['apolice_num']);

         $n = $this->getX1(['start'=>'Renova apólice nº:']);//Numero da apólice renovada
         if($n<>""){

             $n = $this->getX1(['start'=>'Renova apólice nº:','remove'=>'Renova apólice nº:']);//Numero da apólice renovada
             $n = explode(" ", $n);
             $numApolicRen = $n[0].$n[1].$n[2];
             $data['apolice_re_num'] = $numApolicRen;
         }else{
            $data['apolice_re_num']='';
         }




        $data['inicio_vigencia'] = $this->getX1(['start'=>'24h do dia ','remove'=>'24h do dia ','cb'=>function($v){  $n=explode(' ',$v); return $n[0];  }]);//Data Início de vigência

        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'24h do dia ','sanitize'=>true]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'emissao']);
        if(!$blocktext){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'24h do dia ','sanitize'=>true]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Processo ']);
        }
        //dd($blocktext,$this->text);
        $n=[];
        TextUtility::execFncInStr($blocktext,10,function($v) use(&$n){
            if(ValidateUtility::isDate($v) && strlen(trim($v))==10){
                $n[]=$v;
            }
        });

        if(count($n)<2){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Válida até','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'APÓLICE']);
            $data['termino_vigencia'] = TextUtility::getSearchText($blocktext,'','datebr',['side'=>'right']);
        }else{
            $data['termino_vigencia'] = $n[1];
        }

         //Dados do Segurado
         $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'Segurado:','end'=>'Fone comercial:']);

         if(empty($blocktext)){
            $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'Segurado(a):','end'=>'Fone comercial:']);
         }
         //dd($blocktext,$this->text);

         $n = TextUtility::getSearchText($blocktext,'CPF:','value',['side'=>'right']);//Doc Segurado
         if(empty($n)){
            $n = TextUtility::getSearchText($this->text,'CPF:','value',['side'=>'right']);//Doc Segurado
         }
         //dd($n);
         $data['tipo_pessoa'] = 'FISICA';//tipo segurado

         if(!$n){
             $n = TextUtility::getSearchText($blocktext,'CNPJ:','value',['side'=>'right']);
             $data['tipo_pessoa'] = 'JURIDICA';//tipo segurado
         }

         $n=str_replace(['-','.','/'], ['','',''], $n);

         if(strlen($n)>14){
             $n = substr($n, 1);
         }
        //dd($data);
         $data['segurado_doc'] = trim($n);//documento segurado
         $data['segurado_nome'] = $this->getX1(['start'=>'Segurado: ','remove'=>'Segurado: ','cb'=>function($v){  $n=explode(':',$v); return $n[0];  }]);//Nome Segurado
         //dd($n);
         if(strpos('habitualmente',$n)!=false){
            $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'Segurado:','end'=>'Fone comercial:']);
            //dd($blocktext);
            $data['segurado_nome'] = $blocktext;
         }

         if(empty($data['segurado_nome'])){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Segurado(a):','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Sexo']);
            $blocktext = trim(str_replace(['Segurado(a):','Sexo'], [''], $blocktext));
            $data['segurado_nome'] = $blocktext;
         }
         if(empty($data['segurado_nome'])){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Segurado(a):','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'CNPJ:']);
            $blocktext = trim(str_replace(['Segurado(a):','CNPJ:'], [''], $blocktext));
            $data['segurado_nome'] = $blocktext;
         }
        // dd($data['segurado_nome'],$this->text);

        return ['success'=>true,'data'=>$data];
    }

    public function getDados2(){
        $data=[];

        //captura o tipo da apólice - para verificar se é do tipo automovel
        $data['apolice_prod_ref'] = $this->checkRamo();
        if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];


        //dados do corretor
        $n = $this->getX1(['start'=>'Nome:','remove'=>'Nome:']);//nome do corretor
        $data['corretor_nome'] = trim($n);//nome do corretor


        $n = $this->getX1(['start'=>'SUSEP Oficial: ','remove'=>'SUSEP Oficial: ']);//susep do corretor
        $n = str_replace(['.'], [''], $n);
        $n = FormatUtility::sanitizeAllText($n);
        $data['corretor_susep'] = trim($n);//susep do corretor


        //dados da apólice

        $n = $this->getX1(['start'=>'emissão da apólice']);
        $n = explode(" ", $n);
        $n = $n[2]??'';
        $n = str_replace([":","apólice","APOLICE"], ["","apolice","apolice"], $n);

        if(empty($n)){
            if(strpos($this->text,'Esta é a apólice')!=false){
                $n = 'apolice';
            }
        }
        //dd($n);
        $data['data_type'] = $n;
        if($data['data_type']=='')return ['success'=>false,'data'=>[],'msg'=>'endosso','ignore'=>true,'code'=>'read03'];

        $n= $this->getX1(['start'=>'Apólice:','return_type'=>'next']);
        //dd($n);
        if(strpos($n,'Endosso')!==false){
            return ['success'=>false,'data'=>[],'msg'=>'endosso','ignore'=>true,'code'=>'read03'];
        }


         $text= TextUtility::getPartOfStr($this->text, ['start'=>'Processo SUSEP','end'=>'DADOS CADASTRAIS','sanitize'=>false]);
         $n = TextUtility::getSearchText($text,'Nº.','value',['side'=>'right']);
         if($n=='Data'){
            $n = TextUtility::getSearchText($text,'Nº.','datebr',['side'=>'right']);
            $n = TextUtility::getSearchText($text,$n,'value',['side'=>'right']);
         }
         $data['seguradora_doc'] =$n;//CNPJ Seguradora.



         $n = $this->getX1(['start'=>'Proposta:','cb'=>function($v){ return explode(":",$v)[1];  }]);//Numero da Proposta Seguradora
         $n = trim($n);
         $n = str_replace(" ", "", $n);
         $n = preg_replace("/[^0-9]/", "", $n);
         $data['proposta_num'] = $n;//Numero da Proposta Seguradora

         if(empty($data['proposta_num'])){
            $text_0 = explode('split',$this->text);
            $text_0 = $text_0[1];
            $blocktext = TextUtility::getPartOfStr($text_0, ['start'=>'Proposta:','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Data']);
            $p1 = TextUtility::getSearchText($blocktext,'Proposta:','number',['side'=>'right']);
            $p2 = TextUtility::getSearchText($blocktext,$p1,'number',['side'=>'right']);
            $data['proposta_num'] = $p1.$p2;
            //dd($data['proposta_num'],$blocktext);
         }

         //dd(strlen($data['seguradora_doc']));
         if(empty($data['seguradora_doc']) || strlen($data['seguradora_doc'])<12){
            $text_0 = explode('split',$this->text);
            $text_0 = $text_0[1];
            $text= TextUtility::getPartOfStr($text_0, ['start'=>'Processo SUSEP Nº.','sanitize'=>false]);
            $text= TextUtility::getPartOfStr($text, ['end'=>'Segurado','sanitize'=>false]);
            //dd($text);
            $data['seguradora_doc'] =TextUtility::getSearchText($text,'Nº.','value',['side'=>'right']);
         }


         //dd($data['proposta_num'],$blocktext);
         $n = $this->getX1(['start'=>'Apólice:','remove'=>'Apólice:']);//Número da Apólice na Seguradora
         $n = explode(" ", $n);
         $numApolice = $n[0].($n[1]??'').($n[2]??'');

         $data['apolice_num'] = $numApolice;//Número da Apólice na Seguradora

         //atualizado para ser capturado pela função numQuiverConfig()
         //$data['apolice_num_quiver'] =$n[2]??'';//Número da Apólice Quiver
         //dd($this->text);
         $data['data_emissao'] =TextUtility::getSearchText($this->text,'emissão:','datebr',['side'=>'right']);//Data de Emissão

         $n = $this->getX1(['start'=>'Renova apólice nº:']);//Numero da apólice renovada
         if($n<>""){

             $n = $this->getX1(['start'=>'Renova apólice nº:','remove'=>'Renova apólice nº:']);//Numero da apólice renovada
             $n = explode(" ", $n);
             $numApolicRen = $n[0].$n[1].$n[2];
             $data['apolice_re_num'] = $numApolicRen;
         }else{
            $data['apolice_re_num']='';
         }


        $data['inicio_vigencia'] = $this->getX1(['start'=>'24h do dia ','remove'=>'24h do dia ','cb'=>function($v){  $n=explode(' ',$v); return $n[0];  }]);//Data Início de vigência

        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'24h do dia ','sanitize'=>true]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'emissao']);
        //dd($blocktext);
        if(!$blocktext){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'24h do dia ','sanitize'=>true]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Processo ']);
        }

        if(strpos($blocktext,'dados cadastrais')!==false){
            $text_0 = explode('split',$this->text);
            $text_0 = $text_0[1];
            $blocktext = TextUtility::getPartOfStr( $text_0, ['start'=>'24h do dia ','sanitize'=>true]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Processo']);

            $data['inicio_vigencia'] = TextUtility::getSearchText($blocktext,'24h','datebr',['side'=>'right']);
            $data['termino_vigencia'] = TextUtility::getSearchText($blocktext,'Processo','datebr',['side'=>'left']);
        }

       // dd($blocktext);
        $n=[];
        TextUtility::execFncInStr($blocktext,10,function($v) use(&$n){
            if(ValidateUtility::isDate($v) && strlen(trim($v))==10){
                $n[]=$v;
            }
        });
        //dd($n);
        if(count($n)>1){
            $data['termino_vigencia'] = $n[1];
        }else{
            $data['termino_vigencia'] = $n[0];
        }

        if($data['inicio_vigencia']==$data['termino_vigencia']){
            $text_0 = explode('split',$this->text);
            $text_0 = $text_0[1];
            $blocktext = TextUtility::getPartOfStr( $text_0, ['start'=>'24h do dia ','sanitize'=>true]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Processo']);

            $data['inicio_vigencia'] = TextUtility::getSearchText($blocktext,'24h','datebr',['side'=>'right']);
            $data['termino_vigencia'] = TextUtility::getSearchText($blocktext,'Processo','datebr',['side'=>'left']);

        }

        //dd($data['termino_vigencia']);

         //Dados do Segurado

         if(strpos($this->text,'Segurado(a):')!==false){
            $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'Segurado(a):','end'=>'Telefone comercial:']);
         }else{
            $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'Segurado:','end'=>'Fone comercial:']);
         }

         if(strpos($this->text,'CPF:')!==false || strpos($this->text,'CNPJ:')!==false){
            $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'DADOS DA SUA APÓLICE','end'=>'Estado:']);
         }

         if(strpos($this->text,'CPF:')!==false || strpos($this->text,'CNPJ:')!==false){
           $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'DADOS CADASTRAIS','end'=>'Endereço:']);
        }

         $n = TextUtility::getSearchText($blocktext,'CPF:','value',['side'=>'right']);//Doc Segurado


         $data['tipo_pessoa'] = 'FISICA';//tipo segurado

         if(!$n){
             $n = TextUtility::getSearchText($blocktext,'CNPJ:','value',['side'=>'right']);
             $data['tipo_pessoa'] = 'JURIDICA';//tipo segurado
         }
        // dd($n,$blocktext);

         $n=str_replace(['-','.','/'], ['','',''], $n);

         if(strlen($n)>14){
             $n = substr($n, 1);
         }


         //dd($this->text);
         $data['segurado_doc'] = trim($n);//documento segurado

         if(empty($data['segurado_doc'])){
            $text_0 = explode('split',$this->text);
            $text_0 = $text_0[1];
            $blocktext = TextUtility::getPartOfStr($text_0,['start'=>'Processo SUSEP','end'=>'Endereço:']);
            $data['segurado_doc'] = TextUtility::getSearchText($blocktext,'CPF','document',['side'=>'right']);
            //dd($data['segurado_doc'],$blocktext);
         }


         if(empty($data['segurado_doc'])){
            $text_0 = explode('split',$this->text);
            $text_0 = $text_0[1];
            $blocktext = TextUtility::getPartOfStr($text_0,['start'=>'DADOS CADASTRAIS','end'=>'CEP:']);
            $data['segurado_doc'] = TextUtility::getSearchText($blocktext,'CPF','document',['side'=>'right']);
            //dd($data['segurado_doc'],$blocktext);
         }

         $data['segurado_nome'] = $this->getX1(['start'=>'Segurado(a): ','remove'=>'Segurado(a): ','cb'=>function($v){  $n=explode(':',$v); return $n[0];  }]);//Nome Segurado
         $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'Segurado(a):','end'=>'Fone comercial:']);

         if(strpos('habitualmente',$data['segurado_nome'])!=false){
            $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'Segurado:','end'=>'Fone comercial:']);
            $data['segurado_nome'] = $blocktext;
         }
         //dd($data['segurado_nome']);

        return ['success'=>true,'data'=>$data];
    }

    public function getDados3(){//usado no empresarial
        $data=[];
        //dd(123);
        //captura o tipo da apólice - para verificar se é do tipo automovel
        $data['apolice_prod_ref'] = $this->checkRamo();
        if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];

        //dados do corretor
        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'DADOS DO CORRETOR','sanitize'=>false]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'E-mail:']);
        if(empty($blocktext)){
            $blocktext = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'DADOS DO CORRETOR','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'E-mail:']);
        }
        $n = $this->getX1(['start'=>'DADOS','return_type'=>'next'],$blocktext);//nome do corretor
        //dd($n);
        $n = str_replace('Nome: ', '', $n);
        $data['corretor_nome'] = trim($n);//nome do corretor
        //dd($n,$blocktext,$this->text_ws02);

        $n = $this->getX1(['start'=>'SUSEP Oficial: ','remove'=>'SUSEP Oficial: ']);//susep do corretor
        $n = str_replace(['.'], [''], $n);
        $n = FormatUtility::sanitizeAllText($n);
        $data['corretor_susep'] = trim($n);//susep do corretor
        $data['corretor_susep'] = str_replace('/','7',$data['corretor_susep']);
       // dd($data['corretor_susep']);


        //dados da apólice

        if(strpos($this->text,'Item: 02')!==false){
            return ['success'=>false,'data'=>[],'msg'=>'Mais de um item encontrado na leitura da apólice','ignore'=>true,'code'=>'read24'];
        }

        $n = $this->getX1(['start'=>'DADOS DA APÓLICE']);
        $n = explode(" ", $n);
        $n = $n[2]??'';
        $n = str_replace([":","apólice","APOLICE","APÓLICE"], ["","apolice","apolice","apolice"], $n);

        $data['data_type'] = 'apolice';
        if($data['data_type']=='')return ['success'=>false,'data'=>[],'msg'=>'endosso','ignore'=>true,'code'=>'read03'];

        $n= $this->getX1(['start'=>'Apólice:','return_type'=>'next']);
        //dd($n);
        if(strpos($n,'Endosso')!==false){
            return ['success'=>false,'data'=>[],'msg'=>'endosso','ignore'=>true,'code'=>'read03'];
        }
        //dd($n);


         $n = $this->getX1(['start'=>'SUSEP N']);
         //dd($data['apolice_prod_ref']);
         if($data['apolice_prod_ref']=='Ramo:118' || $data['apolice_prod_ref']=='Ramo:114'){
             $n = TextUtility::getSearchText($this->text,'Processo SUSEP:','value',['side'=>'right']);
             $data['seguradora_doc'] =$n;//CNPJ Seguradora.
         }else{
             $n = explode(' ', $n);
           // dd($n);
            $data['seguradora_doc'] =$n[2];//CNPJ Seguradora.
            if(!ValidateUtility::isCNPJ($data['seguradora_doc'])){
            $data['seguradora_doc'] = TextUtility::getSearchText($this->text,'Auto - CNPJ:','cnpj',['side'=>'right']);
           // dd($data['seguradora_doc']);
            }
            if(!ValidateUtility::isCNPJ($data['seguradora_doc'])){
                $data['seguradora_doc'] = TextUtility::getSearchText($this->text,'CNPJ:','cnpj',['side'=>'right']);
            }

            if($data['seguradora_doc']==''){
                 $data['seguradora_doc'] =$n[2];//CNPJ Seguradora.
            }
           //dd($data['seguradora_doc']);
         }


         $n = $this->getX1(['start'=>'Proposta:','cb'=>function($v){ return explode(":",$v)[1];  }]);//Numero da Proposta Seguradora
         $n = trim($n);
         $n = str_replace(" ", "", $n);
         $n = preg_replace("/[^0-9]/", "", $n);
         $data['proposta_num'] = $n;//Numero da Proposta Seguradora


         $n = $this->getX1(['start'=>'Apólice:','remove'=>'Apólice:']);//Número da Apólice na Seguradora
         $n = explode(" ", $n);
         $numApolice = $n[0].($n[1]??'').($n[2]??'');
         $data['apolice_num'] = $numApolice;//Número da Apólice na Seguradora

         //dd($data['apolice_num']);
         if(strpos($data['apolice_num'],'NOVA')!==false || $data['apolice_num']=='*Renovação*' || $data['apolice_num']=='*NOVA*' || $data['apolice_num']=='*NOVA*Númeroda' || $data['apolice_num']=='“Renovação*Númeroda'){
             $n = $this->getX1(['start'=>'Número da apólice:','remove'=>'Número da apólice:']);//Número da Apólice na Seguradora
             $n = explode(" ", $n);
             $numApolice = $n[0].($n[1]??'').($n[2]??'');
             $data['apolice_num'] = $numApolice;//Número da Apólice na Seguradora

         }
         $data['apolice_num'] = preg_replace('/[^0-9]/', '', $data['apolice_num']);


         //atualizado para ser capturado pela função numQuiverConfig()
        // $data['apolice_num_quiver'] =$n[2]??'';//Número da Apólice Quiver

         $data['data_emissao'] =trim($this->getX1(['start'=>'emissão da apólice: ','remove'=>'emissão da apólice: ']));//Data de Emissão

         if($data['data_emissao']==''){
             $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Para validade ','sanitize'=>true]);
             $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Porto Seguro Cia']);
             $n = TextUtility::getDateExtenso($blocktext,'datebr');
             $data['data_emissao'] = $n;

         }
         if($data['data_emissao']==''){
             $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Porto Seguro Cia. de Seguros Gerais','sanitize'=>false]);
            // dd($this->text);
             $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'FORMA DE PAGAMENTO']);
             $n = TextUtility::getDateExtenso($blocktext,'datebr');
             $data['data_emissao'] = $n;
         }

         if(empty($data['data_emissao'])){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'assina esta','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'ANOTAÇÕES']);
            $n = TextUtility::getDateExtenso($blocktext,'datebr');
            $data['data_emissao'] = $n;
            //dd($blocktext, $data['data_emissao']);
        }
        if(empty($data['data_emissao'])){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'A presente proposta','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'ANOTAÇÕES']);
            $n = TextUtility::getDateExtenso($blocktext,'datebr');
            $data['data_emissao'] = $n;
            //dd($blocktext, $data['data_emissao'],$this->text);
        }

        if(empty($data['data_emissao'])){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'CLAUSULAS GERAIS','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'ANOTAÇÕES']);
            $n = TextUtility::getDateExtenso($blocktext,'datebr');
            $data['data_emissao'] = $n;
            //dd($blocktext, $data['data_emissao'],$this->text);
        }

        if(empty($data['data_emissao'])){// data está escrita
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'CLÁUSULAS GERAIS','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Local e data']);
            $n = TextUtility::getDateExtenso($blocktext,'datebr');
            $data['data_emissao'] = $n;
            //dd($blocktext, $data['data_emissao'],$this->text);
        }
        //dd($data['data_emissao'],$blocktext,$this->text);

         $n = $this->getX1(['start'=>'Renova apólice nº:']);//Numero da apólice renovada
         if($n<>""){

             $n = $this->getX1(['start'=>'Renova apólice nº:','remove'=>'Renova apólice nº:']);//Numero da apólice renovada
             $n = explode(" ", $n);
             $numApolicRen = $n[0].$n[1].$n[2];
             $data['apolice_re_num'] = $numApolicRen;
         }else{
            $data['apolice_re_num']='';
         }


        $data['inicio_vigencia'] = $this->getX1(['start'=>'24h do dia ','remove'=>'24h do dia ','cb'=>function($v){  $n=explode(' ',$v); return $n[0];  }]);//Data Início de vigência

        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'24h do dia ','sanitize'=>true]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'emissao']);
        if(!$blocktext){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'24h do dia ','sanitize'=>true]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Processo ']);
        }
        //dd($blocktext);


        $n = TextUtility::getSearchText($blocktext,'','datebr',['limit'=>false]);

        $data['termino_vigencia'] = $n[1] ?? '';


         //Dados do Segurado
         $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'DADOS DO SEGURADO','end'=>'Telefone:']);
         if(empty($blocktext)){
            $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'Processo SUSEP']);
            $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'Telefone:']);
         }
         //dd($blocktext,$this->text);

         $n = TextUtility::getSearchText($blocktext,'CPF:','value',['side'=>'right']);//Doc Segurado

         $data['tipo_pessoa'] = 'FISICA';//tipo segurado

         if(!$n){
             $n = TextUtility::getSearchText($blocktext,'CNPJ:','value',['side'=>'right']);
             $data['tipo_pessoa'] = 'JURIDICA';//tipo segurado
         }

         $n=str_replace(['-','.','/'], ['','',''], $n);

         if(strlen($n)>14){
             $n = substr($n, 1);
         }
         $data['segurado_doc'] = trim($n);//documento segurado

         $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'DADOS DO SEGURADO','sanitize'=>false]);
         $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'E-mail:']);
         $n = $this->getX1([$blocktext,'start'=>'DADOS DO SEGURADO','return_type'=>'next']);;//nome do corretor

         if(empty($blocktext)){
            $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'Processo SUSEP']);
            $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'Telefone:']);
            $n = $this->getX1([$blocktext,'start'=>'Processo SUSEP','return_type'=>'next2']);;//nome do corretor
         }
         $n = str_replace(['Nome: ','Razão Social: '], '', $n);
         $data['segurado_nome'] = $n;//Nome Segurado

         if(strpos($data['segurado_nome'],'SUSEP')!==false || strpos($data['segurado_nome'],'Código')!==false){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Social:','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'CNPJ:']);
            $blocktext = str_replace(['CNPJ:','Social:'],'',$blocktext);
            $data['segurado_nome'] = trim($blocktext);
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

      $text0 = FormatUtility::sanitizeAllText($this->text);

         //Forma de Pagamento


         $n = $this->text;

         $line_info_pgto = $n;
         //dd($line_info_pgto);
         $text_pgto = TextUtility::getPartOfStr($this->text,['start'=>'FORMA DE PAGAMENTO']);
         $text_pgto = TextUtility::getPartOfStr($text_pgto,['end'=>'Os descontos d']);
         $pgto_tipo= PgtoData::getPgtoTipo($text_pgto);
         //dd($pgto_tipo,$text_pgto);
         if(!$pgto_tipo){
             if(strpos($line_info_pgto,'Banco:')!==false){// Débito em conta
                $pgto_tipo = 'debito';
             }elseif(strpos($line_info_pgto,'Fatura')!==false || strpos($line_info_pgto,'Cartão de Crédito')!==false){// Cartão de Crédito
                $pgto_tipo = 'cartao';
             }elseif(strpos($line_info_pgto,'vista')!==false || strpos($line_info_pgto,'À vista')!==false || strpos($line_info_pgto,'Carnê')!==false){// Boleto
                 $pgto_tipo = 'boleto';
             }
         }

         if(!$pgto_tipo){
            $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'FORMA DE PAGAMENTO']);
            $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'Seguro sem']);

            $n = TextUtility::getSearchText($blocktext,'de contratação','value',['side'=>'right']);
           // dd($n,$blocktext);

            if($n=='UNICA'){
                $pgto_tipo = 'boleto';
            }
         }

         if($pgto_tipo==false){
             $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'FORMA DE PAGAMENTO']);
             $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'Parcela']);

             if(strpos($blocktext,'100% Programa Sempre Presente')!==false){
                 $pgto_tipo = 'programa_sempre_presente';
             }

         }
         //dd($pgto_tipo);
         if($pgto_tipo=='debito' || $pgto_tipo=='boleto' || $pgto_tipo=='carne'){

            $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'TOTAL DO SEGURO','end'=>'Código de registro']);

            if(!$blocktext){
                $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'TOTAL DO SEGURO']);
                $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'Seu seguro']);
            }
           // dd($blocktext);
            $text0 = FormatUtility::sanitizeAllText($this->text);
            if(!$blocktext){
                $blocktext = TextUtility::getPartOfStr($text0,['start'=>'dados do pagamento']);
                $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'descontos']);
            }

            if(!$blocktext){
                $blocktext = TextUtility::getPartOfStr($text0,['start'=>'dados de pagamento']);
                $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'descontos']);
            }

            if(!$blocktext){
                $blocktext = TextUtility::getPartOfStr($text0,['start'=>'Banco:']);
                $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'DESC.']);
            }

            //dd($blocktext);
            $blocktext = str_replace('*QUITADA', '01/01/3000', $blocktext);
            $blocktext = str_replace('*quitada', '01/01/3000', $blocktext);
            $blocktext = str_replace('R$', 'R$ ', $blocktext);
            $venc_parcelas = PgtoData::getTableVencParc($blocktext);
            if(!$venc_parcelas){
                $venc_parcelas = PgtoData::getTableVencParc_mixed($blocktext);
            }

            if(!$venc_parcelas){

                $blocktext = TextUtility::getPartOfStr($text0,['start'=>'forma de pagamento']);
                $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'questionario ']);
                $blocktext = str_replace('R$', 'R$ ', $blocktext);
                $venc_parcelas = PgtoData::getTableVencParc_mixed($blocktext);
                //dd($venc_parcelas,'*');
            }

            $data['fpgto_tipo']=$pgto_tipo;
            $data['fpgto_tipo_code']= PgtoData::getPgtoCode($pgto_tipo)[1];
            $data['fpgto_n_prestacoes'] = $venc_parcelas['fpgto_n_prestacoes'];

            //dd($venc_parcelas,$blocktext);
            if($venc_parcelas['fpgto_n_prestacoes']=='01' && $venc_parcelas['fpgto_datavenc_1']!=''){// parcela única a vista
                //dd($venc_parcelas,$blocktext);
                $parcelas = [];
                $parcelas[] = $venc_parcelas['fpgto_valorparc_1'];
            }else{

                $parcelas = [];

                $x=1;
                foreach($venc_parcelas as $i=>$n){

                    if(TextUtility::isNumberFormated($n))$parcelas[]=$n;
                    if($i=='fpgto_valorparc_1' && $n='01/01/3000'){
                        $venc_parcelas[$i] = str_replace('01/01/3000',$this->getDate1aParc($data['fpgto_tipo'],$data),$venc_parcelas[$i]);
                    }elseif($i!='fpgto_valorparc_1' && $n='01/01/3000'){
                        $venc_parcelas[$i] = str_replace('01/01/3000',$this->getDateUaParc($data['fpgto_tipo'],$venc_parcelas),$venc_parcelas[$i]);
                    }
                    $x++;
                }
            }


            $premio = PgtoData::getPremioTotalParcela($this->text, $parcelas,$this->validate_premio_margem);

            $data = $data + $venc_parcelas + ['fpgto_premio_total'=>$premio];
            $r= PgtoData::addFields1($data);
            if($r)$data = $data + $r;


            //dd($parcelas,$blocktext);
             $premio = PgtoData::getPremioTotalParcela($this->text, $parcelas,$this->validate_premio_margem);
              //dd($this->text_ws02);

             if(!$premio){
                $blocktext = TextUtility::getPartOfStr($this->text_ws02,['start'=>'TOTAL DO SEGURO']);
                $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'Em atendimento']);
                $premio = TextUtility::getSearchText($blocktext,'TOTAL DO SEGURO','number_formated',['side'=>'right']);
             }
             //dd($premio);
             if(!$premio){
                return ['success'=>false,'data'=>$data,'msg'=>'Valor do prêmio não compatível'];
             }

             $n= PgtoData::addFields1($data);

             $data = $data + $n + ['fpgto_premio_total'=>$premio];

             if(!$data['fpgto_premio_total']){
                $data['fpgto_premio_total'] = $premio;
             }


         }elseif($pgto_tipo=='cartao' || $pgto_tipo=='programa_sempre_presente'){
             $data['fpgto_tipo']=$pgto_tipo;
             $data['fpgto_tipo_code']= PgtoData::getPgtoCode($pgto_tipo)[1];

            // $blocktext = $this->getX1(['start'=>'FORMA DE PAGAMENTO','end'=>'Pagamento ','split'=>false],$this->text_ws02);
             //$blocktext = str_replace('R$', 'R$ ', $blocktext);


            $blocktext = TextUtility::getPartOfStr($this->text_ws02,['start'=>'FORMA DE PAGAMENTO']);
            $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'DESCONTOS']);
            $blocktext = str_replace('R$', 'R$ ', $blocktext);


             if(empty($blocktext)){
                $blocktext = TextUtility::getPartOfStr($this->text_ws02,['start'=>'Vencimento']);
                $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'Os descontos']);
                $blocktext = str_replace('R$', 'R$ ', $blocktext);
             }

            if(empty($blocktext)){
                $blocktext = TextUtility::getPartOfStr($this->text_ws02,['start'=>'DADOS DE PAGAMENTO']);
                $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'CONDIÇÕES']);
                $blocktext = str_replace('R$', 'R$ ', $blocktext);
             }

             //dd($blocktext,$this->text_ws02);
             if(strpos($blocktext,'cada acionamento')!==false || strpos($blocktext,'DADOS DO')!==false){
                $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'DADOS DO MOTORISTA']);
                //dd($blocktext,$this->text_ws02);
             }


             //procura pela relação de parcelas que tenham o texto "1a 999,99     2a 999,99  ...   12a 99999"
             //dd($blocktext,$this->text_ws02);
             $valores=TextUtility::getSearchText($blocktext,'','number_formated',['limit'=>false]);
             $dtvenc=TextUtility::getSearchText($blocktext,'','datebr',['limit'=>false]);


             //dd('****************',$valores,$dtvenc,$blocktext);
             //ordena a matriz
             ksort($valores);
             ksort($dtvenc);
             //dd($valores,$dtvenc);

             if(count($dtvenc)<1){//não tem data de pagamento informado
                 $n = PgtoData::makeTable(count($valores),$this->getDate1aParc($data['fpgto_tipo'],$data),array_values($valores));//monta a tabela
                 $data = $data + $n;
             }else{
                $n = PgtoData::makeTable(count($valores),array_values($dtvenc),array_values($valores));//monta a tabela
                $data = $data + $n;
             }
             $parcelas = $valores;


             //**** descontos: ver os casos: 29897 e 29956 ****
                //verifica se tem desconto
                $desc='';
                if(strpos($this->text,'PROGRAMA SEMPRE PRESENTE')!==false){//existe o texto com o desconto
                    $n = TextUtility::getPartOfStr($this->text,['start'=>'PROGRAMA SEMPRE PRESENTE']);
                    $n = TextUtility::getPartOfStr($n,['end'=>'pontos','side_len'=>[0,10]]);
                    $n = str_replace('*',' ',$n);//existem alguns '*' grupdados com textos
                    $desc = TextUtility::getSearchText($n,'','number_formated');
                    $data['fpgto_desc']=$desc;

                }
                //dd($data);
                $premio = PgtoData::getPremioTotalParcela($this->text, $parcelas,$this->validate_premio_margem,$desc);
                if(!$premio){//não achou o prêmio
                    //dd('*',$premio,$parcelas,$desc);
                    //é possível que o desconto esteja informado dentro das parcelas, como uma parcela, e neste caso não deve ser verificando o valor do desconto no prêmio total
                    if(in_array($desc,$parcelas)){
                        $premio = PgtoData::getPremioTotalParcela($this->text, $parcelas,$this->validate_premio_margem);
                        $data['fpgto_desc']='';//tira o desconto
                         //dd($premio);
                    }
                }
                if(!$premio){
                    //é possível que o desconto esteja informado em um bloco assim 'Programa de Fidelidade - Resgate de Pontos ... {N} pontos {number_formated}'
                    //lógica: captura este valor e e verifica se bate com a soma das parcelas e o valor total
                    $blocktext = $this->getX1(['start'=>'FORMA DE PAGAMENTO','end'=>'QUESTIONÁRIO DE AVALIAÇÃO DE RISCO','split'=>false]);
                    $blocktext = $this->getX1(['start'=>'PROGRAMA FIDELIDADE','split'=>false,'remove'=>'*'],$blocktext);
                    $n = TextUtility::getSearchText($blocktext,'iupp','number_formated',['side'=>'right','max_words'=>2]);
                    if($n){//achou o número
                        //portanto acrescenta como mais uma parcela para que não função getPremioTotalParcela possa ser verificado se bate com o valor total
                        $parcelas['desconto_pontos']=$n;
                        $premio = PgtoData::getPremioTotalParcela($this->text, $parcelas,$this->validate_premio_margem);
                        $data['fpgto_desc']=$n;//tira o desconto
                    }
                    //dd($n,$parcelas,$premio,$blocktext);
                }
            if(!$premio){
               $premio = TextUtility::getSearchText($this->text,'TOTAL DO SEGURO','number_formated',['side'=>'right']);
               $data['fpgto_premio_total'] = $premio;
            }
            if(empty($premio)){
                $premio = TextUtility::getSearchText($this->text_ws02,'TOTAL DO SEGURO','number_formated',['side'=>'right']);
                $data['fpgto_premio_total'] = $premio;
             }
            //dd($premio,$this->text_ws02);

             //dd($premio);
             $n= PgtoData::addFields1($data);
             $data = $data + $n + ['fpgto_premio_total'=>$premio];



         }
        //dd($data);


        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'premio liqui','end'=>'perda parcial','sanitize'=>true]);
        if(strpos($blocktext,'perda parcial')!==true){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'premio liqui','end'=>'Em atendimento ','sanitize'=>true]);
        }

        if(!$blocktext){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'PRÊMIO LÍQUIDO']);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Em atendimento']);
            $blocktext = str_replace('PRÊMIO LÍQUIDO', 'premio liquido', $blocktext);
        }
        $blocktext = str_replace('r$', 'r$ ', $blocktext);
        //dd($blocktext,$this->text);

        $r=PgtoData::getFielsPremioAdd($blocktext,$data,['tem_juros'=>true]);
        //dd($r);


        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'encargos financeiros','end'=>'iof','sanitize'=>true]);
        $r2=TextUtility::getSearchText($blocktext,'encargos financeiros','number_formated',['side'=>'right']);


        if($r2<>'0,00'){
            $r['fpgto_adicional']=$r2;
        }

        $data = $data + $r ;

        if(empty($data['fpgto_iof'])){
            $data['fpgto_iof'] = TextUtility::getSearchText($blocktext,'iof','number_formated',['side'=>'left']);
        }

        if($data['fpgto_iof']=='0,00' || empty($data['fpgto_iof'])){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'VALOR DO SEGURO']);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'VALOR DAS']);
            //dd($blocktext);
            $data['fpgto_iof'] = TextUtility::getSearchText($blocktext,'iof','number_formated',['side'=>'right']);
            $data['fpgto_juros'] = TextUtility::getSearchText($blocktext,'parcelamento','number_formated',['side'=>'right']);
        }
        //dd($data['fpgto_iof']);
        if(empty($data['fpgto_premio_liquido']) || $data['fpgto_premio_liquido']=='0,00' || empty($data['fpgto_iof'])){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Prêmio coberturas']);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'E VALORES']);
            $blocktext = str_replace('R$','R$ ',$blocktext);

            if(empty($blocktext)){
                $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Prêmio coberturas']);
                $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'LÍQUIDO']);
                $blocktext = str_replace('R$','R$ ',$blocktext);
                //dd($blocktext);
            }

            if(empty($blocktext)){
                $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Prêmio coberturas']);
                $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'VALORES DAS ']);
                $blocktext = str_replace('R$','R$ ',$blocktext);
                //dd($blocktext,$this->text);
                if(empty($data['fpgto_iof'])){
                    $data['fpgto_iof'] = TextUtility::getSearchText($blocktext,'iof','number_formated',['side'=>'right']);
                    $data['fpgto_juros'] = TextUtility::getSearchText($blocktext,'parcelamento','number_formated',['side'=>'right']);
                }
               // dd($blocktext,$this->text);
            }
            $preco_servicos = '0.00';
            if(empty($blocktext)){
                $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'DESCRIÇÃO PREÇO']);
                $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'DESCONTOS']);
                $blocktext = str_replace('R$','R$ ',$blocktext);
                $preco_servicos = FormatUtility::nDecimal(TextUtility::getSearchText($blocktext,'benefícios e serviços','number_formated',['side'=>'right']));

                if(empty($data['fpgto_iof'])){
                    $data['fpgto_iof'] = TextUtility::getSearchText($blocktext,'iof','number_formated',['side'=>'right']);
                    $data['fpgto_juros'] = TextUtility::getSearchText($blocktext,'parcelamento','number_formated',['side'=>'right']);
                }

            }

            $premi_cober = FormatUtility::nDecimal(TextUtility::getSearchText($blocktext,'coberturas','number_formated',['side'=>'right']));
            $adicionais = FormatUtility::nDecimal(TextUtility::getSearchText($blocktext,'adicionais','number_formated',['side'=>'right']));

            if(empty($adicionais)){
                $adicionais ='0.00';
            }
           //dd($premi_cober,$adicionais,$blocktext,$this->text);
            $pre_liqui = FormatUtility::numberFormat($premi_cober+$adicionais+ $preco_servicos);
            $data['fpgto_premio_liquido'] = $pre_liqui;
        }

        if(empty($data['fpgto_iof'])){
            $blocktext = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'Vidros Laterais']);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'TOTAL DO']);
            $blocktext = str_replace('R$','R$ ',$blocktext);
            //dd($blocktext,$this->text_ws02);
            $data['fpgto_iof'] = TextUtility::getSearchText($blocktext,'TOTAL','number_formated',['side'=>'left']);
            $data['fpgto_juros'] = $this->getX1(['start'=>'Retrovisor','return_type'=>'next2'], $blocktext);
            $data['fpgto_adicional'] = $this->getX1(['start'=>'Retrovisor','return_type'=>'next1'], $blocktext);
        }

        //dd($data,$this->text);

        if($pgto_tipo=='programa_sempre_presente'){
             $data['fpgto_tipo']='';
        }
       // dd($data);
        return $data;
    }

    public function getPremio2($data){//residencial
        //premio_liquido
        //premio_servico
        //premio_custo
        //premio_adicional
        //premio_iof???
        //premio_total
        //dd(123);
         //Forma de Pagamento
         $text0 = FormatUtility::sanitizeAllText($this->text);

         $n = $this->text_no_space;

         $line_info_pgto = $n;
         //dd(123);
         $pgto_tipo= PgtoData::getPgtoTipo($this->text,$text0);


          if($data['apolice_prod_ref']=='Ramo:114'){
              $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'DADOS DO PAGAMENTO']);
              $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'INFORMAÇÕES']);
              $pgto_tipo= PgtoData::getPgtoTipo($blocktext);
              //dd($pgto_tipo);
          }
         if(!$pgto_tipo){
             if(strpos($line_info_pgto,'Banco:')!==false){// Débito em conta
                $pgto_tipo = 'debito';
             }elseif(strpos($line_info_pgto,'Fatura')!==false || strpos($line_info_pgto,'Cartão de Crédito')!==false){// Cartão de Crédito
                $pgto_tipo = 'cartao';
             }elseif(strpos($line_info_pgto,'vista')!==false || strpos($line_info_pgto,'À vista')!==false || strpos($line_info_pgto,'Carnê')!==false || strpos($line_info_pgto,'BOLETO BANCARIO')!==false){// Boleto
                 $pgto_tipo = 'boleto';
             }
         }

         if(!$pgto_tipo){
            $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'FORMA DE PAGAMENTO']);
            $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'Seguro sem']);

            $n = TextUtility::getSearchText($blocktext,'de contratação','value',['side'=>'right']);
           // dd($n,$blocktext);

            if($n=='UNICA'){
                $pgto_tipo = 'boleto';
            }
         }

         if($pgto_tipo==false){
             $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'FORMA DE PAGAMENTO']);
             $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'Parcela']);

             if(strpos($blocktext,'100% Programa Sempre Presente')!==false){
                 $pgto_tipo = 'programa_sempre_presente';
             }

             if($pgto_tipo==false){
                 if(strpos($blocktext,'CARTAO')!==false){
                     $pgto_tipo = 'cartao';
                 }
             }

         }

         if(empty($pgto_tipo)){
            $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'DADOS DO PAGAMENTO']);
            $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'Parcela']);

            $pgto_tipo = PgtoData::getPgtoTipo($blocktext);

        }

        //dd($pgto_tipo);
         if($pgto_tipo=='debito' || $pgto_tipo=='boleto' || $pgto_tipo=='carne'){

            $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'TOTAL DO SEGURO','end'=>'Código de registro']);

            if(!$blocktext){
                $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'TOTAL DO SEGURO']);
                $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'Seu seguro']);
            }
           // dd($blocktext);
            $text0 = FormatUtility::sanitizeAllText($this->text);
            if(!$blocktext){
                $blocktext = TextUtility::getPartOfStr($text0,['start'=>'dados do pagamento']);
                $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'descontos']);
            }

            if(!$blocktext){
                $blocktext = TextUtility::getPartOfStr($text0,['start'=>'dados de pagamento']);
                $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'descontos']);
            }

            $blocktext2 = TextUtility::getPartOfStr($text0,['start'=>'dados do pagamento']);
            $blocktext2 = TextUtility::getPartOfStr($blocktext2,['end'=>'informacoe']);

            $blocktext2 = str_replace('*QUITADA', '01/01/3000', $blocktext2);
            $blocktext2 = str_replace('*quitada', '01/01/3000', $blocktext2);
            $blocktext2 = str_replace(', ', ',', $blocktext2);
            $blocktext2 = str_replace('r$', 'r$ ', $blocktext2);


            $venc_parcelas = PgtoData::getTableVencParc($blocktext2);
            if(!$venc_parcelas){
                $venc_parcelas = PgtoData::getTableVencParc_mixed($blocktext2);
            }

            if(!$venc_parcelas){
                $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'FORMA DE PAGAMENTO','sanitize'=>false]);
                $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'INFORMAÇÕE']);
                $venc_parcelas = PgtoData::getTableVencParc_mixed($blocktext);
                //dd($venc_parcelas,'*');
            }

            if($venc_parcelas['fpgto_n_prestacoes']=='00'){
                $blocktext = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'PARCELAMENTO DO SEGURO','sanitize'=>false]);
                $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'Havendo']);
                $venc_parcelas = PgtoData::getTableVencParc_mixed($blocktext);
                //dd($venc_parcelas,$this->text_ws02);
            }
            //dd($venc_parcelas['fpgto_n_prestacoes'], $blocktext2);
            $parcelas = [];
            foreach($venc_parcelas as $i=>$n){
                if(TextUtility::isNumberFormated($n))$parcelas[]=$n;
                if($n=='01/01/3000')$venc_parcelas[$i] = str_replace('01/01/3000',$this->getDate1aParc($data['fpgto_tipo'],$data),$venc_parcelas[$i]);
            }
            $r = PgtoData::getArrayFromData($venc_parcelas);
            $r = PgtoData::getTableOrderDate($r['datavenc'],$r['valor']);
            $r = PgtoData::makeTable(count($r['0']),$r['0'],$r['1'],1,'');
            $venc_parcelas=$r;

            $premio = PgtoData::getPremioTotalParcela($this->text_ws02, $parcelas,$this->validate_premio_margem);
            //dd($premio);
            $data = $data + $venc_parcelas + ['fpgto_premio_total'=>$premio];
            $r= PgtoData::addFields1($data);
            if($r)$data = $data + $r;
            $data['fpgto_tipo']=$pgto_tipo;
            $data['fpgto_tipo_code']= PgtoData::getPgtoCode($pgto_tipo)[1];

            //dd($parcelas,$blocktext);
            // $premio = PgtoData::getPremioTotalParcela($this->text, $parcelas,$this->validate_premio_margem);
             // dd($parcelas);
             if(!$premio){
                return ['success'=>false,'data'=>$data,'msg'=>'Valor do prêmio não compatível'];
             }

             $n= PgtoData::addFields1($data);
             //dd($n);
             $data = $data + $n + ['fpgto_premio_total'=>$premio];

         }elseif($pgto_tipo=='cartao' || $pgto_tipo=='programa_sempre_presente'){
            //dd(123);
            $blocktext2 = TextUtility::getPartOfStr($text0,['start'=>'DADOS DO PAGAMENTO','end'=>'informacoe']);
            $blocktext2 = TextUtility::getPartOfStr($blocktext2,['end'=>'informacoe']);
            //dd($blocktext2);
            if($blocktext2==''){
                $blocktext2 = TextUtility::getPartOfStr($text0,['start'=>'DADOS DO PAGAMENTO']);
                $blocktext2 = TextUtility::getPartOfStr($blocktext2,['end'=>'para validade']);
                //dd($blocktext2);
            }

            if($blocktext2==''){
                $blocktext2 = TextUtility::getPartOfStr($text0,['start'=>'dados do pagamento']);
                $blocktext2 = TextUtility::getPartOfStr($blocktext2,['end'=>'informacoes']);
                //dd($blocktext2,$text0);
            }

            if(empty($blocktext2)){
                $blocktext2 = TextUtility::getPartOfStr($this->text_ws02,['start'=>'FORMA DE PAGAMENTO']);
                $blocktext2 = TextUtility::getPartOfStr($blocktext2,['end'=>'INFORMAÇÕES']);
                //dd($blocktext2,$this->text_ws02);
            }


            $blocktext2 = str_replace('*QUITADA', '01/01/3000', $blocktext2);
            $blocktext2 = str_replace('*quitada', '01/01/3000', $blocktext2);
            $blocktext2 = str_replace('r$', 'r$ ', $blocktext2);
            //dd($blocktext2);
            $venc_parcelas = PgtoData::getTableVencParc($blocktext2);

            if(!$venc_parcelas){
                $venc_parcelas = PgtoData::getTableVencParc_mixed($blocktext2);
                //dd($venc_parcelas,$blocktext2);
            }

            if(!$venc_parcelas){
                $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'FORMA DE PAGAMENTO','sanitize'=>true]);
                $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'INFORMAÇÕE']);
                $venc_parcelas = PgtoData::getTableVencParc_mixed($blocktext);
                //dd($venc_parcelas,'*');
            }




            //dd($venc_parcelas['fpgto_datavenc_3']);
            //dd($venc_parcelas);
            $parcelas = [];
            foreach($venc_parcelas as $i=>$n){
                if(TextUtility::isNumberFormated($n))$parcelas[]=$n;
                if($n=='01/01/3000')$venc_parcelas[$i] = str_replace('01/01/3000',$this->getDate1aParc($data['fpgto_tipo'],$data),$venc_parcelas[$i]);
            }

            $text_tipo_pag = TextUtility::getPartOfStr($blocktext,['start'=>'FORMA DE PAGAMENTO']);
            $text_tipo_pag = TextUtility::getPartOfStr($text_tipo_pag,['end'=>'PARCELAMENTO']);
            //dd($text_tipo_pag);
            if(strpos($text_tipo_pag,'CARTAO')!==false){
                $venc_parcelas = PgtoData::getArrayFromData($venc_parcelas);
                $venc_parcelas = PgtoData::getTableOrderDate($venc_parcelas['datavenc'],$venc_parcelas['valor']);
                $venc_parcelas = PgtoData::makeTable(count($venc_parcelas['0']),$venc_parcelas['0'],$venc_parcelas['1'],1,'');
            }
            //dd($venc_parcelas);


            $premio = PgtoData::getPremioTotalParcela($this->text, $parcelas,$this->validate_premio_margem);

            $data = $data + $venc_parcelas + ['fpgto_premio_total'=>$premio];
            $r= PgtoData::addFields1($data);
            if($r)$data = $data + $r;
            $data['fpgto_tipo']=$pgto_tipo;
            $data['fpgto_tipo_code']= PgtoData::getPgtoCode($pgto_tipo)[1];

            //dd($parcelas,$blocktext);
             $premio = PgtoData::getPremioTotalParcela($this->text, $parcelas,$this->validate_premio_margem);
             // dd($parcelas);
             if(!$premio){
                $blocktext2 = TextUtility::getPartOfStr($this->text_ws02,['start'=>'TOTAL DO SEGURO']);
                $blocktext2 = TextUtility::getPartOfStr($blocktext2,['end'=>'DADOS']);
                $premio =TextUtility::getSearchText($blocktext2,'SEGURO','number_formated',['side'=>'right']);
                //dd($premio,$blocktext2,$this->text_ws02);
                //return ['success'=>false,'data'=>$data,'msg'=>'Valor do prêmio não compatível'];
             }

             $n= PgtoData::addFields1($data);
             //dd($n);
             $data = $data + $n;
             $data['fpgto_premio_total'] = $premio;



             //dd($data);

         }



        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'premio liqui','end'=>'perda parcial','sanitize'=>true]);
        if(strpos($blocktext,'perda parcial')!==true){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'premio liqui','end'=>'Em atendimento ','sanitize'=>true]);
        }

        if(!$blocktext){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'VALORES DO SEGURO']);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'DADOS DO PAGAMENTO']);
            $blocktext = str_replace('PRÊMIO LÍQUIDO', 'premio liquido', $blocktext);
        }
       // dd($blocktext,$this->text);
        $r=PgtoData::getFielsPremioAdd($blocktext,$data,['tem_juros'=>true]);

        if(!$r['fpgto_iof'] || !$r['fpgto_juros'] || $r['fpgto_juros']=='0,00'){//erro ao capturar os dados
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'premio liqui','end'=>'total do seguro','sanitize'=>true]);
            $r['fpgto_iof']=TextUtility::getSearchText($blocktext,'iof','number_formated',['side'=>'right']);
            $r['fpgto_juros']=TextUtility::getSearchText($blocktext,'juros','number_formated',['side'=>'right']);
            if($r['fpgto_juros']=='0,00')$r['fpgto_juros'] = TextUtility::getSearchText($blocktext,'encargos financeiros','number_formated',['side'=>'right']);
            if(!$r['fpgto_juros'])$r['fpgto_juros']='0,00';
            //dd($r,$blocktext);
        }

        if(!$r['fpgto_iof'] || !$r['fpgto_juros'] || $r['fpgto_juros']=='0,00'){//erro ao capturar os dados
            $blocktext = TextUtility::getPartOfStr($text0, ['start'=>'VALORES DO SEGURO','end'=>'DADOS DO PAGAMENTO','sanitize'=>true]);
            $blocktext = str_replace('r$', 'r$ ', $blocktext);
            $r['fpgto_premio_liquido']=TextUtility::getSearchText($blocktext,'iof valor','number_formated',['side'=>'right']);
            if(strpos($blocktext,'serv')!==false){
                $r['fpgto_premio_liq_serv']=TextUtility::getSearchText($blocktext,$r['fpgto_premio_liquido'],'number_formated',['side'=>'right']);
            }else{
                $r['fpgto_premio_liq_serv']='0,00';
            }
            $r['fpgto_adicional']='0,00';
            $r['fpgto_iof']=TextUtility::getSearchText($blocktext,'preco total','number_formated',['side'=>'left']);
            $r['fpgto_juros']='0,00';
            //dd($r,$blocktext);
        }

        $premio_serv = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'Preço líquido do plano de serviços','end'=>'IOF','sanitize'=>false]);
        $premio_serv = TextUtility::getSearchText($premio_serv,'plano de serviços','number_formated',['side'=>'right']);

        if(empty($premio_serv)){
            $premio_serv = 0;
        }

        //dd($r['fpgto_premio_liquido'],$premio_serv,$this->text_ws02);
        if($r['fpgto_premio_liquido']=='' && $premio_serv<=0){
            $blocktext = TextUtility::getPartOfStr($text0, ['start'=>'VALORES DO SEGURO','end'=>'DADOS DO PAGAMENTO','sanitize'=>true]);
            $blocktext = str_replace('r$', 'r$ ', $blocktext);
            $preco_coberturas= (float)FormatUtility::nDecimal(TextUtility::getSearchText($blocktext,'preco liquido das coberturas','number_formated',['side'=>'right']));
            $preco_servicos = (float)FormatUtility::nDecimal(TextUtility::getSearchText($blocktext,'preco liquido do plano','number_formated',['side'=>'right']));
            $r['fpgto_premio_liquido'] = $preco_coberturas + $preco_servicos;
            $r['fpgto_premio_liquido'] = FormatUtility::numberFormat($r['fpgto_premio_liquido'],2);
            $r['fpgto_premio_liq_serv']='0,00';
        }else{//quer dizer que está separado o valor do premio liquido e premio de serviços.
            $premio_cober = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'Preço líquido das coberturas','end'=>'plano de serviços','sanitize'=>false]);
            $premio_cober = str_replace('R$','R$ ',$premio_cober);
            $premio_cober = TextUtility::getSearchText($premio_cober,'das coberturas','number_formated',['side'=>'right']);

            $r['fpgto_premio_liquido'] = $premio_cober;
            $r['fpgto_premio_liq_serv']=$premio_serv;
            //dd($premio_serv,$this->text_ws02);
        }

        if($r['fpgto_premio_liquido']==''){
            $blocktext = TextUtility::getPartOfStr($text0, ['start'=>'VALOR (R$)','end'=>'preco total','sanitize'=>false]);
            $blocktext = str_replace('r$', 'r$ ', $blocktext);
            $r['fpgto_premio_liquido'] = TextUtility::getSearchText($blocktext,'VALOR','number_formated',['side'=>'right']);
            //dd($blocktext);
        }

        if(empty($r['fpgto_iof'])){
            $blocktext = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'VALORES DO SEGURO','end'=>'DADOS DO PAGAMENTO','sanitize'=>false]);
            $blocktext = str_replace('R$','R$ ',$blocktext);
            $r['fpgto_iof']= TextUtility::getSearchText($blocktext,'IOF','number_formated',['side'=>'right']);
            $r['fpgto_premio_liquido']= TextUtility::getSearchText($blocktext,'das coberturas','number_formated',['side'=>'right']);
            //dd($blocktext);
        }

        if($r['fpgto_iof']==$r['fpgto_premio_liquido']){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'VALORES DO SEGURO','sanitize'=>true]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'DADOS DO PAGAMENTO','sanitize'=>true]);
            $blocktext = str_replace('R$','R$ ',$blocktext);
            //dd($blocktext,$this->text);
            $r['fpgto_premio_liquido'] = TextUtility::getSearchText($blocktext,'das coberturas','number_formated',['side'=>'left']);
        }

        if(empty($r['fpgto_premio_liquido'])){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'VALORES DO SEGURO','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'DADOS DO PAGAMENTO','sanitize'=>false]);
            $blocktext = str_replace('R$','R$ ',$blocktext);
            //dd($blocktext,$this->text);
            $r['fpgto_premio_liquido'] = TextUtility::getSearchText($blocktext,'coberturas','number_formated',['side'=>'left']);
        }

        if(empty($r['fpgto_premio_liquido'])){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'VALORES DO SEGURO','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'DADOS DO PAGAMENTO','sanitize'=>false]);

            if(empty($blocktext)){
                $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Obrigatoriedade de contratação','sanitize'=>false]);
                $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'DADOS DO PAGAMENTO','sanitize'=>false]);
            }
            $blocktext = str_replace('R$','R$ ',$blocktext);
            //dd($blocktext,$this->text);
            $r['fpgto_premio_liquido'] = TextUtility::getSearchText($blocktext,'coberturas','number_formated',['side'=>'right']);
            $r['fpgto_iof'] = TextUtility::getSearchText($blocktext,'IOF','number_formated',['side'=>'right']);
        }

        if(empty($r['fpgto_premio_liquido'])){
            $blocktext = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'DADOS DO PAGAMENTO','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'PREÇO TOTAL','sanitize'=>false]);
            $blocktext = str_replace('R$','R$ ',$blocktext);
            //dd($blocktext,$this->text);
            $r['fpgto_premio_liquido'] = TextUtility::getSearchText($blocktext,'coberturas','number_formated',['side'=>'right']);
            $r['fpgto_iof'] = TextUtility::getSearchText($blocktext,'IOF','number_formated',['side'=>'right']);
        }
        $data = $data + $r ;
       // dd($data);

        if($pgto_tipo=='programa_sempre_presente'){
             $data['fpgto_tipo']='';
        }

        return $data;
    }

     /**
     * Retorna os dados do premio
     */
    public function getPremio3($data){
        //premio_liquido
        //premio_servico
        //premio_custo
        //premio_adicional
        //premio_iof???
        //premio_total

      //define que não deve ser alterado os valores já capturados dentro da função PgtoData::validateAll(..., $change_data)
      //este comando está desativando o ajuste do valor adicional que é retirado do prêmio total para bater a conta
      //até momento, esta stiuação não ocorre nas apólices da porta
      $this->pgto_data_change_data = false;

      $text0 = FormatUtility::sanitizeAllText($this->text);

         //Forma de Pagamento
         $n = $this->text_no_space;
         $line_info_pgto = $n;
         //dd($line_info_pgto);
         $pgto_tipo= PgtoData::getPgtoTipo($this->text,$text0);

         if(!$pgto_tipo){
             if(strpos($line_info_pgto,'Banco:')!==false){// Débito em conta
                $pgto_tipo = 'debito';
             }elseif(strpos($line_info_pgto,'Fatura')!==false || strpos($line_info_pgto,'Cartão de Crédito')!==false){// Cartão de Crédito
                $pgto_tipo = 'cartao';
             }elseif(strpos($line_info_pgto,'vista')!==false || strpos($line_info_pgto,'À vista')!==false || strpos($line_info_pgto,'Carnê')!==false){// Boleto
                 $pgto_tipo = 'boleto';
             }
         }

         if(!$pgto_tipo){
            $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'FORMA DE PAGAMENTO']);
            $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'Seguro sem']);
            $n = TextUtility::getSearchText($blocktext,'de contratação','value',['side'=>'right']);
            //dd($n,$blocktext);

            if($n=='UNICA'){
                $pgto_tipo = 'boleto';
            }
         }

         if($pgto_tipo==false){
             $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'FORMA DE PAGAMENTO']);
             $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'Parcela']);

             if(strpos($blocktext,'100% Programa Sempre Presente')!==false){
                 $pgto_tipo = 'programa_sempre_presente';
             }

         }

         if($pgto_tipo=='debito' || $pgto_tipo=='cartao' || $pgto_tipo=='boleto' || $pgto_tipo=='carne'){
            $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'TOTAL DO SEGURO','end'=>'Código de registro']);


            if(!$blocktext){
                $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'TOTAL DO SEGURO']);
                $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'Seu seguro']);
            }


            $text0 = FormatUtility::sanitizeAllText($this->text);
            if(!$blocktext){
                $blocktext = TextUtility::getPartOfStr($text0,['start'=>'dados do pagamento']);
                $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'descontos']);
            }

            if(strpos($blocktext,'Parabrisa')!=false){
                $blocktext = TextUtility::getPartOfStr($blocktext,['start'=>'FORMA DE PAGAMENTO']);

                if(strpos($blocktext,'DESCONTOS')!=false){
                    $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'DESCONTOS']);
                }else{
                    $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'QUESTIONÁRIO']);
                }
            }

           // dd($data);
            //dd($blocktext);
            $blocktext = str_replace('*QUITADA', '01/01/3000', $blocktext);
            $blocktext = str_replace('*quitada', '01/01/3000', $blocktext);
            $venc_parcelas = PgtoData::getTableVencParc($blocktext);
            //dd($venc_parcelas,$blocktext);
            if(!$venc_parcelas){
                $venc_parcelas = PgtoData::getTableVencParc_mixed($blocktext);
                //dd($venc_parcelas,'*');
            }
           // dd($venc_parcelas,'*');

            if(!$venc_parcelas){
                $blocktext = TextUtility::getPartOfStr($text0,['start'=>'forma de pagamento']);
                $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'questionario ']);
                $venc_parcelas = PgtoData::getTableVencParc_mixed($blocktext);
                //dd($venc_parcelas,$blocktext);
            }

            if(empty($venc_parcelas)){
                $blocktext = TextUtility::getPartOfStr($text0,['start'=>'forma de pagamento']);
                $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'parcela  valor ']);
                $venc_parcelas = PgtoData::getTableVencParc_mixed($blocktext);
            }

            if($venc_parcelas['fpgto_n_prestacoes']=='00'){
                $blocktext = TextUtility::getPartOfStr($text0,['start'=>'forma de pagamento']);
                $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'parcela  valor ']);
                $venc_parcelas = PgtoData::getTableVencParc_mixed($blocktext);
                //dd($venc_parcelas);
            }


            if($venc_parcelas['fpgto_n_prestacoes']=='00'){
                $blocktext = TextUtility::getPartOfStr($text0,['start'=>'forma de pagamento']);
                //dd($blocktext);
                $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'dados dos']);
                $venc_parcelas = PgtoData::getTableVencParc_mixed($blocktext);
                //dd($venc_parcelas);
            }


            $data['fpgto_tipo']=$pgto_tipo;
            $data['fpgto_tipo_code']= PgtoData::getPgtoCode($pgto_tipo)[1];
            $data['fpgto_n_prestacoes'] = $venc_parcelas['fpgto_n_prestacoes'];


            //dd($pgto_tipo,$venc_parcelas,$blocktext);
            if($venc_parcelas['fpgto_n_prestacoes']=='01' && $venc_parcelas['fpgto_datavenc_1']!=''){// parcela única a vista                //dd($venc_parcelas,$blocktext);

                $parcelas = [];
                $parcelas[] = $venc_parcelas['fpgto_valorparc_1'];

            }else{
                //dd($data);
                $parcelas = [];
                $x=1;
                foreach($venc_parcelas as $i=>$n){

                    if(TextUtility::isNumberFormated($n))$parcelas[]=$n;
                    if($i=='fpgto_valorparc_1' && $n='01/01/3000'){
                        $venc_parcelas[$i] = str_replace('01/01/3000',$this->getDate1aParc($data['fpgto_tipo'],$data),$venc_parcelas[$i]);
                    }elseif($i!='fpgto_valorparc_1' && $n='01/01/3000'){
                        $venc_parcelas[$i] = str_replace('01/01/3000',$this->getDateUaParc($data['fpgto_tipo'],$venc_parcelas),$venc_parcelas[$i]);
                    }
                    $x++;
                }
            }


            $premio = PgtoData::getPremioTotalParcela($this->text, $parcelas,$this->validate_premio_margem);

            $data = $data + $venc_parcelas + ['fpgto_premio_total'=>$premio];
            $r= PgtoData::addFields1($data);
            if($r)$data = $data + $r;


            //dd($parcelas,$blocktext);
             $premio = PgtoData::getPremioTotalParcela($this->text, $parcelas,$this->validate_premio_margem);
             // dd($parcelas);

             if(!$premio){
                $premio = TextUtility::getSearchText($blocktext,'TOTAL DO SEGURO','number_formated',['side'=>'right']);
             }

             if(!$premio){
                $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'Encargos financeiros']);
                $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'VALORES ']);
                $premio = TextUtility::getSearchText($blocktext,'TOTAL DO SEGUR','number_formated',['side'=>'right']);
             }

             //dd($premio,$blocktext);
             if(!$premio){
                return ['success'=>false,'data'=>$data,'msg'=>'Valor do prêmio não compatível','code'=>'READ05'];
             }

             $n= PgtoData::addFields1($data);

             $data = $data + $n + ['fpgto_premio_total'=>$premio];

             if(!$data['fpgto_premio_total']){
                $data['fpgto_premio_total'] = $premio;
             }


         }


        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'premio liqui','end'=>'perda parcial','sanitize'=>true]);
        if(strpos($blocktext,'perda parcial')!==true){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'premio liqui','end'=>'Em atendimento ','sanitize'=>true]);
        }

        if(!$blocktext){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'PRÊMIO LÍQUIDO']);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Em atendimento']);
            $blocktext = str_replace('PRÊMIO LÍQUIDO', 'premio liquido', $blocktext);
        }

       // dd($blocktext);
        $r=PgtoData::getFielsPremioAdd($blocktext,$data,['tem_juros'=>true]);
        $r['fpgto_adicional']='0,00'; //esta linha é zerada para que somente os códigos abaixo capturem corretamente o valor adicional
        //dd($r);

        if(!$r['fpgto_iof'] || !$r['fpgto_juros'] || $r['fpgto_juros']=='0,00'){//erro ao capturar os dados
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'premio liqui','end'=>'total do seguro','sanitize'=>true]);
            $r['fpgto_iof']=TextUtility::getSearchText($blocktext,'iof','number_formated',['side'=>'right']);
            $r['fpgto_juros']=TextUtility::getSearchText($blocktext,'juros','number_formated',['side'=>'right']);
            if($r['fpgto_juros']=='0,00')$r['fpgto_juros'] = TextUtility::getSearchText($blocktext,'encargos financeiros','number_formated',['side'=>'right']);
            if(!$r['fpgto_juros'])$r['fpgto_juros']='0,00';

        }

        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'encargos financeiros','end'=>'iof','sanitize'=>true]);
        $r2=TextUtility::getSearchText($blocktext,'encargos financeiros','number_formated',['side'=>'right']);
        if($r2=='0,00'){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Coberturas adicionais, serviços e benefícios','end'=>'iof','sanitize'=>true]);
            $r2=TextUtility::getSearchText($blocktext,'encargos financeiros','number_formated',['side'=>'right']);
            if($r2=='')$r2='0,00';
        }


        if($r2<>'0,00'){
            $r['fpgto_adicional']=$r2;
        }

        if(empty($r['fpgto_iof'])){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'iof total do seguro','sanitize'=>true]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'coberturas','sanitize'=>true]);
            $r['fpgto_iof'] = TextUtility::getSearchText($blocktext,$data['fpgto_premio_total'],'number_formated',['side'=>'left']);
            $r['fpgto_adicional']='0,00';
            //dd($r['fpgto_iof'],$blocktext);
        }
        $data = $data + $r ;




        if($pgto_tipo=='programa_sempre_presente'){
             $data['fpgto_tipo']='';
        }
        //dd($data);
        return $data;
    }

    private static function clearText($text){
        $text =  preg_replace('/[^\d\-]/', '',$text);
        return $text;
    }

}
