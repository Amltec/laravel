<?php

namespace App\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use App\Utilities\FormatUtility;
use Auth;
use App\Services\MetadataService;
use App\Utilities\ImageUtility;
use Config;

class FilesDirectService{
    private static $mimetypes_not=['application/x-msdownload'];
    private static $folders_not=['uploads','_tmp','_system','images'];//pastas negadas para criação ou exclusão automático de arquivos


    /**
     * Faz o upload o arquivo
     * @param $file - deve conter o objeto request file, ex: $request->file('field_upload_name');
     * @param $opt - (opcional) valores:
     *          string filename - se informado, irá gravar o arquivo com este nome e caso exista será sobrescrito. Sintaxe: 'filename.ext'
     *          string folder
     *          int|string folder_id - se informado, irá gerar uma pasta com este ID dentro da pasta final gerado nesta função
     *          booelan private
     *          boolean account_off - se true, considerado o diretório /app ao invés do diretório accounts
     *          string account_id  - id da conta. Se não informado, irá capturar o valor de Auth::User()->getAuthAccount('id'). Válido apenas para account_off=false
     *          array metadata - sintaxe: [meta_name,meta_value]    //obs: todos os 4 parâmetros precisam serem informados
     *          boolean folderdate - se false, irá gravar o arquivo diretamente no diretório informado, se true irá gravar com o ano/mês. Default false
     *          accept      - (array) mimetypes aceitos. Caso não passe na validação retornará a um erro. Opcional.
     *
     *          //somente se o upload for uma imagem
     *          max_width   - (int) default false
     *          max_height  - (int) default false
     *          image_fit   - (boolean) se false corta a imagem se necessário para manter as dimensões exatas, se true ajusta as dimensões da imagem para para caber dentro do tamanho informado
     */
    public static function uploadFile($file,$opts=[]){
        if(empty($file))return ['success'=>false,'msg'=>'Erro no upload. Campo vazio.'];

        $opt = array_merge([
            'filename'=>'',
            'folder'=>'files',
            'folder_id'=>null,
            'private'=>false,
            'account_off'=>false,
            'account_id'=>null,
            'metadata'=>false,
            'max_width'=>false,
            'max_height'=>false,
            'image_fit'=>true,
            'folder_date'=>false,
            'date'=>date('Y-m-d'),//data de referência para localização da pasta
            'accept'=>[],
        ],$opts);

        //indica o modo de origem deste upload. Valores: form | path
        $upl_method='form';

        if(gettype($file)=='string'){//quer dizer que foi informado um caminho do arquivo
            if(!file_exists($file))return ['success'=>false,'msg'=>'Arquivo não encontrado para realizar upload.'];
            $file = new \Symfony\Component\HttpFoundation\File\File($file);
            $upl_method = 'path';
        }


        //ajusta a var mimetype
        if(!empty($opt['accept']) && !is_array($opt['accept']))$opt['accept']=[$opt['accept']];
        if($opt['accept']){
            foreach($opt['accept'] as $i => $m){
                if($m=='*'){//quer quizer que todos os tipos são aceitos, e portanto
                    $opt['accept']='*';//redefine a var
                    break;
                }elseif($m=='image/*'){
                    unset($opt['accept'][$i]);
                    $opt['accept']+=['image/jpeg','image/gif','image/png'];
                }
            }
        }


        if(in_array($opt['folder'],self::$folders_not))return  ['success'=>false,'msg'=>'Pasta '.$opt['folder'].' negada'];

        $path = self::createStoragePath([
            'folder'=>$opt['folder'],
            'folder_id'=>$opt['folder_id'],
            'private'=>$opt['private'],
            'account_off'=>$opt['account_off'],
            'account_id'=>$opt['account_id'],
            'folder_date'=>$opt['folder_date'],
            'date'=>$opt['date']
        ]);

        //dump('count 1', $file,$opts);
        $post_max_size = FormatUtility::bytesVal(ini_get('upload_max_filesize'));//retorna ao valor em bytes
        if(empty($post_max_size))$post_max_size=50*1000*1024;//em bytes 50MB;
        $size = $file->getSize();
        //dump('***',$size);

        //tamanho máximo do arquivo
        if(empty($size) || $size>$post_max_size){
            return ['success'=>false,'msg'=>'Tamanho de arquivo não permitido, maior que '. FormatUtility::bytesFormat($post_max_size)];
        }

        if($upl_method=='form' && !$file->isValid()){
            return ['success'=>false,'msg'=>'Ocorreu algum erro ao carregar o arquivo. Tente novamente'];
        }

        //$file = $request->file($uploadname);// já capturado acima
        $minetype = $file->getMimeType();
        //mime types negados
        if(in_array($minetype, self::$mimetypes_not))return ['success'=>false,'msg'=>'Tipo de arquivo não permitido'];
        //mime types aceitos
        if($opt['accept']!='*' && !empty($opt['accept']) && !in_array($minetype, $opt['accept']))return ['success'=>false,'msg'=>'ATipo de arquivo não permitido Formatos aceitos: '. join(', ',$opt['accept']) .'.'];

        $is_image = in_array($minetype,['image/jpeg','image/gif','image/png']);//se true, é uma imagem


        //inicia o upload
        try{
            $originalname = $upl_method=='form' ? $file->getClientOriginalName() : $file->getFilename();


            if($opt['filename']){//nome já definido, sobrescreve se necessário
                $filename = $opt['filename'];
                $ext = strtolower(pathinfo($filename,PATHINFO_EXTENSION));//extensão do arquivo
                $filename = basename($opt['filename'],'.'.$ext);//tira a extensão

            }else{
                //verifica se o arquivo já existe com este nome e caso exista, renomeia-o
                $ok=false;
                $ext = strtolower(pathinfo($originalname,PATHINFO_EXTENSION));//extensão do arquivo
                $filename = str_slug(substr($originalname,0,strlen($originalname)-strlen($ext)-1));//somente o nome do arquivo sem extensão
                if(empty($filename) || empty($ext)){
                    return ['success'=>false,'msg'=>'Nome do arquivo ou extensão inválido'];
                }

                $filename_copy = $filename;
                for($i=1;$i<=1000;$i++){//tenta 1000x
                    if(file_exists($path['basedir'] .DIRECTORY_SEPARATOR . $filename.'.'.$ext)){
                        $filename = $filename_copy.'-'.$i;//renomeia o arquivo para tentar novamente
                    }else{//arquivo não existe
                        $ok=true;break;
                    }
                }
                if(!$ok){
                    return ['success'=>false,'msg'=>'Erro ao gravar o arquivo (code: TryNameRepeat). Tente novamente alterando o nome do arquivo.'];
                }
            }
            //dd($path,$filename,$ext);
            //grava o arquivo no diretório
            try{
                $file->move($path['basedir'], $filename.'.'. $ext);

                $r = self::getInfo([
                        'filename'=>$filename.'.'.$ext,
                        'folder'=>$path['relative_dir'],
                        'folder_id'=>$opt['folder_id'],
                        'private'=>$opt['private'],
                        'account_off'=>$opt['account_off'],
                        'account_id'=>$opt['account_id'],
                     ],'array');
               //parâmetros adicionais retornados
               $r['upl_method']=$upl_method;
               $r['filename_original']=$originalname;

            } catch (\Exception $e) {
                $r=['success'=>false,'msg'=>$e->getMessage()];
            }

        } catch (\Exception $e) {
            $r=['success'=>false,'msg'=>$e->getMessage()];

        }

        //verifica se o arquivo é uma imagem, e redimenciona conforme parâmetro
        if($is_image && is_numeric($opt['max_width']) && is_numeric($opt['max_height'])){
            $w=(int)$opt['max_width'];
            $h=(int)$opt['max_height'];
            if($w>0 && $h>0){
                //dd($w,$h,$r['file_path'], $path['basedir'], $opt['image_fit']);
                ImageUtility::resizeThumbnail($w, $h, $r['file_path'], $r['file_path'], $opt['image_fit']);
            }
        }

        //grava os dados metadados
        if($opt['metadata']){//sintaxe: [area_name=>,area_id,meta_name,meta_value]
            $m=$opt['metadata'];
            if(isset($m['area_name']) && isset($m['area_id']) && isset($m['meta_name']) && isset($m['meta_value']))MetadataService::set($m['area_name'],$m['area_id'], $m['meta_name'], $m['meta_value']);
        }

        //unset($r['file_path'],$r['file_dir']);//remove estes parâmetros, pois não são necessários no retorno json
        //return response()->json($r);

        return $r;
    }


