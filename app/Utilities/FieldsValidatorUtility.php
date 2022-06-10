<?php
namespace App\Utilities;
/*
 * Classe de verificações e formatações do comando validator() do laravel (para post store e edit de dados)
 * 
 */
Class FieldsValidatorUtility{
   
    
    /**
     * Retorna a um array com o padrão de mensagens de validação. 
     * Utilizar para as mensagens do Validator nos métodos store e edit.
     * Estas mensagens estão ajustadas para serem serem a função /public/js/admin.js->awFormAjax() (string {{::attribute}}
     */
    public static function getMessages(){
        return [
            'required' => 'Campo obrigatório',//{{:attribute}} é obrigatório
            'min' => 'Mínimo de :min caracteres',
            'max' => 'Máximo de :max caracteres',
            'required_with' => 'Valor não compatível {{:values}}',
            'same' => 'Os valores dos campos não combinam',
            'unique' => 'Este valor já está cadastrado',
            'required_if'=>'Campo obrigatório',
            'required_if'=>'Campo obrigatório',
            'date'=>'Data inválida',
            'date_format'=>'Data inválida',
            'email'=>'E-mail inválido',
            'mimes'=>'O arquivo deve estar no formato: png, gif, jpg, jpeg.',
            'digits'=>'O campo precisa ter {{:values}} digits',
            'numeric'=>'O campo deve ser um número',
            
        ];
    }
    
    
    /**
     * Permite customizar as mensagens padrões retornadas pelo comando Validator(...)->errors()->messages()
     * @param array $msgs - mensagens de Validator(...)->errors()->messages()
     * @param string $field_name - nome do campo de $msgs
     * @param string $from_msg - texto original do campo $msgs[$field_name] para comparar e substituir
     * @param string $new_msg - texto para substituição
     * @return array $msgs
     */
    public static function customMessages($msgs,$field_name,$from_msg,$new_msg){
        if(isset($msgs[$field_name])){
            $m=$msgs[$field_name];
            if(is_array($m)){
                if(trim(strtolower($m[0]))==trim(strtolower($from_msg)) || empty($value_from)){
                    $msgs[$field_name][0]=$new_msg;
                }
            }else{
                if(trim(strtolower($m))==trim(strtolower($from_msg)) || empty($value_from)){
                    $msgs[$field_name]=$new_msg;
                }
            }
        }
        return $msgs;
    }
    
}