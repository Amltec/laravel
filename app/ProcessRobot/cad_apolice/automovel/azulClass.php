<?php
namespace App\ProcessRobot\cad_apolice\automovel;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\ProcessRobot\cad_apolice\ProcessAutomovelClass;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;
use App\ProcessRobot\cad_apolice\Classes\Insurers\azulInsurer;


/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 19:45 17/02/2020
 */
class azulClass extends ProcessAutomovelClass{
    use azulInsurer;

    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt=[]){
        $this->process_opt = $opt;
        $this->splitThisText($text);
        $this->text_ws02 = \App\Utilities\FilesUtility::readPDF($opt['path'],['engine'=>'ws02','pass'=>$this->process_opt['pass']])['text'];

        $r = $this->processTipo01();
    	return $this->ValidateData($r);
    }



    private function processTipo01(){
        $text_ap = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'DE SEGURO','sanitize'=>true]);
        $text_ap = TextUtility::getPartOfStr($text_ap, ['end'=>'24h','sanitize'=>true]);
        $data['apolice_num'] = TextUtility::getSearchText($text_ap,'SEGURO','value',['side'=>'right']);//numero da apólice

        if(strlen( $data['apolice_num'])<17){//Utiliza o OCR - ait_tessrct
            $data = $this->getDados1();
            if(!$data['success'])return $data;
            $data = $data['data'];
            $data['segurado_proprietario_veiculo_1'] = 'sim';// segurado é proprietario do veiculo
            $data['prop_nome_1'] = $data['segurado_nome'];// nome proprietario do veiculo

            $text_vei = TextUtility::getPartOfStr($this->text, ['start'=>'Modelo:']);
            $text_vei = TextUtility::getPartOfStr($text_vei, ['end'=>'EM CASO']);

            if(empty( $text_vei)){
                $text_vei = TextUtility::getPartOfStr($this->text, ['start'=>'Modelo:']);
                $text_vei = TextUtility::getPartOfStr($text_vei, ['end'=>'Garantia']);
                $text_vei = str_replace('Garantia','EM CASO',$text_vei);
            }
            //dd($text_vei);
            $text_vei = str_replace(['BICOMBUST¬VEL',],['BICOMBUSTIVEL'],$text_vei);
            $text_cep = TextUtility::getSearchText($text_vei,'Pernoite:','cep',['side'=>'right']);
            if(!TextUtility::isCepFormated($text_cep)){
                $data['segurado_pernoite_cep_1'] ='';
            }
            $data['segurado_pernoite_cep_1'] = $text_cep;//Cep Pernoite

            $text_vei_clean = $text_vei;//bloco de texto dos dados do veiculo sem os números formatados
            $del1 =  TextUtility::getSearchText($text_vei,'','number_formated',['limit'=>false]);
            for($x=0;$x<count($del1)-1;$x++){
                $text_vei_clean = str_replace($del1[$x],'',$text_vei_clean);
            }
            //dd($text_vei_clean);
            if(strpos($text_vei_clean, '0Km: NÃO')!==false){
                $data['veiculo_zero_1'] ='n';
                $data['veiculo_data_saida_1']= '';
            }else{
                $data['veiculo_zero_1'] ='s';
                $data['veiculo_data_saida_1']= TextUtility::getSearchText($text_vei_clean,'','datebr',['side'=>'right']);;
            }
            $data['veiculo_nf_1']='';//não tem esse dado

            //marca
            //dd($text0);
            $data['veiculo_fab_1'] = '';//não tem essa informação
            $data['veiculo_fab_code_1'] = '';//não tem essa informação
            $data['veiculo_tipo_1'] = 'a';
            //combustível
            $n = TextUtility::getSearchText($text_vei_clean,'Combustível:','value',['side'=>'right']);
            $n=$this->getData_combustivel($n);
            $data['veiculo_combustivel_1'] = $n[0]??'';
            $data['veiculo_combustivel_code_1'] = $n[1]??'';

            //placa
            $blocktext = TextUtility::getSearchText($text_vei_clean,'Placa:','value',['side'=>'right']);
            if(strpos($blocktext,'INFORMAR')!==false){
                $data['veiculo_placa_1'] = 'nd zero';
            }else{
                $data['veiculo_placa_1'] = TextUtility::getSearchText($blocktext,'/','value',['side'=>'right']);;
            }

            //ano fab/modelo
            $n = TextUtility::getPartOfStr($text_vei_clean, ['start'=>'Ano Fab']);
            $n = TextUtility::getPartOfStr($n, ['end'=>'Placa:']);

            $data['veiculo_ano_fab_1'] = substr(TextUtility::getSearchText($n,'Fab','number',['side'=>'right']),0,2);
            $data['veiculo_ano_modelo_1'] = substr(TextUtility::getSearchText($n,'Mod','number',['side'=>'right']),0,2);

            if((int)$data['veiculo_ano_fab_1']>=95){//!IMPORTANTE: considera veículos do ano 1995 para cima, e é válido na lógica até 2095
                $data['veiculo_ano_fab_1'] = '19'.$data['veiculo_ano_fab_1'];
            }else{//maior que 2000
                $data['veiculo_ano_fab_1'] = '20'.$data['veiculo_ano_fab_1'];//!IMPORTANTE: esta lógica é válida somente para ano de veículos que estão entre 2000 e 2099, fora deste intervalo está sugeito a erro
            }

            if((int)$data['veiculo_ano_modelo_1']>=95){//!IMPORTANTE: considera veículos do ano 1995 para cima, e é válido na lógica até 2095
                $data['veiculo_ano_modelo_1'] = '19'.$data['veiculo_ano_modelo_1'];
            }else{//maior que 2000
                $data['veiculo_ano_modelo_1'] = '20'.$data['veiculo_ano_modelo_1'];//!IMPORTANTE: esta lógica é válida somente para ano de veículos que estão entre 2000 e 2099, fora deste intervalo está sugeito a erro
            }

            //código ci
            $data['veiculo_ci_1'] = TextUtility::getSearchText($text_vei_clean,'EM CASO','value',['side'=>'left']);
            // dd($data['veiculo_ci_1'],$text_vei_clean);
            $ci =  $data['veiculo_ci_1'];
            $data['veiculo_ci_1'] = str_replace('.','',$data['veiculo_ci_1']);

            $n = TextUtility::getPartOfStr($text_vei_clean, ['start'=>'Chassi:']);
            $n = TextUtility::getPartOfStr($n, ['end'=>'Renavam:']);
            $data['veiculo_chassi_1'] = $this->getData_chassi($n);

            //dd($data['veiculo_chassi_1'],$text_vei_clean );
            $mod_vei = TextUtility::getPartOfStr($text_vei_clean, ['start'=>'Modelo:']);
            $mod_vei = trim(str_replace(['Castor'],'Casco',$mod_vei));
            $mod_vei = TextUtility::getPartOfStr($mod_vei, ['end'=>'Casco']);
            $mod_vei = trim(str_replace(['Modelo:','Casco'],'',$mod_vei));
            $data['veiculo_modelo_1'] =$mod_vei;//modelo veículo

            if(strpos($data['veiculo_modelo_1'],'Cobertura B')!==false){
                $mod_vei2 = trim($this->getX1(['start'=>'Ano Fab','return_type'=>'prev'],$this->text_ws02));
                $data['veiculo_modelo_1'] =$mod_vei2;//modelo veículo
            }

            //dd($data['veiculo_modelo_1']);
            $data['veiculo_fab_1']='';//não tem o fabricante
            $data['veiculo_fab_code_1']='';//não tem o fabricante


            $n = TextUtility::getSearchText($this->text,'TARIFA:','value',['side'=>'right']);//Codigo Fipe
            $n = str_replace(' ', '', $n);
            $n = ltrim(ltrim(strtolower($n),'o'),'0');
            $n = explode('/', $n);
            $data['veiculo_cod_fipe_1'] = $n[0]??'';// Codigo Fipe

            $n = TextUtility::getPartOfStr($text_vei_clean, ['start'=>'nus:']);
            if(empty($n)){
                $n = TextUtility::getPartOfStr($text_vei_clean, ['start'=>'Bonus']);
            }
            $data['veiculo_classe_1'] =TextUtility::getSearchText($n,'nus','number',['side'=>'right']);

            //dd($data['veiculo_classe_1'],$text_vei_clean,$this->text);
            $text_vei_clean = str_replace('FassageToss','Passageiros:',$text_vei_clean);
            $n = TextUtility::getPartOfStr($text_vei_clean, ['start'=>'Passageiros:']);
            $n = TextUtility::getPartOfStr($n, ['end'=>'Renavam:']);
            $n = TextUtility::getSearchText($n,'','number',['limit'=>false]);
           // dd($n,$data['veiculo_chassi_1'],$text_vei_clean);
            $data['veiculo_n_lotacao_1'] =$n[0]??'';
            $data['veiculo_n_portas_1'] =$n[1]??'';



        }else{// utiliza o Java - WS02
            $data = $this->getDados();
            if(!$data['success'])return $data;
            $data = $data['data'];
            $data['segurado_proprietario_veiculo_1'] = 'sim';// segurado é proprietario do veiculo
            $data['prop_nome_1'] = $data['segurado_nome'];// nome proprietario do veiculo

            $text_vei = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'OUVIDORIA:']);
            $text_vei = TextUtility::getPartOfStr($text_vei, ['end'=>'EM CASO']);

            if(empty( $text_vei)){
                $text_vei = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'OUVIDORIA:']);
                $text_vei = TextUtility::getPartOfStr($text_vei, ['end'=>'RCFV DANOS']);
                $text_vei = str_replace('RCFV DANOS','EM CASO',$text_vei);
            }
            //dd($text_vei,$this->text_ws02);
            $text_vei = str_replace(['BICOMBUST¬VEL',],['BICOMBUSTIVEL'],$text_vei);
            $text_cep = TextUtility::getSearchText($text_vei,'','cep',['side'=>'right']);
            if(!TextUtility::isCepFormated($text_cep)){
                $data['segurado_pernoite_cep_1'] ='';
            }
            $data['segurado_pernoite_cep_1'] = $text_cep;//Cep Pernoite

            $text_vei_clean = $text_vei;//bloco de texto dos dados do veiculo sem os números formatados
            $del1 =  TextUtility::getSearchText($text_vei,'','number_formated',['limit'=>false]);
            for($x=0;$x<count($del1)-1;$x++){
                $text_vei_clean = str_replace($del1[$x],'',$text_vei_clean);
            }
            $zero = $this->getX1(['start'=>$data['segurado_pernoite_cep_1'],'return_type'=>'prev'],$text_vei_clean);
            if(strpos($zero, 'N¡O')!==false){
                $data['veiculo_zero_1'] ='n';
                $data['veiculo_data_saida_1']= '';
            }else{
                $data['veiculo_zero_1'] ='s';
                $data['veiculo_data_saida_1']= TextUtility::getSearchText($zero,'','datebr',['side'=>'right']);;
            }
            $data['veiculo_nf_1']='';//não tem esse dado

            //marca
            //dd($text0);
            $data['veiculo_fab_1'] = '';//não tem essa informação
            $data['veiculo_fab_code_1'] = '';//não tem essa informação

            //combustível
            $n = TextUtility::getSearchText($text_vei_clean,$data['segurado_pernoite_cep_1'],'value',['side'=>'right']);
            $n=$this->getData_combustivel($n);
            $data['veiculo_combustivel_1'] = $n[0]??'';
            $data['veiculo_combustivel_code_1'] = $n[1]??'';

            //placa
            $blocktext = $this->getX1(['start'=>$data['segurado_pernoite_cep_1'],'return_type'=>'prev3'],$text_vei_clean);
            if(strpos($blocktext,'INFORMAR')!==false){
                $data['veiculo_placa_1'] = 'nd zero';
            }else{
                $data['veiculo_placa_1'] = TextUtility::getSearchText($blocktext,'/','value',['side'=>'right']);;
            }

            //ano fab/modelo
            $n=TextUtility::getSearchText($blocktext,'','number',['limit'=>false]);
            //dd($n,$text0,$this->text);
            $data['veiculo_ano_fab_1'] = $n[0]??'';
            $data['veiculo_ano_modelo_1'] = $n[1]??'';

            if((int)$data['veiculo_ano_fab_1']>=95){//!IMPORTANTE: considera veículos do ano 1995 para cima, e é válido na lógica até 2095
                $data['veiculo_ano_fab_1'] = '19'.$data['veiculo_ano_fab_1'];
            }else{//maior que 2000
                $data['veiculo_ano_fab_1'] = '20'.$data['veiculo_ano_fab_1'];//!IMPORTANTE: esta lógica é válida somente para ano de veículos que estão entre 2000 e 2099, fora deste intervalo está sugeito a erro
            }

            if((int)$data['veiculo_ano_modelo_1']>=95){//!IMPORTANTE: considera veículos do ano 1995 para cima, e é válido na lógica até 2095
                $data['veiculo_ano_modelo_1'] = '19'.$data['veiculo_ano_modelo_1'];
            }else{//maior que 2000
                $data['veiculo_ano_modelo_1'] = '20'.$data['veiculo_ano_modelo_1'];//!IMPORTANTE: esta lógica é válida somente para ano de veículos que estão entre 2000 e 2099, fora deste intervalo está sugeito a erro
            }


            //código ci
            $data['veiculo_ci_1'] = TextUtility::getSearchText($text_vei_clean,'EM CASO','value',['side'=>'left']);
            //dd($data['veiculo_ci_1'],$text_vei_clean);
            $ci =  $data['veiculo_ci_1'];
            $data['veiculo_ci_1'] = str_replace('.','',$data['veiculo_ci_1']);

            $n = trim($this->getX1(['start'=>$data['segurado_pernoite_cep_1'],'return_type'=>'prev2'],$text_vei_clean));
            $n = explode(' ',$n);
            //dd($n[0],$text_vei_clean);
            $data['veiculo_chassi_1'] = $this->getData_chassi($n[0]);

            $mod_vei = trim($this->getX1(['start'=>$data['veiculo_placa_1'],'return_type'=>'prev'],$text_vei_clean));
            $data['veiculo_modelo_1'] =$mod_vei;//modelo veículo

            $data['veiculo_fab_1']='';//não tem o fabricante
            $data['veiculo_fab_code_1']='';//não tem o fabricante
            $data['veiculo_tipo_1'] = 'a';

            $n = TextUtility::getSearchText($text_vei_clean,$ci,'value',['side'=>'left']);;//Codigo Fipe
            $n = str_replace(' ', '', $n);
            $n = ltrim(ltrim(strtolower($n),'o'),'0');
            $n = explode('/', $n);
            $data['veiculo_cod_fipe_1'] = $n[0]??'';// Codigo Fipe

            $block_text = str_replace([$data['veiculo_combustivel_1'],' BICOMBUSTIVEL'],'',$text_vei_clean);
            $data['veiculo_classe_1'] =TextUtility::getSearchText($block_text,$data['segurado_pernoite_cep_1'],'number',['side'=>'right']);

            if(is_numeric($data['veiculo_classe_1'])==false){
                $block_text = TextUtility::getPartOfStr($this->text, ['start'=>'OUVIDORIA:'],['sanitize'=>false]);
                $block_text = TextUtility::getPartOfStr($block_text, ['end'=>'Uso:'],['sanitize'=>false]);
                //dd( $block_text,$this->text );
                $block_text = str_replace(['bonus:','Bonus:','bonus :'],'bonus',$block_text);
                $data['veiculo_classe_1'] =TextUtility::getSearchText($block_text,'Bônus:','number',['side'=>'right']);
            }

            $n = TextUtility::getSearchText($text_vei_clean,$data['veiculo_chassi_1'],'number',['limit'=>false]);
            //dd($n,$data['veiculo_chassi_1'],$text_vei_clean);
            $data['veiculo_n_lotacao_1'] =$n[0];
            $data['veiculo_n_portas_1'] =$n[1];

        }



        $data = $this->getPremio($data);
        //dd($data);
        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }


