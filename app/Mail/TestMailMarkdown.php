<?php

namespace App\Mail;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;


use Illuminate\Notifications\Messages\MailMessage;

/**
 * Classe de teste de envio de e-mail
 */
class TestMailMarkdown extends Mailable{
   /*private $data;
    public function __construct($data=null){
        $this->data = $data;
    }
    */
    public function build(){
        $time=time();
        return $this
                ->from(env('MAIL_USERNAME'),env('APP_NAME'))
                ->to('aurelio@aurlweb.com.br')
                ->subject('E-mail de teste '.$time)
                //->attach(storage_path('app/image.jpg'))
                //->text('mail.blank_plain',['html'=>$html])
                ->markdown('mail.blank_markdown',['html'=>"Minha mensagem de texto".$time]);

    }
    
    
    public function message(){
        return (new MailMessage)
                ->greeting('Hello!')
                ->line('One of your invoices has been paid!')
                ->action('View Invoice', '#')
                ->line('Thank you for using our application!');
    }
    
    
    /**
     * Este método é chamado pela execução da fila, adicionado pela classe App\Services\JobSerice
     * Dispara o envio de e-mail
     */
    public function JobStart(){
        Mail::send($this);
    }
}
