<?php

namespace App\Errors;

class read05Error implements _ErrorInterface{
    public static function title(){
        return 'Divergência no valor do prêmio total em relação a soma das parcelas';
    }
    
    public static function description(){
        return 'Divergência no valor do prêmio total em relação a soma das parcelas.';
    }
    
    public static function descriptionAdmin(){
         return 'Divergência no valor do prêmio total em relação a soma das parcelas.';
    }
    
     
    public static function solution(){
        return '
            - Verificar os dados do prêmio;<br>
            - O prêmio total deve ser igual a soma das parcelas do prêmio;<br>
            - O prêmio total deve ser igual a soma do prêmio líquido + juros + adicional + iof;<br>
            - Caso os dados esteja com divergência a emissão deverá ser feita manualmente no Quiver, em seguida mudar o status da apólice no painel do robô para "concluído";<br>
        ';
    }
}