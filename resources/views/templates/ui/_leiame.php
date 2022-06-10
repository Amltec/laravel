<?php
/*
Instruções gerais.
Arquivos UI consistem de blocos prontos/funcionais a serem inseridos nas páginas - templates.blade.php
Sintaxe: @include('templates.ui.elementname',array params)


taxs_form.blade - blocos de taxonomias - params:
    id              - (string) id do box. Caso não definido, será criado automaticamente.
    term_id         - (model Term | integer) id do termo a ser exibido (requerido)
    tax_id_parent   - (integer) id do registro pai a ser iniciado.
    is_multiple     - (boolean) se true permite múltiplas seleções (equivale a true checkbox, false radio). Default true
    is_level        - (booelan) se true indica que deverá deverá exibir em níveis (caso exista). Default true
    is_add          - (boolean) se true exibe o botão que permite adicionar uma nova taxonomia. Default true
    is_add_checked  - (boolean) se true faz com que o novo item adicionado seja marcado (válido para $is_add=true). Default false.
    is_search       - (booelan) se true exibe o campo de pesquisa. Default true
    is_collapse     - (booelan) se adiciona a opção de collapse nos nívels de seleção (somente se is_level=true. Default false.
    is_header       - (booelan) se false não exibe o bloco do cabeçalho. Default true.
    show_icon       - (booelan|string) se = true - exibe um ícone padrão de cada item. Default false. Obs: utiliza o atributo data-icon="" com o nome íconde (default fa-folder)
                                       se = string - nome do ícone padrão
    show_check      - (booelan) se false oculta o campo checkbox/radio de cada item. Default true. Obs: se is_multiple=true, então força a 'true' este parâmetro
    start_collapse  - (boolean) se true inicializa com todos os itens colapsados. Default false.
    icons_code      - (array) code icons: 0 open, 1 close. Default: [f07c, f07b] | (string) apenas code, ex 'f07c'
    box_is_collapse - (booelan) se true exibe a opção collapse do container principal. Default true.
    box_is_close    - (booelan) se true exibe a opção fechar do container principal. Default false.
    is_popup        - (booelan) se true, então converte a caixa para o formato popup. Default false.
    title           - (boolean|string) título do metabox (opcional). Se não informado irá capturar o nome do termo.
    class           - (string) classe no container.
    attr            - (string) atributo do container.
    class_select    - (booelan) adiciona a classe indicando que está selecionado (adiciona a class .selected na tag label)
    taxs_start      - (string|int|array) ids das taxonomias para vir marcado junto deste template. Ex: '1,2,3'   |   1   | [1,2,3]
    metabox         - (boolean|array) o mesmo de templates.components.metabox, mas somente aceito os campos abaixo: 
                            is_border   - (booelan) exibe a borda. Default true.

    Sintaxe de nome de campos automático: 
        autofield.taxs.term_id = {term_id}
        autofield.taxs.term_{term_id} = {tax_id}
       
    Eventos javascript da tabela (trigger):
        close(), show() - oculta/exibe o box principal (somente para is_popup=true)
        select(opt)     - seleciona todos os registros. Parâmetro json opt. 
                            id - (int) id do registro / taxonomia (opcional). Se não informado, será selecionado/deselecionado todos.
                            select - (boolean)
                            
        //(retorno da função com triggerHandler)
        get_select(ret)          - retorna aos itens selecionados. Parâmetro ret: 'obj' - retorna aos objetos das linhas, null retorna um array de ids (array).
        
 
        //funções a serem personalizadas pelo usuário (trigger)
        onClickItem(opt)   - ao clicar no item de seleção. Parâmetro json opt: sel (boolean), input (jquery object), item (jquery object)
        onBeforeAdd(opt)   - antes de adicionar um novo item (somente para $is_add=true). 
                                Parâmetro json opt: tax_title, tax_id_parent, term_id
                                Pode ser usado com o triggerHandler() com return boolean.
        onAfterAdd(opt)    - depois de adicionar um novo item (somente para $is_add=true)
                                Parâmetro json opt: success (boolean), tax_title, tax_id_parent, term_id, 
                                        input , item //se success=true
        



auto_fields.blade - campos/componentes automáticos:
    Este template pode criar apenas blocos de campos ou ativar o recurso completo de um formulário (post store or update)
    Parâmetros:
        class       - classe deste bloco
        attr        - atributo do bloco. Valores já programador:
                        form-table-type="{tableTypeName}" - ativa os blocos padrões. Valores para {tableTypeName}: contacts, addresses, ... (nomes das tabelas padrões - views disponíveis em templates.ui.table_{name}.blade)
                        
        form        - (array) ativa o formulário dentro do bloco: 
                        id              - id do formulário (opcional)
                        class           - classe do formulário (opcional)
                        autoinit        - (boolean) indica se deve executar o js do formulário ao carregar. Default true.
                        url_action      - url da ação do formulário (store ou update)
                        url_back        - url do botão salvar
                        url_success     - url após salvar os dados (redireciona após salvar). Aceita a string ':id' para substituir pelo campo id de retorno, ex: '/user/edit/:id'
                        data_opt        - (array) opções do formulário (para o atributo <form data-opt=opt_json /> - são os mesmos parâmetros de função js/admin.js->awFormAjax() )
                        bt_back         - (string) nome do botão voltar. (boolean) True exibe no modo padão, False desativa.
                        bt_save         - (string) nome do botão salvar. (boolean) True exibe no modo padão, False desativa.
                        autodata        - (array object) o mesmo do parâmetro $autodata descrito mais abaixo. Deve ser utilizado para atualização de dados. Se ==false, desconsidera o campo
                        files           - (boolean) se true, indica que o form é um upload de arquivos
                        alert           - (boolean) ativa o padrão de mensagens de alerta já programado dentro do formulário. Defaul true.
                        method          - (string) valores: POST, PUT, GET, DEL, ... (opcional) caso não definido será ajustado automática (assim: se existe autodata então é PUT, caso contrário é POST)
                         
        metabox     - (boolean) true ativa o metabox nas configurações padrões. Default false.
                      (array) ativa o metabox e com as configurações personalizadas (veja em templates.components.metabox //obs: o atributo 'content' será automático com o autofield).
        layout_type - (string) vertical (default), horizontal, two_column, three_column, four_column, horizontal_two_column, row (todo o formulário em uma única linha)
        prefix      - (string) prefixo padrão dos campos (opcional). É obrigatório somente se definido o parâmetro block_dinamic.
        block_dinamic - (array) se defindo indica que o bloco poderá ser criado/duplicado dinamicamente (loop de campos). Default false. Valores:
                            string  mode             - indica o modo visual de controle do bloco. Valores: block (default), inline. 
                            array|boolean remove     - exclusão do bloco. Obs se ==false então desativa (default true). Valores para array:
                                                        (string) ajax - rota para exclusão (opcional)
                                                        (boolean) confirm - confirmação de exclusão (opcional)
                            boolean remove_last     - se true indica que somente a última linha poderá ser removida. Default false.
                            boolean add             - true (default) ativa, false desativa.
                            boolean numeral         - exibe o número das linhas, false (default) não exibe
                            string block_title      - nome do bloco usado para gravar automaticamente na string de log automática (opcional). Ex: 'Parcelas' - neste caso irá gerar campos assim no logo: 'Parcelas 1 > field_name...'
                        Observações: 
                            1) Os campos precisam ter o caractere '{N}' no nomes dos campos, pois é procurado automaticamente por esta string para substituir pelo respectivo índice do bloco. Altera os atributo: name, id, value, div label.
                            2) Serão criados os campos de controle {prefix}_autofield_count e {prefix}_autofield_remove_ids

        autodata    - (array object) dos valores dos campos. Se o parâmetro block_dinamic!=false, então o procura um loop dentro do autodata (ex: $autodata[ array1, array2, ... ])
                        Alternativamente, também pode ser informando pelo parâmetro acima 'form'->'autodata' (neste caso será considerado sempre como atualização de dados - input _method PUT)
                        
        autocolumns - (array) name, singular_parâmetros:
                        //requeridos
                        label       - título do campo. 
                        type        - tipo do campo (são os templates.components). Valores:
                                            info, text, select, select_icon, textarea, upload, uploadzone, uploadbox, password, number, date, time, cpf, cnpj, cep, color, colorbox
                                            datetime - data e hora juntos
                                            daterange - intervalo de duas datas
                                            currency (formato 9.999,99), decimal (formato 99.99)
                                            uf (lista de estados)
                                            phone, phone_mark (telefone + marcador)
                                            email, email_mark (e-mail + marcador)
                                            cidade_uf - combinação de campos cidade e uf (neste caso o nome do campo será separado por '|' para 0:cidade e 1:uf)
                                            select2 - cria um campo com o plugin select2
                                            radio - campos de radio
                                            checkbox - campos de checkbox
                                            sim_nao - campos de radio para valor booleano
                                            editor - editor de texto
                                            editorcode - editor de código
                                            button|link|submit - botões (ocupam uma linha inteira)
                                            button_field|button_group|submit_field - botões (ocupam apenas o campo da linha)
                                            text_filemanager
                                            html - campo html digitado pelo programador. Aceita apenas os parâmetros: label, name, id, class_group, class_label, class_div, html
                                                
                        list        - valores do campo (para type=select - mais em templates.components.select),
                        //opcionais
                        placeholder - ...
                        maxlength   - ...
                        mask        - máscara automática para type=text
                        attr        - ... (quaisquer demais atributos no formato html)
                        class_group, class_label, class_div, class_field
                        id          - ...
                        text        - somente se type=info
                        require     - (boolean) se true indica que é requerido (adiciona nos campos 'class_group'=>'require')
                        info_html   - (string) texto html adicional exibido depois do campo.
                        _format     - parâmetro interno de visualização de dados no campo. 
                                        (function) deve conter uma função de formação com retorno. Ex: function($val){return $val;}
                        //exclusivos por campo
                        //{phone|email}_is_mark  - quer dizer que irá abrir um campo combo com marcador (cada um tem seus respectivos templates)
                        
                        //para type=html
                        html        - string|function contendo o html de retorno
                        
                        * Obs: é possível substituir todos os parâmetros acima por function, ex: 
                            'name'=>function(){ return '****'; }




auto_list.blade - tabela / lista de dados padrão
    Parâmetros:
        data            - (array object) lista de dados (são os dados dos registros da view)
        columns         - (array) dados das colunas. Sintaxe: [col_name=>label].
                            Label = string - Título da coluna | array(title,function value) - coluna com opções.
                            Ex: 'column=>[
                                    'Titulo',   //não precisa informar a chave do array
                                    //opcionais
                                    value       => function($val,$reg=null){ return 'custom value'},    //$val - valor atual, $reg - objeto com todos os valores do registro atual //obs: também pode ser echo $val ao invés de return $val.
                                    calc_total  => function($val,$reg){return...;} //função de total/subtotal das colunas. Em $val é informado o valor atual calculado. Ex: function($val,$reg){return $val+=$reg->val;}
                                    alt         => string - texto alternativo
                                    alt_cell    => str|fnc - texto alternativo em cada linha da tabela
                                    format      => str|fnc - os mesmos valores do parâmetro '$rl' da função App\Utilities\FormatUtility::formatDataSingle(). Ex: format=>'type:datebr'
                                 ]
        columns_show    - (string) nome das colunas a serem exibidas separadas por virgula. Caso vazio, exibe todas de 'columns'.
        html_before     - (string) html a ser adicionado antes da lista
        html_after      - (string|function) html a ser adicionado depois da lista
        html_not        - (string|function) html a ser adicionado caso nenhum registro seja encontrado
        row_opt         - (array) opções a serem executadas por linha. Valores:
                            actions     - (function) funções gerais para a serem executadas no início de cada linha (sem retorno na função), fnc: function($reg){ $reg->custom_field=123; }
                            class       - (string|function) classe de cada linha, ex fnc: function($reg){ return 'myclass-'.$reg->id; }
                            attr        - (string|function) atributos adicionais de cada linha, ex fnc: function($reg){ return 'data-field="..."'; }
                            lock_del    - (array|function) array - ids que não deve permitir a exclusão | function - função para verificar se deve permite a exclusão, function($reg){  return $reg->id==1; } //se retornar a true, não permite excluir
                            lock_click  - (array|function) array - ids que não deve permitir o click / abertura do regtistro | function - função para verificar se deve permite o click, function($reg){  return $reg->id==1; } //se retornar a true, não permite clicar
                                             (string) 'deleted' - se tiver esta string, indica que irá bloquear os registros excluídos para click
                            
        metabox         - (boolean) true ativa o metabox nas configurações padrões. Default false.
                            (array) ativa o metabox e com as configurações personalizadas (veja em templates.components.metabox //obs: o atributo 'content' será automático com o autofield).
                            Parâmetros adicionais:
                                fit_table (booelan) - se true, ajusta a tabela para encaixar dentro do metabox considerando o padding padrão do metabox (semelhante ao metabox['is_padding']=false)

        options        - (array) opções (opcionais):
                            is_trash     - se true indica se a lista visualizada é uma lista de registros excluídos. Caso omitido, tenta captura automaticamente via GET pelo parâmetro 'is_trash' (valores: s|n). Default false.
                                                Obs: É utilizado para indicar a lista com estilos próprios de registros excluídos e respectivos botões da barra de ferramentas
                                                     Se true, ao clicar no menu de opção 'Listar da Lixeira' é recarregado a página atual adicionando o parâmetro via GET is_trash=s. Ao clicar em 'Sair da Lixeira', este parâmetro é omitido.
                                                     Para recarregamento ajax, é informado este parâmetro da mesma forma.
                            checkbox     - exibe o campo checkbox. Default false.
                            collapse     - exibe o campo collapse. Default false.
                            header       - exibe o cabeçalho. Default true.
                            footer       - exibe o rodapé. Default true.
                            pagin        - exibe a paginação. Default true.
                            select_type  - tipo da seleção da linha ao ser clicada. Valores: 0 - não permite, 1 - permite 1 seleção (default), 2 permite várias seleções. 
                                                Obs: se existir o campo checkbox, será ativada a selação somente ao clicar neste campo
                                                Obs2: se==0 então, seta para checkbox=false
                            
                            confirm_remove- pergunta se quer remover. Valores: false (default), true - texto padrão, (string) texto personalizado.
                            total        - Default false. Se true ativa a linha final com os valores totais das colunas (precisa ter a função do cálculo na variável das colunas, ex: columns=>[.., .., 'total'=>function(){}] (mais instruções acima)
                            subtotal     - Default false. Se true ativa com o subtotal de cada agrupamento. Válido somente se 'field_group' for defindo (precisa também ter a função do cálculo na variável das colunas, ex: columns=>[.., .., 'total'=>function(){}] (mais instruções acima)
                            toolbar      - exibe a barra de ferramentas no topo. Default false.

                            //para todos os itens abaixo requer toolbar=true
                            toolbar_menu  - exibe o menu de opções da barra de ferramentas. Default true. 
                            remove       - exibe o botão de remover/restaurar da barra ferramentas. Default true.
                            reload       - exibe o botão de reload para recarregamento da página (requer toolbar=true). Se definido routes.load então irá carregar via ajax, caso contrário dará um reload página. Default false.
                            regs         - exibe o campo de registros por página. Default true.
                            columns_sel  - exibe o campo de seleção de colunas. Default false. 
                                                Obs: exibe os campos conforme parâmetro $columns_show. Ao clicar para exibir uma coluna, irá sempre carregar a página pela url querystring atual + parâmetro columns_show=col1,col2,...
                            list_remove  - exibe a opção de listar os registros excluidos. Default true.
                            search       - exibe o botão de remover. Default true.
                                                Obs: Ao pesquisar, irá sempre carregar a página pela url querystring atual + parâmetro filter_s=...
                                                     Se definido routes.load, então irá processar a lista em ajax.
                            bt_add      - (boolean|string) exibe um botão padrão 'Adicionar'. Se string, informar o nome do botão
                            order       - (boolean) exibe o recurso de ordenar manualmente as linhas das tabelas.
                            post_data   - (array) dados adicionais enviados junto de toda ação de post realizado dentro da lista (ex: ao enviar ou remover um arquivo, etc (opcional). Sintaxe: [field=>value,...]. Default null.
                            allow_trash - (boolean) indica se está habilitado o recurso da lixeira. Default true.
                                        
        routes          - (array) urls das rotas paras as ações (opcionais):
                            load        - (ajax) carrega a lista (para ajax get).
                            remove      - (ajax) exclusão de registros (para ajax post). Post data{id:...}. Return json esperado: {success:true}. Caso não definido e executado o trigger('remove'), apenas removerá do dom este item.
                            collapse    - (ajax) para quando for collapsado um item (para ajax post)
                            click|dblclick - quando for clicado em um item da lista (para  redirect url). 
                                            Obs: utilize o parâmetro get 'rd=.... ' para gera o botão voltar automaticamente na view seguinte, ex: rota click'=>route('admin.app.show',$id,'rd='. urlencode(Request::fullUrl()) ]),
                                            Obs2: é recomendado disparar um dos dois eventos para evitar conflitos...
                            add         - rota para quando for clicado no botão de adicionar. Precisa existir para exibir o botão padrão.
                            Sintaxe das urls: (string) - url absoluta da rota.
                                              (function) - (somente para click, collapse) função para retornar a url.
                                                                    Ex1: function($reg){ return route('xx',$reg->id) }
                                                                    Ex2: function(){return route('admin.user.destroy',':id');}, //válido somente para a rota 'remove', sendo :id, o nome do campo 'id' a ter o valor subituído
                                               (array)    - (somente para click) - valores: 0 string|function da url, ... demais índices como atributos string html]. 
                                                                    Ex1: [ route('xx'), 'target'=>'blank' ]  }
                                                                    Ex2: function($reg){ return [ route('xx',$reg->id), 'target'=>'blank' ]  }
 
        field_id        - (string nome da coluna que corresponde ao ID principal do registro. Default 'id'.
        field_group     - (string) nome da coluna do qual irá agrupar os dados. Opcional.
        field_click     - (string|array) nome da coluna que irá disparar o 'routes.click'. Default '' (toda a linha). Aceita também 'none' ou ===false para conter o link sem nehuma ação.
        
        open_modal      - (boolean|array) opções para que o item da lista seja aberto em uma janela modal via ajax. Default false (link direto)
                            Parâmetros:
                                title_add|edit  - título da janela para adição/edição de registro (para add, requer: options['bt_add'] && routes['add']
                                width           - largura da janela, default 'lg' (valores de public/js/main.js->awModal())
        
        //requer options.toolbar=true
        toolbar_buttons  - (array) botões adicionais. Sintaxe [ [$button, $button, $id=>$button, ... ...],... ]. Sintaxe para $button:
                                    (array) array de configuração do botão (veja em template.componentes.button). 
                                    (string) html do botão ou qualquer outro elemento.
        toolbar_buttons_right  - o mesmo de 'toolbar_buttons' mas com botões alinhados a direita.
        toolbar_menus    - (array) de menus de opções adicionais. Sintaxe: array de configuração do menu (veja em template.componentes.menu). Sintaxe [ [$button1],... ]
                                    Ex de html de botão manual: '<button type="button" class="btn btn-default margin-r-5" title="Restaurar"><i class="fa fa-recycle"></i></button>';
        //obs: para os elementos de toolbar_buttons, toolbar_buttons_right e toolbar_menus, considere as classes:
                        - .j-show-on-select - exibe ao selecionar 1 ou mais linhas (caso contrário oculta)
                        - .j-hide-on-select - oculta ao selecionar 1 ou mais linhas (caso contrário exibe)
        
        class           - (string) classe do container principal (nenhuma valor programado até agora).
        list_id         - (string) id da tabela (opcinal)
        list_class      - (string) classe na tabela de dados. Valores já programados: table-hover, table-bordered, table-striped, table-condensed, table-large
        list_attr       - (string) atributos na lista
        
        taxs            - (array) ativa o botão de taxonomia para os registros (inclui a view templates.ui.taxs_form). 
                            Se ==true exibe com as configurações padrões. Se ==false (default) desativa. 
                            Sintaxe array [term_id=>[opt=>val,...]] - são as mesmas opções de templates.ui.tax_form (obs: os parâmetros id e term_id são monstados automaticamente).
                                Parâmetros:
                                tax_form   - se==true, exibe na configuração padrão ou array com as opções da view componente templates.ui.tax_form
                                button     - se==true, para exibir nas configurações padrões ou array com as opções da view templates.components.button (adiciona o botão na posição do parâmetro auto_list.blade['toolbar_buttons']). Default true. (para exibir o componente templates.ui.tax_form)
                                show_list  - nome da coluna onde deverá exibir as taxonomias de cada registro. Default ==false.
                                ???xxxbt_only_sel- se==true, exibe dinamicamente o botão da taxonomia somente se houver registros selecionados. Default false (válido para button==true).
                                tax_form_type- tipo da lista da taxonomia. Valores (caso não definido permite ambas as opções):
                                                'list' - permite apenas filtrar as lista pela taxonomia
                                                'set' - permite apenas aplicar as taxonomia na lista (neste caso o botão que exibe o box é exibido apenas quando houver registros)
                                area_name   - nome de área para referência na programação (requerido). Ex: 'accounts', 'users', 'custom_name', ...
                                term_model  - model do termo. Opcional. Caso não enviado esta mesma var é capturado dentro deste arquivo.


        taxs_start      - (string|int|array) ids das taxonomias para iniciar filtrando a lista (opcional) Ex: '1,2,3'  |  1     |   ['1',2,3]
                                Obs: válido somente se '$taxs' acima for definido. 
                                Obs2: caso queria filrar a lista de dados, o parâmetro $data acima deve já vir filtrado.
        

        footer_cb       - (function) opcional. Função que permite substituir o padrão do rodapé da lista. Recebe como primeiro parâmetro um array com as opções:
                                    info - string de informações do totais de registros, ex: 'Exibindo 1 - 10 de 609 registros'
                                    pagin - html dos botões de paginação
                                Ex: footer_cb($opt){ return (or echo) $opt['pagin'] . ' - <a href=#>Meu link</a>'; }

        //parâmetros interno na programação (opcional, caso não setado, é capturado automaticamente caso a requisição seja do tipo ajax (usando o comando $request->ajax() para identificar) )
        is_ajax_load    - (boolean) deve ser setado para uso com ajax - carrega somente os registros no formato html (para dentro do TBODY). Default null (captura automática).
        
    Eventos javascript da tabela (trigger): //*** obs: escrever estes eventos diretamente no DOM ***
        select(opt)     - seleciona todos os registros. Parâmetro json opt. 
                            id - (int) id do registro (opcional) Se não informado, será selecionado/deselecionado todos (funciona somente se options.select_type!=2).
                            select - (boolean)
                            
        collapse(opt)   - colapsa o grupo de colunas (requer options.is_collapse). 
                            id - (int) id do registro.
                            select - (boolean)
                            
        remove(opt)     - remove o registro da tabela. Parâmetro json opt:
                            id - (int|array|obj.row-item) dos objetos selecionados. Caso não definido, irá capturar automaticamente os selecionados.
                            confirm - (booelan) sobrepõe a configuração de options.confirm_remove somente nesta ação.
                            restore - (booelan) se true, indica que o registro será restaurado da lixeira. Caso contrário será removido (remove definitivamente caso já esteja na lixeira).
                                                Obs: o 'restore' apenas adiciona restore=s na requisão ajax e retira o item da lista atual
                            
        load(opt)       - carrega a lista via ajax (utiliza options.routes.load). Parâmetro json opt:
                            id - (int) id do registro atual. Neste caso será sempre atualizado (caso exista) ou inserido a linha. Opcional. Neste caso é informado via GET o parâmetro 'filter_id=...'
                            pos - (string) indica se deve carregar os dados sobrepondo, antes ou depois da lista atual. Valores: 'load', 'before', 'after'. Default 'load'.
                            page - (string) número da página/paginação (somente para type=load). Aceita 'next|prev' para indicar a página seguinte
                            search - (string) de pesquisa - adiciona o parâmetro filter_q=search
 
        //(retorno da função com triggerHandler)
        get_select(ret)          - retorna aos itens selecionados. Parâmetro ret: 'obj' - retorna aos objetos das linhas, null retorna um array de ids (array).
        onOpen()                 - função disparada ao clicar em algum item da lista. Se a função tiver um 'return false', então irá cancelar o evento click padrão.
                                    Parâmetros json opt retornado: url, id, oTr
        
        //funções a serem personalizadas pelo usuário (trigger)
        onSelect()               - ao selecionar cada registro. Parâmetros json opt retornado: select, count, oTrs
        onRemove()               - ao remover cada registro. Parâmetros json opt retornado: success, id, oTr, total - total de itens processados, index - número do item atual processado
        onBeforeRemove()         - antes de remover cada o registro. Parâmetros json opt retornado: success, id, oTr, total - total de itens processados, index - número do item atual processado.
                                        Pode ser usado com o triggerHandler() com return boolean.
        onCollapse()             - ao disparar o comando collapse (retorno com sucesso). Parâmetro json opt retornado: oTr, oTrCollapse
        

        
    Dados (json) - sintaxe $(table).data('...')
        options   - (json) retorna as opções da lista
 
    Eventos javascript por linha (em TBODY) (trigger):
        click({select:true})




auto_groups.blade - grupos do ui auto_fields chamados a partir de um único arquivo:
    Monta uma lista de blocos de campos a partir com o auto_fields.blade através de um único array de parâmetros.
    Parâmetros:
        form        - (array) os mesmos parâmetros de form.blade
        autofields  - (array) os mesmos parâmetros de auto_fields.blade. Se definido irá executar somente este caso e ignorar a var abaixo $autogroups
        autogroups  - (array) array de valores a escrever na tela ou nos respectivos plugins. Valores:
                            (string, int) string html para escrever na tela ou número
                            (function) function com callback para ser execuado
                            (int => array) inclui com o ui auto_fields, ex: [ 1=>array(...), ... ]
                            (str => array) inclui qualquer outro ui ou component, ex: [ array(...), ... ] 
        metabox     - (boolean) true ativa o metabox nas configurações padrões. Default false.
                      (array) ativa o metabox e com as configurações personalizadas (veja em templates.components.metabox //obs: o atributo 'content' será automático com o autofield).



files_list.blade.php - lista de arquivos
    Monta a lista padrão de arquivos do gerenciador de arquivos.
    Obs: já está padronizado com todo o sistema filemanager, incluindo rotas, controlers, models para o controle \App\Services\FilesService.
    Parâmetros via GET aceitos:
        filter_q, folder, is_private (s|''), is_trash (s|''),... e demais parâmetros files_filter abaixo

    Parâmetros:
        controller - string com o nome da classe ou classe FilesController para carregamento das configurações (para utilização do método ->getConfig()). Ex: 'files' ou (new \App\Http\Controller\FilesController)
        files - array de registro do controller \App\Services\FilesService::getList. 
                Caso não informado será capturado automaticamente o parâmetro 'files_filter' abaixo:
                Se ===false irá carregar a lista vazia
       
        files_filter - array de filtros para captura automática da lista de dados (pelo controller \App\Services\FilesService::getList).
                Parâmetros: são todos do método getList() de FilesService.
                Obs1: Os seguintes parâmetros querystring são identificados de forma automática para o funcionamento dos botões já padronizados:
                        id          - filtra apenas por um único registro
                        q           - filtro por palavra chave (nome, titulo) (+ informações em \App\Services\FilesService::getList())
                        folder      - filtro or nome da pasta. Obs: se informado area_name e area_id este campo será desconsiderado. Aceita nomes separados por virgula ou array
                        private     - filtro por arquivos privados. Valores: 's' private, '' publico (default)
                        trash       - filtro por arquivos da lixeira. Valores: 's' trash, '' normal (default)
                        modeview    - altera para o modo de visualização de imagens. Valores: 's' ou '' (default) (obs: este parâmetro não interfere no filtro da lista)
                        regs        - registros por página
                        area_name   - ...
                        area_id     - ...
                        area_status - ...
                        metadata    - (array) filtro por metadata - sintaxe:  [meta_name=>meta_value,...]       //mais informações em \App\Models\Traits\MetadataTrait.php
                        meta_name|meta_value - o mesmo filtro acima, mas filta apenas 1 metaname e 1 metadata (é mais fácil informar estas parâmetros por querystring)
                        ... demais parâmetros passados em \App\Services\FilesService::getList()
                Obs2: este filtro e as opções acima serão válidas somente se o parâmetro 'files' acima não for informado.
                Obs3: também pode ser do tipo array, e neste caso deve vir como objeto da classe \App\Utilities\CollectionUtility.<br>
                      Todo o array precisa conter os mesmos campos da tabela 'files', contendo os campos adicionais:<br>
                        getPath         - caminho do arquivo
                        getUrl          - url da imagem
                        getUrlThumbnail - url imagem em mininarura
                        getIcon         - nome da classe do ícone (opcional). Ex: 'fa fa-file-o' ou \App\Services\FilesService::getIconExt('jpg')['class']
                        //Confira o ex filemanager03 para mais informações
                        
        files_opt - array:
                toolbar     - (boolean) exibe/oculta
                bt_search   - (boolean) exibe/oculta
                bt_upload   - (boolean) exibe/oculta
                bt_folder   - (boolean) exibe/oculta. Se true será exibido apenas se houver mais de 1 pasta disponível.
                bt_access   - (boolean) exibe/oculta. Se true será exibido apenas se houver mais de 1 tipo de acesso disponível (público e privado).
                bt_remove   - (boolean) exibe/oculta
                mode_view   - (boolean) exibe/oculta
                thumbnails  - (boolean) gera as miniaturas se for imagem. Default true
                modeview_img- (boolean) se true inicializar com o modo de visualização como imagem. Defaul false.
                show_view   - (boolean) exibe/oculta a imagem de visualização da lista (válido apenas para modeview_img=false). Default true. Obs: em resumo é o mesmo do parâmetro 'columns_show' sem constar a string 'icon|view'
                list_compact- (boolean) se true, exibe a lista em um formato mais compacto. Default false.
                metabox     - (booelan) se false oculta o metabox da janela, se true|array ativa e mescla as configurações
                columns_show - (string) nome das colunas a serem exibidas (sep ','). Valores: (icon|view),file_title,file_name,created_at,file_size,folder,status    //obs: para exibir icon|view o parâmetro show_view precisar ser true
                title       -  (string) título da página. Opcional. Se ==false, oculta o título
                accept      - tipos de arquivos aceitos no filtro e upload. Default '' (todos).
                filetype    - tipos de arquivos aceitos no fitlro. Valores: image, audio, video, pdf. String ou array
                list_class  - (string) classe na tabela de dados (veja em auto_list.blade). Valores adicionais: 
                                    table-mode-view (visualização como imagem - também adicionado automaticamente se modeview_img=true)
                search_in   - (string) local onde a busca poderá ser efetuada. Valores: 
                                    access  - somente dentro da pasta de acesso pública ou privada (conforme setado em files_filter['private'] (default)
                                    folder  - somente a pasta informada em files_filter['folder']
                                    area    - somente dentro do area_name e area_id informado
                                    all     - em todas as pastas e considerando público ou privado

                folders_show - (boolean) exibe ou não a lista de pastas. Default true.
                folders_list - (boolean|array) lista adicional de pasta para exibir na lista. Sintaxe: [folder_name=>title,...]. Default false. Caso não informado será considera o padrão do arquivo FilesController()->files__config()[folders_list]
                restrict_user- (boolean) se true, irá restringir a lista a partir dos dados do usuário logado
                

                //*** requer bt_upload==true (opcionais) ***
                form_opt    - (array) parâmetros do formulário (são os mesmos valores de public/js/main.js->awFormAjax()). Obs: estes valores já são definidos com um padrão, mas podem ser alterados
                upload_mode - (string) tipo do botão de upload. Valores: 
                                    upload      - (default) botão de ação direta de upload
                                    filemanager - abre o gerenciador de arquivos para seleção de arquivos.
                
                //somente para para upload_mode=select
                filemanager_opt - (array) opções do filenamager. Caso não definido, será informado automaticamente a partir dos parâmetros da lista atual.
                                        Parâmetros: os mesmos valores de js/admin.js -> awFilemanager()
                
                onSelectFile  - (string) function js para callback após a seleção de arquivos. //Obs: consulta mais informações do parâmetro em /public/js/main.js -> awFilemanager({onSelectFile:...})
                
                
                //somente para para upload_mode=upload
                fileszone   - (boolean|array) se permite o uploadzone. Mais detalhes em admin.js->awUploadZone().
                uploadComplete  - (string) function js para callback depois do upload. Valores:
                                    'reload' - irá disparar um reload na página (dispara o comando js: window.location.reload()).
                                    Demais valores padrões conforme sintaxe em admin.js->callfnc();
                                    Se '' ou não definido, nenhum ação é executada.
                uploadSuccess   - (string) function js para callback depois do upload. Valores:
                                    'route_load' - irá disparar a rota 'load' do template ui.auto_list.blade (requer que seja setado o parâmetro ['auto_list']['routes']['load'].
                                    Demais valores padrões conforme sintaxe em admin.js->callfnc();
                                    Se '' ou não definido, nenhum ação é executada.
                mode_select     - (boolean) se true, exibe um botão de 'selecionar arquivos' no rodapé e ao clicar dispara o evento jquery 'onSelectFile' (mais detalhes abaixo). Default false.
                                    Obs: recomendado exibir dentro de uma janela modal (faz mais sentido)
                
                //todos os casos
                upload_opt      - (array) parâmetros de upload. São os mesmos enviados via post para o controller FilesController@post -> campo $data['data-opt'] -> FilesService@uploadFile.
                                  Ex de valores: private=>,folder=>,mimetype=>,...
                file_view       - (string) tipo da visualização ao arquivo. Valores:
                                        '' ou false - desativado (padrão)
                                        'modal' - abre em janela modal
                                        'panel'  - abre através em um painel lateral
                                  Obs: será aberto via ajax o link (auto_list.routes.click) do item da lista (+ informações em templates.ui.auto_list.blade). Ex: 'auto_list'=>['routes'=>['click'=>route(...)]]
                edit_data       - (boolean|array) se definido habilita as opções de edição para os campos: file_title (tabela files), status (tabela files_relations). Default false. Parâmetros:
                                    - (boolean)  - se true irá definiir automaticamente a edição dos campos: files.file_title, files_relations.status. 
                                    - title     - exibe o campo título
                                    - status    - exibe o campo status (exibe somente se informado os parâmetros area_name|area_id informado)
                                    - area_name|area_id - referência para vínculo do arquivo
 
        auto_list - array - parâmetros:
                Qualquer parâmetro de view.templates.ui.auto_list.blade. 
                Obs: dependente da opção informada, poderá sobrescrever algum parâmetro atual deste template files_list.blade.
                Obs2: já existe um padrão de link para visualização de arquivos, mas se desejar alterar, pode ser usado apenas o parâmetros 'auto_list.routes.'click'. O parâmetro 'auto_list.routes.field_click' não é considerado neste caso.
                Parâmetros adicionais:
                        routes =>   upload    - (string) rota do upload (opcional)
                                    edit      - (string) rota para edição dos dados (opcional) (válido apenas se files_opt.edit_data for definido). Caso não informado será gerado automaticamente para route('{$prefix}.app.get',['files,'edit-file',{id}]);
                                    view      - (string) rota do link de visualização (opconal) (válido apenas se files_opt.edit_data for definido)
                                    //Caso não informado algumas das rotas acima, será setado o padrão do controller 'files'

        area_name|area_id   - se informado, será setado automaticamente estes campos em todos os demais parâmetros acima que possam utilizá-los. 


    Eventos javascript da tabela (trigger):
        //funções a serem personalizadas pelo usuário (trigger)
        onSelectFile(opt)     - function js para callback ao selecionar o arquivo. Válido somente se ['files_opt']['mode_select'] = true
                                    //Obs: consulta mais informações do parâmetro em /public/js/main.js -> awFilemanager({onSelectFile:...})

    Obs: todos os eventos de auto_list.blade também são válidos.
    *** Importante - sobre a configuração de diretórios: ***
        






tag_item_list.blade - lista de taxonomias com o templates.components.tag_item.blade.php
    Monta uma lista de itens de taxonomias selecionada para o frontend.
    Todas são adicionadas dentro de uma div '<div class="row-taxonomy-items">'.$r_taxs.'</div>';
    Parâmetros:
        tax_rel     - deve ser uma model: \App\Tax\Model, coleção de Model->getTaxRelation() ou \App\Services\TaxsService::getRelationByArea();
        term_id     - (int|array) id do termo para filtro dos resultados (opcional)
        opt         - (array) demais opções a serem mescladas de templates.components.tag_item.blade.php
        id          - id do container (opcional)
        class       - classe do contaier (opcional)
        attr        - atributos do container (opcional)
        tax_filter  - array taxs id. Se informado, irá filtrar a listra de tax_rel. Ex de valor: [1,2,...]
        




view.blade  - visualizador de dados 
    Uso geral para qualquer tipo de dados
        Parâmetros:
            data    - (array) dados a sere exibidos. Sintaxes:
                        [(text|int|function),  (array)...     ]
                        ['name'=>(text|int|function)]
                        ['name'=>(array) attributes]       - valores de attributes:
                            title       - título do campo. Se ==false ou não definido, não cria a parte do título
                            alt         - ...
                            value       - (text|int|function|array|object) valor do campo
                            class_row    - classe da linha
                            class_field - classe na coluna do campo/label (se definido o parâmetro 'title')
                            class_value - classe na coluna do valor (coluna principal)
                            type        - tipo do valor para formatação automática. 
                                            Valores: (default) string, file, img, dateauto, date, time, datetime, number, price, link, bytes, video, youtube, audio, iframe, boolean, sn (sim não) //obs: vídeo ou youtube executaram o mesmo processo
                                                     array|object- monta uma tabela de array.
                                                     model       - escreve os dados de um model conforme padrão laravel
                                                     @viewname   - neste caso irá incluir um template view. os atributos. 
                                                                   Neste caso os parâmetros de inclusão devem em 'value'. Ex: [ ['type'=>'@templates.componentes.button','value'=>['title'=> 'Primary','color'=>'primary'] ] ]
                                                     taxonomy    - Indica que é um array da tabela de taxonomias (da tabela taxs, ex captura $model->getTaxsData()). 
                                                                        Sintaxe esparada em value: [{tax_id}=>model taxs,...]       //veja mais exemplos em view05.blade
                                                                        Obs: se  o parâmetro 'title'===true, então automaticamente com o nome do termo
                                                     dump        - Escreve o valor dentro de um comando dump().
                                                     
                            id          - atributo id
                            attr        - (string|array)demais atributos adicionados na linha
                            attr_value  - (string|array)atributos adicionados no object (para type=img|iframe|video|audio|...)
                            hide_title  - (booelan) o mesmo de 'hide_title' descrito mais abaixo. Válido para (se value=array|object)

                            format      - (function) se definido, é utilizado como um filtro do valor. Ex: function($v){ return strtoupper($v); }
                                            Obs: é recomendado o uso com o parâmetro 'model' para formatar os respectivos valores.
                                            Obs2: é obrigatório um return com o valor da função.


            data_type - (string) tipo do processamento do param 'data'. Valores:
                        default   - padrão conforme especificado nos parâmetros data.
                        array     - irá processar toda a tabela como array (exibindo todos os valores)
                        model     - escreve os dados de um model conforme padrão laravel

            hide_title - (booelan) indica que deve ocultar a coluna de título por default para todos registros / tabelas descendentes. Default false.
                            Obs: é o mesmo de 'data'=['title'=>false], mas se setado aqui, irá ocultar para todos.
            
            model - (object) model com os dados da tabela. Neste caso o parâmetro 'data' não precisa do 'value', apenas dos dados da estrutura.
                        Ex 'model'=>$model,'data'>[field_name=>[title=>,type=>,...]]
            
            filter - (string|array) filtra os campos a serem exibidos. Valores:
                        (string) 'not_empty'    - filtra para todos os registros não vazios. Considera vazio: '', false, 0, null. Default ===false.
                        (array)  [field,...]    - para todos os campos compatíveis (informar o nome do campo)
                    
            arrange     - (string) padronização das colunas campo e valor. A proporção será sempre de 12. 
                            Valores: '1', '1-11', '2-12', ... '11-1'        //Default '2-10'
                            Obs:     se ='' ou 'line', então quebra em linhas
            
            class       - classe para o container da view. 
                                Classes gerais: view-hover, view-bordered,  view-striped, view-condensed, view-large
                                                view-fields-line (ajusta o campo+valor em linha (para arrange=line|'')
                                Classes de colunas: view-col2, view-col3, view-col4
                                Estas classes quebram as colunas no modo responsivo: view-col-break-[780,520,480,360]
                                
            class_row   - classe padrão para todas as linhas. 
                                Valores de ex: no-padding, text-center, break-cell (quebra as células em linhas)
            class_field - classe padrão para todas as colunas de campo
            class_value - classe padrão para todas as colunas de valor
            attr        - atributo da div principal
            format      - (function) é o mesmo do parâmetro acima [data][format]... se definido, será aplicado a todos os valores e executado após o [data][format].
                            Ex: function($v){ return strtoupper($v); }
                        

                        

files_view.blade - visualizador de arquivos da tabela files
    Parâmetros:
        controller   - (string|class) nome do controller ou classe FilesController gerenciar os arquivos
                        Necessário somente se 'file' abaixo informado
        //se o arquivo estiver no DB files (utiliza a classe FilesService::getInfo())
        file         - (int) id do registro da tabela 'file'
                     - (object) model do registro da tabela 'file'

        //se o arquivo estiver em diretório (utiliza a classe FilesDirectService::getInfo())
        filename     - (string) nome do arquivo
        folder       - (string) default 'files'
        folder_date  - (boolean)
        private      - (boolean) default false
        account_id   - (int) opcional. Default current account id user logged
        account_off  - (boolean) opcional. Se true, considerado o diretório /app ao invés do diretório accounts
        
        //demais parâmetros
        route_action - (string) url para post dos comandos de enviar para a lixeira, excluir e restaurar. Caso não definido faz o post diretamente para a rota: route('admin.file.post|postDirect')
                            Variáveis POST enviadas: action=trash|restore|remove, id (para @post), file (para @postDirect)
                            Obs: ao definir uma rota, ex: route('custom_route'), certifique-se que o método do controller esteja preparado para tratar as entradas exatamente o padrão do controller FilesController@post|postDirect.
        onRemove     - (string) função javascript ao ser disparado após o envio do post (para remover ou restaurar da lixeira). 
                            Recebe como 1º parâmetro um json - sintaxe: {success: success, msg, action, file_id, oBt (jquery button) }
                            Ex: '@function(r){if(r.success)alert('Sucesso');}'
        fields       - (string, array) dos nomes dos campos a serem exibidos. Se string dividir os valores por virgula. Valores:
                            (para @postDirect)  name, size, type, updated_at, folder, storage, link
                            (para @post)        title, name, size, type, created_at, updated_at, deleted_at, user, folder, storage, link, thumbnail, relations
        bt_remove    - (boolean) se false não exibe o botão de remover, default true.
        bt_link      - (boolean) se false não exibe o botão de link, default true.
        view_params  - (array) todos os parâmetros view.blade. Opcional.

    Eventos javascript:
        //funções a serem personalizadas pelo usuário (trigger)
        //executadas no objeto do botão class .j-btn-remove     (obs: para diferençar os botões use as classes .j-btn-remove.j-action-(remove|restore))
        //ex: ...find('.j-btn-remove').on('onRemove',...);
        onRemove()   - ao remover cada registro. Parâmetros json opt retornado: success, msg, action, file_id, oBt (jquery button)

    


tab.blade | tab_content.blade - params:
    //Gera uma div de tabs de conteúdo
    data       - array de opções:
                tab_id => title, class, active (boolean)
                          content (string|function) - obs: se não definido, não cria a div content
                          menu (array) gera um menu ao invés do seu conteúdo. O conteúdo é o memso de templates.components.menu.blade
                          icon (string) (ex fa-close)
                          badge   - texto menor dentro do botão
                          badge_color - valores: as mesmas das classes de cores (somente o nome, ex 'red')
                          attr  - atributo na tag LI do item tab (opcional)
                          disabled - (boolean) default false
                          href - (string) link do tab
    class       - class div content. Valores programados: tab_clean. Li
    class_li    - class item li. Algumas classes utilizadas:
                        pull-right    - irá alinhar o item a direita
    id
    attr    
    title       - título geral da janela tabs
    icon        - ícone ao lado do título da janela tabs
    tab_active  - id do tab que será selecionado por padrão. Válido apenas se não for definido o parâmetro [data]['active=>true]] acima
    tab_active_js- ativa a função javascript para detectar e focar automaticamente no tab ao carregar a página. Default false.
    content     - (string|function|boolean) se definido irá cria um conteúdo padrão para todos os tabs (adicionado antes do conteúdo de cada tab)
                        Se ==false desativa a exibição de todo o conteúdo (neste caso use também o templates.ui.tab_content.blade
    is_content_clean  - se true irá limpa a formatação padrão do conteúdo do tab. Default false.
    *** Exemplo para gerar apenas o tab sem conteúdo:
                @include('templates.ui.tab',['data'=>[...], 'content'=>false, 'class'=>'no-margin' ])
                @include('templates.ui.tab_content',['content'=>(string|function)])     //serve apenas para gerar a div que envolve o conteúdo


accordion.blade - params:
    //Gera uma div de accordion de conteúdo
    data       - array de opções:
                tab_id => title, class, active (boolean)
                          content (string|function)
                          icon (string) (ex fa-close)
                          right (string|function) - contéudo na direita do título
                          badge   - texto menor dentro do botão
                          badge_color - valores: as mesmas das classes de cores (somente o nome, ex 'red')
    class       - class div content
    class_li    - class item li. Algumas classes utilizadas:
                        pull-right    - irá alinhar o item a direita
    id
    attr    
    default_hide - (boolean) se true, deixa todos ocultos por default
    show_arrow   - (boolean) se true, exibe as setas padrão de navegação/collapse. Default false.



form.blade - params:
    //Gera um padrão simples de formulário
    url         - route post action
    url_back    - url do botão voltar
    bt_back     - (string) nome do botão voltar. (boolean) True exibe no modo padão, False desativa.
    method      - default 'put'
    content     - (string|function) - aceita também array com dois parâmetros - sinstaxe: [function name,array params]
    bt_save     - (string|boolean) - string nome do botão ou bollean (=false para ocultar)
    url_success - url após salvar os dados (redireciona após salvar). Aceita a string ':id' para substituir pelo campo id de retorno, ex: '/user/edit/:id'
    class       - valores:
                        form-no-padd  - retira a margem padrão a direita
                        ...
    id          - ...
    attr        - (string|array) ...
    data_opt    - (array) opções do formulário (para o atributo <form data-opt=opt_json /> - são os mesmos parâmetros de função js/admin.js->awFormAjax() )
    alert_msg   - (boolean) se false irá desativar a estrutura de alerta padrão. Default true.
                      Se false, é recomando escrever a view do alerta em algum local dentro do form - comando: view('templates.components.alert-structure')




toolbar.blade - params:
    //Padrão de barra de ferramentas para adicionar acima de uma view, form ou lista de dados
    autodata    - (array) valores dos filtro para preenchimento automático dos campos autocolumns.
                    Obs: caso não seja definido um campo existente, o mesmo irá procurar o parâmetro via querystring.
    metabox     - (boolean) true ativa o metabox nas configurações padrões. Default false.
                  (array) ativa o metabox e com as configurações personalizadas (veja em templates.components.metabox).
    autocolumns - (array) parâmetros dos campos do filtros. São os mesmos valores de auto_fields.blade->autocolumns
                    Obs: o botão de submit do form do filtro é incluído automaticamente
                    Para cada campo/parâmetro pode ser adicionado o 'width' com a largura em pixel do campo (opcional)
    is_filter   - (boolean) se false desativa o evento padrão de filtro de campos (refresh da página)
    is_form     - (boolean) se false desativa o formulário padrão
    class       - valores: 
                        ui-toolbar-line 
                        ui-toolbar-marg-label - adiciona uma margem para a label




editor_view.blade - params:
    //Gera um editor de texto com uma janela de visualização HTML instantânea e integrador com o gerenciador de arquivos
    editor_type      - tipo do editor - valores: editor (default), editorcode



attachment_list.blade - params:
    //Padrão para lista de anexos (obs: semelhante ao arquivo files_list.blade, mas com alterações para ficar mais compatível com a lista de anexos)
    controller  - string com o nome da classe ou classe FilesController para carregamento das configurações (para utilização do método ->getConfig()). Ex: 'files' ou (new \App\Http\Controller\FilesController)
    files       - (array) lista de arquivos (padrão da classe App\Services\FilesService::getList())
    routes      - (array) load, remove, edit, upload. Obs: opcional e caso não informado será considerado o padrão de 'files'
    area_name|area_id
    files_opt   - (array) array de valores para sobrescrever as opções padrões deste template. Parâmetros: os mesmos de templates.ui.files_list['files_opt'],
    auto_list   - (array) array de valores para sobrescrever as opções padrões deste template. Parâmetros: os mesmos de templates.ui.auto_list,