Teste de envio de e-mail.<br>
Veja mais detalhes no código fonte.<br>

@php
use App\Services\MailService;

    /*
    //*** Envio por e-mail por classe ***
    MailService::sendClass(new \App\Mail\TestMail(),[
        'queue'=>false  //para enviar imediatamente
    ]);
    echo 'Envio por classe ok';
    */
    
    
    
    /*
    MailService::sendClass(new \App\Mail\TestMailMarkdown(),[
        'queue'=>false  //para enviar imediatamente
    ]);
    echo 'Envio por classe ok';
    */
    
    
    
    
    //*** Envio de e-mail manual por parâmetro ***
    MailService::send([
        'to'=>'aurelio@aurlweb.com.br',
        'subject'=>'E-mail de teste manual '.time(),
        'message'=>'Minha <strong>mensagem</strong> de teste',
        'is_html'=>true,
        'markdown'=>true,
        'queue'=>false,  //para enviar imediatamente
    ]);
    echo 'Envio manual por parâmetro ok';
    
    
@endphp