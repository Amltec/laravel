<?php
namespace App\ProcessRobot\cad_apolice\automovel;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\ProcessRobot\cad_apolice\ProcessAutomovelClass;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;
use App\ProcessRobot\cad_apolice\Classes\Insurers\libertyInsurer;

/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 19:45 17/02/2020
 */
class libertyClass extends ProcessAutomovelClass{
     use libertyInsurer;

    protected $pdf_engine = 'ws02'; //nome do interpretador de pdf (valores em  \App\Utilities\FilesUtility::readPDF)
    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt=[]){
        $this->process_opt = $opt;
    	$this->text = $this->limitText($text);
        $r = $this->processTipo01();
    	return $this->ValidateData($r);
    }




    /* Padrão para os arquivos de boleto, cartão e débito - versão anterior a 2020:
    	Pasta: C:\xampp\htdocs\robo-gc\_test-pdf-php\aw_test\Files\cad_apolice\automovel\liberty\pdf
    */
    private function processTipo01(){
        $pg = $this->getPagina1();

        $data = $this->getDados();
        if(!$data['success'])return $data;
        $data = $data['data'];

        //CEP de Pernoite

        $textPernoite = TextUtility::getPartOfStr($this->text, ['start'=>'Código FIPE'] );
        $textPernoite = TextUtility::getPartOfStr($textPernoite, ['end'=>'DADOS DO CORRETOR']);
       //dd($textPernoite);

        $n='';

            if(strpos($textPernoite,'Pernoite')!==false){
                $n=$this->getX1(['start'=>'Pernoite','return_type'=>'next','cb'=>function($v){  $n=explode(' ',$v); return $n[2]; }]);

                if(is_null($n)){
                    $n="";
                }else{
                    if(is_numeric($n)==false){
                        $n=$this->getX1(['start'=>'Pernoite','return_type'=>'next']);//sintaxe esperada ex: PARTICULAR 3702 / {cep pernoite} 0,5 FACULTATIVA 51820341770575
                        //dd($n);
                        $n=trim(explode('/',$n)[1]);
                        $n=trim(explode(' ',$n)[0]);
                    }
                }

            }else{
                $n="";
            }

        $data['segurado_pernoite_cep_1'] = $n;
        //dd($data['segurado_pernoite_cep']);
        //Dados do Proprietário
        $n=$this->getX1(['start'=>'DADOS DO PROPRIETÁRIO','return_type'=>'next2','remove'=>'CPF/CNPJ']);
        $n=explode(' ',$n);
        $r='';
        foreach($n as $word){//percorre a array e ignora os números nos nomes
            if(!is_numeric(str_replace(['-','.','/'],['','',''],$word)))$r.=$word.' ';
        }
        $data['prop_nome_1'] = trim($r);

        if( $data['prop_nome_1'] ==''){ $data['prop_nome_1']=$data['segurado_nome'];};

        if ($data['prop_nome_1'] == $data['segurado_nome']){$data['segurado_proprietario_veiculo_1']='SIM';}else{$data['segurado_proprietario_veiculo_1']='NÃO';};


        //veiculo zero
        $text_vei_zero = $this->getX1(['start'=>'Fab/Mod','return_type'=>'next']);
        if(strpos($text_vei_zero, '0KM')!==false){
             $data['veiculo_zero_1'] ='s';
         }else{
             $data['veiculo_zero_1'] ='n';
         }
        $data['veiculo_data_saida_1']='';//não tem esse dado
        $data['veiculo_nf_1']='';//não tem esse dado



        //dd( $data['veiculo_chassi']);

        //Código FIPE
        $n = trim($this->getX1(['start'=>'Código FIPE','return_type'=>'next','remove'=>'Marca/Tipo do Veículo']));
        $n = explode(' ',$n)[0];
        $data['veiculo_cod_fipe_1'] = trim($n);



        //Ano fabricação e ano modelo
        $n = $this->getX1(['start'=>'Fab/Mod','return_type'=>'next']);

        $n = str_replace("0KM", '', $n);
        $n = trim($n);

        if(strlen($n)>11)$n = substr($n, strlen($n)-11, strlen($n));//sintaxe: pega os últimos caracteres da string para '... 2016 / 2017'

        $n = explode("/",$n);//Ano Fab

        $data['veiculo_ano_fab_1'] = trim($n[0]);//Ano Fab

        $data['veiculo_ano_modelo_1'] = trim($n[1]);   //Ano Model

        //Marca do Veículo
        //$marcaMod = $this->getMarcaModelo( $this->getX1(['start'=>'Marca/Tipo','return_type'=>'next','remove'=>'Ano Fab/Mod'])  );//Fabricante Veiculo
        $data['veiculo_fab_1'] = '';//Fabricante Veiculo
        $data['veiculo_tipo_1'] = 'a';
        //Modelo do Veículo
        $n = $this->getX1(['start'=>'Marca/Tipo','return_type'=>'next','remove'=>[
            'Ano Fab/Mod',
            $data['veiculo_cod_fipe_1'],
            $data['veiculo_ano_fab_1'].' / '.$data['veiculo_ano_modelo_1']
        ]]);
        $data['veiculo_modelo_1'] = $n;


       // $data['veiculo_fab_code_1'] = $this->quiverVeiCode($data['veiculo_fab_1']); //código do fabricante
        $data['veiculo_fab_code_1'] = '';// vazio porque na apólice não tem o fabricante


        $data['veiculo_combustivel_1'] ='';//vazio porque na apólice não tem essa informação
        //$data['veiculo_combustivel_code_1'] = $this->getCombustivelCode($data['veiculo_combustivel_1']);
        $data['veiculo_combustivel_code_1'] = '';//vazio porque não tem essa informação na apólice
        //dd($data);
        //Chassi
        if(strpos($this->text,'Aliro Seguro')!==false){
            $block_text = TextUtility::getPartOfStr($this->text,['start'=>'ITEM 001','end'=>'Pernoite']);
            $chassi = TextUtility::getSearchText($block_text,'Categoria','value',['side'=>'right']);
            $data['veiculo_chassi_1'] = $this->getData_chassi($chassi);
            $data['veiculo_placa_1'] = TextUtility::getSearchText($block_text,$data['veiculo_chassi_1'],'value',['side'=>'right']);
            $data['veiculo_n_lotacao_1'] = TextUtility::getSearchText($block_text,$data['veiculo_placa_1'],'number',['side'=>'right']);

        }else{
            $block_text = str_replace($data['veiculo_modelo_1'],'',$this->text);//retira o modelo do texto, pois alguns modelos vem com o texto 'chassi' na descrição
            $n = trim(TextUtility::getPartOfStr($block_text, ['start'=>'Chassi','return_type'=>'next','remove'=>'placa']));
            if($n)$n = explode(' ',$n)[0];
            if(!$n)$n = trim(TextUtility::getPartOfStr($block_text, ['start'=>'Chassi','end'=>'chassi','side_len'=>[0,30]]));
            $n = $this->getData_chassi($n);
            $data['veiculo_chassi_1'] = trim($n);

            if(strlen($data['veiculo_chassi_1'])<16){
                $n=TextUtility::getPartOfStr($block_text, ['start'=>'Chassi','end'=>'Capacidade','sanitize'=>false]);
                $n=TextUtility::getSearchText($n,'Chassi','value',['side'=>'right']);
                if(strlen($n)==13)$n.='0000';
                $data['veiculo_chassi_1']=$n;
            }

            //Placa
            $n0 = $this->getX1(['start'=>'Placa','return_type'=>'next','remove'=>'Capacidade','remove'=>[
                $data['veiculo_chassi_1'],
                $data['veiculo_cod_fipe_1']
            ]]);

            if(strpos($data['veiculo_chassi_1'],'ATEGORIA')!==false){
                $textVei = TextUtility::getPartOfStr($this->text, ['start'=>'Categoria'],['sanitize'=>false]);
                $textVei = TextUtility::getPartOfStr($textVei, ['end'=>'Utilização']);
                $data['veiculo_chassi_1'] = TextUtility::getSearchText($textVei,'Categoria','value',['side'=>'right']);
            }

            $textVei = TextUtility::getPartOfStr($this->text, ['start'=>'Categoria'],['sanitize'=>false]);
            $textVei = TextUtility::getPartOfStr($textVei, ['end'=>'Utilização']);

           // if(strpos($textVei,$data['veiculo_chassi_1'])===false){
           //     $data['veiculo_chassi_1'] = TextUtility::getSearchText($textVei,'Categoria','value',['side'=>'right']);
           // }

        // dd($data['veiculo_chassi_1'],$block_text);
            $n = explode(' ',$n0)[0];
            $n=$this->getData_placa($n);
            if(!$n){
                $n=$this->getData_placa($n0);
            }
            if(!$n){
                $block_text = FormatUtility::sanitizeAllText($this->text);
                $block_text = TextUtility::getPartOfStr($block_text,['start'=>$data['veiculo_chassi_1'],'end'=>$data['veiculo_chassi_1'],'side_len'=>[0,15]]);
                $n = $this->getData_placa($block_text);
                if(stripos('a/c', $block_text)==false && empty($n)){
                    $n='nd zero';
                }

            }

            $data['veiculo_placa_1'] = trim($n);


            //Locação
            $n = trim($this->getX1(['start'=>'Capacidade','return_type'=>'next','cb'=>function($v){ return explode(" ",$v)[0];  }]));
            if(!is_numeric($n)){
                $n = trim($this->getX1(['start'=>'Capacidade','return_type'=>'next']));
                $n = TextUtility::getPartOfStr($n,['end'=>'PAS','remove'=>'PAS']);//sintaxe esperada: '.... {num portas} PAS'
                $n = explode(' ',$n);
                $n = $n[count($n)-1];//ex: 005

                if(empty($n)){
                    $n = trim($this->getX1(['start'=>'Capacidade','return_type'=>'next']));
                    $n = TextUtility::getSearchText($n,'VEICULOS','number',['side'=>'left']);
                }
            }

            $data['veiculo_n_lotacao_1'] = substr($n, 1);
            if(empty($data['veiculo_n_lotacao_1'])&& $n<=7){
                $data['veiculo_n_lotacao_1'] = $n;
            }

            if(strlen($data['veiculo_n_lotacao_1'])>2 || is_numeric($data['veiculo_n_lotacao_1'])==false){
                $data['veiculo_n_lotacao_1'] = TextUtility::getSearchText($block_text,$data['veiculo_placa_1'],'number',['side'=>'right']);
            }
            //dd($data['veiculo_n_lotacao_1']);

            if($data['veiculo_n_lotacao_1']>5){
                $n = TextUtility::getPartOfStr($this->text,['start'=>'DADOS DO VEÍCULO','end'=>'Utilização']);
                $data['veiculo_n_lotacao_1'] = TextUtility::getSearchText($n,$data['veiculo_placa_1'],'number',['side'=>'right']);
            }

            if($data['veiculo_n_lotacao_1']>5){
                $n = TextUtility::getPartOfStr($this->text,['start'=>'DADOS DO VEÍCULO','end'=>'Utilização']);
                $data['veiculo_n_lotacao_1'] = TextUtility::getSearchText($n,$data['veiculo_chassi_1'],'number',['side'=>'right']);
                //dd($data['veiculo_n_lotacao_1'],$data['veiculo_chassi_1'],$this->text);
            }

        }

       // dd($data['veiculo_n_lotacao_1']);
            $data['veiculo_n_portas_1'] = ''; //na apólice da Liberty não tem esta informação

            //C.I
        // $n=trim($this->getX1(['start'=>'CI Atual','return_type'=>'next']));
            $n = TextUtility::getPartOfStr($this->text, ['start'=>'CI Atual','end'=>'Classe']);
            $n = $this->getData_ci($n);

            $data['veiculo_ci_1'] = $n;

            $block_text = TextUtility::getPartOfStr($this->text, ['start'=>'bonus','end'=>'dados do seguro','sanitize'=>true]);

            $data['veiculo_classe_1']=TextUtility::getSearchText($block_text,'bonus','number',['side'=>'right']);

       // dd($data['veiculo_classe_1']);
        $data = $this->getPremio($data);

        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }



    private static function clearText($text){
        $text =  preg_replace('/[^\d\-]/', '',$text);
        return $text;
    }

}
