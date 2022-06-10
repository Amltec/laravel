<?php

namespace App\Http\Controllers\AdminClass;
use Gate;

/**
 * Classe base admin class para ser extendida
 */
class BaseClass{
    //somente usuários autenticados podem acessar este controle (autenticação pelo controller)

    //esta variável deve ser modificado pelo respectivo controller para que contenha o nome do menu selecionado (atributo name pelo método getMenus()) (opcional)
    //Obs: esta variável é usada dentro da visa templates.admin.menuleft.blade
    public static $menuSelected='';//menu a ser selecionado
    public $prefix='';//para palavras compostas usar '-', ex: super-admin



    function __construct(){
        //armazena o prefixo do admin, capturando o primeiro diretório depois da urlbase do site para
        $this->prefix = \Config::adminPrefix();
    }


    //***** Funções recomendadas *****

    /**
     * Matriz de menus - sintaxe:
     *      'name' => [
     *          title   =>'...',
     *          link    =>'#',
     *          icon    =>'...',
     *          sub     =>[ ... ],
     *          can     =>'...',
     *          header  =>(bool)    //neste caso apenas o parâmetro 'title' é considerado
     *      ],
     **/
    public static function getMenus(){
        return ['dashboard'=>['title'=>'Painel','link'=>'#','icon'=>'home']];
    }

    //html antes e deposi de iniciar o html
    public static function menuHeader(){return '';}
    public static function menuFooter(){return '';}

    //scripts css,js
    public static function scriptsHeader(){return '';}
    public static function scriptsFooter(){return '';}


    /**
     * Retorna a um array com o menu principal o menu principal
     */
    public function getMenuMain(){
        $menumain = $this->getMenus();

        //verifica as autorizações
        foreach($menumain as $name=>$opt){
            if(isset($opt['can'])){
                if(Gate::denies($opt['can'])){//é negado a permissão
                    unset($menumain[$name]);//remove do menu
                }
            }
            if(isset($opt['sub']) && is_array($opt['sub'])){
                foreach($opt['sub'] as $name2=>$opt2){
                    if(isset($opt2['can'])){
                        if(Gate::denies($opt2['can'])){//é negado a permissão
                            unset($menumain[$name]['sub'][$name2]);//remove do submenu
                        }
                    }
                }
            }
        }

        return $menumain;
    }


    /**
     * Formato o link
     * @param array|string $link    - array sintaxe: 0 routename, 1 routeparams
     * @return string route link
     */
    protected static function formatLink($link){
        if(is_array($link)){
            $r=route($link[0],$link[1]);
        }else if($link){
            $r=$link;
        }else{
            $r='#';
        }
        return $r;
    }

}
