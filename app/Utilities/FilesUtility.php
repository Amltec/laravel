<?php
namespace App\Utilities;
use Exception;


/*
 * Classe de leitura de arquivos, como: PDF
 */
class FilesUtility{

    /**
     * Faz a leitura do arquivo em PDF
     * @param string $file - caminho do arquivo
     * @param array $opt - parâmetros:
     *               boolean page_split - se true irá dividir o texto em páginas marcar o início e fim do texto com as strings {page-start:1}...{page-end:1}... Default true.
     *               string engine - deve conter o nome base do sistema que irá processar o pdf. Valores:
     *                          auto        tenta captuar automaticamente (padrão) (segue a lógica: primeiro tenta com o pdfparser e depois com 'ws02'
     *                          pdfparser       \Smalot\PdfParser\Parser()
     *                          ws02            Java: com pdfbox
     *
     *                          //robô em Autoit em máquina local - Não habiltado nesta função
     *                          ait_ocr01       AutoIt com Google Vision OCR
     *                          ait_xpdfr       AutoIt com XpdfReader
     *                          ait_ocr01_xpdfr AutoIt com Google Vision OCR e XpdfReader
     *                          ait_ocr01_aws   AutoIt com Google Vision OCR e AWS
     *                          ait_ocr01_tessrct AutoIt com Google VIsion OCR e Tessract exe
     *                          ait_aws         AutoIt com AWS Textract
     *                          ait_tessrct     Autoit com Tesseract exe
     *
     *                          //métodos desativados
     *                          !ws01            Python: com pdfminer        (servidor webservices.aurlweb.com.br/pdf/reader-text/?file=...)    //desatviado em 08/12/2021
     *                          !ws02_ocr        PHP com Google Vision OCR   (servidor webservices2.aurlweb.com.br/pdf/reader-text-ocr.php?file=...)     //desatviado em 08/12/2021
     *
     *               string file_url - url do arquivo $file. Deve ser informado considerando a necessidade de processar o arquivo pelos engines 'ws01' e 'ws02' (que usar uma url externa para acessar o arquivo)
     *               pass - senha do arquivo (caso esteja protegido). Valores:
     *                          false       - (bool) não usará a senha (default)
     *                          '...'       - (str) com uma única senha do arquivo. Caso vazio será considerado true.
     *                          ['...',...]  - (arr) de senhas do arquivo para a função tentar uma a uma. Caso vazio será considerado true.
     *                          true        - (bool) irá tentar capturar automaticamente a partir do nome do arquivo considerando a sintaxe: {doc}-{filename}.pdf.
     *                                              Lógica: neste caso o sistema tentará acessar o arquivo com a senha capturando apenas os números de {doc} e tentando a seguinte combinação:
     *                                              1) 4, 5 ou 6 primeiros digitos a esquerda
     *                                              2) 4, 5 ou 6 últimos digitos a direita
     *              file_name - nome original do arquivo (para a captura automático da senha (informar pass=true))
     *
     * @return array['engine'=>...,'text'=>..., 'pass'=>...]
     */
    public static function readPDF($file,$opt=[]){
        $opt = array_merge([
            'page_split'=>true,
            'engine'=>'auto',
            'file_url'=>null,
            'pass'=>false,
            'file_name'=>null,
        ],$opt);

        $engine = $opt['engine'];

        //captura as senhas caso informado
        $pass = $opt['pass'];
        if($pass===true || !$pass){
            $pass = self::getPassByName( $opt['file_name'] );
        }else{
            try{
                $pass = json_decode($pass,true);
            }catch(Exception $e){
                if(!is_array($pass))$pass = [$opt['pass']];//neste caso sonsidera uma única string
            }
        }

        if(in_array($engine,['ws01','ws02_ocr','ait_ocr01','ait_xpdfr','ait_ocr01_xpdfr','ait_ocr01_aws','ait_ocr01_tessrct','ait_aws','ait_tessrct'])){
            if($engine=='ws01'){
                //Python: com pdfminer    (servidor webservices.aurlweb.com.br/pdf/reader-text/?file=...)
                $ws_pdfread = 'http://webservices.aurlweb.com.br/pdf/reader-text/?file=';

            /*//movido para outro IF
            }else if($engine=='ws02'){
                //Java: com pdfbox        (servidor webservices2.aurlweb.com.br/pdf/reader-text/?file=...)
                $ws_pdfread = 'http://webservices2.aurlweb.com.br/pdf/reader-text.php?file=';*/

            }else if($engine=='ws02_ocr'){
                //PHP com Google Vision OCR   (servidor webservices2.aurlweb.com.br/pdf/reader-text-ocr.php?file=...)
                $ws_pdfread = 'http://webservices2.aurlweb.com.br/pdf/reader-text-ocr.php?file=';
            }else if(in_array($engine,['ait_ocr01','ait_xpdfr','ait_ocr01_xpdfr','ait_ocr01_aws','ait_ocr01_tessrct','ait_aws','ait_tessrct'])){
                 return ['text'=>'Extrator '.$engine.' não habilitado nesta função','engine'=>$engine,'pass'=>$pass];
            }
            //if($engine=='ws02')dd($opt);

            //este extrator está com problemas para endereços https e por isto é trocado abaixo
            if($engine=='ws01')$opt['file_url'] = str_replace('https://','http://', $opt['file_url']);

            //converte o path de $file em url
            if($opt['file_url']){
                $url = $ws_pdfread . $opt['file_url'];
                $curl_handle=curl_init();
                curl_setopt($curl_handle, CURLOPT_URL,$url);
                curl_setopt($curl_handle, CURLOPT_TIMEOUT, 50);
                curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
                $r = curl_exec($curl_handle);
                //dd($r);
                curl_close($curl_handle);
            }else{
                $r='Url do arquivo não informado para o processador PDF ('.$engine.')';
            }

        }else if($engine=='ws02'){
            //PdfBox - java
            $filejar = base_path() .'/app/Plugins/PdfBox/bin/pdfbox-app-2.0.19.jar';
            //dd($filejar, file_exists($filejar));
            $pdfbox = (new \App\Plugins\PdfBox\PdfBox);
            $pdfbox->setPathToPdfBox($filejar);

            if($pass){//com senha
                $r='';
                foreach($pass as $p){//tenta com todas as possíveis senhas capturadas...
                    try{
                        if($pass)$pdfbox->setPass($p);
                        $r = $pdfbox->textFromPdfFile($file);
                        //se chegou até aqui, é porque deu certo a senha
                        $pass=[$p];//grava apenas a senha correta
                    }catch(Exception $e){
                        if(stripos($e->getMessage(),'InvalidPasswordException')===false){
                            continue;
                        }
                    }
                }
            }else{//sem senha
                $r = $pdfbox->textFromPdfFile($file,null);
            }

            //dd($filejar,$file,$r);

        }else{//opy[engine]=auto
            //tenta com engine=pdfparser
            $engine='pdfparser';
            $parser = new \Smalot\PdfParser\Parser();

            try{
                $pdf = $parser->parseFile($file);
                $r=$pdf->getText();
            }catch(Exception $e){//obs: pode dar erro dependendo do tipo do pdf e neste caso, tenta outro método (no padrão ws02)
                return self::readPDF($file, array_merge($opt,['engine'=>'ws02','pass'=>$pass]));
            }
            $n=FormatUtility::removeAcents($r,true);

            if($n==''){//não conseguiu extrair o texto e portanto considera que deve tentar por outro método
                if($opt['engine']=='auto'){
                    $r = self::readPDF($file, array_merge($opt,['engine'=>'ws02']));
                    $engine = $r['engine'];
                    $r = $r['text'];
                }else{
                    if($pass){
                        $r = 'Arquivo com senha não suportado para este método '. strtoupper($engine);
                    }else{
                        $r = 'Erro desconhecido na extração';
                    }
                }
            }else{
                if(empty($pdf->getObjectsByType('Pages'))){//o arquivo não tem páginas
                    try{
                        $r=$pdf->getText();
                    }catch(Exception $e){
                        $r= 'Erro ao processar arquivo '.$file .chr(10).
                            'Message: '.$e->getMessage() .chr(10).
                            'File: '.$e->getFile() .chr(10).
                            'Line: '.$e->getLine();
                    }
                }else{//existem páginas
                    $pages  = $pdf->getPages();
                    $r='';
                    foreach($pages as $index => $page){
                        if($opt['page_split']){
                            $r.='{page-start:'. ($index+1).'}'.chr(10).$page->getText().chr(10).'{page-end:'. ($index+1).'}'.chr(10);
                        }else{
                            $r.=$page->getText().chr(10);
                        }
                    }
                }
            }
        }
        return ['text'=>$r,'engine'=>$engine,'pass'=>$pass];
    }



