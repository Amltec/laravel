<?php
namespace App\ProcessRobot\cad_apolice\automovel;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\ProcessRobot\cad_apolice\ProcessAutomovelClass;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;
use App\ProcessRobot\cad_apolice\Classes\Insurers\itauInsurer;


/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 19:45 17/02/2020
 */
class itauClass extends ProcessAutomovelClass{
    use itauInsurer;
    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */

    public function process($text,$opt=[]){
        $this->process_opt = $opt;
        $this->text = $this->limitText($text);
        $this->text0 = FormatUtility::sanitizeAllText($text);

       if($this->pdf_engine!='ws02')$this->text_ws02 = \App\Utilities\FilesUtility::readPDF($opt['path'],['engine'=>'ws02','pass'=>$this->process_opt['pass']])['text'];

        $r = $this->processTipo01();
    	return $this->ValidateData($r);
    }


    private function processTipo01(){
        $data = $this->getDados();
        if(!$data['success'])return $data;
        $data = $data['data'];

        //dd($data);
        //dados do veículo
        $blocktext = str_replace('VEÍCULO','Veículo',$this->text);
        $blocktext = FormatUtility::getPartOfStr($blocktext,['start'=>'Veículo:']);
        $blocktext = FormatUtility::getPartOfStr($blocktext,['end'=>'Dados da sua']);
        $blocktext = str_replace([':','    '],[': ',' '],$blocktext);



            $text_vei_zero = TextUtility::getSearchText($blocktext,'Zero km:','value',['side'=>'right']);;
            if(strpos($text_vei_zero, 'Sim')!==false){
                 $data['veiculo_zero_1'] ='s';
             }else{
                 $data['veiculo_zero_1'] ='n';
             }

            $data['veiculo_data_saida_1']='';//não tem esse dado
            $data['veiculo_nf_1']='';//não tem esse dado


            $n = $this->getX1(['start'=>'Veículo','remove'=>'Veículo:'],$blocktext);
            $n = $this->getMarcaModelo($n);
            $data['veiculo_fab_1'] = $n['marca'];// Fabricante do Veiculo
            $data['veiculo_fab_code_1'] = $this->quiverVeiCode($data['veiculo_fab_1']); //código do fabricante
            $data['veiculo_modelo_1'] = substr($n['modelo'],0,50);// Modelo do Veiculo

            $n=str_replace('(0km)','',$blocktext);
            $ano = TextUtility::getSearchText($n,'ano de fabricação','ano');
            if(!$ano)$ano = TextUtility::getSearchText($this->text,'ano de fabricação','ano',['max_words'=>1]);
            $data['veiculo_ano_fab_1'] = $ano;
            $data['veiculo_ano_modelo_1'] = TextUtility::getSearchText($n,'ano do modelo','ano');
             $data['veiculo_tipo_1'] = 'a';
             //dd($data,$blocktext);
            $data['veiculo_chassi_1'] = TextUtility::getSearchText($blocktext,'Chassi:','value',['side'=>'right']);


            $chassi_bkp = $data['veiculo_chassi_1'];

            //dd($data,$data['veiculo_chassi_1'],$this->text_ws02);
            if(strpos($this->text_ws02, $data['veiculo_chassi_1'])===false){
                //dd($this->text_ws02);
                $blocktext1 = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'Email:']);
                $blocktext1 = TextUtility::getPartOfStr($blocktext1, ['end'=>'Dados da']);

                $chassi = explode(chr(10), $blocktext1);

                $chassi_ok ='';
                for($i=0;$i< count($chassi); $i++) {
                        $chassi_ok = $this->getData_chassi($chassi[$i]);
                        if($chassi_ok!='')break;
                }

               $data['veiculo_chassi_1'] = $chassi_ok;
               if($data['veiculo_chassi_1']==''){
                   $data['veiculo_chassi_1'] = $chassi_bkp;
               }
                //dd($blocktext, $chassi, $data['veiculo_chassi']);
            }

           // dd($data['veiculo_chassi']);

            $data['veiculo_cod_fipe_1'] = TextUtility::getSearchText($blocktext,'fipe:','value');

            //$n = TextUtility::getSearchText($blocktext,'placa:','value');
            $n = FormatUtility::getPartOfStr($blocktext,['start'=>'placa:']);
            $n=$this->getData_placa($n);
            if(!$n){
                $n = FormatUtility::getPartOfStr($blocktext,['start'=>'placa:','end'=>'chassi']);
                $n = str_replace(' ','',$n);//remove os espaços, pois as vezzes vem espaço entre as placas
                $n=$this->getData_placa($n);
            }
            $data['veiculo_placa_1'] = $n;

            if($data['veiculo_placa_1']==''){
                 $data['veiculo_placa_1'] = TextUtility::getSearchText($blocktext,'Placa:','value',['side'=>'right']);
            }

            //dd($blocktext);
            if(strpos($this->text_ws02, $data['veiculo_placa_1'])===false && $data['veiculo_placa_1']!=''){

                $blocktext1 = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'Email:']);
                $blocktext1 = TextUtility::getPartOfStr($blocktext1, ['end'=>'Dados da']);

                $placa = substr($blocktext1, 100,-60);
                $data['veiculo_placa_1'] = TextUtility::getSearchText($placa,$data['veiculo_chassi_1'],'value',['side'=>'left']);
                //dd($blocktext1, $placa, $data['veiculo_placa']);
            }


            $data['veiculo_n_lotacao_1'] = '';//vazio porque não tem essa informação na apólice - lotação
            $data['veiculo_n_portas_1'] = TextUtility::getSearchText($blocktext,'portas:','number');

            $n = $this->getData_combustivel($blocktext);
            $data['veiculo_combustivel_1'] = $n[0];
            $data['veiculo_combustivel_code_1'] = $n[1];
            $data['veiculo_fab_code_1'] = $this->quiverVeiCode($data['veiculo_fab_1']); //código do fabricante
            $data['segurado_pernoite_cep_1'] = TextUtility::getSearchText($this->text0,'pernoita','cep_formated');


            //proprietário do veículo
            $data['prop_nome_1'] = $data['segurado_nome'];//Por padrão não está sendo informado o nome do proprietário
            $data['segurado_proprietario_veiculo_1']=$data['prop_nome_1']==$data['segurado_nome']?'SIM':'NÂO';

            //dd($data);
            $data = $this->getPremio($data);

        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }


}
