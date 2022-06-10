<?php
/*
Template padrão dashboard da área administrativa (para extender a partir de templates.admin.index)
Páginas a serem chamadas (para extender a partir dos templates abaixo):
    index           - visualização completa do template
    index-single    - visualização apenas do conteúdo do template (sem o topo, barra lateral, etc)

Exemplo de utilização na blade
        @extends('templates.admin.index',[
            'dashboard'=>[
                params...
            ]
        ])
        @section('title')
            ...
        @endsection
        @section('title')
            ...
        @endsection



Parâmetros de layout 'dashboard' já definidos / padronizados.
    navbar          - (boolean) indica se deve exibir a barra de navegação. Default true.
    header          - (boolean) indica se deve exibir o cabeçalho. Default true.
    bt_back         - (boolean|string) Se true/definido o texto do botão voltar. Default 'Voltar'.
    route_back      - (string) rota/url do botão voltar. Caso não definido será captura automaticamente caso exista $_GET['rd'], e caso não encontre, não exibe. 
                             Valores padrões: aceita a string ':back'  - gera a url o comando javascript: 'href="javascript:history.back();"'
                             Caso vazio não exibe o botão voltar.
                             Ex de código de rota PHP: route('name').'?rd='. urlencode(Request::fullUrl()
    padding         - (boolean) se false irá remover a margem interna. Default true.
    menu_collapse   - (boolean) se true irá colapsar o menu lateral. Default false.
    single_page     - (boolean) carrega o tema index-single.php
    white_page      - (boolean) se true irá carregar com fundo branco, ao invés do padrão do tema. Default false.
    grid_page       - (booelan) carrega o tema index-grid.php
    ... demais opções de acordo com cada template
    

*** Páginas templates ***
Obs: para todos os modelos abaixo existe os campos @push:
    head            - (string|function) adiciona conteúdo ao cabeçalho (antes da tag body, dentro da tag HEAD). Ex: <script>...</script>
    bottom          - (string|function) adiciona conteúdo ao rodapé (antes de terminar a body). Ex: <script>...</script>

index.php:
    Campos @section:
        title           - título da página
        title_bar       - título na barra de título do navegador, caso não informado será o memo de title
        description     - (string|function) descrição abaixo do título
        toolbar-header  - (string|function) html acima do conteúdo      //não disponível para template index-single)
        content-view    - (string|function) com os dados da página
    Parâmetros:
        dashboard       - (array) parâmetros de layout dashboard (destalhes descritos acima). Opcional.


index-single.php:
    Página vazia para exibição com fundo branco, ao invés do padrão do tema (sem a visualização da estrutura do dashboard adminLTE)
    Campos @section:
        title_bar
        title
        description     - (string|function) descrição abaixo do título
        content-view


index-grid.php:
    Página com grade de conteúdo
    Campos @section:
        title_bar
        grid_top
        grid_left
        grid_center     (obrigatório)
        grid_right
        grid_bottom
        
        Caso não informado o grid_center, aceita os conteúdos:
            title
            description
            content-view (obrigatório)
        
    Parâmetros adicionais $grid_...:
        (array) grid_sizes:[top=>, left=>, right=>, bottom=>]
        (boolean) grid_border - exibe / oculta a borda (default true)