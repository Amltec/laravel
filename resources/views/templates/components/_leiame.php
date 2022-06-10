<?php
/*
Instruções gerais de componentes templates.blade.php
Demais campos não descrito, utilizar o padrão Laravel Collective https://laravelcollective.com/docs/master/html
Sitaxe: @include('templates.components.text',array params)
Obs: todos os templates procuram o valor 'value' na seguinte ordem:
    1º procura pela variável $autodata->{$fieldname} que vem automaticamente do campo autofield
    2º procura através da varável que é passado no comando do laravel Form::model($autodata,...) - uitiliza o comando Form::getValueAttribute($name.'_ddi')
    3º procura pela variável $value informando no como parâmetro ao chamar o respectivo include
Obs2: a maior parte dos componentes abaixo requer outras funcionalidades, mas se chamados pelo template.ui.auto_fields, então serão inicializados em com todas as suas dependências.


hidden.blade - params:
    type        - hidden (default)
    id          - identificador do campo
    name        - nome do campo (obrigatório)
    value       - valor padrão (para botões, será o texto do botão)
    attr        - demais atributos do campo (formato string)
    class_field - classe do campo

text.blade - params:
    type        - text (default), password, hidden, search, date, time, cep, cnpj, cpf, number, phone_only (somente campo com máscara), decimal, currency, daterange, color)
    id          - identificador do campo
    name        - nome do campo (obrigatório)
    label       - rótulo do campo
    value       - valor padrão (para botões, será o texto do botão)
                    Obs: para os campos decimal e price, a entrada pode ser: (int|float) ex 1000.05, (string), ex '1.000,05'
    placeholder - texto placeholder
    maxlength   - atributo maxlength
    attr        - demais atributos do campo (formato string)
    class_group - classe da div do grupo do campo. Classes já padronizadas:
                         (.form-group).require - campo requerido
    class_label   - classe da div acima do campo
    class_div   - classe da div acima do campo
    class_field - classe do campo
    picker      - (boolean) se True adiciona o objeto picker (somente para type=date|time|datetime|daterange|color). Default false.
    button      - (array) botão (veja em templates.components.button).
                        Parâmetro adicional: 'align' => left(default)|right - indica se o botão ficará a esquerda ou direita do campo
    data_label  - rótulo para o campo (somente se o parâmetro 'label' não definido)
    width       - largura do campo em pixels (obs: alterado na  classe .control-div)
    width_group - largura do campo em pixels (obs: alterado na  classe .form-group)

datetime.blade - params
    //identico ao 'text', mas sem o parâmetro: type, placeholder, maxlength.
    Obs: 1) são criados 2 campos adicionais de controle de data e hora, mas para fins de request, o nome do campo continuará o mesmo
         2) o valor de entrada do campo aceito nos formatos: dd/mm/aaaa ou aaaa-mm-dd

textarea.blade - params:
    //identico ao 'text', mas sem o parâmetro: type
    resize       - (booelan) se true irá desabilitar o resize do campo
    rows         - (int) número de linhas
    auto_height  - (boolean|int) define a altura automática. 
                        (boolean) se ===true então limite a largura máxima da tela, 
                        (int) valor da altura máxima. 


select.blade - params:
    //identico ao 'text', mas sem os parâmetros: type, placeholder
    list         - valores da lista. Sintaxes:
                        lista normal    - [value=>text,...]
                        lista grupo     - [label=>[value=>text... ]  ]

select2.blade - params:
    //identico ao 'select', mas utilize o plugin js select2() para criar o campo (combo select + input de busca).
    ajax_url     - rota para pesquisa dinâmica no campo
    data-select - demais parâmetros de inicialização do select2 (o parâmetro ajax_url acima pode ser informado aqui se preferir).
    //atributos html aceitos:
            - data-ajax-url="..."       - url para requisição ajax (GET)      //obs: é enviado o parãmetro 'q' como campo de busca na url ajax    
            - data-ajax-once="true"     - faz com que a lista ajax seja carregada uma única vez
            - data-allow-clear="true"   - permite limpar o campo após ter selecionado um resultado
            - data-tags="true"          - permite inserir um texto no campo independente dos valores da lista
            - demais atributos adicione com data-{attributes}  - veja mais em https://select2.org/configuration/data-attributes
            //obs: na lógica, irá procurar pelo dado $().data('data-select') e caso não encontre procura pelo atributo '<... data-select="....">'
 

select_icon.blade - params:
    //campo select de lista de ícones do tema
    //identico ao 'select', mas já vem com a lista pronta
    
    

radio.blade - params:
    id          - identificador do campo
    name        - nome do campo (obrigatório)
    label       - rótulo do campo
    value       - valor padrão (para botões, será o texto do botão)
    default     - valor do campo value padrão caso o parâmetro 'value' acima não seja definido (opcional)
    attr        - demais atributos do campo (formato string) (será atribuído na tag .class_div)
    class_group - classe da div do grupo do campo. Classes já padronizadas:
                         (.form-group).require - campo requerido
    class_label - classe da div acima do campo
    class_div   - classe da div acima do campo. Classes já programadas: small1, small2
    class_field - classe no campo
    class_item  - classe de cada item
    list        - valores da lista. Sintaxe: [value=>text]
    break_line  - (boolean) quebra em linhas as opções. Default false.
    data_label  - rótulo para o campo (somente se o parâmetro 'label' não definido)
    info_html   - ...
    
checkbox.blade - params:
    //identico ao 'radio'
    value       - array dos valores da lista
    value_all   - nome do valor que representa todos os campos. Se definiddo, ao ser marcado o campo (ex 'marcar tudo'), todos as opções deste campo serão marcadas

sim_nao.blade - params:
    //identico ao 'radio' mas com os valores padrões da lista 'sim' ou 'não' considerando que 'value' é um valor booleano
    list        - se não informado, será informado o valor padrão ['s'=>'Sim',s'=>'Não']   //obs: informar apenas dois valores
    value       - aceita os valores para - false: 'n','0','false'   - true: 's','1','true'

upload.blade - params:
    //identico ao 'text', mas sem os parâmetros: type, placeholder
    //No momento está configurado para ser usado dentro de templates.ui.autofields.blade. Se precisar de um botão individual, utilize o template button.blade ([type=upload])
    class_button    - padrão 'default'. Valores: primary, info, ...
    accept          - tipos de arquivos aceitos. Default 'image/*'.
    icon            - ícone do upload, default 'fa-upload'. Se==false não exibe.
    multiple        - (boolean) se true, permite a seleção múltipla de arquivos
    //Observações:
    Veja mais detalhes em arquivo public/js/admin.js->awFormAjax() para upload


uploadzone.blade - params:
    //faz o upload permitindo arrastar e soltar em uma determinada área
    //(correspondende a função javascript admin.js -> awUploadZone())
    //      obs: para inicializar, basta existir no html o comando: <div ui-uploadzone="on"></div> e disparar a função awUploadZone()
    title       - título dentro da janela
    name        - nome do campo. Default 'file'.
    id          - id do campo. Default null.
    class       - classe adicional
    multiple    - (boolean) se true, permite a seleção múltipla de arquivos
    accept      - tipos de arquivos aceitos. Default 'image/*'.
    height      - altura em px (opcional).
    

uploadbox.blade - params:
    //Campo de upload completo que permite upload, visualização e removação do arquivo.
    //Este campo retorna a um inputtext com o ID ou Url do arquivo para submit do form (precisa estar dentro de um form com submit para gravar os dados retornados após o upload)
    //Para envio do upload, é utiliza a janela modal JS awUploadFieldBox() (em js/admin.js)
    /Todos os posts ajax são para a rota 'admin.file.post|postDirect'
    //Obs1: este tipo de campo permite apenas um envio por vez
    //Obs2: é criado no dom para request no controller, os campos: input'{name}', input'{name}--url'
    name        - requerido, nome do campo input hidden, que irá armazenar a url do arquivo retornado
                    //obs: será criado automaticamente um campo input hidden 'name-fileid', que irá armazenar o ID de retorno (se houver)
    label       - rótulo do campo
    value       - se upload_db=true  - deve ser informado o id da tabela files.id
                  se upload_db=false - deve ser informado um array serial php com os parâmetros: (str)filename,(str)folder,(bool)private,(bool)account_off
                                                Obs: no carregamento do campo não é considerado este parâmetro 'value', pois este valor é gerado automaticamente através do parâmetro 'upload' (veja abaixo)
                                                     caso informado, no carregamento deste campo será desconsiderado.
                  se vazio (para ambos os casos), quer dizer que a imagem não existe
    attr        - demais atributos do campo (formato string)
    data_label  - rótulo para o campo (somente se o parâmetro 'label' não definido)
    class_group - classe da div do grupo do campo. Classes já padronizadas:
                         (.form-group).require - campo requerido
    class_label   - classe da div acima do campo
    class_div   - classe da div acima do campo
    class_field - classe do campo
    + todos os demais parâmetros de button.blade
    
    upload_view  - array de parâmetros de visualização do upload. Se ==false não exibe
                    - width, height
                    - remove - (string) valores: '' não remove, 'e' limpa o campo, 'e2' confirma e limpa, 'r' remove, 'r2' confirma e remove. Default 'r2'.
                    - thumbnail (small,medium,large,full). Defalt medium
                    - filename_show - (se ==false), irá ocultar o nome do arquivo (caso exibido). Default true.

    filemanager - (false|array) se array, indica que o upload será através da janela do filemanager (não irá abrir a janela modal). Parâmetros
                    Parâmetros aceitos (veja + em \App\Http\Controllers\FilesController->indexModal()    (e ->base_params_list())   (e em /public/js/awFilemanager()) ):
                        controller
                        private,taxs,folder,
                        area_name,area_id
                        metadata,meta_name,meta_id
                        onSelectFile - function js callback. Parãmetro retornado: json opt, ex: ,onSelectFile(opt){ console.log(opt...) }
                        Obs: neste arquivo, somente o parâmetro multiple está desativado
                    Aceita também os valores boolean:
                        true - para ativar com a configuração padrão (veja os valores em padrões estão no arquivo views.templates.ui.files_list.blade)
                        false - para desativar o editor
                    //Obs: se ==false (default) processa apenas o upload direto por janela modal (ver parâmetros abaixo)
                    
    //para filemanager==false
    route       - rota para onde sera publicado. Caso não informado será considerado automaticamente para admin.file.post|postdirect conforme parâmetro abaixo upload_db
    upload_db   - (booelan) se true, irá armazenar o arquivo na tabela 'files' pelo controler FilesService@post, se false usará o controler FilesService@postDirect. Default true.
    controller  - (string|FileController class) ex: 'files' | \App\Http\Controller\FilesController. Obs: somente para upload_db=true
    upload      - array de parâmetros do upload:
                    - filename,folder,private,account_off   //estes parâmetros são obrigatórios se upload_db=false
                    - accept,max_width,max_height,image_fit,filename,filetitle, 
                    - ... e demais parâmetros da classe \App\Services\FilesService@post|postDirect
    upload_form - array de parâmetros do form:
                    - onBefore|onSucesss|onError:    (string) function js (veja mais em admin.js->awFormAjax()



editor.blade - params:
    id          - identificador do campo
    name        - nome do campo (obrigatório)
    label       - rótulo do campo
    value       - valor padrão (para botões, será o texto do botão)
    attr        - demais atributos do campo (formato string)
    class_group - classe da div do grupo do campo. Classes já padronizadas:
                         (.form-group).require - campo requerido
    class_label   - classe da div acima do campo
    class_div   - classe da div acima do campo. Classes já padronizadas:
                         .editor-border - borda para o editor (somente para template=inline)
    class_field - classe do campo
    height      - (int) altura do editor
    auto_height  - (boolean|int) define a altura automática. 
                        (boolean) se ===true então limite a largura máxima da tela, 
                        (int) valor da altura máxima. 
    toolbar_fixed - (boolean) se true irá deixar a barra de ferramentas fixa no topo ao rolar a tela (obs: não está funcionando para o jodit)
    plugin      - (string) nome do plugin do editor. Valores: ckeditor, jodit (em desenvolvimento)
    template    - (string) nome do template do editor, valores: se true, exibe a versão curta do editor
                    short       - exibe uma versão curta do editor com as principais opções
                    text        - exibe somente as opções de texto
                    text_short  - exibe somente as opções de texto com editor pequeno
                    inline      - modo de linha
    
    filemanager - (array|boolean) parâmetros adicionais para quando for aberto o gerenciador de arquivos.
                    Parâmetros aceitos (veja + em \App\Http\Controllers\FilesController->indexModal()    (e ->base_params_list()) ):
                        controller,
                        private,taxs,folder,
                        area_name,area_id
                        metadata,meta_name,meta_id
                            Obs: pela lógica dentro do editor, parâmetros como multiple, onSelectFile são desconsiderados
                    Aceita também os valores boolean:
                        true - para ativar com a configuração padrão (veja os valores em padrões estão no arquivo views.templates.ui.files_list.blade)
                        false - para desativar o editor
    mention      - (boolean|array) ativa o recurso mention. Default false. Se array, deve conter os mesmo atributos de public/js/mention.js->awMention()
    data_label  - rótulo para o campo (somente se o parâmetro 'label' não definido)


editorcode.blade - params:
    toolbar          - (boolean|array) barra de ferramentas. Valores:
                            true    - (default) ativa a barra de ferramentas no modo padrão
                            false   - oculta a barra de ferramentas
                            array   - customiza os botões, valores:
                                            (boolean) com True para setar os botões já programados (citados abaixo)
                                            (string) nome dos botões já programados, valores: filemanager, textwrap, fullscreen
                                            (array) parâmetros do componente do botão (templates.components.button), ex: ['title'=>'Botão','onclick'=>...]
                                            (function) qualquer comando a ser executado dentro da barra de ferramentas
                                            (view) ex view(...)
                                            Exemplos: 
                                                [true, ['title'=>'Meu botão',...], function(){...} ]
                                                ['filemanager', 'fullscreen','id'=>['title'=>'Botão1',...], ...]
    theme_dark       - (boolean) se true irá ativar o tema escuro. Default false.
    editor_mode      - (string) modo do editor. Valores: html (default), javascript, css, markdown
    save_key         - (boolean) se true irá ativar a tecka de atalho 'ctrl+s' para salvar o form. Default true.
    height           - (int) altura do editor. Obs: utilize 0 ou 1 para deixar na altura mínima.
    html_pre         - (boolean) exibe o campo 'Adicionar parágrafos automaticamente'. Default false.
                            Obs: se true, será gerado um campo com o nome $name.'_html_pre' com o valor 's' caso marcado
    value_pre        - (boolean|string) valor do campo html_pre (caso true). Valores: true|s|1 para marcar
    ... demais identico ao 'editor' com exceção dos parâmetros: toolbar_fixed, plugin, template
    











info.blade - params: 
    //gera um bloco de texto / informações (div)
    id          - identificador
    label       - rótulo do bloco
    text        - texto do bloco (formato string|function)
    attr        - demais atributos do bloco (formato string)
    class_group - classe da div do grupo / bloco
    class_label   - classe da div label
    class_div   - classe da div text
        
    

cidade_uf.blade | uf.blade | pais.blade - params:
    //Combo de campo: text/select para cidade/uf    |   exibe o campo select uf
    //Para cidade_uf - são gerados dois campos separados um com cada nome. Sintaxe para name e id abaixo: sitaxe: [cidade]|[uf]
    name        - nome do campo
    id          - id do campo
    label       - rótulo do campo
    class_group - classe da div do grupo do campo
    class_label   - classe da div acima do campo
    class_div   - classe da div acima do campo
    placeholder - (booelan) se true, exibe o placeholder padrão cidade e uf
    value       - (string) por padrão nome do campo principal, ex: cidade
    value_uf     - valor para o campo uf
 

endereco_num.blade - params:
    //Combo de campo: text/text para endereço e número
    //São gerados dois campos separados um com cada nome. Sintaxe para name e id abaixo: sitaxe: [endereco]|[numero]
    name        - nome do campo
    id          - id do campo
    label       - rótulo do campo
    class_group - classe da div do grupo do campo
    class_label   - classe da div acima do campo
    class_div   - classe da div acima do campo
    placeholder - (booelan) se true, exibe o placeholder padrão endereço e número
    value       - (string) por padrão nome do campo principal, ex: endereço
    value_num   - valor para o campo número


phone.blade - params:
    //identico ao 'text'='fone', mas sem os parâmetros: type, placeholder
    //Combo de campo: ddi + phone + marcador
    //Parâmetros adicionais dos campos:
    name e id   - ambos os campos são adicionados automaticamente os sufixos: _ddi, _num, _mark. Ex: name='meufone', então os campos serão: meufone_ddi, meufone_num e meufone_mark
    value_mark - valor para o campo marcador
    value_ddi - valor para o campo ddi do telefone
    is_mark - (boolean) default false (para o template phone_mark)
    is_ddi - (boolean) default false

email.blade | phone_mark.blade - params:
    //identico ao 'text', mas sem os parâmetros: type
    //Combo de campo: email + marcador
    //identico ao 'text', mas sem os parâmetros: type, placeholder
    //Obs adicionais dos campos:
    name e id   - ambos os campos são adicionados automaticamente os sufixos: _mark. Ex: name='meuemail', então os campos serão: meuemail_email e meuemail_mark
    is_mark - (boolean) default false  (para o template phone_mark)
    value_mark - valor para o campo marcador

button.blade - params:
    icon    - nome do ícone do botão (ex fa-check)
    icon_pos- left (default), right, top
    title   - texto do botão. Deixe vazio ou ===false caso queira exibir apenas o ícone.
    alt     - texto alternativo
    color   - default (default), primary, success, info, danger, warting. Aceita false para não setar o padrão 'default'
    size    - lg, sm, xs, '' (default)
    id      - id do botão
    attr    - atributos html
    class   - valores já programados: btn-lg, btn-sm, btn-xs, disabled, btn-block
                classes de cores: bg-maroon, bg-purple, bg-navy, bg-orange, bg-olive, bg-red, bg-green, bg-blue
    badge   - texto menor dentro do botão
    badge_color - valores: as mesmas das classes de cores (somente o nome, ex 'red')
    sub     - (array) sub menu do botão (veja em templates.components.menu)
    sub_opt - (array) sub menu apenas como opções do botão (é o subbotão ao lado do botão - veja em templates.components.menu)
    class_menu - classe do menu (veja em menu.blade)
    class_group - classe do grupo (para quando houver uma combinação de botões)
    onclick - parâmetro onclick. Importante: Usar apenas aspas no meio do texto, ex:  'onclick'=>'alert("123 \"4\" 567");'
                     //obs: o mesmo pode ser feito com parâmetro attr (ex:'attr'=>'onclick="..."' )
                     //obs2: não é recomando utilizar se o parâmetro se 'post' (abaixo) for definido
    type    - tipo do botão. Valores: '' (default), 'submit','upload', 'link' (gerado automaticamente se existir parâmetro 'href') 
                    //se definir apenas o tipo 'submit' sem outros parâmetros, já virá com uma configuração padrão, ex: view('templates.components.button',['type'=>'submit'])
    //somente se type==upload
        id
        name
        accept
        multiple

    //Parâmetros adicionais: se adicionado os itens abaixo, o tipo do botão será link, ex <a></a>...
        href
        target

    //post data
    post        - (array) parâmetros que permitem fazer um post via ajax diretamente no controller ao clicar do botão
                    url     - (string) route post
                                Obs: é sempre esperado um json de retorno desta rota
                    method  - (string) default POST
                    data    - (array) campos e valores do post. Ex: ['a'=>1,'b'=>2]...
                    confirm - (string) msg de confirmação)
                    cb      - (string) nome ou função js callback
                            - (array) array de strings de nomes ou função js callback (executado cada um na ordem informada)
                    

button_field.blade - params:
    //identico ao 'text', mas no lugar o campo exibe o button.blade
    //obs: os parâmetros são mesclados entre estas duas views


button_group.blade - params:
    //grupo de botões tendo como parâmetro um array de valores identico ao 'button'
    buttons     - array de templates button.blade .Ex: [ ['title'=>'Botão A'], ['title'=>'Botão B'] ]



menu.blade - params:
    //monta um item de menu
    sub     - (array) itens do submenu - sintaxes possíveis: 
                1   array(id=>title)    OU
                2   array(id=>array('title'=>, 'icon'=>, 'header'=>(boolean), 'link'=>(href), 'attr'=>, 'class'=>, 'class_li'=>, 'checkbox'=>(booelan), 'alt'=>, 'html'=>, 'onclick'=>'..' sub'=>(array|array function - as mesmas opções de sub))      OU
                        Obs: para nova janela, utilize: 'attr'=>'target="_blank"'
                        Obs2: em onclick - usar apenas: 'apóstrofo'
                3   (string) 'sep' - separador
                //informações:
                    checkbox - se true, adiciona um campo checkbox (neste caso ignora o atributo 'icon') 
                               neste caso existe o campo adicional 'checked'=true|false para marcar o campo automaticamente
                    html     - permite inserir qualquer html abaixo do item A
               //nomes de classes úteis: disabled, ...
                    
    id_menu      - id do menu (opional)
    class_menu   - classe do menu (opcional). Valores já programados: dropdown-menu-right
 

alert.blade - params:  
    //mensagem de erro padrão
    type    - valores: success, info, warning, danger (default) // aceita também true (success) false (danger)
    msg     - mensagem do erro
    isclose - (boolean) exibe a o botão fechar (default true)
    content - texto completo do erro (opcional)
    class   - classe adicional
alert-structure.blade - o mesmo acima, mas sem parâmetros e a estrutura crua para javascript (sem parâmetros)



metabox.blade - params:
    //gera os painéis de conteúdos (metaboxs) dentro do dashboard
    id          - 
    class       - 
    title       - título do metabox
    content     - string html function ou function html callback.
    footer      - string html function ou function html callback. Se ==false não exibe.
                  array - para botões padrões. Sintaxe: 'bt'=>true|[...],'bt2'=>true|[...]
    header      - string html function ou function html callback. Se ==false não exibe. (adiciona ao lado do título)
    color       - cor da borda - valores: primary, default, info, danger, ...
    is_border   - (booelan) exibe a borda. Default true.
    is_close    - (boolean) exibe o botão fechar. Default false.
    is_collapse - (boolean) exibe o botão minimizar. Default false.
    is_header   - (boolean) exibe o cabeçalho. Default true.
    is_footer   - (boolean) exibe o cabeçalho. Default false.
    is_bg       - (boolean) preenche a cor de cabeçalho do box. Default false.
    is_padding  - (boolean) se true (default), exibe o padding
   


tag_item.blade - params:
    //Gera os tags padrões em html (correspondende a função javascript admin.js -> awTagItem())
    opt     - (array):
        id:'',          //identificador - opcional
        title:'Tag',    //indica o modo visual de controle do bloco
        link:null,      //link ao ser clicado
        class:'',       //classe adicional
        color:'gray',   //valores: green, yellow, teal, aqua, red, purple, maroon, navy, olive, black, gray ou hexadecimal (ex #ffcc99)
        icon:null,      //ex: fa-edit
        attr:'',        //atribute hmtl
        type:'badge',   //tipo do elemento utilizado, valores: badge ou btn
        btClose:false,  //exibe o botão fechar
        confirmClose:false,//true exibe a janela de confirmação, (string) exibe com mensagem personalizada, false não exibe
        //os parâmetros abaixo são para uma padronização das do recurso de taxonomias
        term_id:null,
        tax_id:null
    events    - (boolean) false,   //se true aplica os eventos na tag (aciona o javascript)
    model     - (object) model - registro da tabela 'taxs', se informado não é necessário setar os parâmetros de 'opt' (caso contrário irá sobrescrever os dados desta model)

    Eventos javascript da tabela (trigger):
         remove() - remove o item (padrão jQuery)
        //personalizados pelo usuário
        onBeforeClose()   - antes de remover (pode ser usado com o triggerHandler() com return boolean)
        onClose()   - após remover
        onClick()   - ao clicar
 


tree.blade - params:
    //Gera uma lista de diretórios
    id_menu      - id do menu (opional)
    class_menu   - classe do menu (opcional). Valores já programados: tree-condensed
    icon_def     - padrão classe do ícone. Default 'fa-folder' (opcional). 
    sub_icon_def - padrão classe do ícone para os subitens. Default o mesmo de icon_def. 
    collapse_def - padrão do efeito collapse. Default true. (opcional). 
    show_caret   - exibe o botão caret em caso de submenu. Default true.
    show_icon    - exibe o botão o ícone para todos os casos
    pos_caret    - posição da seta para o menu principal. Valores: left (default),right
    pos_caret_def- posição da seta para para os demais níveis
    link_force   - (boolean) se true - irá sempre disparar o click no item do diretório, se false irá sempre collapsar/expandir sempre que houver subdiretórios mesmo com link informado no item
    select       - id da pasta para manter selecionado. Obs: será marcado o primeiro atributo [data-id] encontrado.
    routes       - (array) urls das rotas paras as ações (opcionais):
                        click   - (function) quando for clicado no item, ex: function($id,$item){ return route(...); }
    sub          - (array) itens do submenu - sintaxes possíveis: 
                    1   array(id=>title)    OU
                    2   array(id=>array('title'=>, 'icon'=>(class icon), 'icon_color|_def'=>(hex ou name), 'icon_def'=>(def icon sub), 'header'=>(boolean), 'link'=>(href), 'attr'=>, 'class'=>, 'class_li'=>, 'alt'=>, 'html'=>, 'collapse'=>(boolean),
                            'sub'=>(array - as mesmas opções de sub)), 
                    OU
                     3   (string) 'sep' - separador
                    //informações:
                        icon     - personaliza o ícone somente no item atual
                        icon_def - personaliza o ícone para todos do sub diretório (somente se existir submenu). Se ==false, reseta para o padrão ascendente do ícone.
                                        Obs: o mesmo vale para o icon_color_def
                        html     - permite inserir qualquer html abaixo do item (se informado, o submenu não irá funcionar)


menuv.blade - params:
    //Gera um menu vertical no padrão do tema
    O mesmo de tree, mas configurado para ficar semelhante ao menu lateral principal padrão do sistema


text_filemanager.blade - params:
    //O mesmo de text.blade, mas com campo para abrir o gererenciador de arquivos e retorna aos arquivos selecionados no inputtext
    //Parâmetros adicionais
    type2                    - (string) permite alterar o tipo do input. Valores: text (default), ou hidden (exibe apenas o botão
    filemanager_param        - (array) array de parâmetro ao abrir a função js awFilemanager(). Os valores são os mesmos desta função (veja + em /public/js/awFilemanager()).
    filemanager_return       - (string) comando do tipo do retorno ou function js de retorno. É disparado após selecionar os arquivos. Retorna os mesmos parâmetros de 'onSelectFile' da função js awFilemanager().
                                Valores:
                                    'urls'         - irá retorna a urls dos arquivos (tamanho original) separados por virgula (padrão)
                                    'ids'           - irá retorna aos ids dos arquivos separados por virgula
                                    '@function'     - função callback. Recebe o 1º parâmetro var json. Requer return string como filtro de valores para o input text deste template (veja + em /public/js/awFilemanager()).
                                                            Ex:function(opt){ return awCount(opt.files) }
                            
colorbox.blade - params:
    //Gera um campo de color com modelos prontos.
    //Obs: para campos simples de cor, utilize o component text[type=color]
    label, class_group, class_div, class_label, name, id, info_html    - parâmetros iguais ao component blade.text
    value   - valor da lista.
    none    - se true irá exibir como primeira opção nenhum/vazio. Default true.
    list    - lista de cores em hexadecimal. Sintaxe: [value=>color]   ou  [value=>color_bg|color_text].
              Exs: ['1'=>'#cc0000'], ['2'=>'#ffffff|#cc0000']...
              Obs: caso não informado, será captura a lista padrão de cores da classe \App\Utilities\ColorsUtility::$colors.


html.blade - params:
    //Escreve um campo html dentro do formulário
    id, name, class_group, class_div, class_label, label, attr,
    html    - (string, function) conteúdo html

