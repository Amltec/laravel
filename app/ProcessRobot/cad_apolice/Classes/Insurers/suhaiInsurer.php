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
trait suhaiInsurer{

    //método de inicialização
    public function initInsurer(){

         $this->pdf_engine = 'ait_xpdfr'; //nome do interpretador de pdf (valores em  \App\Utilities\FilesUtility::readPDF)
         //margem de diferença de centavos entre o cálculo do iof com o iof capturado em $data para o PgtoData::validateAll(). Obs: por padrão deveria ser igual, mas na prática existe diferença de centávos entre as apólices.
         //$this->validate_iof_margem=0.11;
    }



      /**
     * Configuração para o padrão do número do quiver
     */
    function numQuiverConfig(){
        return [
            'ex_num'=>'1003106649733',
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

        $data=[];
        //*** captura o tipo da apólice - para verificar se é do tipo automovel ***
        $data['apolice_prod_ref'] = $this->checkRamo();
        if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];
       // dd($data['apolice_prod_ref']);
        $n= $this->text;//verifica se é frota
        //dd($n);
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
        $corretor_text = TextUtility::getPartOfStr($this->text, ['start'=>'CORRETOR','end'=>'ESTIPULANTE']);
        $data['corretor_susep'] = TextUtility::getSearchText($corretor_text,'SUSEP','number',['side'=>'right']);

        $n = TextUtility::getPartOfStr($corretor_text, ['start'=>'Nome:','end'=>'Email']);
        if(strpos($n,'SUSEP')!=false){
            $n = TextUtility::getPartOfStr($corretor_text, ['start'=>'Nome:','end'=>'SUSEP']);
        }
        $n=str_replace(['Nome:','Email','SUSEP'],'',$n);
        $data['corretor_nome'] = trim($n);
        //dd($data['corretor_susep'],$data['corretor_nome'],$this->text);

        //*** dados da apólice ***
        $data['seguradora_doc'] = TextUtility::getSearchText($this->text,'SUHAI SEGURADORA','cnpj',['sanitize'=>false]);
        $apolice_text = TextUtility::getPartOfStr($this->text, ['start'=>'Nº:','end'=>'Data de Vi']);
        $data['apolice_num'] = TextUtility::getSearchText($apolice_text,'Nº:','number',['side'=>'right']);

        //atualizado para ser capturado pela função numQuiverConfig()
        //$data['apolice_num_quiver'] = ltrim($data['apolice_num'],0);
        //$data['apolice_num_quiver'] = str_replace('-','',$data['apolice_num_quiver']);
        //$data['apolice_num'] = TextUtility::getSearchText($apolice_text,'MS AUTO','number',['side'=>'right']);

        $n = TextUtility::getPartOfStr($this->text, ['start'=>'Proposta:','end'=>'Filial']);
        $n = TextUtility::getSearchText($n,'Proposta:','number',['side'=>'right']);
        $data['proposta_num'] = $n;

        $n = TextUtility::getPartOfStr($this->text, ['start'=>'Apólice Anterior','end'=>'Proprietário']);
        $data['apolice_re_num'] = TextUtility::getSearchText($n,'Apólice Anterior','numberstr');

        $data['inicio_vigencia'] = TextUtility::getSearchText($this->text,'Das 24:00','datebr',['side'=>'right']);
        $data['termino_vigencia'] = TextUtility::getSearchText($this->text,'até às 24','datebr',['side'=>'right']);

        $n = TextUtility::getPartOfStr($this->text, ['start'=>'As Condições Gerais']);
        $n = TextUtility::getPartOfStr($n, ['end'=>'SUHAI SEGURADORA']);

        if(empty($n)){
            $n = TextUtility::getPartOfStr($this->text, ['start'=>'Ouvidoria']);
            $n = TextUtility::getPartOfStr($n, ['end'=>'Diretor']);
        }
        //dd($n,$this->text);
        $n = TextUtility::getDateExtenso($n,'datebr');
        $data['data_emissao'] = $n;

        //*** dados do segurado ***
        $segurado_text = TextUtility::getPartOfStr($this->text, ['start'=>'PROPONENTE','end'=>'Endereço:']);
        $data['segurado_nome'] = TextUtility::getPartOfStr($segurado_text,['start'=>'Nome:','end'=>'CPF/','remove'=>['Nome:','CPF/']]);
        if(strpos($data['segurado_nome'],'Email')!=false){
            $data['segurado_nome'] = TextUtility::getPartOfStr($segurado_text,['start'=>'Nome:','end'=>'Email','remove'=>['Nome:','Email']]);
        }
        $data['segurado_nome'] = trim($data['segurado_nome']);
        $data['segurado_doc']= TextUtility::getSearchText($segurado_text,'CPF/','document');
        $n=$data['segurado_doc'];$data['tipo_pessoa'] = ValidateUtility::isCPF($n) ? 'FISICA' : (ValidateUtility::isCNPJ($n)?'JURIDICA':'');
        return ['success'=>true,'data'=>$data];
    }


