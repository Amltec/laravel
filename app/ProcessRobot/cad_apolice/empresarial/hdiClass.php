<?php
namespace App\ProcessRobot\cad_apolice\Empresarial;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\ProcessRobot\cad_apolice\ProcessEmpresarialClass;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;
use App\ProcessRobot\cad_apolice\Classes\Insurers\hdiInsurer;

/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 19:45 17/02/2020
 */
class hdiClass extends ProcessEmpresarialClass{
    use hdiInsurer;
    
    
    //Campos obrigatórios adicionais para personalização na classe filha (obs: neste caso segue as regras existentes no validate da classe filha, ex ProcessAutomovelClass.php)
    protected $validate_required = ['empresarial_bairro'=>false];//sintaxe field=>boolean
    
    
    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt=[]){
        $this->process_opt = $opt;
    	$this->text = $text;
        $r = $this->processTipo01();
    	return $this->ValidateData($r);
    }
    
    
    
    /* Padrão para os arquivos de boleto, cartão e débito - versão anterior a 2020:
    */
    private function processTipo01(){
        $pg = $this->getPagina1();
     
        //*** dados do seguro
        $data = $this->getDados();
        if(!$data['success'])return $data;
        $data = $data['data'];
        
        
        //**** dados da residência
               
        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'localizacao','sanitize'=>true,'remove'=>'localizacao']);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'ocupacao','remove'=>'ocupacao']);
        $blocktext = trim(str_replace(['000001 - ','{page-end:2}','{page-start:3}'], ['','',''], $blocktext));
        
        $block_segurado = TextUtility::getPartOfStr($this->text, ['start'=>'Endereço','sanitize'=>false]);
        $block_segurado = TextUtility::getPartOfStr($block_segurado, ['end'=>'Código','remove'=>'Código']);        
        
        
        $endereco_seg = TextUtility::getPartOfStr($block_segurado, ['start'=>'endereco :','end'=>'telefone :','sanitize'=>true]);
        $endereco_seg = trim(str_replace(['endereco :','telefone :'], '', $endereco_seg));
        $n = explode(',', $endereco_seg);
        $endereco_seg =$n[0];
        $numero_seg = TextUtility::getSearchText($n[1],'','number',['side'=>'right']);  ;
        if($numero_seg==''){
            $numero_seg='s/n';
        }
        
        $bairro_seg = TextUtility::getPartOfStr($block_segurado, ['start'=>'bairro','end'=>'cidade','sanitize'=>false]);
        $bairro_seg = trim(str_replace(['Cidade','Bairro',':'], '', $bairro_seg));
        //dd($bairro_seg,$block_segurado);
        $n = TextUtility::getPartOfStr($block_segurado, ['start'=>'cidade :','end'=>'cep :','sanitize'=>true]);
        $n = trim(str_replace(['cidade :','cep :'], '', $n));
        $n = explode('-', $n);
        $cidade_seg = $n[0];
        $estado_seg = $n[1];
        $cep_seg = TextUtility::getSearchText($block_segurado,'CEP :','value',['side'=>'right']);  
        
        if(strpos($blocktext, ',')==false){           
            $n = explode('-', $blocktext);
            $endereco_seg = explode('-', $endereco_seg);
            $endereco_seg = $endereco_seg[0];
        }else{
            $n = explode(',', $blocktext);         
        }
       
        
        $endereco = $n[0];
        //dd($endereco);
        $n = explode('-', $n[1]);
        $numero = TextUtility::getSearchText($n[0],'','number',['side'=>'right']);        
        if($numero==''){
            $numero = TextUtility::getSearchText($endereco,'','number',['side'=>'right']);
        }
        
        if($numero==''){
            $numero = 's/n';
        }
        if($numero_seg=='0'){
            $numero_seg = 's/n';
        }
        if($numero=='0'){
            $numero = 's/n';
        }
        
        if(strpos($endereco, $endereco_seg)!==false){
            $endereco = $endereco_seg;
        }
        //dd($endereco_seg,$endereco,$numero_seg,$numero);
        if($endereco_seg==$endereco && $numero_seg==$numero){//verifica se o endereço do local segurado é o mesmo do segurado
            $cidade = $cidade_seg;
            $bairro = $bairro_seg;
            $estado = $estado_seg;
            $cep = $cep_seg;
            $complemento = TextUtility::getPartOfStr($block_segurado, ['start'=>'0 / Q','end'=>'Telefone','sanitize'=>false]);
            $complemento = TextUtility::getPartOfStr($block_segurado, ['start'=>'/ ','end'=>'Telefone','sanitize'=>false]);
            $complemento = str_replace(['/ ','Telefone'], '', $complemento);            
        }else{ 
            $blocktext = TextUtility::getPartOfStr($blocktext, ['start'=>$numero,'sanitize'=>true]);
            $cep      = TextUtility::getSearchText($blocktext,'','cep',['side'=>'right']);
            $estado   = TextUtility::getSearchText($blocktext,'- '.$cep,'value',['side'=>'left']);
            $cidade   = TextUtility::getSearchText($blocktext,'- '.$estado,'value',['side'=>'left']);               
            $bairro ='';
            $complemento = '';
        }
        
        $data['empresarial_endereco_1'] = $this->formatField1($endereco);
        $data['empresarial_numero_1'] = $this->formatField1($numero);
        if($complemento){
            $data['empresarial_compl_1'] = $this->formatField1($complemento);
        }else{
             $data['empresarial_compl_1'] = '';
        }
       $bairro = $this->formatField1($bairro);
        $data['empresarial_bairro_1'] = str_replace('NOME CONTATO', '', $bairro);
        $data['empresarial_cidade_1'] = $this->formatField1($cidade);
        $data['empresarial_uf_1'] = $this->formatField1($estado);
        $data['empresarial_cep_1'] = $cep;
   
        //*** dados do prêmio
        $data = $this->getPremio($data);
        
        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }

    //Deixa tudo em maiúscula e sem acentos
    private function formatField1($val){
        return strtoupper(FormatUtility::sanitizeBreakText(FormatUtility::removeAcents($val,true)));
    }
}