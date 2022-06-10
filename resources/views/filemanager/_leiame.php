<?php
/*
Gerenciador de arquivos do tema.
Sitaxe: @include('filemanager.namepage',array params)


Arquivo index.blade - janela principal do gerenciador de arquivos.
    Parâmetros:
        click_open_ajax - (boolean) se true irá abrir o arquivo em janela modal via ajax. Default false.
                            Obs: recomendado abrir a view-modal (que já está pronta para esta finalidade)

        + todos os parâmetros de lista de arquivos (em templates.ui.files_list.blade).
        


Arquivo view.blade
    Visualização de arquivo por página completa (diretamente por url no browser)


Arquivo view_panel|view_modal.blade
    Visualização de arquivo para ser carregado dentro do painel da lista de arquivos ou janela modal por ajax



