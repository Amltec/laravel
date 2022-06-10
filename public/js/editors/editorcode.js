/**
 * Editor de Código
 * Funções complementares de lugins/codemirror-5.44.0/lib/codemirror.js
 * Funções do arquivo:
 *      editorcodeAWLoad()
 *      editorcodeAWMention()
 */


/**Função de inicialização para cada instância carregada do editor
 * @param jsonj options: form, field
 * @data editor - ex: $(field).data('editor')
 * 
 * Eventos:
 *  setValue(val)       - seta um valor no editor
 */
function editorcodeAWLoad(options){//esperado: form, field
    let oForm=options.form;
    let field=options.field;
    let wrap=field.closest('.editorcode-wrap');
    let opt=field.attr('data-editor-opt');if(opt)opt=$.parseJSON(field.attr('data-editor-opt'));if(!opt)opt={};
    //console.log(opt);
    
    let modes={
        html:{
            name: "htmlmixed",
            scriptTypes: [{matches: /\/x-handlebars-template|\/x-mustache/i,
                           mode: null},
                          {matches: /(text|application)\/(x-)?vb(a|script)/i,
                           mode: "vbscript"}]
        },
        css:'text/x-less',
        js:'application/ld+json',
        markdown:{
            name: "gfm",
        }
    };
    
    let editor_opt={
        lineNumbers: true,
        lineWrapping: true,
        styleActiveLine: true,
        matchBrackets: true,
        mode: modes[opt.editor_mode??'html'],
        selectionPointer: true,
        theme:'default',
        extraKeys: {},
        //readOnly: true,
    };
        //console.log(editor_opt)
        if(opt.theme)editor_opt.theme=opt.theme;
        if(opt.save_key){
            editor_opt.extraKeys['Ctrl-S'] = function(cm){ oForm.trigger('submit'); };
        };
    
    let editor=CodeMirror.fromTextArea(field[0],editor_opt);
    field.data('editor',editor);
    
    
    if(opt.auto_height){
        let n,o=$(editor.getWrapperElement());
        editor.setSize('100%', '100%');
        n=opt.height;
        if(n)o.css('min-height',n).find('.CodeMirror-scroll:eq(0)').css('min-height',n);
        
        n=opt.max_height;
        if($.isNumeric(n))o.css('max-height',n).find('.CodeMirror-scroll:eq(0)').css('max-height',n);
    }else{
        if(opt.height)editor.setSize('100%', opt.height);
    }
    
    
    //fullscreen
    let original_height;
    let fFullScreen=function(t){//t = true|false|auto(default)
        let tx = t=='auto'? !wrap.hasClass('fullscreen') : t;
        let cursor = editor.getCursor();
        if(!tx){
            $(document.body).removeClass('editorcode-fullscreen');
            wrap.removeClass('fullscreen');
            editor.setSize('100%', original_height);
        }else{
            $(document.body).addClass('editorcode-fullscreen');
            original_height = editor.getWrapperElement().offsetHeight;
            wrap.addClass('fullscreen');
            editor.setSize('100%', '100%');
        };
        editor.focus();
        editor.setCursor(cursor.line, cursor.ch);
    };
    
    
    //Ações nos botões
    wrap.on('click','[data-editor-cmd]',function(e){
        e.stopPropagation();
        let o=$(this);
        let c=o.attr('data-editor-cmd');
        if(c=='filemanager'){
            let json=$.parseJSON(o.attr('data-filemanager'));
            json.param_cb=field;
            json.onSelectFile=function(opt2){setText(opt2.urls);};
            awFilemanager(json);
            
        }else if(c=='textwrap'){
            let cursor = editor.getCursor();
            editor.setOption("lineWrapping", !editor.getOption("lineWrapping"));
            editor.focus();
            editor.setCursor(cursor.line, cursor.ch);
            
        }else if(c=='fullscreen'){
            fFullScreen('auto');
        }
    });
    
    //captura sempre a última posição do cursor
    let cursor_last={};
    editor.on('mousedown',function(){ setTimeout(function(){ cursor_last=editor.getCursor(); },0); });
    editor.on('keyup',function(){cursor_last=editor.getCursor();});
    
    //tirar a classe de erro ao editar
    editor.on('blur',function(){
        wrap.closest('.form-group').removeClass('has-error');
    });
    
    
    //seta um conteúdo dentro do editor
    let setText = function(data){
        let doc = editor.getDoc();
        if(awCount(cursor_last)==0){//não foi clicado / setado o focus no editor
            //foca no final
            editor.focus();
            editor.setCursor(editor.lineCount(), 0);
            cursor_last=editor.getCursor();
        };
        doc.replaceRange(data, cursor_last);
    };
    
    //mentions
    if(opt.mention){
        let mentionConfig=$.extend(true,{},opt.mention);
        editorcodeAWMention(editor,mentionConfig);
    };
    
    
    //eventos
    field.on({
        'setValue':function(e,val){ setText(val); }
    });

};


