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
trait mapfreInsurer{

    //método de inicialização
    public function initInsurer(){
        $this->pdf_engine = 'ws02'; //nome do interpretador de pdf (valores em  \App\Utilities\FilesUtility::readPDF)

        //Validações da classe filha. Segue a mesma regra da var $fields_rules da classe de produto
        //Campos obrigatórios adicionais para personalização na classe filha (obs: neste caso segue as regras existentes no validate da classe filha, ex ProcessAutomovelClass.php)
        $this->validate_required = ['veiculo_cod_fipe'=>false];//sintaxe field=>boolean
    }

    /**
     * Configuração para o padrão do número do quiver
     */
    function numQuiverConfig(){
        return [
            'ex_num'=>'7156000368731',
            'len'=>5,
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

        //*** captura o tipo da apólice - para verificar se é do tipo automovel ***
        $data['apolice_prod_ref'] = $this->checkRamo();
        if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];

        //dd($data['apolice_prod_ref']);
        if($data['apolice_prod_ref']=='Ramo:014' || $data['apolice_prod_ref']=='Ramo:018'){
            $tmp = trim(TextUtility::getSearchText($this->text,'endosso:','number'));
            if($tmp!='00000')return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];
            $data['data_type']='apolice';
            //dd($tmp);
        }else{
            //verifica se é endosso
            $tmp = trim(TextUtility::getSearchText($this->text,'endosso:','number',['max_words'=>1]));
            $data['data_type'] = $tmp?'endosso':'apolice';
            if($data['data_type']=='endosso')return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];
        }



        //*** dados do corretor ***
        $corretor_text = TextUtility::getPartOfStr($this->text, ['start'=>'dados do corretor','end'=>'DADOS DO SEGURADO']);
        $n = TextUtility::getPartOfStr($corretor_text,['start'=>'nome:','end'=>'CPF/CNPJ','remove'=>['nome:','CPF/CNPJ']]);
        //verifica se no início do nome do corretor vem um número/código e neste caso desconsidera
        if(is_numeric(explode(' ',$n)[0])){
            $n=explode(' ',$n);
            unset($n[0]);
            $n = trim(trim(join(' ',$n),'-'));
        }
        $data['corretor_nome'] = $n;
        $data['corretor_susep'] = ltrim(TextUtility::getSearchText($corretor_text,'susep:','value'),'0');


        //*** dados da apólice ***
        $apolice_text = TextUtility::getPartOfStr($this->text, ['start'=>'DADOS DA SEGURADORA','end'=>'Endereço']);
        $data['seguradora_doc'] = TextUtility::getSearchText($apolice_text,'','cnpj');

        $apolice_text = TextUtility::getPartOfStr($this->text, ['start'=>'DADOS GERAIS','end'=>'DADOS DA SEGURADORA']);
        $data['proposta_num'] = TextUtility::getSearchText($apolice_text,'Nº Proposta:','value');

        $n=TextUtility::getSearchText($apolice_text,'Nº Apólice:','value');
        $data['apolice_num'] = $n;

         //atualizado para ser capturado pela função numQuiverConfig()
        //$data['apolice_num_quiver'] = ltrim(substr($n,5),'0');


        $data['apolice_re_num'] = TextUtility::getSearchText($apolice_text,'Renova apólice Nº:','value');
        $data['inicio_vigencia'] = TextUtility::getSearchText($apolice_text,'Vigência início 24h do dia:','value');


        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'24h do dia:','sanitize'=>true]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Data e hora']);
        $n=[];
        TextUtility::execFncInStr($blocktext,10,function($v) use(&$n){
            if(ValidateUtility::isDate($v) && strlen(trim($v))==10){
                $n[]=$v;
            }
        });
        $data['termino_vigencia'] = $n[1];
        //dd($data['termino_vigencia'], $blocktext);
        //dd($blocktext,$n);



        $n=TextUtility::getPartOfStr($this->text, ['start'=>'SAO PAULO,']);
        $data['data_emissao'] = TextUtility::getDateExtenso($n,'datebr');

        if($data['data_emissao']==''){////usado no residencial
            $n=TextUtility::getPartOfStr($this->text, ['start'=>'emitem e assinam']);
            $data['data_emissao'] = TextUtility::getDateExtenso($n,'datebr');
        }
        //*** dados do segurado ***
        $segurado_text = TextUtility::getPartOfStr($this->text, ['start'=>'DADOS DO SEGURADO','end'=>'QUESTIONÁRIO']);
        $data['segurado_nome'] = TextUtility::getPartOfStr($segurado_text,['start'=>'Nome:','end'=>'Tipo de pessoa:','remove'=>['Nome:','Tipo de pessoa:']]);
        $n = TextUtility::getSearchText($segurado_text,'CPF:','cpf');
        if(!$n)$n = TextUtility::getSearchText($segurado_text,'CNPJ:','cnpj');
            $data['segurado_doc'] = $n;

        if($data['segurado_doc']==''){//usado no residencial
            $n = TextUtility::getSearchText($segurado_text,'CPF/CNPJ','cpf');
            if(!$n)$n = TextUtility::getSearchText($segurado_text,'CPF/CNPJ','cnpj');

        }
        $data['segurado_doc'] = $n;
        $data['tipo_pessoa'] = ValidateUtility::isCPF($n) ? 'FISICA' : (ValidateUtility::isCNPJ($n)?'JURIDICA':'');

        return ['success'=>true,'data'=>$data];
    }


    /**
     * Retorna os dados do premio
     */
    public function getPremio($data){

       //*** dados do pagamento ***
        $pgto_text = TextUtility::getPartOfStr($this->text, ['start'=>'PAGAMENTO DO PRÊMIO - VALORES EM R$','end'=>'OBSERVAÇÕES']);
            $n = TextUtility::getPartOfStr($pgto_text, ['end'=>'seguro em reais']);
            if($n)$pgto_text=$n;
        $pgto_text = str_replace('FCA + DEBITO','DEBITO',$pgto_text);

        $data['fpgto_tipo']=PgtoData::getPgtoTipo($pgto_text);
        $data['fpgto_tipo_code']=PgtoData::getPgtoCode( $data['fpgto_tipo'] )[1];
        if(empty($data['fpgto_tipo'])){
            $pgto_text_forma = TextUtility::getPartOfStr($pgto_text, ['start'=>'Forma:','end'=>'parcela:']);

            $data['fpgto_tipo']=PgtoData::getPgtoTipo($pgto_text_forma);
            $data['fpgto_tipo_code']=PgtoData::getPgtoCode( $data['fpgto_tipo'] )[1];
        }

        $data['fpgto_premio_total']=PgtoData::getPremioTotal($this->text);
        if($data['fpgto_premio_total']==''){//usado no residencial
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'demonstrativo de premio','end'=>'Vencimento','sanitize'=>true]);
            $blocktext = str_replace('premio:', 'premio total:', $blocktext);
            $data['fpgto_premio_total']=PgtoData::getPremioTotal($blocktext);
            //dd($data['fpgto_premio_total'],$blocktext);
        }
        if($data['fpgto_premio_total']=='0,00'){
            $n = TextUtility::getPartOfStr($this->text, ['start'=>'DEMONSTRATIVO DE PRÊMIO DA APÓLICE','end'=>'contribuições ','sanitize'=>false]);
            //dd($n);
            $data['fpgto_premio_total']=TextUtility::getSearchText($n,'Prêmio:','number_formated',['side'=>'right']);
        }
        $data['fpgto_n_prestacoes']=TextUtility::getSearchText($pgto_text,'Nº de parcela:','value');

        //primeira parcela
        $data['fpgto_datavenc_1']=TextUtility::getSearchText($pgto_text,'Vencimento da 1ª parcela:','value');
        $data['fpgto_valorparc_1']=TextUtility::getSearchText($pgto_text,'Valor da 1ª parcela:','value');
        //dd( $data['fpgto_valorparc_1'],$pgto_text);
        //aqui irá pegar da 2ª parcela em diante
        $pgto_text = TextUtility::getPartOfStr($this->text, ['start'=>'VENCIMENTO DAS PARCELAS - VALORES EM R$','end'=>'Seguro em reais não sujeito']);
        $pgto_text = PgtoData::addSpaceDateText($pgto_text)['text'];    // separa as datas se estiverem grudadas
       // dd($pgto_text);

        if(empty($pgto_text)){
            $pgto_text = TextUtility::getPartOfStr($this->text, ['start'=>'PAGAMENTO DO PRÊMIO - VALORES']);
            $pgto_text = TextUtility::getPartOfStr($pgto_text, ['end'=>'seguro']);
        }
        //dd($pgto_text,$this->text);

        if(strpos($pgto_text,'Vencimento')===false){
           // dd(strpos($pgto_text,'Vencimento'));
            //$pgto_text = TextUtility::getPartOfStr($this->text, ['start'=>'PAGAMENTO DO PRÊMIO - VALORES']);
            //$pgto_text = TextUtility::getPartOfStr($pgto_text, ['end'=>'Este seguro']);
        }
        //dd($pgto_text);
       // dd($pgto_text,$this->text);
        //tira os caracteres númericos de 1 digito apenas, pois estão entre as datas e parcelas e estão interferindo na leitura
        $str='';
        foreach(explode(' ',$pgto_text) as $n){
            if(strlen($n)==1 && is_numeric($n))continue;
            $str.=$n.' ';
        }
        $pgto_text=$str;
        $pgto_text= TextUtility::getPartOfStr($pgto_text, ['start'=>'Parcela Data Valor 02']);
        //dd($pgto_text);

        $r=PgtoData::getTableVencParc($pgto_text,$pgto_text,2); //,2 - nomes dos campos iniciando a partir da segunda parcela
        //dd($r);
        if($r){//tem parcelas
            $data = $data + $r;
        }else{
            $r=PgtoData::getTableVencParc($pgto_text,$pgto_text,2);
        }
        $data = $data + PgtoData::addFields1($data);
        if($data['fpgto_premio_total']=='' || $data['fpgto_premio_total']=='0'){
            //retorna a data, mesmo faltando as demais informações, pois será validado no ...
            return $data;
        }
       // dd($data);
        $adicional = '0,00';
        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'demonstrativo de premio','end'=>'Vencimento','sanitize'=>true]);
        $block_text = FormatUtility::sanitizeAllText($this->text);
        $block_text = TextUtility::getPartOfStr($block_text, ['start'=>'bonus'],['sanitize'=>true]);
        if(strpos($blocktext, 'encargos')!==false){
            //dd(123);
            $adicional = TextUtility::getSearchText($block_text,'encargos','number_formated',['side'=>'right']);
            if($adicional==''){//usado no residencial
                 $adicional = TextUtility::getSearchText($blocktext,'encargos','number_formated',['side'=>'right']);
            }
            // dd($adicional);
            $r=PgtoData::getFielsPremioAdd($blocktext,$data,['tem_juros'=>false,'adicional'=>$adicional]);
        }else{
            $r=PgtoData::getFielsPremioAdd($blocktext,$data,['tem_juros'=>false]);
        }

        if($r['fpgto_premio_liquido']=='0,00' || $r['fpgto_premio_liquido']==''){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'sobre demonstrativo de','end'=>'Vencimento','sanitize'=>true]);
            if($blocktext==''){
                $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'DEMONSTRATIVO DE PRÊMIO DA APÓLICE','sanitize'=>false]);
                $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'PAGAMENTO']);
            }
            $r=PgtoData::getFielsPremioAdd($blocktext,$data,['tem_juros'=>false]);

            if(empty($r['fpgto_premio_liquido'])){
                $data['fpgto_premio_liquido']=TextUtility::getSearchText($blocktext,'Prêmio líquido:','value',['side'=>'right']);
                //dd(123);
            }
            //dd($r,$blocktext);
        }

        if($r['fpgto_adicional']==''){//usado no residencial
            $data['fpgto_adicional']='0,00';
        }else{
            $data['fpgto_adicional']=$adicional;
        }
        // dd($data);
        $data = $data + $r;
        // dd($data);

        return $data;
    }

    private static function clearText($text){
        $text =  preg_replace('/[^\d\-]/', '',$text);
        return $text;
    }

}
