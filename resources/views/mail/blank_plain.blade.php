@php
/*
    Template para envio de mensagens manuais apenas no formato de text (por \App\Services\MailService)
    Espera os parâmetros: $html ou $text - ambos serão escritos como texto
*/
@endphp

{{ $text ?? $html ?? '' }}