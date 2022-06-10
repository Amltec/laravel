<?php
namespace App\ProcessRobot\cad_apolice\residencial;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\Utilities\FilesUtility;
use App\ProcessRobot\cad_apolice\ProcessResidencialClass;
use App\ProcessRobot\cad_apolice\Classes\Insurers\sompoInsurer;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;


/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 27/07/2020
 */
class sompoClass extends ProcessResidencialClass{
    use sompoInsurer;

    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt=[]){//$opt=[path,url]
        $this->process_opt = $opt;
        $this->text = $text;
        $this->text = $this->limitText($text);
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
        //dd($this->text);
        $data = $this->getDados_tipo3();
        if(!$data['success'])return $data;
        $data = $data['data'];


        //**** dados da residência
        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'LOCAL DE RISCO','sanitize'=>false]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Classificação:']);

        if($blocktext==''){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'DADOS DO SEGURO','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Bens Compreendidos']);
        }

        if(strpos($blocktext,'Bairro' )!==true){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'DADOS DO SEGURO','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Cód.']);
            $blocktext = str_replace('Bairro :','Bairro:',$blocktext) ;
            $blocktext = str_replace('C omplemento:','Complemento:',$blocktext);
            $endereco = TextUtility::getPartOfStr($blocktext, ['start'=>'Endereço:','end'=>'LOCAL','sanitize'=>false]);
            $endereco = trim(str_replace(['Endereço:','LOCAL'], '', $endereco));

            if(strpos($endereco,'Cidade:' )!==false){
                $endereco = TextUtility::getPartOfStr($blocktext, ['start'=>'Endereço:','end'=>'Cidade:','sanitize'=>false]);
                $endereco = trim(str_replace(['Endereço:','Cidade:'], '', $endereco));
            }
            if(strpos($blocktext,'Complemento:' )!==true){
                $endereco = explode('Complemento:',$endereco);
                $endereco = trim($endereco[0]);
            }

            $endereco = trim(str_replace('Complemento:','',$endereco));

            $n = substr($endereco, -6);
           // dd($endereco);
            if(strpos($n,' ')!==false){
                $n=explode(' ',$n);
                if(is_numeric($n[1])){
                    $n=$n[1];
                    $numero = $n;
                    $endereco = trim(str_replace([$n], '', $endereco));
                }else{
                    $numero = '';
                }

            }

            //dd($endereco);


            $complemento = '';
            //dd($blocktext);
            $blocktext = str_replace(['B ai r r o:','Ba i r r o:','B ai rr o:','Bai r ro:','B a i r r o :'], 'Bairro:',  $blocktext);
            $bairro = TextUtility::getPartOfStr($blocktext, ['start'=>'Bairro:','end'=>'Cep:','sanitize'=>false]);
            $bairro = trim(str_replace(['Bairro:','Cep:'], '', $bairro));

            if($bairro==''){

                $bairro = TextUtility::getPartOfStr($this->text, ['start'=>'Bairro:','sanitize'=>false]);
                $bairro = TextUtility::getPartOfStr($bairro, ['end'=>'Cep:','sanitize'=>false]);
                $bairro = trim(str_replace(['Bairro:','Cep:'], '', $bairro));
            }



         }else{// segundo formato bloco local de risco


            $endereco = TextUtility::getPartOfStr($blocktext, ['start'=>'Endereço:','end'=>'Bairro:','sanitize'=>false]);
            $endereco = trim(str_replace(['Endereço:','Bairro:'], '', $endereco));

            if(strpos($endereco,'Cidade:' )!==false){
            $endereco = TextUtility::getPartOfStr($blocktext, ['start'=>'Endereço:','end'=>'Cidade:','sanitize'=>false]);
            $endereco = trim(str_replace(['Endereço:','Cidade:'], '', $endereco));
            }
            //dd($endereco);
            $numero   = TextUtility::getSearchText($endereco,'','number',['side'=>'right']);

            $complemento = '';
            $blocktext = str_replace(['B ai r r o:','Ba i r r o:','B ai rr o:','Bai r ro:','B a i r r o :'], 'Bairro:',  $blocktext);
            $bairro = TextUtility::getPartOfStr($blocktext, ['start'=>'Bairro:','end'=>'Cidade:','sanitize'=>false]);
            $bairro = trim(str_replace(['Bairro:','Cidade:'], '', $bairro));

            if($bairro==''){

                $bairro = TextUtility::getPartOfStr($this->text, ['start'=>'Bairro:','sanitize'=>false]);
                $bairro = TextUtility::getPartOfStr($bairro, ['end'=>'Cep:','sanitize'=>false]);
                $bairro = trim(str_replace(['Bairro:','Cep:'], '', $bairro));
            }
        }


        $cidade = TextUtility::getPartOfStr($blocktext, ['start'=>'Cidade:','end'=>'Estado:','sanitize'=>false]);
        $cidade = trim(str_replace(['Cidade:','Estado:'], '', $cidade));
        if(strpos($cidade,'Classifica' )!==false){
            $cidade = TextUtility::getPartOfStr($blocktext, ['start'=>'Cidade:','end'=>'Classifica','sanitize'=>false]);
            $cidade = trim(str_replace(['Cidade:','Classifica'], '', $cidade));
        }
        //dd($cidade);
        $estado = TextUtility::getPartOfStr($blocktext, ['start'=>'Estado:','end'=>'Cep:','sanitize'=>false]);
        $estado = trim(str_replace(['Estado:','Cep:'], '', $estado));

        if($estado==''){
            $estado = TextUtility::getPartOfStr($this->text, ['start'=>'Estado:','sanitize'=>false]);
            $estado = TextUtility::getPartOfStr($estado, ['end'=>'Classificação:','sanitize'=>false]);
            $estado = trim(str_replace(['Estado:','Classificação:'], '', $estado));
        }

        $cep = TextUtility::getPartOfStr($blocktext, ['start'=>'Cep:','end'=>'Classificação:','sanitize'=>false]);
        $cep = trim(str_replace(['Cep:','Classificação:'], '', $cep));

        if($cep==''){
            $cep = TextUtility::getSearchText($blocktext,'','cep',['side'=>'right']);
        }
        $data['residencial_endereco_1'] = $endereco;
        $data['residencial_numero_1'] = $numero;
        $data['residencial_compl_1'] = $complemento;
        $data['residencial_bairro_1'] = strtoupper($bairro);
        $data['residencial_cidade_1'] = strtoupper($cidade);
        $data['residencial_uf_1'] = strtoupper($estado) ;
        $data['residencial_cep_1'] = $cep;


        //*** dados do prêmio
        $data = $this->getPremio_tipo3($data);

        //dd($data,$this->text);
        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }


}
