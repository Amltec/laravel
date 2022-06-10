<?php

namespace App\Mail\Base;
use Illuminate\Mail\Mailable;


/**
 * Classe complementar do serviço App\Mail\MailService->send() para envio direto de e-mail por parâmetros
 */
class DefaultServiceMail extends Mailable{
    public $view;
    public $view_params;
    public $opt;
    
    public function __construct($view,$view_params,$opt){
        $this->view = $view;
        $this->view_params = $view_params;
        $this->opt = $opt;
    }

    public function build(){
        $opt = $this->opt;
        $mail = $this->from($opt['from'],$opt['from_name'])
                ->to($opt['to'])
                ->subject($opt['subject']);
                
        if($opt['cc'])$mail->cc($opt['cc']);
        if($opt['bcc'])$mail->bcc($opt['bcc']);
        if($opt['text'])$mail->text($opt['text']);

        $as=$opt['attachments'];
        if($as){
            if(!is_array($as))$as=[$as];
            foreach($as as $a){
                if(file_exists($a))$mail->attach($a);
            }
        }
        
        if($opt['markdown']){
            return $mail->markdown($this->view,$this->view_params);
        }else{
            return $mail->view($this->view,$this->view_params);
        }
    }
    
    /**
     * Este método é chamado pela execução da fila, adicionado pela classe App\Services\JobSerice
     * Dispara o envio de e-mail
     */
    public function JobStart(){
        \Illuminate\Support\Facades\Mail::send($this);
    }
}
