<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;


class TestNotification extends Notification implements ShouldQueue{
    use Queueable;
    
    private $data;
    public function __construct($data=null){
        $this->data = $data;
    }
   

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable){
        return ['mail','database'];
        //return [\App\Channels\WhatsappChannel::CLASS];
    }

    /**
     * Get the mail representation of the notification.
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable){
        //return \App\Services\MailService::sendClass(new \App\Mail\TestMail( $notifiable, ['queue'=>false] ));   //$notifiable = model user
        return \App\Services\MailService::send([
            'to'=>$notifiable->user_email,
            'subject'=>'E-mail de teste por notificação '.time(),
            'message'=>'Minha <strong>mensagem</strong> de teste',
            'is_html'=>true,
            'queue'=>false,  //para enviar imediatamente quando executado este método
        ]);
    }

    /**
     * Get the array representation of the notification.
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable){
        return [];
    }
    
    
    /**
     * Get the database representation of the notification.
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDataBase($notifiable){
        return [
            'data'=>$this->data,
            'user'=>$notifiable->toArray()
        ];
    }
    
    
    public function toWhatsApp($notifiable){
        return [] ;//object/data to class App\Channels\WhatsappChannel->send(...);
    }
}