    /**
     * Retorna os dados do premio
     */
    public function getPremio_tipo1($data){

        //*** dados do pagamento ***
        $pgto_text = TextUtility::getPartOfStr($this->text, ['start'=>'Prêmio Líquido:']);
        $pgto_text = TextUtility::getPartOfStr($pgto_text, ['end'=>'Atendimento']);
        $pgto_text = str_replace('Parcelas','',$pgto_text);
        //dd($pgto_text,$this->text);
        $data['fpgto_tipo']='boleto';
        $data['fpgto_tipo_code']='10';

        //$valores = TextUtility::getSearchText($pgto_text,'','number_formated',['limit'=>false]);
        $datavenc = TextUtility::getSearchText($pgto_text,'','datebr',['limit'=>false]);

        if(empty($datavenc)){
            $pgto_text = TextUtility::getPartOfStr($this->text, ['start'=>'PARCELAMENTO DO SEGURO']);
            $pgto_text = TextUtility::getPartOfStr($pgto_text, ['end'=>'Prêmio']);
            $pgto_text = str_replace('Parcelas','',$pgto_text);
            $datavenc = TextUtility::getSearchText($pgto_text,'','datebr',['limit'=>false]);
        }
        //dd($datavenc);
        $parcelas = count($datavenc);

        //$total_parc = substr_count ( $pgto_text, 'parcela:' );

        $valores = [];
        for($i=0;$i<$parcelas;$i++){
            $valores[$i] = TextUtility::getSearchText($pgto_text, ($i+1).'a parcela:','number_formated',['side'=>'right']);
        }
        $r = PgtoData::makeTable($parcelas,$datavenc,$valores,$inicia_parcela=1,$data_add=null);

        if($r)$data = $data + $r;

        $data = $data + PgtoData::addFields1($data);

        $text_premio = TextUtility::getPartOfStr($this->text, ['start'=>'PARCELAMENTO DO SEGURO']);
        $text_premio = TextUtility::getPartOfStr($text_premio, ['end'=>'Ouvidoria']);
        $data['fpgto_premio_total'] = TextUtility::getSearchText($text_premio,'Prêmio Total:','number_formated',['side'=>'right']);
        $data['fpgto_premio_liquido'] = TextUtility::getSearchText($text_premio,'Líquido Total','number_formated',['side'=>'right']);
        $data['fpgto_premio_liq_serv'] = '0,00';
        $data['fpgto_desc'] = '0,00';
        $data['fpgto_iof'] = TextUtility::getSearchText($text_premio,'IOF:','number_formated',['side'=>'right']);
        $data['fpgto_adicional'] = TextUtility::getSearchText($text_premio,'Ad.Frac.:','number_formated',['side'=>'right']);
        $data['fpgto_custo'] = '0,00';
        $data['fpgto_juros'] = '0,00';
        $data['fpgto_juros_md'] = '0,00';
       // dd($this->text);

        return $data;
    }

}

