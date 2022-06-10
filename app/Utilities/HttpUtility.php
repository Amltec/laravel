<?php

namespace App\Utilities;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7;
use Illuminate\Support\Str;

/*
 * Classe de requisições HTTP, utilizados para consumo de APIs
 * Exemplo: return HttpUtility::post($url,['a'=>1,'b'=>2]);
 */
class HttpUtility{

    public static function get($url, $fields=[], $options=[]){
        return self::request('get',$url,$fields,$options);
    }

    public static function post($url, $fields=[], $options=[]){
        return self::request('post',$url,$fields,$options);
    }

    public static function put($url, $fields=[], $options=[]){
        return self::request('put',$url,$fields,$options);
    }

    public static function delete($url, $fields=[], Array $options=[]){
        return self::request('delete',$url,$fields,$options);
    }

    public static function upload($url, $file_path, $fields=[], Array $options=[]){
        $options=[
            'multipart'=>[
                ['name'=>'file', 'contents' => fopen($file_path, 'r')],
            ],
        ];
        //adiciona os demais campos
        foreach($fields as $f => $v){
            $options['multipart'][]=['name'=>$f,'contents'=>$v];
        }
        //dd('options',$options);
        return self::request('upload',$url,null,$options);
    }

    /**
     * Executa a requisição http
     * @param $method - valores: get, post, put, delete, upload
     * @param $url - ...
     * @param array $fields - [field=>value,...]
     * @param array $options - (array) headers, (int) timeout seconds
     * @return array - para success=true [success,data,code,http],    para success=false [success,msg,data,exception]
     */
    public static function request($method,$url,$fields=[],$options=[]) {
        $client = new Client();
        $method = strtoupper($method);

        $http_opt=[];   //'allow_redirects'=>true
        if($method=='UPLOAD'){
            $method='POST';
            $http_opt = $options;
            //obs: $fields não é usado aqui
        }else{
            if($method=='GET'){
                $http_opt['query']=$fields;
            }else{
                $http_opt['form_params']=$fields;
            }
            if($options['headers']??false){
                $http_opt['headers']=$options['headers'];
                unset($options['headers']);
            }
            if($options['timeout']??false){
                $http_opt['connect_timeout']=$options['timeout'];
                unset($options['timeout']);
            }
        }
        $http_opt = array_merge($http_opt,$options);
        //dd($http_opt);

        try{
            $res = $client->request($method,$url,$http_opt);
            return self::ret($res);

        }catch(RequestException $e){
            $r = '';//Psr7\Message::toString($e->getRequest());
            if($e->hasResponse()){
                //$r .= Psr7\Message::toString($e->getResponse()) ;
                $r .= $e->getMessage();
                //dd($r);
            }
            $r = self::getTextError($r);
            return ['success'=>false,'data'=> $r,'exception'=>$e,'msg'=>self::getMsgCode($e->getCode()),'code'=>$e->getCode(),'error_type'=>'request'];

        }catch(ClientException $e){
            $r = Psr7\Message::toString($e->getRequest());
            $r .= Psr7\Message::toString($e->getResponse());
            $r =self::getTextError($r);
            return ['success'=>false,'data'=>$r,'exception'=>$e,'msg'=>self::getMsgCode($e->getCode()),'code'=>$e->getCode(),'error_type'=>'client'];

        }catch(Exception $e){
            return ['success'=>false,'data'=>$e->getMessage(),'exception'=>$e,'msg'=>self::getMsgCode(500),'code'=>500,'error_type'=>'exception'];
        }
    }

    /**
     * Executa a requisição http com o padrão já fazendo um login e depois solicitando com os dados como o token de acesso
     * @param array $params - valores:
     *              url     - ex: http://localhost:8080/api/
     *              auth    - array de campos de autenticação, ex: [user_name=>,user_pass=>...]
     *              login   - nome da url de login. Default 'login'.
     *              action  - nome da ação, ex: 'me'   //irá enviar para http://localhost:8080/api/me
     *              method  - método da ação. Default 'get'
     *              params  - array parâmetros adicionas, ex: [a=1, b=>2]
     * @return self::request()
     * @Example: requestAction([ url=>'http::localhost:8080', auth=>[user_name=>'login',user_pass'=>senha]  action=>'me', params=[...] ])
     */
    /*public static function requestTo($params){
        $params = array_merge([
            'url'=>'',
            'auth'=>[],
            'login'=>'token/login',
            'action'=>'',
            'method'=>'get',
            'params'=>[],
        ],$params);
        extract($params);

        //faz o login
        $r = self::request('post',$url.'/'.$login,$auth);
        if(!$r['success'])return $r;
        $data=$r['data'];

        //captura os dados
        $params['token']=$data['access_token'];    //obs: considera que estes nomes sejam padroes dentro da estrutura da api informada em $params[url]
        $r = self::request($method,$url.'/'.$action,$params);
        return $r;
    }*/


    //******* private functions ******
    private static function ret($res){
        $n=$res->getHeader('content-type')[0]??null;
        if($n=='application/json'){
            $r = json_decode($res->getBody(),true);
        }else{
            $r = $res->getBody()->getContents();
        }
        return ['success'=>true,'data'=>$r,'code'=>$res->getStatusCode()];  //,'http'=>$res
    }

    //Retorna ao texto de uma mensagem caso esteja em html
    private static function getTextError($text){
        /*if(stripos($text,'body')!==false){
            if(preg_match('~<body[^>]*>(.*?)</body>~si', $text, $body)){
                $text = strip_tags($body[1]);
                $text = preg_replace('/\s{2,}/', "\n", $text); // (**)
                $text = trim($text);
            }
        }*/
        return Str::limit($text,1000);
    }


    //Retorna ao texto a partir do código de erro
    private static function getMsgCode($code){
        return [
            200 => 'Ok',
            401 => 'Não autorizado',
            404 => 'Página não encontrada',
            405 => 'Método não permitido',
            500 => 'Erro interno de servidor',
            502 => 'Bad Gateway',
            504 => 'Gateway Timeout',
            419 => 'Página Expirada',
        ][$code] ?? 'Erro '.$code;
    }
}