function editorcodeAWMention(editor,opts){
    //código similar ao do arquivo /public/js/mentionjs.js, mas reescrito para ficar compatível com o CodeMirror (por isto não precisa importá-lo)
    var opt = $.extend(true,{
        key:'@link',            //texto chave que irá adicionar o callback
        onselect:function(){},  //ao clicar/selecionar um item. Parâmetros recebidos: (json) return, (jquery) item clicked, (json) data loop form ajax
        
        //template ao selecionar o item da lista. Aceita string|function. Utilize {field} contendo o nome do campo retornado do ajax. Ex: '{code}' ou function(data){return '{'+data.code+'}'}
        template_text:'{code}',         //formato de texto
        template_html:function(data){   //formato html   //aceita =false para desativar (fica apenas o modo de template_text)
            if(data.html){
                return data.html;
            }else{
                let tmp = document.createElement("DIV");
                tmp.innerHTML = data.title;
                let title = tmp.textContent || tmp.innerText || '';
                return '<a href="'+ data.code +'" data-mentions="ok">'+ title +'</a>';
            }
        },
        //*** customização adicional da função awBoxList ***
        box_list:{},    //valores: os mesmos de public/js/main.js->awBoxList()
    },opts);
    
    
    const _selTemplate=function(type,data){//type=html|text
        let r, n=opt['template_'+type];
        if(type=='html' && n===false)n=opt['template_text'];
        if(typeof(n)=='function'){
            r=n.call(null,data);
        }else{
            r=n;
            for(let i in data){
                r=r.replace('{'+ i +'}',data[i]);
            };
        }
        return r;
    };
    
    
    let awMention__o=null;
    editor.on('keyup',function(edit){
        if(awMention__o)return;//para evitar que seja carregado + de 1x
        
        const e=window.event;
        const k=window.event.keyCode;
        if(e.altKey || e.shiftKey || $.inArray(k,[8,9,13,16,17,18,37,39,38,40,27])!==-1)return;//8 backspace, 9 tab, 13 enter, 16 shift, 17 ctrl, 18 alt, 37 left, 39 right, 38 up, 40 down, 27 esc
        if(e.ctrlKey && (k!=32))return;//avança para se for ctrl+space
        
        let line = edit.doc.getCursor().line,   //Cursor line
            ch = edit.doc.getCursor().ch,       //Cursor character
            n = opt.key.length,
            ps = Math.max(ch - n,0),
            lineStr = edit.doc.getLine(line),
            stringToTest = lineStr.substr(ps,n);
        
        //interrompe caso opt.key não seja encontrado
        if(stringToTest!=opt.key)return;
        
        //verifica se nas laterais existem espaços
        n=lineStr.substr(ps-1,1);//console.log('left',ps,'|'+n+'|',lineStr)
        if(ps>0 && $.inArray(n,['',' ','<','>','"',"'"])===-1)return;//sem espaço a esquerda
        
        //não precisa validar caractere a direita
        //n=lineStr.substr(ps+opt.key.length,1);//console.log('end',[ps,ch],'|'+n+'|',lineStr)
        //if(ps>0 && $.inArray(n,['','','<','>'])===-1)return;//sem espaço a direita
        
        //console.log(lineStr,'***',lineStr.substr(n,1),ps);return;
        let offset = edit.cursorCoords(true);
        
        
        //para este caso, é esperado o retorno (da rota /links/search) no formato: array[json,...]
        //  para cabeçalhos {title,head:true,disable:true}
        //  para resultados {title,code,url}    //o texto em 'code' é que será inserido na página
        
        let box_list_opt = $.extend(true,{
            search_onshow:true,
            title:'Pesquisar Links',
            ajax: {
                url: admin_vars.url_app + '/links/search'
            },
            onselect:function(item,data){
                let rpl = _selTemplate('text',data);
                let doc = edit.getDoc();
                
                let ps = ch-opt.key.length+rpl.length;
                doc.replaceRange(rpl, {line:line,ch:ch-opt.key.length}, {line:line,ch:ch});
                edit.focus();
                edit.setCursor(line, ps);
                
                let r={cursor:{line:line,ch:ps},key:rpl,offset:offset};
                opt.onselect.call(null,r,item,data);
                return r;
            },
            template:function(data){
                if(data.head){//quer dizer tem sub dados, portanto este é um grupo
                    return '<strong>'+ data.title +'</strong>';
                }else{
                    return '<span>'+ data.title +'</span> <small style="color:#ccc;margin-left:10px">'+ data.code +'</small>';
                }
            },
        },opt.box_list);


        const obj = awBoxList(box_list_opt).on('close',function(e,opt2){
            awMention__o=null;
            if(opt2 && opt2.isCloseEsc){//foca o cursor novamente no campo
                editor.focus();
                editor.setCursor(line, ch);
            }
        });
        awMention__o=obj;
        
        obj.trigger('show',{ pos:[offset.left, offset.top] });
         
    });
};