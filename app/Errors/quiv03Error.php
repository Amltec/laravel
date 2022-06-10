<?php

namespace App\Errors;

class quiv03Error implements _ErrorInterface{
    public static function title(){
        return 'Campos bloqueados';
    }
    
    public static function description(){
        return 'Campos bloqueados no Quiver, não foi possível atualizar.';
    }
    
    public static function descriptionAdmin(){
         return 'Campos bloqueados no Quiver, não foi possível atualizar.';
    }
    
     
    public static function solution(){
       
        return '
            - Verificar no Quiver se os campos do véiculo e prêmio estão bloqueados;<br>
            - Se o prêmio estiver bloqueado provavelmente já deve existir comissão baixada nessa apólice;<br>
            - Os campos devem ser desbloqueados no Quiver, em seguida mudar o status da apólice no painel do robô para "pronto para emitir";<br>
            - Caso os campos não sejam desbloqueados no Quiver a emissão deve ser feita manualmente, em seguida mudar o status da apólice no painel do robô para "concluído"<br>
        ';
    }
}