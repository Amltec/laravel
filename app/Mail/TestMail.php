<?php

namespace App\Mail;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
/**
 * Classe de teste de envio de e-mail
 */
class TestMail extends Mailable{
   /*private $data;
    public function __construct($data=null){
        $this->data = $data;
    }
    */
    public function build(){
        $time=time();
        $html='Minha mensagem de texto '.$time;
        return $this
                ->from(env('MAIL_USERNAME'),env('APP_NAME'))
                ->to('aurelio@aurlweb.com.br')
                ->subject('E-mail de teste '.$time)
                //->attach(storage_path('app/image.jpg'))
                ->text('mail.blank_plain',['html'=>$html])
                ->view('mail.blank',['html'=>$html]);
    }
    
    
    /**
     * Este método é chamado pela execução da fila, adicionado pela classe App\Services\JobSerice
     * Dispara o envio de e-mail
     */
    public function JobStart(){
        Mail::send($this);
    }
}