    /**
     * Cria o caminho da pasta e retorna ao endereço do diretório
     * @param array $opt (opcionais):
     *  boolean private
     *  int account_id
     *  string folder
     *  int|string folder_id
     *  string filename
     * @return array - dir, basedir
     */
    public static function createStoragePath($opts){
        $opt = array_merge([
            'private'=>false,
            'account_id'=>null,
            'folder'=>'files',
            'folder_id'=>null,
            'date'=>date('Y-m-d'),//data de referência para localização da pasta
            'filename'=>'',         //se informado, retorna na função o nome do arquivo ao caminho apenas
            'account_off'=>false,   //se true, considerado o diretório /app ao invés do diretório accounts
            'folder_date'=>false,   //se false, irá gravar o arquivo diretamente no diretório informado, se true irá gravar com o ano/mês. Default false
        ],$opts);

        $prefix = Config::adminPrefix();
        if($prefix=='super-admin'){
            if($opt['account_id']==null){
                $opt['account_off']=true;
            }
        }

        $user = Auth::User();//usuário logado
        if($opt['private'] && !$user){//tenta acessar a pasta privada mas não está logado
            if(!$opt['account_id'])//o id da conta também não foi informado
                exit('FilesDirectService::createStoragePath() - Acesso negado');
        }

        if($opt['account_off']===true){
            $path = ($opt['private']?'app':'storage'.DIRECTORY_SEPARATOR.'app').
                    DIRECTORY_SEPARATOR . $opt['folder'];

        }else{
            if(empty($opt['account_id']))$opt['account_id']=$user->getAuthAccount('id');

            $path = ($opt['private']?'accounts':'storage'.DIRECTORY_SEPARATOR.'accounts').
                    DIRECTORY_SEPARATOR . $opt['account_id'].
                    DIRECTORY_SEPARATOR . $opt['folder'];
        }

        //separado por data
        $rel_dir='';
        if($opt['folder_date']){
            $oDate= \DateTime::createFromFormat('Y-m-d', $opt['date']);
            $rel_dir=DIRECTORY_SEPARATOR . $oDate->format('Y').
                     DIRECTORY_SEPARATOR . $oDate->format('m');
            $path.=$rel_dir;
        }

        if($opt['folder_id'])$path .= DIRECTORY_SEPARATOR . $opt['folder_id'];

        //ajusta o diretório para a pasta pública ou privada
        $path_all = $opt['private']?storage_path($path):public_path($path);

        //cria o diretório
        if(!file_exists($path_all)){
            (new Filesystem)->makeDirectory($path_all, $mode = 0777, true, true);
        }

        $r=[
            'relative_dir'=>$opt['folder'].$rel_dir,
            'dir'=>$path,
            'basedir'=>$path_all,
        ];
        if($opt['filename']){
            $r['dir'].=DIRECTORY_SEPARATOR.$opt['filename'];
            $r['basedir'].=DIRECTORY_SEPARATOR.$opt['filename'];
        }
        return $r;

    }