    /**
     * Faz download de arquivo a partir uma url
     * Em análise
     */
    /*public static function downloadFile($url,$path=null,$filename=null){
            $p=STORAGEPATH;
            if($path)$p.=DIRECTORY_SEPARATOR.$path;
            if(!file_exists($p))createFolder($path);

            $ext='';
            if($filename=='*'){
                    $filename = basename($url);
                    $n=explode('.',$filename);
                    $ext=end($n);
                    $filename = uniqid().'.'.$ext;
            }else if(!$filename){
                    $filename = basename($url);
            }
            if($ext==''){
                    $n=explode('.',$filename);
                    $ext=end($n);
            }

            $path_all = $p . DIRECTORY_SEPARATOR . $filename;
            if(file_exists($path_all))unlink($path_all);

            copy($url, $path_all);
            $success = file_exists($path_all);

            return array('success'=>$success,'path'=>$path_all,'dir'=>$p,'filename'=>$filename,'ext'=>$ext);
    }
    */

    /**
     * Retorna ao número de páginas de um pdf
     * @return int
     */
    public static function numberPagesPDF($pdf){
        $pdftext = file_get_contents($pdf);
        return preg_match_all("/\/Page\W/", $pdftext, $dummy);
    }


    /**
     * Retorna a lista de senhas de capturas a partir do nome do arquivo
     * @param $filename - sintaxe: {doc}-{filename}.pdf.
     *            Lógica: neste caso o sistema tentará acessar o arquivo com a senha capturando apenas os números de {doc} e tentando a seguinte combinação:
     *            1) 4, 5 ou 6 primeiros digitos a esquerda
     *            2) 4, 5 ou 6 últimos digitos a direita
     * @return array | null
     */
    public static function getPassByName($filename){
        if(strpos($filename,'--')===false)return null;
        $doc = explode('--',$filename)[0];
        $doc = trim(str_replace(['.','-','/',' '],'',$doc));//remove a formatação
        if(!$doc)return null;
        $r=[];

        //monta as combinações
        $r[] = substr($doc,0,4);
        $n = substr($doc,0,5);  if(!in_array($n,$r))$r[]=$n;
        $n = substr($doc,0,6);  if(!in_array($n,$r))$r[]=$n;
        $n = substr($doc,-4);   if(!in_array($n,$r))$r[]=$n;
        $n = substr($doc,-5);   if(!in_array($n,$r))$r[]=$n;
        $n = substr($doc,-6);   if(!in_array($n,$r))$r[]=$n;

        return $r;
    }
}
