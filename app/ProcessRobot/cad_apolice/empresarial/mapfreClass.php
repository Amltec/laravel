<?php
namespace App\ProcessRobot\cad_apolice\empresarial;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;

use App\ProcessRobot\cad_apolice\ProcessEmpresarialClass;
use App\ProcessRobot\cad_apolice\Classes\Insurers\mapfreInsurer;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;


/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 29/07/2020
 */
class mapfreClass extends ProcessEmpresarialClass{
    use mapfreInsurer;
    
    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt=[]){       
        $this->process_opt = $opt;
        $this->text = FormatUtility::sanitizeBreakText($text);//retira somente as quebras de linha
        $r = $this->processTipo01();
    	return $this->ValidateData($r);
    }
    
    
    private function processTipo01(){        
        $data = $this->getDados();
        
        if(!$data['success'])return $data;
        $data = $data['data'];
        
        //**** dados da residência
        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Endereço do Risco:','sanitize'=>false]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Tipo de']);

        if(empty($blocktext)){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Endereço do Risco:','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Possui']);
        }
        //dd($blocktext, $this->text);       
        
        $endereco = TextUtility::getPartOfStr($blocktext, ['start'=>'Endereço do Risco:','end'=>'Nº:','sanitize'=>false]);
        $endereco = trim(str_replace(['Endereço do Risco:','Nº:'], '', $endereco));
           
        $numero   = TextUtility::getPartOfStr($blocktext, ['start'=>'Nº:','end'=>'Complemento:','sanitize'=>false]);
        $numero = trim(str_replace(['Nº:','Complemento:'], '', $numero));
        //dd($numero);
        if($numero=='' || strpos($numero, 'Bairro')!==false){
            
            $numero   = TextUtility::getPartOfStr($blocktext, ['start'=>'Nº:','end'=>' Bairro:','sanitize'=>false]);
            $numero = trim(str_replace(['Nº:',' Bairro:'], '', $numero));
        }
        
        $complemento = TextUtility::getPartOfStr($blocktext, ['start'=>'Complemento:','end'=>'Bairro:','sanitize'=>false]);
        $complemento = trim(str_replace(['Complemento:','Bairro:'], '', $complemento));
        
        $bairro = TextUtility::getPartOfStr($blocktext, ['start'=>'Bairro:','end'=>'CEP:','sanitize'=>false]);
        $bairro = trim(str_replace(['Bairro:','CEP:'], '', $bairro));
        
        $cidade = TextUtility::getPartOfStr($blocktext, ['start'=>'Cidade:','end'=>'Estado:','sanitize'=>false]);
        $cidade = trim(str_replace(['Cidade:','Estado:'], '', $cidade));
        
        $estado = TextUtility::getPartOfStr($blocktext, ['start'=>'Estado:','end'=>'Tipo de','sanitize'=>false]);
        if(empty($estado)){
            $estado = TextUtility::getPartOfStr($blocktext, ['start'=>'Estado:','end'=>'Possui','sanitize'=>false]);
        }
       // dd($estado,$blocktext);
        $estado = trim(str_replace(['Estado:','Tempo de','Possui'], '', $estado));
        
        $cep = TextUtility::getPartOfStr($blocktext, ['start'=>'CEP:','end'=>'Cidade:','sanitize'=>false]);
        $cep = trim(str_replace(['CEP:','Cidade:'], '', $cep));    
              
        $data['empresarial_endereco_1'] = strtoupper($endereco);
        $data['empresarial_numero_1'] = $numero;
        $data['empresarial_compl_1'] = $complemento;
        $data['empresarial_bairro_1'] = strtoupper($bairro);
        $data['empresarial_cidade_1'] = strtoupper($cidade);
        $data['empresarial_uf_1'] = strtoupper($estado) ;
        $data['empresarial_cep_1'] = $cep;
        
        
        $data = $this->getPremio($data);
        
        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }

    

}