     /**
     * Retorna as informações do arquivo (para ser usado na view)
     * @param array $opt (opcionais):
     *  string filename     - nome do arquivo
     *  string folder       - default 'files'
     *  int|string folder_id
     *  boolean private     - default false
     *  int account_id      - opcional. Default current account id user logged
     *  string account_off  - opcional. Se true, considerado o diretório /app ao invés do diretório accounts
     *  @param return      - json|object|array (return $file)
     */
    public static function getInfo($opts,$return='array'){
        $opt = array_merge([
            'filename'=>'',         //se informado, retorna na função o nome do arquivo ao caminho apenas
            'folder'=>'files',
            'folder_id'=>null,
            'private'=>false,
            'account_off'=>false,    //se true, considerado o diretório /app ao invés do diretório accounts
            'account_id'=>null
        ],$opts);

        $path = self::createStoragePath([
            'filename'=>$opt['filename'],
            'folder'=>$opt['folder'],
            'folder_id'=>$opt['folder_id'],
            'private'=>$opt['private'],
            'account_id'=>$opt['account_id'],
            'account_off'=>$opt['account_off'],
        ]);

        $path_all = $path['basedir'];

        if(!file_exists($path_all)){
            $r= ['success'=>false,'msg'=>'Arquivo '.$path['dir'].' não localizado'];
        }else if(is_dir($path_all)){
            $r= ['success'=>false,'msg'=>'Pasta ou tipo não suportado para leitura'];
        }else{
            $file_info = pathinfo($path_all);

            if($opt['private']){
                $url_serial = base64_encode(serialize($opt));
                $file_url = route('app.filedirect.load',['files',$url_serial]);
            }else{
                $file_url = url($path['dir']);
            }
            $r =[
                'success'=>true,
                'file_url'=>str_replace('\\','/',$file_url), //url
                'file_name' => $file_info['filename'],
                'file_name_full' => $file_info['filename'].'.'.$file_info['extension'],
                'file_size' => filesize($path_all),
                'file_mimetype' => mime_content_type($path_all),
                'file_path' => $path_all,//caminho completo
                'file_dir' => $file_info['dirname'],
                'file_relative_dir'=>$path['relative_dir'],
                'file_ext' => $file_info['extension'],
                'private' => $opt['private'],
                'file_lastmodified' => filemtime($path_all),//return timestamp
                'is_image' => in_array($file_info['extension'],['jpg','jpeg','gif','bmp','png','bmp','svg']),

                //estes são os dados para armazenar em algum input text (como da view templates.components.uploadbox.blade
                'data_serialize'=> serialize([
                    'filename'=>$opt['filename'],
                    'folder'=>$opt['folder'],
                    'private'=>$opt['private'],
                    'account_off'=>$opt['account_off'],
                    'account_id'=>$opt['account_id'],
                ])
            ];
            if($r['is_image']){
                $sizes = getimagesize($path_all);
                $r['width']=$sizes[0];
                $r['height']=$sizes[1];
            }
        }
        if($return=='json'){
            return json_encode($r,true);
        }else if($return=='object'){
            return (object)$r;
        }else{
            return $r;
        }
    }



    /**
     * Deleta o arquivo
     * @param array $opt - são os mesmos de self::getInfo
     */
    public static function removeFile($opt){
        $file = self::getInfo($opt);
        if(!$file['success'])return $file;

        try{
            //deleta o arquivo
            $p=$file['file_path'];
            //dd($p,$id);
            if(file_exists($p)){
                (new Filesystem)->delete($p);
            }
            $r=['success'=>true,'msg' => 'Arquivo deletado'];
        }catch(\Exception $e) {
            $r=['success'=>false,'msg'=>$e->getMessage()];
        }
        return $r;
    }
}
