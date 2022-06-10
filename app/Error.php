<?php
namespace App;

/**
 * Retorna a informações de errors
 * Dados na pasta \App\Errors\{code}Error
 * O campo $code corresponde ao código do erro, que existe em arquivo na pasta \App\Errors\ e também nas pastas dos controllers do processo (ex \App\Http\Controllers\Process\ProcessCadApoliceController::$statusCode)
 */
class Error{
    private static $list=[];
    
    /**
     * Retorna se o erro existe
     */
    public static function exists($code){
        return self::getClass($code)?true:false;
    }
    
    /**
     * Retorna ao código do erro
     */
    public static function get($code,$field='',$default=''){
        $class = self::getClass($code);
        if($class){
            if($field=='description_admin' || $field=='admin'){
                return $class::descriptionAdmin();
            }else if($field=='solution'){
                return $class::solution();
            }else{//$field=description
                return $class::description();
            }
        }else{
            return $default;
        }
    }
    
    
    private static function getClass($code){
        if(!array_key_exists($code,self::$list)){
            $class = '\\App\\Errors\\'. strtolower($code) .'Error';
            if(class_exists($class)){
                self::$list[$code] = $class;
            }else{
                self::$list[$code] = null;
            }
        }
        return self::$list[$code];
    }
    
}
