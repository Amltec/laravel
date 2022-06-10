<?php
namespace App\ProcessRobot\cad_apolice\automovel;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;

use App\ProcessRobot\cad_apolice\ProcessAutomovelClass;
use App\ProcessRobot\cad_apolice\Classes\Insurers\bradescoInsurer;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;

/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 19:45 17/02/2020
 */
class bradescoClass extends ProcessAutomovelClass{
    use bradescoInsurer;

    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt=[]){
    	$this->text = $this->limitText($text);
        $this->process_opt = $opt;

        $tipo = $this->detectTipo();

        if($tipo=='tipo2'){
            $r = $this->processTipo02();

    	}else{//$tipo1
            $r = $this->processTipo01();
    	}

    	return $this->ValidateData($r);
    }





    /* Padrão para os arquivos de boleto, cartão e débito - versão a partir de 2020:
		Pasta: C:\xampp\htdocs\robo-gc\_test-pdf-php\aw_test\Files\cad_apolice\automovel\bradesco\pdf
    	Arquivos:
    		Bradesco Apolice - Layout 2020 - Debito CC
    		Bradesco Apolice - Layout 2020 - Carnê
    */
    private function processTipo02(){
         //*** dados do seguro
        $data = $this->getDados();
        if(!$data['success'])return $data;
        $data = $data['data'];


       // dd($data['segurado_pernoite_cep_1']);
        //dados do veículo


        if($data['tipo_pessoa']=='JURÍDICA' || $data['tipo_pessoa']=='JURIDICA'){
                //dados do proprietário
                $data['prop_nome_1'] =$data['segurado_nome'];//Nome
                $data['segurado_proprietario_veiculo_1']='SIM';

        }else{
                //dados do proprietário
                $data['prop_nome_1'] =$this->getX1(['start'=>'Dados do proprietário','return_type'=>'next3']);//Nome
                $data['segurado_proprietario_veiculo_1']=$this->getX1(['start'=>'segurado é proprietário do veículo','return_type'=>'next','remove'=>'1']);
                if(empty($data['prop_nome_1']) && empty($data['segurado_proprietario_veiculo_1'])){
                    //atualiza os dados do proprietário com os dados do segurado
                    $data['prop_nome_1'] = $data['segurado_nome'];
                    $data['segurado_proprietario_veiculo_1']='SIM';

                }

        }

        if($data['prop_nome_1'] == $data['segurado_nome']){
            $data['segurado_proprietario_veiculo_1']='SIM';
        }else{
            $data['segurado_proprietario_veiculo_1']='NAO';
        }

       // dd($data['tipo_pessoa']);

        //endereço de pernoite do veículo

        $data['segurado_pernoite_cep_1'] =$this->getX1(['start'=>'Cep','return_type'=>'next']);

        if($data['segurado_pernoite_cep_1']=='CPF/CNPJ'){
            $text_cep = str_replace('EXCEPCIO ', '', $this->text);
            $data['segurado_pernoite_cep_1']=TextUtility::getSearchText($text_cep,'CEP','cep',['side'=>'right']);
        }

        $data['veiculo_zero_1'] ='n';//não existe esse dado
        $data['veiculo_data_saida_1'] ='';//não existe esse dado
        $data['veiculo_nf_1'] ='';//não existe esse dado

        $marcaMod = $this->getMarcaModelo(	$this->getX1(['start'=>'Marca/Tipo Veículo','return_type'=>'next'])	);

        $data['veiculo_fab_1'] =$marcaMod['marca'];	//Marca do Veículo
        $data['veiculo_modelo_1'] =$marcaMod['modelo'];	//Modelo do Veículo

        if($data['veiculo_modelo_1']==''){
            $data['veiculo_modelo_1'] = $this->getX1(['start'=>'Marca/Tipo Veículo','return_type'=>'next']);
        }

        $data['veiculo_fab_code_1'] = $this->quiverVeiCode($data['veiculo_fab_1']); //código do fabricante
        $data['veiculo_ano_fab_1'] =$this->getX1(['start'=>'Ano de Fabricação/Modelo','return_type'=>'next','cb'=>function($v){ return $v ? explode("/",$v)[0] : '';}]);		//Ano Fab
        $data['veiculo_ano_modelo_1'] =$this->getX1(['start'=>'Ano de Fabricação/Modelo','return_type'=>'next','cb'=>function($v){ return $v ? explode("/",$v)[1] : '';}]);		//Ano Mod.

        //chassi
        $n=$this->getX1(['start'=>'Chassi','return_type'=>'next','remove'=>' ']);
        if((strlen($n)!=16 && strlen($n)!=17) || stripos($n,'fabricação')!==false){//provavelmente existe a palavra chassi antes do esperado (ex: no modelo do veiculo)
            $n=$n=$this->getX1(['start'=>'Chassi','return_type'=>'next','remove'=>' ','count'=>2]);
        }

        if($n=='Não'){
            $n =FormatUtility::sanitizeBreakText($this->text);
            $n =TextUtility::getPartOfStr($n, ['start'=>'Ano de','end'=>'Remarcado']);
            $n = TextUtility::getSearchText($n,'Chassi','value');
            //dd($n,$this->text);
        }
        $data['veiculo_chassi_1']=$n;

        $data['veiculo_cod_fipe_1'] =$this->getX1(['start'=>'Código FIPE','return_type'=>'next']);			//Código FIPE
        $n = $this->getX1(['start'=>'Placa','return_type'=>'next']);            //Placa

        if($n=='CPF/CNPJ'){
            $n = $this->getX1(['start'=>'Placa ','return_type'=>'next']);            //Placa
        }
        //dd($this);
        $data['veiculo_placa_1'] = str_replace(' ', '', $n);
        $data['veiculo_uso_1'] =$this->getX1(['start'=>'Uso do Veículo','return_type'=>'next']);				//Uso do Veículo
        $data['veiculo_tipo_1'] =$this->getX1(['start'=>'Uso do Veículo','not'=>'Marca','return_type'=>'next3']);     //Tipo
        if(stripos($data['veiculo_tipo_1'],'automovel')!==false){
            $data['veiculo_tipo_1'] = 'a';
        }elseif(stripos($data['veiculo_tipo_1'],'caminhao')!==false){
            $data['veiculo_tipo_1'] = 'c';
        }elseif(stripos($data['veiculo_tipo_1'],'moto')!==false){
            $data['veiculo_tipo_1'] = 'm';
        }else{
            $data['veiculo_tipo_1'] = 'a';
        }

        //Tipo de combustivel do veículo

        $data['veiculo_combustivel_1'] =$this->getX1(['start'=>'Combustível','return_type'=>'next',]);		//Combustível
        $data['veiculo_combustivel_code_1'] = $this->getCombustivelCode($data['veiculo_combustivel_1']);



        $data['veiculo_n_portas_1'] =$this->getX1(['start'=>'Nº de Portas','return_type'=>'next']);			//Nº de Portas
        $data['veiculo_n_eixos_1'] =$this->getX1(['start'=>'Nº de Eixos','return_type'=>'next']);			//Nº de Eixos
        $data['veiculo_n_lotacao_1'] =$this->getX1(['start'=>'Lotação','return_type'=>'next']);		//Lotação Oficial do Veículo
        $data['veiculo_ci_1'] = $this->getX1(['start'=>'CI','return_type'=>'next','remove'=>'.','page'=>3]);
        if(is_numeric($data['veiculo_ci_1'])==false && strlen($data['veiculo_ci'])<12)$data['veiculo_ci_1']='';

        $block_text = TextUtility::getPartOfStr($this->text, ['start'=>'Bônus','end'=>'CI']);
        //dd($block_text);
        $data['veiculo_classe_1'] =TextUtility::getSearchText($block_text,'Bônus','value',['side'=>'right']);

      //*** dados do prêmio
       $data = $this->getPremio($data);

       return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }


    /* Padrão para os arquivos de boleto, cartão e débito - versão anterior a 2020:
    	Pasta: C:\xampp\htdocs\robo-gc\_test-pdf-php\aw_test\Files\cad_apolice\automovel\bradesco\pdf
    	Arquivos:
    		Bradesco Apólice - boleto.pdf
    		Bradesco Apólice - cartao.pdf
    		Bradesco Apólice - debito.pdf
    */
    private function processTipo01(){
        //*** dados do seguro
        $pg = $this->getPagina1();
        $data = $this->getDados($pg);
        if(!$data['success'])return $data;
        $data = $data['data'];

        //dados do veículo

        if($data['tipo_pessoa']=='JURÍDICA'){
                $data['segurado_proprietario_veiculo_1']=$this->getX1(['start'=>'segurado é proprietário do veículo','return_type'=>'next','remove'=>'1']);
                //dados do proprietário
                $data['prop_nome_1']=$data['segurado_nome'];//Nome


        }else{
                $data['segurado_proprietario_veiculo_1']=$this->getX1(['start'=>'segurado é proprietário do veículo','return_type'=>'next','remove'=>'1']);
                //dados do proprietário
                $data['prop_nome_1'] =$this->getX1(['start'=>'Proprietário','return_type'=>'next2','page'=>[$pg+2,$pg]]);//Nome
        }

        if(!$data['prop_nome_1']){
            //$n=$this->getX1(['start'=>'Dados do proprietário','split'=>false, 'end'=>'CPF/CNPJ','xremove'=>['Dados do proprietário','CPF/CNPJ','Nome'] ]);
            $n=$this->getX1(['start'=>'Dados do proprietário','split'=>false ]);
            $n=$this->getX1(['end'=>'CPF/CNPJ','split'=>false, 'remove'=>['Dados do proprietário','CPF/CNPJ','Nome', '_'] ],$n);
            $data['prop_nome_1'] = $n;
        }



        //endereço de pernoite do veículo
        $data['segurado_pernoite_cep_1'] =$this->getX1(['start'=>'Cep','return_type'=>'next']);

        $vei_data_text = $this->getX1(['start'=>'Dados do veículo','split'=>false]);
        $vei_data_auto = $this->getData_veiculo($vei_data_text);//armazena os dados capturados automaticamente (para usar em alguns casos abaixo)

        $marcaMod = $this->getMarcaModelo( $this->getX1(['start'=>'Marca/Tipo Veículo','return_type'=>'next','page'=>[$pg+2,$pg]]) );
        $data['veiculo_fab_1'] =$marcaMod['marca'];	//Marca do Veículo
        $data['veiculo_modelo_1'] =$marcaMod['modelo'];	//Modelo do Veículo
        if(!$data['veiculo_fab_1']){
            $marcaMod = $this->getMarcaModelo($this->getX1(['start'=>'Marca/Tipo Veículo','return_type'=>'next']));
            $data['veiculo_fab_1'] =$marcaMod['marca'];	//Marca do Veículo
            $data['veiculo_modelo_1'] =$marcaMod['modelo'];	//Modelo do Veículo
        }

        $data['veiculo_fab_code_1'] = $this->quiverVeiCode($data['veiculo_fab_1']); //código do fabricante

        $data['veiculo_ano_fab_1'] =$this->getX1(['start'=>'Ano Fab./Mod.','return_type'=>'next2','page'=>[$pg+2,$pg],'cb'=>function($v){ return $v ? explode("/",$v)[0] : '';}]);		//Ano Fab
        $data['veiculo_ano_modelo_1'] =$this->getX1(['start'=>'Ano Fab./Mod.','return_type'=>'next2','page'=>[$pg+2,$pg],'cb'=>function($v){ return $v ? explode("/",$v)[1] : '';}]);		//Ano Mod.
        if(!$data['veiculo_ano_fab_1'] || !$data['veiculo_ano_fab_1']){
            $data['veiculo_ano_fab_1'] = $vei_data_auto['veiculo_ano_fab_1'];
            $data['veiculo_ano_modelo_1'] = $vei_data_auto['veiculo_ano_modelo_1'];
        }

        $data['veiculo_chassi_1'] =$this->getX1(['start'=>'Chassi','not'=>'Remarcado','return_type'=>'next3','page'=>[$pg+2,$pg],'remove'=>' ']);			//Chassi
        if(!$data['veiculo_chassi_1'])$data['veiculo_chassi_1'] = TextUtility::getSearchText($vei_data_text,'Chassi','value');

        $data['veiculo_cod_fipe_1'] =$this->getX1(['start'=>'Código FIPE','return_type'=>'next3','page'=>[$pg+2,$pg]]);			//Código FIPE
        if($data['veiculo_cod_fipe_1']==''){
            $block_text = TextUtility::getPartOfStr($this->text, ['start'=>'Código FIPE','end'=>'Uso do Veículo']);
            $block_text = FormatUtility::sanitizeAllText($block_text);
            $block_text = str_replace('.', '', $block_text);
            $data['veiculo_cod_fipe_1'] =trim(TextUtility::getSearchText($block_text,'fipe','value',['side'=>'right']));
        }
       //dd( $data['veiculo_cod_fipe_1'],$block_text);
        $data['veiculo_zero_1'] ='n';//não existe esse dado
        $data['veiculo_data_saida_1'] ='';//não existe esse dado
        $data['veiculo_nf_1'] ='';//não existe esse dado

        $data['veiculo_placa_1'] =$this->getX1(['start'=>'Licença','return_type'=>'next3','page'=>[$pg+2,$pg]]);			//Placa
        if(!$data['veiculo_placa_1'])$data['veiculo_placa_1'] = $vei_data_auto['veiculo_placa_1'];

        $data['veiculo_uso_1'] =$this->getX1(['start'=>'Uso do Veículo','return_type'=>'next','page'=>[$pg+2,$pg]]);				//Uso do Veículo


        $data['veiculo_tipo_1'] =$this->getX1(['start'=>'Tipo','not'=>'Marca','return_type'=>'next','page'=>[$pg+2,$pg]]);				//Tipo
        if($data['veiculo_tipo_1']=='FÍSICA' || $data['veiculo_tipo_1']=='JURÍDICA'){
            $block_text = TextUtility::getPartOfStr($this->text, ['start'=>'Uso do Veículo','end'=>'Combustível']);
             $data['veiculo_tipo_1'] =trim(TextUtility::getSearchText($block_text,'Tipo','value',['side'=>'right']));
        }

        if(stripos($data['veiculo_tipo_1'],'automovel')!==false || stripos($data['veiculo_tipo_1'],'passeio')!==false || stripos($data['veiculo_tipo_1'],'Pick-up')!==false){
            $data['veiculo_tipo_1'] = 'a';
        }elseif(stripos($data['veiculo_tipo_1'],'caminhao')!==false){
            $data['veiculo_tipo_1'] = 'c';
        }elseif(stripos($data['veiculo_tipo_1'],'moto')!==false){
            $data['veiculo_tipo_1'] = 'm';
        }


        $data['veiculo_ci_1'] = $this->getX1(['start'=>'C I','return_type'=>'next2','remove'=>'.']);
        if(!$data['veiculo_ci_1']){
            $n=$this->getX1(['start'=>'Dados da sua apólice','split'=>false]);
            $data['veiculo_ci_1'] = $this->getX1(['start'=>'CI','return_type'=>'next','remove'=>'.'],$n);
        }

        $block_text = TextUtility::getPartOfStr($this->text, ['start'=>'Bônus','end'=>'CI']);
       //dd($block_text);
        $n =trim(TextUtility::getSearchText($block_text,'COMPREENSIVA','value',['side'=>'right']));
        $n = FormatUtility::extractNumbers($n);
        $data['veiculo_classe_1'] =$n;

        if($data['veiculo_classe_1']==''){
            $block_text = TextUtility::getPartOfStr($this->text, ['start'=>'Bônus','end'=>'cobertura']);
            $block_text = FormatUtility::sanitizeAllText($block_text);
            $block_text = str_replace('.', '', $block_text);
            $data['veiculo_classe_1'] =trim(TextUtility::getSearchText($block_text,'ci','number',['side'=>'left']));
        }
        //dd($data['veiculo_classe_1']);


        //Tipo de combustivel do veículo

        $data['veiculo_combustivel_1'] =$this->getX1(['start'=>'Combustível','return_type'=>'next','page'=>[$pg+2,$pg]]);		//Combustível
        $data['veiculo_combustivel_code_1'] = $this->getCombustivelCode($data['veiculo_combustivel_1']);
        if(!$data['veiculo_combustivel_1']){
             //dd($vei_data_auto);
            $data['veiculo_combustivel_1'] = $vei_data_auto['veiculo_combustivel_1'];
            $data['veiculo_combustivel_code_1']= $vei_data_auto['veiculo_combustivel_code_1'];
        }

        $data['veiculo_n_portas_1'] =$this->getX1(['start'=>'Nº de Portas','return_type'=>'next3','paxge'=>[$pg+2,$pg]]);			//Nº de Portas
        if(!$data['veiculo_n_portas_1'])$data['veiculo_n_portas'] = TextUtility::getSearchText($vei_data_text,'Nº de Portas','value');

        $data['veiculo_n_lotacao_1'] =$this->getX1(['start'=>'Lotação Oficial do Veículo','return_type'=>'next3','page'=>[$pg+2,$pg]]);		//Lotação Oficial do Veículo
        if(!$data['veiculo_n_lotacao_1'])$data['veiculo_n_lotacao'] = TextUtility::getSearchText($vei_data_text,'Lotação','value');

        //Dados do Prêmio
        $data = $this->getPremio($data);

        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }

}
