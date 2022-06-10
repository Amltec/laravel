Teste de envio de notificações.<br>
Veja mais detalhes no código fonte.<br>

@php
use App\Services\NotificationService;
    
    //*** envio de notificação por classe de teste ***
    \Auth::user()->notify(
        new \App\Notifications\TestNotification([
            'id'=>'123456',
            'title'=>'Teste de Notificação 1',
            'message'=>'Mensagem de exemplo de notificação',
            'type'=>'mymsg01',
        ])
    );
    echo 'ok';
    
    
@endphp