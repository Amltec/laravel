<?php
namespace App\Utilities;
use Url;
use Request;

/*
 * Classe de formatação de string HTML
 */
class HtmlUtility{
    
    /*
     * Verifica se o html está vazio
     */
    public static function isEmpty($html){
        $r = str_replace([chr(10),chr(13),chr(9),'&nbsp;',''],'',self::removeHTML($html));
        return trim($r)=='';
    }
    
    /**
     * Retorna ao target='_blank' automático no caso da url ser externa a do site.
     */
    public static function targetHref($url){
        $site_url=\URL::to('/');
        $r='';
        if($url!=''){
                $a=substr($url,0,1);
                if($a!='#' && $a!='/'){
                        $a=str_replace('http://','',str_replace('https://','',	strtolower($site_url)		));
                        $b=str_replace('http://','',str_replace('https://','',	strtolower(substr($url,0,strlen($site_url)))	));
                        $a=$a ? explode('?',$a)[0] : '';
                        $b=$b ? explode('?',$b)[0] : '';
                        if($a && $b && strpos($a,$b)===false)$r='target="_blank"';
                }
        }
        return $r;
    }
    
    /**
     * Convete array to attributes html. Ex: de ['a'=>1,'b'=>2] para 'a="1" b="2"'
     */
    public static function buildAttributes($attributes){
        if(empty($attributes))return '';
        if(!is_array($attributes))return $attributes;
        
        $attributePairs = [];
        foreach($attributes as $key => $val){
            if(is_int($key)){
                $attributePairs[] = $val;
            }else{
                $val = htmlspecialchars($val, ENT_QUOTES);
                $attributePairs[] = "{$key}=\"{$val}\"";
            }
        }

        return join(' ', $attributePairs);
    }
    
    
    /**
     * Sanitiza o html, deixando somente as tags principal (a tag <p> por ex é removida)
     */
    public static function sanitizeHTML($html){
        return strip_tags($html,'<h1><h2><h3><h4><h5><h6><img><a><strong><em><b><i><ul><li><blockquote><div><span><table><thead><tbody><tfooter><tr><th><td>');
    }
    
    
    /**
     * Remove todo o html da string
     */
    public static function removeHTML($html){
        return strip_tags($html);
    }
    
    /**
     * Formata um texto pura em HTML (ex insere as tags <br>, etc)
     */
    public static function formatHTML($pee, $br = true){//equivale a função wpautop() do wordpress
        return \App\Utilities\Addons\HtmlUtilityFormatAddon::formatHTML($pee, $br);
    }
    
    /**
     * Formata string em markdown
     */
    public static function markdown($string){
        $parsedown = new \Parsedown();
        return $parsedown->text($string);
    }
    
    /**
     * Altera no texto um domínio do sistema de uma url pelo código {url_base}
     */
    public static function urlDomainToCode($text){
        $n = URL::to('/');
        $text = str_ireplace($n, '{url_base}', $text);      //http[s]://...
        $text = str_ireplace(str_ireplace(['http://','https://'],'',$n), '{url_base_w}' ,$text);    //only www...
        return $text;
    }
    
    /**
     * Altera no texto o código {url_base} pelo domínio do sistema de uma url
     */
    public static function urlCodeToDomin($text){
        $n = URL::to('/');
        $text = str_ireplace('{url_base}', $n, $text);
        $text = str_ireplace('{url_base_w}', str_ireplace(['http://','https://'],'',$n), $text);
        return $text;
    }
    
    /**
     * Retorna a url querystring atual mesclada a um array 
     * @param array arr - valores a serem mesclados
     * @param array $opt - (boolean) full, (boolean) encode
     * @return string
     */
    public static function rqArr($arr,$opt=[]){
        $u=Request::fullUrlWithQuery($arr);
        if($opt['full']??false){
            //nenhuma ação
        }else{
            $u = substr($u, strpos($u,'?')+1 , strlen($u));
        }
        if($opt['encode']??false)$u = urlencode($u);
        return $u;
    }
    
    /**
     * Adiciona um campo querystring a partir da string url
     * @return string
     */
    public static function addQS($url,Array $qs){
        if(strpos($url,'?')===false)return $url;
        $a=parse_url($url);
        parse_str($a['query'],$q);
        $q = array_merge($q,$qs);
        return $a['scheme'].'://'.$a['host'].'/'.$a['path'].'?'.http_build_query($q);
    }
    
    
    /**
     * Retorna ao html de importação js css
     * @param $type - css, js
     * @param $files - string|array
     */
    public static function importJSCSS($type,$files,$name='',$version=''){
        if(!is_array($files))$files=[$files];
        $r='';
        foreach($files as $f){
            if(substr($f,0,1)=='/')$f=url('/').$f;
            if($version)$f.='?ver='.$version;
            if($type=='js'){
                $r.='<script data-name="'.$name.'" src="'.$f.'"></script>';
            }else{//css
                $r.='<link data-name="'.$name.'" rel="stylesheet" href="'.$f.'" />';
            }
        }
        return $r;
    }
}