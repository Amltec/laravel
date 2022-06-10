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

        $data = $this->getDados();
        if(!$data['success'])return $data;
        $data = $data['data'];


        $data['segurado_proprietario_veiculo_1'] = 'sim';// segurado é proprietario do veiculo
        $data['prop_nome_1'] = $data['segurado_nome'];// nome proprietario do veiculo

        //$n = $this->getX1(['start'=>'Pernoite:','return_type'=>'next']);//cep pernoite
        $text0 = strtolower(FormatUtility::removeAcents($this->text));
        $text_cep = TextUtility::getPartOfStr($text0, ['start'=>'Pernoite:']);
        $text_cep = TextUtility::getPartOfStr($text_cep, ['end'=>'Equipamento']);
        //dd($text_cep);
        $text_cep = str_replace('clesse de boius:', '', $text_cep);


        $n = TextUtility::getSearchText($text_cep,'Pernoite:','value',['side'=>'right']);
        $n = str_replace(['O','o'], '0', $n); //7407o-070

        if(!TextUtility::isCepFormated($n)){

            $text_cep = str_replace(['O','o'], '0', $text_cep);
            //dd($text_cep);
            $n = TextUtility::getSearchText($text_cep,'pern0ite:','cep',['side'=>'right']);
        }
        $data['segurado_pernoite_cep_1'] = $n;
        //dd($n,$data['segurado_pernoite_cep_1']);

        //Dados da Seguradora
        $data['seguradora_doc'] = $this->getX1(['start'=>'Nr. Código:','return_type'=>'prev','remove'=>'CNPJ: ']);//cep pernoite
        if(empty($data['seguradora_doc'])){
            $text_cnpj = TextUtility::getPartOfStr($this->text, ['start'=>'OUVIDORIA:']);
            $text_cnpj = TextUtility::getPartOfStr($text_cnpj, ['end'=>'Nr.']);
            //dd($text_cnpj);
            $data['seguradora_doc'] = TextUtility::getSearchText($text_cnpj,'CNPJ:','cnpj',['side'=>'right']);
        }
        //Dados do Veículo
        //$data = $data + $this->getData_veiculo($this->text,$data);
        $text0 = strtolower(FormatUtility::removeAcents($this->text));
        //dd( $text0);
        $text_vei = TextUtility::getPartOfStr($text0, ['start'=>'cor predominante']);
        $text_vei = TextUtility::getPartOfStr($text_vei, ['end'=>'Farol e Lanterna']);

        if(strpos($text_vei, 'km:')===false){
             $text_vei = TextUtility::getPartOfStr($text0, ['start'=>'Renavam:']);
             $text_vei = TextUtility::getPartOfStr($text_vei, ['end'=>'Retrovisor:']);
             //dd($text_vei);
        }
        // dd($text_vei);
        if(strpos($text_vei, 'km:')===false){
             $text_vei = TextUtility::getPartOfStr($text0, ['start'=>'Modelo:']);
             $text_vei = TextUtility::getPartOfStr($text_vei, ['end'=>'Categoria T']);
             //dd($text_vei);
        }
        //dd($text_vei);
        $text_vei = str_replace(['okm','0Km:','Okm:','okm:','0km:','OKm:'], '0km', $text_vei);



        if(strpos($text_vei, '0km')!==false){
             $zero = TextUtility::getSearchText($text_vei,'0km','value',['side'=>'right']);
             //dd($zero);
             if($zero=='nao' || $zero=='não' || $zero=='NÃO' || $zero=='NAO'){
                 $data['veiculo_zero_1'] ='n';
             }elseif($zero=='sim'){
                 $data['veiculo_zero_1'] ='s';
             }else{
                 $data['veiculo_zero_1'] ='';
             }

         }else{

             $data['veiculo_zero_1'] ='123';
         }
        //dd($zero);
        //dd($data['veiculo_zero']);
        $data_saida = TextUtility::getSearchText($text_vei,'predominante','datebr',['side'=>'right']);
        $data['veiculo_data_saida_1']= FormatUtility::fixYearDateBr($data_saida, $data['data_emissao']);
        $data['veiculo_nf_1']='';//não tem esse dado

        //marca
       // dd($text0);
        $data['veiculo_fab_1'] = $this->getData_fab($text0);
        $data['veiculo_fab_code_1'] = $this->quiverVeiCode($data['veiculo_fab_1']);

        //combustível
        $n=$this->getData_combustivel($text0);
        $data['veiculo_combustivel_1'] = $n[0]??'';
        $data['veiculo_combustivel_code_1'] = $n[1]??'';

        //placa
        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Acessórios:']);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'chassi']);
        if(strpos($blocktext,'Placa: A INFORMAR')!==false){
            $data['veiculo_placa_1'] = 'nd zero';
        }else{
            $data['veiculo_placa_1'] = $this->getData_placa($blocktext);
        }
        //dd($blocktext,$data['veiculo_placa_1']);
        if($data['veiculo_placa_1']==''){
            $text_vei = TextUtility::getPartOfStr($this->text, ['start'=>'Acessórios:']);
            $text_vei = TextUtility::getPartOfStr($text_vei, ['end'=>'Tipo de']);
            $text_vei = str_replace('/', ' ', $text_vei);
            $data['veiculo_placa_1'] = TextUtility::getSearchText($text_vei,'Vidro','value',['side'=>'left']);
        }

        if($data['veiculo_placa_1']!='' && strpos($this->text_ws02, $data['veiculo_placa_1'])===false){
            $blocktext = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'OUVIDORIA']);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'EM CASO']);
            $blocktext = str_replace('/', ' ', $blocktext);
            //$blocktext = FormatUtility::sanitizeAllText($blocktext);
            $placa = substr($blocktext, 400,-800);
            $data['veiculo_placa_1'] = $this->getData_placa($placa);
            //dd($blocktext, $placa, $data['veiculo_placa_1']);
        }

        if(!$data['veiculo_placa_1']){
            $n= TextUtility::getSearchText($this->text_ait_tessrct, 'placa', function($v){
                $v = $this->getData_placa($v);
                if($v)return $v;
            },['side'=>'right','max_words'=>'10']);
            $data['veiculo_placa_1']=$n;
        }
       // dd($data['veiculo_placa_1']);
        if($data['veiculo_placa_1']==''){

          $n = TextUtility::getSearchText($this->text,'Placa:','value',['side'=>'right']);
          if($n=='A'){
              $data['veiculo_placa_1'] = 'nd zero';
          }

        }
        //dd($data['veiculo_placa_1']);
        if($data['veiculo_placa_1']=='INFORMAR' || $data['veiculo_placa_1']=='AUT')$data['veiculo_placa_1']='nd zero';//quer dizer que não tem placa

        if($data['veiculo_placa_1']==''){
            $n = $this->getX1(['start'=>'Placa:','end'=>'Vidro Para','sanitize'=>true,'split'=>false ]);//Codigo Fipe
            $n = str_replace(' ', '', $n);
            $data['veiculo_placa_1'] = $this->getData_placa($n);
        }

        if($data['veiculo_placa_1']==''){
            $n = $this->getX1(['start'=>'Cobertura Básica:','end'=>'Tipo de','sanitize'=>false,'split'=>false ],$this->text_ws02);//Codigo Fipe
            if(empty($n)) $n = $this->getX1(['start'=>'Blindado:','end'=>'Categoria Tarifaria:','sanitize'=>false,'split'=>false ],$this->text_ws02);//Codigo Fipe
            $n = explode('/',$n);
            $n = substr($n[1]??'',0,7);
            //dd($n,$this->text_ws02);
            $data['veiculo_placa_1'] = $this->getData_placa($n);
        }

        if($data['veiculo_placa_1']==''){
            $n = $this->getX1(['start'=>'Placa:','end'=>'Tipo','sanitize'=>true,'split'=>false ]);//Codigo Fipe
            $n = explode('/',$n);
            $n = trim(str_replace('tipo','',$n[1]));
            $data['veiculo_placa_1'] = $n;
        }


        //dd($data['veiculo_placa_1'] );
        //ano fab/modelo
        $text0 = str_replace('Fab.: O','Fab.: 0',$text0);
        $text0 = str_replace('fab.: o','fab.: 0',$text0);
        $text0 = str_replace('mod.: o','mod.: 0',$text0);
        $n=$this->getData_anoModFab($text0);
        //dd($n,$text0,$this->text);
        $data['veiculo_ano_fab_1'] = $n[0]??'';
        $data['veiculo_ano_modelo_1'] = $n[1]??'';
        $data['veiculo_tipo_1'] = 'a';

        //dd($data['veiculo_ano_modelo_1'],$data['veiculo_ano_fab_1']);
        if(empty($data['veiculo_ano_modelo_1'])){
            $dataVei =2;
        }else{
            $dataVei = $data['veiculo_ano_modelo_1']-$data['veiculo_ano_fab_1'];
        }


        if($dataVei>1){
            $text_car = str_replace('Ano Fab.: O','Ano Fab.: 0',$this->text);
            $textData=TextUtility::getPartOfStr($text_car, ['start'=>'Ano Fab.','end'=>'Placa:']);
            $textData = str_replace(['O','o'],'0',$textData);
            $textData = str_replace(['An0','M0d'],['Ano','Mod'],$textData);
            $textData = strtolower($textData );
            $n=$this->getData_anoModFab($textData);

             $data['veiculo_ano_fab_1'] = $n[0]??'';
             $data['veiculo_ano_modelo_1'] = $n[1]??'';

        }


        //código ci
        $text_ci = str_replace(['kdentificação'], ['Identificação'], $this->text);
        $text_ci = TextUtility::getPartOfStr($text_ci, ['start'=>'Código de Identificação','remove'=>['Proposta','Automóvel']]);

        $text_ci = str_replace(['kdentificação'], ['Identificação'], $text_ci);
        $text_ci = TextUtility::getPartOfStr($text_ci, ['end'=>'EM CASO DE']);
        $text_ci = str_replace(['.','kdentificação'], ['','Identificação'], $text_ci);
       // dd($text_ci);
        $data['veiculo_ci_1'] = $this->getData_ci($text_ci);
        //dd($data['veiculo_ci_1']);
        $block_text = TextUtility::getPartOfStr($this->text, ['start'=>'Chassi','end'=>'Renavam']);
        //$block_text = FormatUtility::sanitizeAllText($block_text);
        $n = TextUtility::getSearchText($block_text,'Chassi:','value',['side'=>'right']);
        if(strlen($n)<14){
            $n = $n.TextUtility::getSearchText($block_text,$n,'value',['side'=>'right']);
            //$data['veiculo_chassi_1'] = $this->getData_chassi($n);
        }else{
           $n = TextUtility::getSearchText($block_text,'Chassi:','value',['side'=>'right']);
        }

        $n= FormatUtility::extractAlphaNum($n);
        $data['veiculo_chassi_1'] = $this->getData_chassi($n);
        $find_chassi=false;
        //verifica se existe dentro do texto $this->text_ws02
        if($data['veiculo_chassi_1'] && strpos($this->text_ws02, $data['veiculo_chassi_1'])!==false){
            $find_chassi=true;
        }else if(empty($data['veiculo_chassi_1']) || strpos($this->text_ws02, $data['veiculo_chassi_1'])===false){
            $blocktext = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'OUVIDORIA']);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'EM CASO']);
            $chassi = substr($blocktext, 400,-700);

            $n = $this->getData_chassi($chassi);
            if($n){
                $data['veiculo_chassi_1'] = $n;
                $find_chassi=true;
            }
        }

        if(!$find_chassi){
            //verifica se existe novamente dentro do texto $this->text_ait_tessrct
            if(empty($data['veiculo_chassi_1']) || strpos($this->text_ait_tessrct, $data['veiculo_chassi_1'])===false){
                $n = TextUtility::getPartOfStr($this->text_ait_tessrct,['start'=>'chassi','end'=>'chassi','side_len'=>50]);
                $data['veiculo_chassi_1'] = $this->getData_chassi($n);
            }
        }
        //dd($data['veiculo_chassi_1']);
        if($data['veiculo_chassi_1']==''){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Chassi:']);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Tipo de']);
            $text_chassi = trim(str_replace(['Chassi:','Tipo de'], '', $blocktext));
            $data['veiculo_chassi_1'] = $text_chassi;
        }
        //dd($data['veiculo_chassi_1']);
        if($data['veiculo_chassi_1']==''){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Chassi:']);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Renavam:']);
            $text_chassi = $this->getData_chassi($blocktext);
            $data['veiculo_chassi_1'] = $text_chassi;
        }
        //dd($data['veiculo_chassi_1']);
        $n = $this->getX1(['start'=>'Acessórios:','return_type'=>'prev','remove'=>'Novo']);//modelo veículo
        if(strpos($n,'Cobertura')!==false){
            $n = $this->getX1(['start'=>'COMPREENSIVA','return_type'=>'next3']);//modelo veículo
        }
        $n=trim(str_replace(['Nova','Novo','NOVO','NOVA'], [''], $n));
        if(!$n){
            $n= trim(TextUtility::getPartOfStr($this->text_ait_tessrct, ['start'=>'Ajuste: Modelo','end'=>'Ano Fab','remove'=>['Ajuste: Modelo','Ano Fab',':']]));
        }
        $mod_vei = TextUtility::getPartOfStr($this->text, ['start'=>'Modelo:']);
        $mod_vei = TextUtility::getPartOfStr($mod_vei, ['end'=>'Renavam:']);
        $data['veiculo_modelo_1'] =$n;//modelo veículo
        //dd($data['veiculo_modelo_1']);
         if($data['veiculo_modelo_1']==':' ){
            $blocktext = TextUtility::getPartOfStr($text0, ['start'=>'modelo:']);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'cobertura']);
            $blocktext = str_replace(["cobertura","novo"], '', $blocktext);
            $text_vei = explode(":", $blocktext);

            $data['veiculo_modelo_1'] = trim($text_vei[4]??'');

            if(empty($data['veiculo_modelo_1'])){
                $data['veiculo_modelo_1'] = trim($text_vei[3]??'');
           }
            //dd($blocktext,$text_vei);

        }elseif(strpos($data['veiculo_modelo_1'], 'Ano Fab.:')!==false || strpos($data['veiculo_modelo_1'],':')!==false || strpos($data['veiculo_modelo_1'],',')!==false || strpos($data['veiculo_modelo_1'],'vidro')!==false || strpos($data['veiculo_modelo_1'],'traseiro')!==false || strpos($data['veiculo_modelo_1'],'blindado')!==false){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Modelo:']);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Casco']);
            $data['veiculo_modelo_1'] = trim(str_replace(["Modelo:","Casco"], '', $blocktext));

            if(strpos($data['veiculo_modelo_1'], 'Cobertura')!==false || strpos($data['veiculo_modelo_1'], ':')!==false){
                $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Acessórios']);
                $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Vidro Para']);
                $data['veiculo_modelo_1'] = $this->getX1([$blocktext,'start'=>'Vidro Para','return_type'=>'prev']);//modelo veículo
                //dd($data['veiculo_modelo_1'],$blocktext);
            }

        }
        if(empty($data['veiculo_modelo_1'])){
            $text_veiculo1 = $this->getX1(['start'=>'Tipo de Franquia:','return_type'=>'prev2']);//modelo veículo
            $text_veiculo1 = explode('Ano Fab',$text_veiculo1);
            $data['veiculo_modelo_1'] = trim($text_veiculo1[0]);
        }

        if(strpos($data['veiculo_modelo_1'], '0Km:')!==false || strpos($data['veiculo_modelo_1'], ':')!==false){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Acessórios']);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Ano Fab']);
            $data['veiculo_modelo_1'] = $this->getX1([$blocktext,'start'=>'Ano Fab','return_type'=>'prev']);//modelo veículo
            //dd($data['veiculo_modelo_1'],$blocktext);
        }
        //dd($data['veiculo_modelo_1'],$this->text);
        $data['veiculo_fab_1']='';//não tem o fabricante
        $data['veiculo_fab_code_1']='';//não tem o fabricante


        $n = $this->getX1(['start'=>'FIPE/TARIFA:','end'=>'Codigo de','sanitize'=>true,'split'=>false ]);//Codigo Fipe

        $n = str_replace(' ', '', $n);
        $n = ltrim(ltrim(strtolower($n),'o'),'0');
        $n = explode('/', $n);
        $data['veiculo_cod_fipe_1'] = TextUtility::getSearchText($n[1]??'','TARIFA:','number',['side'=>'right']);// Codigo Fipe


       if($data['veiculo_cod_fipe_1']==''){
           $n= str_replace('FIPETARIFA:','FIPE TARIFA:',$this->text);
           $n = TextUtility::getSearchText($n,'FIPE TARIFA:','value',['side'=>'right']);
           $n = explode('/', $n);
           $n = $n[0]??'';
           $n = ltrim(ltrim(strtolower($n),'o'),'0');
           $data['veiculo_cod_fipe_1'] = $n;
       }

        // $n = $this->getX1(['start'=>'(C.I.):','end'=>'EM CASO','sanitize'=>true,'split'=>false ]);//Codigo C.I
        $n = $this->getX1(['start'=>'(C.I.):','end'=>'INTEGRAL, ','sanitize'=>true,'split'=>false ]);//Codigo C.I

        if(!$data['veiculo_ci_1']){
            $n = explode(' ', $n);
            $n = str_replace(['o','.'], ['0',''], $n);
            $data['veiculo_ci_1'] = $n[1]??'';//codigo C.I
        }
        if(!$data['veiculo_ci_1']){
            $n = TextUtility::getPartOfStr($this->text, ['start'=>'(C.I.):','end'=>'Capital']);
            $n = str_replace('.', '', $n);
            $data['veiculo_ci_1'] = $this->getData_ci($n);
        }
        //dd($data['veiculo_ci_1']);

        $block_text = FormatUtility::sanitizeAllText($this->text);
        $block_text = str_replace(['clesse de boius:','Clesse de BôIus','Clesse de BoIus'], ['Classe de Bonus'], $block_text);
        $block_text = str_replace(['Classe de Bonus:'], ['Classe de Bonus'], $block_text);
        $block_text = TextUtility::getPartOfStr($block_text, ['start'=>'bonus'],['sanitize'=>true]);
        $data['veiculo_classe_1'] =TextUtility::getSearchText($block_text,'bonus','number',['side'=>'right']);


       // $block_text = str_replace(['Clesse de BôIus','Clesse de BoIus'], 'Classe de Bonus', $block_text);




        $n = $this->getX1(['start'=>'Passageiros:','cb'=>function($v){ return explode(" ",$v)[1]??'';}]);//Qtde Passageiros
        $data['veiculo_n_lotacao_1'] = $n;//Qtde Passageiros

        $n = $this->getX1(['start'=>'Portas:','cb'=>function($v){ return explode(" ",$v)[1]??'';}]);//Qtde Portas
        $data['veiculo_n_portas_1'] = $n;//Qtde Portas

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


