<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Classe responsável por retornar aos links internos do sistema
 * Utilizado pelos arquivos:
 *  - public/js/mentionjs.js
 *  - ...
 */
class LinksController extends Controller{
    
    /**
     * Relação de links
     * Sintaxe: [code => [title=>..., controller=>...]
     * Ex: [post => [title=>'Posts', controller=>posts] - Utilização: @post:123  //irá procurar o controller postsController@get_links
     * Retorno esperado: [ [code=>,title=>,url=>] ] //ex: 'code=>@post:{id}, title=>Post 123, url=>(opcional)] '
     */
    private $links=[
        'post'          => ['title'=>'Posts', 'controller'=>'Example/ExamplePostController'],
        'cadapolice'    => ['title'=>'Cadastro de Apólice', 'controller'=>'Process/ProcessCadApoliceController'],
    ];
    
    
    /**
     * Retorna a relação de links com seus respectivos códigos para a busca
     * @param $request - campos: 
     *      q        - string já digitada
     *      def     - nome da chave de $links para carregar por padrão caso 'q' não seja informado, ex :'post'
     * @return array - [ [code=>,title=>,url=>, ], ... ]    //demais parâmetros: head (boolean), disabled (boolean), class (string), attr (string)
     */
    public function get_search(Request $request){
        $q = $request->input('q');
        $def = $request->input('def');
        $r=[];
        if($q){
            foreach($this->links as $link){
                $r = $r + $this->loadController($link,$q);
            }
        }else{
            $a=$this->links[$def]??null;
            if($a)$r = $this->loadController($a,$q);
        }
        
        return $r;
    }
    
    /**
     * Converte os links para o formato ckedtiror
     */
    private function loadController($link,$q){
        $a = \App::make('\\App\\Http\\Controllers\\'. str_replace('/','\\',$link['controller']));
        if(method_exists($a,'get_links')){
            $a=$a->get_links($q);
            return [['title'=>$link['title'],'head'=>true]] + $a;
        }else{
            return [];
        }
    }
    
    
}