/**
 * Retorna a matriz de data vencimentos e valores considerando apenas a quantidade de datas e de valores encontrados, e considera a ordem apenas do primeiro registro e dos demais não importa
 * @param $blocktext - enviar o mais exato possível o bloco de texto das parcelas para extração
 */
    private function getThis_formaPgto_tableVencParc($blocktext){
        $datavenc=[];
        $valor=[];

        //procura todas as datas
        TextUtility::execFncInStr($blocktext,10,function($v) use(&$datavenc){
            if(ValidateUtility::isDate($v) && strlen(trim($v))==10)$datavenc[]=$v;
        });

        //procura todos os valores
        foreach(explode(' ',$blocktext) as $n){
            if($this->isNumberFormated($n))$valor[]=$n;
        }

        if(count($datavenc) != count($valor))return false;

        return PgtoData::makeTable(count($datavenc),$datavenc,$valor);
    }


 /**
 * Retorna a matriz de datas ordenadas em ordem crescente
 * @param $arr_data - array de datas
 */
    private function orderArrayDate($arr_data){
        foreach($arr_data as $data){
            $timestamps[] = strtotime(str_replace('/', '-', $data));
        }

        // ordena
        sort($timestamps);

        // converte timestamp para datas
        foreach($timestamps as $timestamp){
            $datavencx[] = date('d/m/Y', $timestamp);

        }
        return $datavencx;
    }
}


