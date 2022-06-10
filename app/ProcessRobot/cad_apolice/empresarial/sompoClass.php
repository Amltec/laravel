<?php
namespace App\ProcessRobot\cad_apolice\empresarial;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\Utilities\FilesUtility;
use App\ProcessRobot\cad_apolice\ProcessEmpresarialClass;
use App\ProcessRobot\cad_apolice\Classes\Insurers\sompoInsurer;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;


/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 27/07/2020
 */
class sompoClass extends ProcessEmpresarialClass{
    use sompoInsurer;

    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt=[]){//$opt=[path,url]
        $this->process_opt = $opt;
        $this->text = $text;
        $this->text0 = FormatUtility::sanitizeAllText($text);
        //dd(12);
        if(strpos($this->text,'DATA EMISSÃO')!==false){
            $r = $this->processTipo02();
        }else{
            $r = $this->processTipo01();
        }
        return $this->ValidateData($r);
    }



    private function processTipo01(){
        $data = $this->getDados_tipo3();
        if(!$data['success'])return $data;
        $data = $data['data'];


        //**** dados da residência
        //dd(strpos($this->text,'LOCAL DE RISCO'));
       // dd(substr_count($this->text, 'LOCAL DE RISCO'));
        $total_local = substr_count($this->text, 'LOCAL DE RISCO');
       // dd($total_local);
        for($i=1;$i<=$total_local;$i++){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'LOCAL DE RISCO - ITEM '.$i,'sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Classificação:']);

            if(empty($blocktext)){
                $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'LOCAL DE RISCO - '.$i,'sanitize'=>false]);
                $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Classificação:']);
            }
            //dd($blocktext,$this->text);

            if(strpos($blocktext,'Bairro' )!==true || empty($blocktext)){
                $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'DADOS DO SEGURO','sanitize'=>false]);
                $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Cód.']);
                $blocktext = str_replace('Bairro :','Bairro:',$blocktext) ;

                $endereco = TextUtility::getPartOfStr($blocktext, ['start'=>'Endereço:','end'=>'LOCAL','sanitize'=>false]);
                $endereco = trim(str_replace(['Endereço:','LOCAL'], '', $endereco));

                 $endereco = str_replace(['C o mp l e me n t o :'], 'Complemento:', $endereco);

                if(strpos($endereco,'Cidade:' )!==false){
                    $endereco = TextUtility::getPartOfStr($blocktext, ['start'=>'Endereço:','end'=>'Cidade:','sanitize'=>false]);
                    $endereco = trim(str_replace(['Endereço:','Cidade:'], '', $endereco));
                }

                if(strpos($endereco,'RESIDENCIA' )!==false){
                    $endereco = TextUtility::getPartOfStr($endereco, ['end'=>'RESIDENCIA','sanitize'=>false]);
                    $endereco = trim(str_replace(['RESIDENCIA'], '', $endereco));
                }

                if(strpos($endereco,'Complemento:' )!==false){
                    $endereco = TextUtility::getPartOfStr($endereco, ['end'=>'Complemento:','sanitize'=>false]);
                    $endereco = trim(str_replace(['Complemento:'], '', $endereco));
                }



                $n = substr($endereco, -6);
                $n = trim($n);
                //dd(trim($n),$endereco);
                if(strpos($n,' ')!==false){
                    $n=explode(' ',$n);
                    if(is_numeric($n[1])) $n=$n[1];
                }
                //dd($n);
                $endereco = trim(str_replace($n, '', $endereco));
                //dd($endereco);
                $numero   = $n;

                $complemento = '';
                //dd($blocktext);
                $bairro = TextUtility::getPartOfStr($blocktext, ['start'=>'Bairro:','end'=>'Cep:','sanitize'=>false]);
                $bairro = trim(str_replace(['Bairro:','Cep:'], '', $bairro));

                if($bairro==''){

                    $bairro = TextUtility::getPartOfStr($this->text, ['start'=>'Bairro:','sanitize'=>false]);
                    $bairro = TextUtility::getPartOfStr($bairro, ['end'=>'Cep:','sanitize'=>false]);
                    $bairro = trim(str_replace(['Bairro:','Cep:'], '', $bairro));
                }

             }else{
                $endereco = TextUtility::getPartOfStr($blocktext, ['start'=>'Endereço:','end'=>'Bairro:','sanitize'=>false]);
                $endereco = trim(str_replace(['Endereço:','Bairro:'], '', $endereco));

                if(strpos($endereco,'Cidade:')!=false){
                    $endereco = explode('Cidade:',$endereco);
                    $endereco = $endereco[0];
                }

                $numero   = TextUtility::getSearchText($endereco,'','number',['side'=>'right']);

                $complemento = '';

                $bairro = TextUtility::getPartOfStr($blocktext, ['start'=>'Bairro:','end'=>'Cidade:','sanitize'=>false]);
                $bairro = trim(str_replace(['Bairro:','Cidade:'], '', $bairro));

             }
             if(empty($bairro)){
                $blocktext2 = TextUtility::getPartOfStr($this->text, ['start'=>'LOCAL DE RISCO - '.$i,'sanitize'=>false]);
                $blocktext2 = TextUtility::getPartOfStr($blocktext2, ['end'=>'Cód.']);
                $bairro = TextUtility::getPartOfStr($blocktext2, ['start'=>'Bairro:','end'=>'Cep:','sanitize'=>false]);
                $bairro = trim(str_replace(['Bairro:','Cep:'], '', $bairro));
            }

            if(empty($bairro)){
                $blocktext2 = TextUtility::getPartOfStr($this->text, ['start'=>'LOCAL DE RISCO - '.$i,'sanitize'=>false]);
                $blocktext2 = TextUtility::getPartOfStr($blocktext2, ['end'=>'Cód.']);
                $blocktext2 = str_replace(['B ai r r o:','Ba i r r o:','B ai rr o:','Bai r ro:','B a i r r o :'], 'Bairro:',  $blocktext2);
                //dd($blocktext2);
                $bairro = TextUtility::getPartOfStr($blocktext2, ['start'=>'Bairro:','end'=>'Cep:','sanitize'=>false]);
                $bairro = trim(str_replace(['Bairro:','Cep:'], '', $bairro));
            }
            //dd($bairro);

            if(empty($endereco)){
                $endereco = TextUtility::getPartOfStr($blocktext, ['start'=>'Endereço:','end'=>'Complemento:','sanitize'=>false]);
                $endereco = trim(str_replace(['Endereço:','Complemento:'], '', $endereco));
                //dd($endereco,$blocktext);
            }else{
                $endereco = strtoupper(str_replace([$numero], '', $endereco));
            }

            if($endereco==$numero){
                $numero='n/d';
            }

            $cidade = TextUtility::getPartOfStr($blocktext, ['start'=>'Cidade:','end'=>'Estado:','sanitize'=>false]);
            $cidade = trim(str_replace(['Cidade:','Estado:'], '', $cidade));
            //dd($cidade);
            $estado = TextUtility::getPartOfStr($blocktext, ['start'=>'Estado:','end'=>'Cep:','sanitize'=>false]);
            $estado = trim(str_replace(['Estado:','Cep:'], '', $estado));

            if(empty($estado)){
                $estado = TextUtility::getPartOfStr($blocktext, ['start'=>'Estado:','end'=>'Classificação','sanitize'=>false]);
                $estado = trim(str_replace(['Estado:','Classificação'], '', $estado));
            }

             if(strpos($estado,'Classifica')!==false){
                $estado = TextUtility::getPartOfStr($estado, ['end'=>'Classifica','sanitize'=>false]);
                $estado = trim(str_replace(['Estado:','Classifica'], '', $estado));
            }

            $cep = TextUtility::getPartOfStr($blocktext, ['start'=>'Cep:','end'=>'Classificação:','sanitize'=>false]);
            $cep = trim(str_replace(['Cep:','Classificação:'], '', $cep));

            if(empty($cep)){
                $cep = TextUtility::getPartOfStr($blocktext, ['start'=>'Cep:','end'=>'Cód.','sanitize'=>false]);
                $cep = trim(str_replace(['Cep:','Cód.'], '', $cep));
            }

            $data['empresarial_endereco_'.$i] = $endereco;
            $data['empresarial_numero_'.$i] = $numero;
            $data['empresarial_compl_'.$i] = $complemento;
            $data['empresarial_bairro_'.$i] = strtoupper($bairro);
            $data['empresarial_cidade_'.$i] = strtoupper($cidade);
            $data['empresarial_uf_'.$i] = strtoupper($estado) ;
            $data['empresarial_cep_'.$i] = $cep;
        }


        //*** dados do prêmio
        $data = $this->getPremio_tipo3($data);

        //dd($data,$this->text);
        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }


}
