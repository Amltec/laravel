@php
/*
    Template para envio de mensagens manuais (por \App\Services\MailService)
    Espera os parâmetros: $html ou $text
*/

if(isset($html)){
    echo $html;
}elseif(isset($text)){
    echo htmlspecialchars($text);
}

@endphp