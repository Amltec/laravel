<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\Base\DefaultServiceMail;
use App\Services\JobService;


/**
 * Classe de serviços responsável pelo envio de e-mails
 * Exemplos:
 *       MailService::sendClass(new \App\Mail\TestMail());        //envio por classe
 *       MailService::send(['to'=>'aurelio@aurlweb.com.br','subject'=>'E-mail de teste','message'=>'Minha mensagem de texto']);   //envio por parâmetro
 */
class MailService{
    
    //cada chave deste array representa um arquivo de e-mail na pasta \App\Mail
    public static $mails=[
        'test'=>'E-mail de teste de sistema',
        //'account_create'=>'E-mail enviado após a criação da conta',
        //'account_login_data'=>'E-mail enviado com os novos dados de acesso',
    ];
    
    
    /**
     * Retorna ao conteúdo da view/markdown do e-mail
     * @param $class - ex: new \App\Mail\TestMail();
     * @return string
     */
    public static function viewMail($class){
        return $class->render();
    }
    
    
    /**
     * Dispara um envio de e-mail padrão a partir de uma classe
     * Ex: $mail->sendClass(new \App\Mail\TestMail());
     */
    public static function sendClass($class,$opt=[]){
        $opt = array_merge(['queue'=>true,'tries'=>null,'retry_after'=>null],$opt);//veja mais informações na classe send()
        self::dispatchMail($class,$opt);
    }
    
    
    /**
     * Envia de e-mail a partir dos parâmetros
     */
    public static function send($opt=[],$view=null,$view_params=[]){
        $opt = array_merge([
            'from'=>env('MAIL_USERNAME'),
            'from_name'=>env('APP_NAME'),
            'to'=>null,                 //mail ou [mail,mail,...]
            'cc'=>null,                 //mail ou [mail,mail,...]
            'bcc'=>null,                //mail ou [mail,mail,...]
            'subject'=>null,            //
            'message'=>null,            //message text or html
            'text'=>null,               //plain text
            'is_html'=>false,           //true to message html
            'attachments'=>null,        //file ou [file,file...]
            'markdown'=>false,          //template markdown
            
            //fila - valores
            //  false   - envio imediato
            //  true    - na fila para enviar o mais rápido possível
            //  (int)   - na fila para enviar após N minutos
            'queue'=>true,
            
            //para de fila (queue!=false)ficao caso de erro no envio número de tentativas em caso de falha
            'tries'=>null,          //número máximo de tentativas. Default 3
            'retry_after'=>null     //tempo em minutos da segunda tentativa em diante. Default 1
        ],$opt);
        
        $view = $view ?? ($opt['markdown']?'mail.blank_markdown':'mail.blank');
        
        if(!is_array($view_params))$view_params=[];
        $view_params[ ($opt['is_html']?'html':'text') ] = $opt['message'];
        
        self::dispatchMail( new DefaultServiceMail($view,$view_params,$opt) ,$opt);
    }
    
    
    /**
     * Executa o comando de enviar e-mail considerando a fila
     */
    private static function dispatchMail($class,$opt){
        $q = $opt['queue'];
        $opt2 = array_intersect_key($opt, array_flip(['tries','retry_after']));
        
        if(is_int($q) && $q>0){//na fila para envio após N minutos
            JobService::send($class,$opt2)->delay( now()->addMinutes($q) );
            
        }elseif($q===true){//na fila
            JobService::send($class,$opt2);
            
        }else{//envio imediato
            Mail::send($class);
        }
    }
    
}