<?php

namespace App\ProcessRobot\cad_apolice;
use App\Utilities\FormatUtility;
use App\Utilities\TextUtility;

/**
 * Classe responsável por detectar o tipo do arquivo a partir do texto e separar os dados iniciais para processamento
 * Esta classe é chamado dentro do arquivo \App\Http\Controllers\Process\ProcessRobotController.php->extractTextFromPdf()
 */
class ProcessAutomovelTypesClass{
    
    //array de seguradas permitidas para o histórico
    private static $hist_insurer_allow=[
        'bradesco'      
    ];
    
    
    /**
     * Valida o texto da apólice e retorna aos dados iniciais
     * @param type $file_text - texto extraído da apólice
     * @return  array[success,type,seguradora_nome,corretor_cpf_cnpj]   //success histórico
     *          array[success,type]   //success apólice
     *          array[success,msg]   //error
     * Obs: type='apolice' ou 'historico'
     */
    public static function getTypes($file_text){
        //Lógica: esta classe irá procurar apenas identificar e retornar a sucesso e o nome nome da seguradora
        $data=[];
        $is_type_historico = strpos($file_text,'Histórico da Apólice')!==false;
        //$data['data_type'] = $is_type_historico ? 'historico' : 'apolice';
        
        if($is_type_historico){
            //campo: nome da seguradora
            //valor esperado: Nº da Proposta {nomecompania}\tvalor
            $n=TextUtility::getPartOfStr($file_text,['start'=>'Nº da Proposta','split'=>chr(10),'remove'=>['Nº da Proposta']]);
            $n=explode(chr(9),$n)[0];//armazena somente o nome da seguradora
            $data['seguradora_nome'] = strtolower($n);
            
            //campo: cpf / cnpj do corretor
            $n=TextUtility::getPartOfStr($file_text,['start'=>'CNPJ / CPF Corretor','split'=>chr(10),'remove'=>'CNPJ / CPF Corretor']);
            if(!$n)return ['success'=>false,'type'=>'historico','msg'=>'Hitórico: CPF / CNPJ do Corretor '. $n .' não encntrado'];
            $data['corretor_cpf_cnpj'] = strtolower($n);
            
            //**** valida se o nome da seguradora está dentre as permitidas ****
            $t=false;
            foreach(self::$hist_insurer_allow as $insurer){
                if($insurer==$data['seguradora_nome'])$t=true;
            }
            if(!$t)return ['success'=>false,'type'=>'historico','msg'=>'Hitórico: Seguradora '. $data['seguradora_nome'] .' não programada'];
            
            
            //sucesso
            return ['success'=>true,'type'=>'historico','seguradora_nome'=> $data['seguradora_nome'],'corretor_cpf_cnpj'=> $data['corretor_cpf_cnpj']];
            
        }else{//apólice
            return ['success'=>true,'type'=>'apolice'];
        }
            
    }
    
    
    
}
