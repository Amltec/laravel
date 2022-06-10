<?php
namespace App\ProcessRobot\cad_apolice\Classes\Insurers;
use App\Utilities\TextUtility;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;


/**
 * Classe trait de funções funções gerais para leitura de apólices pdf de qualquer ramo da Seguradora Sompo
 * Deve ser incorporada a partir da uma classe de um ramo específico, como a classe ex: App\ProcessRobot\cad_apolice\automovel\zurichClass.php
 */
trait zurichInsurer{

    //método de inicialização
    public function initInsurer(){
        $this->pdf_engine = 'ait_tessrct'; //nome do interpretador de pdf (valores em  \App\Utilities\FilesUtility::readPDF)

    }

      /**
     * Configuração para o padrão do número do quiver
     */
    function numQuiverConfig(){
        return [
            'ex_num'=>'0096065',
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

        //dd($this->text);
        //verifica se é endosso
        $tmp = trim(TextUtility::getSearchText($this->text,'endosso:','number',['max_words'=>1]));
        $data['data_type'] = trim($tmp,'0')?'endosso':'apolice';
        if($data['data_type']=='endosso')return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];


        //*** dados do corretor ***
        $corretor_text = TextUtility::getPartOfStr($this->text, ['start'=>'Corretor:','end'=>'A Zurich Brasil Seguros','remove'=>'A Zurich Brasil Seguros']);
        $corretor_text = str_replace(['Corretor:','Inspetor: SUSEP:'], [''], $corretor_text);
        //dd($corretor_text);

        $nome_corretor = preg_replace("/[0-99]/", "", $corretor_text);
        if(strpos($nome_corretor,'Mod.')!==false){
            $nome_corretor = explode('Mod.', $nome_corretor);
            $nome_corretor = $nome_corretor[0];
        }
        $data['corretor_nome'] = trim($nome_corretor);

        $n = TextUtility::getPartOfStr($this->text, ['start'=>'Susep']);
        $n = substr($n,0,50);//limita os caracteres
        $corretor_susep = TextUtility::getSearchText($n,'SUSEP','number',['side'=>'right']);

        $data['corretor_susep'] = TextUtility::getSearchText($corretor_susep,'',function($v){
            $v=trim($v);
            if(ValidateUtility::isNumberStr($v)){
                    return $v= ltrim(str_replace('.', '', $v),0);
            }
        });


        //*** dados da apólice ***
        $apolice_text = TextUtility::getPartOfStr($this->text, ['start'=>'Emissão da apólice','end'=>'Nome completo:']);
       // dd($apolice_text);
        $data['seguradora_doc'] = TextUtility::getSearchText($this->text,'Zurich Minas Brasil Seguros S/A - CNPJ: ','value');
        //dd($data['seguradora_doc']);
        $data['proposta_num'] = TextUtility::getSearchText($apolice_text,'Proposta:','number',['side'=>'right']);

        $text0 = FormatUtility::sanitizeAllText($this->text);
        $text0 = str_replace('Аpdlice:', 'Apolice:', $text0);

        $block_text = TextUtility::getPartOfStr($text0, ['start'=>'apolice:']);
        //dd($block_text);
        $data['apolice_num'] = TextUtility::getSearchText($block_text,'apolice:','number',['side'=>'right']);;
        //$data['apolice_num_quiver'] = ltrim($data['apolice_num'],0);

        $data['apolice_re_num'] = '';//não tem número da apólice renovação

        $n = TextUtility::getPartOfStr($this->text,['start'=>'24hs do dia','end'=>'Té']);
        $n = TextUtility::getDateExtenso($n,'datebr');
        //dd($n);
        $data['inicio_vigencia'] = $n;

        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'mino:','sanitize'=>true]);
        $n = TextUtility::getPartOfStr($blocktext, ['end'=>'dados do segurado']);
        if(!$n)$n = TextUtility::getPartOfStr($blocktext, ['end'=>'dados do proponente']);
        $n = trim(str_replace(['termino: 24hs do dia','dados do segurado','dados do proponente'], '', $n));
        $data['termino_vigencia'] = TextUtility::getDateExtenso($n,'datebr');
        //dd($data['termino_vigencia'], $n);


        $data['data_emissao'] = TextUtility::getSearchText(FormatUtility::sanitizeAllText($this->text),'data de emissao:','datebr');


        //*** dados do segurado ***
        $segurado_text = TextUtility::getPartOfStr($this->text, ['start'=>'Dados do Segurado','end'=>'Endereço']);
        if(!$segurado_text){
            $segurado_text = TextUtility::getPartOfStr($this->text, ['start'=>'Dados do Proponente','end'=>'Atividade']);

        }
        //dd($segurado_text);
        $nome_segurado = '';
        if(strpos($segurado_text,'CPF')!==false){
            //$n = TextUtility::getPartOfStr($segurado_text,['start'=>'Nome completo:','end'=>'CPF','remove'=>'CPF']);
            $n = TextUtility::getSearchText($segurado_text,'CPF:','value',['side'=>'right']);
            $nome_segurado = TextUtility::getPartOfStr($this->text, ['start'=>'Nome completo:','end'=>'CPF:','remove'=>['Nome completo:','CPF:']]);

        }elseif(strpos($segurado_text,'CNPJ')!==false){
            //$n = TextUtility::getPartOfStr($segurado_text,['start'=>'Nome completo:','end'=>'CNPJ','remove'=>'CNPJ']);
            $n = TextUtility::getSearchText($segurado_text,'CNPJ:','value',['side'=>'right']);
            $nome_segurado = TextUtility::getPartOfStr($this->text, ['start'=>'Social:','end'=>'CNPJ:','remove'=>['Social:','CNPJ:']]);
            //dd($nome_segurado);
        }

        if($nome_segurado==''){
             $n = TextUtility::getSearchText($segurado_text,'CNPJ:','value',['side'=>'right']);
             $nome_segurado = TextUtility::getPartOfStr($segurado_text, ['start'=>'Social:','end'=>'CNPJ:','remove'=>['Social:','CNPJ:']]);
        }

        if($nome_segurado==''){
            $n = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'Ocupação','end'=>'Código']);
            $nome_segurado = $this->getX1(['start'=>'Ocupação:','return_type'=>'next'],$n);//Nome;
            $n = TextUtility::getSearchText($n,'','document',['side'=>'right']);
       }

        $data['segurado_nome'] = trim($nome_segurado);
        $data['segurado_doc']= $n;
        $n=$data['segurado_doc'];
        $data['tipo_pessoa'] = ValidateUtility::isCPF($n) ? 'FISICA' : (ValidateUtility::isCNPJ($n)?'JURIDICA':'');

        return ['success'=>true,'data'=>$data];
    }

    public function getDados2(){ //usado no residencial
        $data=[];

        //*** captura o tipo da apólice - para verificar se é do tipo automovel ***
        $data['apolice_prod_ref'] = $this->checkRamo();

        if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];

        //dd($this->text);
        //verifica se é endosso
        $tmp = trim(TextUtility::getSearchText($this->text,'endosso:','number',['max_words'=>1]));
        $data['data_type'] = trim($tmp,'0')?'endosso':'apolice';
        if($data['data_type']=='endosso')return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];


        //*** dados do corretor ***
        $corretor_text = TextUtility::getPartOfStr($this->text, ['start'=>'Corretor:','end'=>'inspetor']);
       // $corretor_text = str_replace(['Corretor:','Inspetor: SUSEP:'], [''], $corretor_text);


        $nome_corretor = preg_replace("/[0-99]/", "", $corretor_text);
        $nome_corretor = TextUtility::getPartOfStr($nome_corretor, ['start'=>'Corretor:','end'=>'Código']);
        if(empty($nome_corretor)){
            $nome_corretor = TextUtility::getPartOfStr($this->text, ['start'=>'Corretor:']);
            $nome_corretor = TextUtility::getPartOfStr($nome_corretor, ['end'=>'Código']);
        }
        //dd($nome_corretor,$this->text);
        $nome_corretor = str_replace(['Corretor:','Código'], [''], $nome_corretor);
        $data['corretor_nome'] = $nome_corretor;
        $data['corretor_nome'] = trim($nome_corretor);

        $corretor_susep = TextUtility::getSearchText($corretor_text,'Susep:','number',['side'=>'right']);
        //dd($corretor_susep,$corretor_text);
        $data['corretor_susep'] = $corretor_susep;

        if(empty($data['corretor_susep'])){
            $corretor_susep = TextUtility::getPartOfStr($this->text, ['start'=>'Susep:']);
            $corretor_susep = TextUtility::getPartOfStr($corretor_susep, ['end'=>'Em']);
            $data['corretor_susep'] = TextUtility::getSearchText( $corretor_susep,'Susep:','number',['side'=>'right']);
        }

        //*** dados da apólice ***
        $apolice_text = TextUtility::getPartOfStr($this->text, ['start'=>'Emissão da apólice','end'=>'Nome completo:']);
       // dd($apolice_text);
        $data['seguradora_doc'] = TextUtility::getSearchText($this->text,'Seguros S/A - CNPJ','cnpj',['side'=>'right']);
        //dd($data['seguradora_doc']);
        $data['proposta_num'] = TextUtility::getSearchText($apolice_text,'Proposta:','number',['side'=>'right']);

        $text0 = FormatUtility::sanitizeAllText($this->text);
        $text0 = str_replace(['Аpdlice:','apolice :'], 'Apolice:', $text0);

        $block_text = TextUtility::getPartOfStr($text0, ['start'=>'apolice:']);
        //dd($block_text,$text0);
        $data['apolice_num'] = TextUtility::getSearchText($block_text,'apolice:','number',['side'=>'right']);
        //$data['apolice_num_quiver'] = ltrim($data['apolice_num'],0);

        $data['apolice_re_num'] = '';//não tem número da apólice renovação

        $n = TextUtility::getPartOfStr($this->text,['start'=>'Início vigência do seguro:','end'=>'Té']);
        $n = TextUtility::getSearchText($n,'do seguro:','datebr',['side'=>'right']);
        $data['inicio_vigencia'] = $n;

        $n = TextUtility::getPartOfStr($this->text,['start'=>'Início vigência do seguro:','end'=>'Dados do']);
        $data['termino_vigencia'] = TextUtility::getSearchText($n,'Término','datebr',['side'=>'right']);
        //dd($data['termino_vigencia'], $n);


        $data['data_emissao'] = TextUtility::getSearchText($this->text,'Data Emissão:','datebr');


        //*** dados do segurado ***
        $segurado_text = TextUtility::getPartOfStr($this->text, ['start'=>'Dados do Segurado','end'=>'Endereço:']);
        $nome_segurado = TextUtility::getPartOfStr($segurado_text, ['start'=>'Social:','end'=>'CNPJ','remove'=>['Social:','CNPJ']]);
        $data['segurado_nome'] = trim($nome_segurado);
        $data['segurado_doc']= TextUtility::getSearchText($segurado_text,'CPF:','document',['side'=>'right']);
        $n=$data['segurado_doc'];$data['tipo_pessoa'] = ValidateUtility::isCPF($n) ? 'FISICA' : (ValidateUtility::isCNPJ($n)?'JURIDICA':'');

        return ['success'=>true,'data'=>$data];
    }

    /**
     * Retorna os dados do premio
     */
    public function getPremio1($data){

         //*** dados do pagamento ***
         $pgto_text = TextUtility::getPartOfStr($this->text, ['start'=>'Plano de Pagamento do Prêmio','end'=>'Corretor:']);
         $data['fpgto_tipo']=PgtoData::getPgtoTipo($pgto_text);

         $data['fpgto_tipo_code']=PgtoData::getPgtoCode( $data['fpgto_tipo'] )[1];
         $data['fpgto_premio_total']=PgtoData::getPremioTotal($this->text);

         $premi_iof = '';
         $premio_liquido = '';

         $r=PgtoData::getTableVencParc($pgto_text);

         if($r==false){
             $r = PgtoData::getTableVencParc_mixed($pgto_text);
         }


         if(!$r){//não conseguiu criar a tabela de valores,

             //tenta novamente desconsiderando as datas
             $r = PgtoData::getTableVencParc_noDate($pgto_text, $this->getDate1aParc($data['fpgto_tipo'],$data),null,null,null,true,$this->validate_premio_margem);
             //dd($r,$pgto_text);
             //verifcar se a soma dos valores bate com o premio total
             $n=PgtoData::getArrayFromData($r)['valor'];
             $sum=0;
             foreach($n as $v){
                 $sum+=(float)FormatUtility::nDecimal($v);
             }
             $sum= FormatUtility::numberFormat($sum);


             //em teste para corrigir problema no premio
             if(PgtoData::checkPremioTotal($sum,$data['fpgto_premio_total'], $this->validate_premio_margem)==false){//valor incorreto
                $pgto_text = TextUtility::getPartOfStr($this->text, ['start'=>'PAGINA 2']);
                if(empty($pgto_text)){
                    $pgto_text = TextUtility::getPartOfStr($this->text, ['start'=>'PÁGINA 2']);
                }
                $pgto_text = explode(' ',$pgto_text);


                //dd($pgto_text);

                $premi_iof = $pgto_text[count($pgto_text)-2];
                $premio_liquido = $pgto_text[count($pgto_text)-5];
                $premio_total = $pgto_text[count($pgto_text)-1];

                if($sum==$premio_total){
                    $data['fpgto_premio_total'] = $premio_total;
                }else{
                    $premi_iof = '';
                    $premio_liquido = '';
                }
             }


         }


         $r = PgtoData::getArrayFromData($r);
         $r = PgtoData::getTableOrderDate($r['datavenc'],$r['valor']);
         $r = PgtoData::makeTable(count($r['0']),$r['0'],$r['1'],1,'');
         //dd($r);

         if($r)$data = $data + $r;

         $data = $data + PgtoData::addFields1($data);
         //dd($data);
         /*
         $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'premio liquido','end'=>'desconto','sanitize'=>true,'remove'=>['premio liquido','r$']]);
         $premio_liq = explode(' ',$blocktext)[0];//espere que o primeiro valor seja do premio liquido
         if(!TextUtility::isNumberFormated($premio_liq))$premio_liq=false;
          */

         $premio_liq='';
         if(!$premio_liq){
             $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'premio liquido com','end'=>'premio total','sanitize'=>true]);
             $cober_adic = TextUtility::getSearchText($blocktext,'coberturas adicionais','number_formated',['side'=>'right']);
             $equip_acess = TextUtility::getSearchText($blocktext,'equipamentos e acessorios','number_formated',['side'=>'right']);
             $blocktext = str_replace(['coberturas adicionais r$ ' . $cober_adic,'equipamentos e acessorios r$ ' . $equip_acess], '', $blocktext);

             $n = TextUtility::getSearchText($blocktext,'com desconto r$','value',['side'=>'right']);
             $n1= '';
             if(strpos($blocktext,'juros premio app')!==false){
                  $n1 = 'ok';
             }

             //dd($n1,$n, $blocktext);
             if($n=='premio' || $n1=='ok'){
                 $premio_liq = TextUtility::getSearchText($blocktext,'premio app r$','number_formated',['side'=>'right']);
             }else{
                 $premio_liq = false;
             }
         }

         $n = TextUtility::getSearchText($blocktext,'iof','value',['side'=>'right']);
         if($n=='coberturas'){
             $iof = $data['fpgto_iof'] = TextUtility::getSearchText($blocktext,'premio total','number_formated',['side'=>'left']);
         }else{
             $iof = TextUtility::getSearchText($blocktext,'iof','number_formated',['side'=>'right','max_words'=>2]);
             if(!$n)$iof=false;
         }
         if(!$iof)$iof='auto';//não achou o iof, portanto calcula


         $n  = (float)FormatUtility::nDecimal($premio_liq);
         $n1 = (float)FormatUtility::nDecimal($iof);

         if($n<$n1){
             $premio_liq = TextUtility::getSearchText($blocktext,'emissao premio app','number_formated',['side'=>'right']);
             //dd($premio_liq);
         }
         if(strpos($blocktext,'carta verde')!==false){
            $text_liq = TextUtility::getPartOfStr($this->text, ['start'=>'Prêmio Auto','end'=>'PCusto de Emissão','sanitize'=>false]);
            $premio_liq = TextUtility::getSearchText($text_liq,'Prêmio APP','number_formated',['side'=>'left']);
         }
         //dd($text_liq);
         if($premio_liq==''){
             $premio_liq = TextUtility::getSearchText($blocktext,'premio liquido','number_formated',['side'=>'right']);
         }

         $text_juros = TextUtility::getPartOfStr($blocktext, ['start'=>'juros','end'=>'iof','sanitize'=>true]);
         $juros = TextUtility::getSearchText($text_juros,'juros','number_formated',['side'=>'right']);

         //dd($juros,$premio_liq,$blocktext,$this->text);
         if($juros!=''){
             $r=PgtoData::getFielsPremioAdd($blocktext,$data,['tem_juros'=>true,'iof'=>$iof,'premio_liquido'=>$premio_liq]);
         }else{
             $r=PgtoData::getFielsPremioAdd($blocktext,$data,['tem_juros'=>false,'iof'=>$iof,'premio_liquido'=>$premio_liq]);
         }
         $data = $data + $r;

         if($premio_liquido!=''){
            $data['fpgto_premio_liquido'] = $premio_liquido;
        }

        if($premi_iof!=''){
            $data['fpgto_iof'] = $premi_iof;
        }

        return $data;
    }

    public function getPremio2($data){//usado no residencial

        //*** dados do pagamento ***
        $pgto_text = TextUtility::getPartOfStr($this->text, ['start'=>'Taxa de juros','end'=>'Cláusulas']);
        $pgto_text = str_replace('R$',' R$ ',$pgto_text);
        $data['fpgto_tipo']=PgtoData::getPgtoTipo($pgto_text);

        //dd($data,$pgto_text);
        $data['fpgto_tipo_code']=PgtoData::getPgtoCode( $data['fpgto_tipo'] )[1];
        $data['fpgto_premio_total']=PgtoData::getPremioTotal($this->text);


        $r=PgtoData::getTableVencParc($pgto_text);

        if($r==false){
            $r = PgtoData::getTableVencParc_mixed($pgto_text);
        }


        $r = PgtoData::getArrayFromData($r);
        $r = PgtoData::getTableOrderDate($r['datavenc'],$r['valor']);
        $r = PgtoData::makeTable(count($r['0']),$r['0'],$r['1'],1,'');
        //dd($r);
        if($r)$data = $data + $r;

        $data = $data + PgtoData::addFields1($data);


        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Demonstrativo do Prêmio','end'=>'Forma de Pagamento','sanitize'=>false]);
        $r=PgtoData::getFielsPremioAdd($blocktext,$data);

         //dd($blocktext,$this->text);

       // $data['fpgto_premio_total'] =

        $data = $data + $r;

        if($data['fpgto_premio_liquido']==''){
            $data['fpgto_premio_liquido'] = TextUtility::getSearchText($blocktext,'Prêmio Líquido','number_formated',['side'=>'right']);
        }

       return $data;
   }
}
