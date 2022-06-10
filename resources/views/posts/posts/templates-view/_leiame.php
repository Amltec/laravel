<?php
/* 
Templates dos componentes de posts para visualização de dados.
Variáveis padrões para todos os componentes:
    $post, $configView, $thisClass, ... (demais variáveis em view.blade)


*** Componentes ***

title.blade - params:
    is_resume       - (boolean) se true irá exibir o resumo antes do conteúdo. Default false

content.blade - params:
    ...
    
image.blade - params:
    cols            - número de colunas (de 1 a 6). Default 3
    thumbnail       - auto(default)|small|medium|large|full


attach.blade - params:
    force           - (boolean) força o download do arquivo. Default false
    class           - 
    icon            -
    attr            - string|array
    title           - string|boolean


taxs.blade - params:
    is_title        - boolean


_editbar.blade - params?
    ... 


