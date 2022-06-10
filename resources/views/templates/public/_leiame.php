<?php
/*
Template padrão de acesso público (para extender a partir de templates.public.index)

Exemplo de utilização na blade
        @extends('templates.public.index')
        @section('title')
            ...
        @endsection
        @section('content')
            ...
        @endsection

Campos @section disponíveis:
    title           - título da página
    title_bar       - título na barra de título do navegador, caso não informado será o memo de title
    content         - (string|function) com os dados da página
    
    //os parâmetros abaixo head|bottom, são adicionados através do método push
    head            - (string|function) adiciona conteúdo ao cabeçalho (antes da tag body, dentro da tag HEAD). Ex: <script>...</script>
    bottom          - (string|function) adiciona conteúdo ao rodapé (antes de terminar a body). Ex: <script>...</script>

