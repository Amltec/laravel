/**
 * Mensão de com @fields - inicialização do plugin
 * Dependências: admin.js (inicialização), caret.js (https://github.com/ichord/Caret.js)
 * Funções do arquivo
 * 
 * awMention()
 * awMentionUILinks()       - Abre a janela de pesquisa de links padrão
 * getInputWordPosition()   - Retorna a palavra em que se encontra a posição do cursor
 * 
 * Arquivos requeridos carregados dinamicamente:
 *      boxlist.js
 */

//Principal de mensões considerando o padrão de busca de links
//Parâmetro jquery|js dom 'field' - campo de input, textarea, contentEditable. Aceita o parâmetro data-mention='{...}' contendo o json a ser mesclado com o parâmetro 'opt' abaixo
function awMention(field,opts){//chamada
    var opt = $.extend(true,{
        key:'@link',            //texto chave que irá adicionar o callback
        cb:awMentionUILinks,    //função callback após identificar o texto em opt.key
        iframe:null,            //setar o objeto dom (caso o campo esteja em um iframe)
        
        //*** configuração padrão para caso o callback = awMentionUILinks() ***
        onselect:function(){},  //ao clicar/selecionar um item. Parâmetros recebidos: (json) return, (jquery) item clicked, (json) data loop form ajax
        //template ao selecionar o item da lista. Aceita string|function. Utilize {field} contendo o nome do campo retornado do ajax. Ex: '{code}' ou function(data){return '{'+data.code+'}'}
        template_text:'{code}',         //formato de texto
        template_html:function(data){   //formato html   //aceita =false para desativar (fica apenas o modo de template_text)
            //console.log(data)
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
    
    if(typeof(field)=='string'){
        field=$(field);
    }else if(field instanceof jQuery == false){
        field=$(field);
    };
    
    //verifica se existe a configuração por atributo e mescla com opt
    let d_opts = field.attr('data-mention');
    if(d_opts){
        d_opts = $.parseJSON(d_opts);
        opt = $.extend(true,opt,d_opts);
    };
    
    field.on('keyup.awMention',function(e){
        const k=e.keyCode;
        if(e.altKey || e.shiftKey || $.inArray(k,[8,9,13,16,17,18,37,39,38,40,27])!==-1)return;//8 backspace, 9 tab, 13 enter, 16 shift, 17 ctrl, 18 alt, 37 left, 39 right, 38 up, 40 down, 27 esc
        if(e.ctrlKey && (k!=32))return;//avança para se for ctrl+space
        
        var o=$(this);
        var field_html=o.prop('nodeName')=='DIV' || o.attr('contentEditable')!=undefined;
        
        var ps = o.caret('pos', {iframe: opt.iframe});
        var pe = ps;
        
        const w=getInputWordPosition(o,ps,opt.key);
        
        var frame_pos = opt.iframe ? $(opt.iframe).offset() : {left:0,top:0};
        var offset = $(this).caret('offset', {iframe: opt.iframe});
        //console.log(frame_pos,offset)
        offset.left += frame_pos.left;
        offset.top += frame_pos.top;
        
        var n=w.text;
        if(n==opt.key && opt.cb){
            opt.cb.call(null, {
                field:field, 
                key:opt.key, 
                val:this.value, 
                cursor:{ps:w.ps, pe:w.pe, p:ps}, 
                offset:offset,
                field_html:field_html,
                iframe:opt.iframe,
                box_list:opt.box_list,
                onselect:opt.onselect,
                template_text:opt.template_text,
                template_html:opt.template_html,
            });
        }
    });
    
};

//setTimeout(function(){  awMentionUILinks({field:$('#field1'), key:'@link', val:'qwewqe qwe @link', cursor:{ps:1,p2:3,p:2}, offset:{left:300,top:150,height:18}       })     },100);//teste


/**
 * Abre a janela de pesquisa de links padrão
 * Auxiliar de awMention
 */
var awMentionUILinks__o=null;
function awMentionUILinks(options){
    //Parâmetros json opts da função awMention(): 
    //      field (jquery)
    //      key 
    //      val 
    //      cursor:{ps,pe,p}            - posição do cursor, valores: ps inicial, pe final, p atual
    //      offset:{left,top,height} 
    //      field_html (boolean)        - coordenadas em pixels a partir da posição do cursor
    //      iframe
    //      box_list
    //      template_text
    //      template_html
    //Métodos:
    //      onselect(item,data)         - executa o onselect iterno desta função (+ info no exemplo forms07...)
    
    if(awMentionUILinks__o)return;//para evitar que seja carregado + de 1x
    
    const _selTemplate=function(type,data){//type=html|text
        let r, n=options['template_'+type];
        if(type=='html' && n===false)n=options['template_text'];
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
    
    
    const _fOnSelect=function(item,data){
            //console.log('clicked',data,opt)
            if(!options)return;
            
            //substitui o texto pela string retornada
            let f = options.field;
            let v = options.field_html ? f.text() : f.val();
            let p = options.cursor;
            let rpl;
            obj.trigger('close');
            
            if(options.field_html){
                    //**** para div contentEditable *****
                    //lógica:   calcula a posição do node que contém o texto de options.key que iniciou todo este processo
                    //          depois separa este node e calcula a posição da string options.key dentro dele
                    //          depois seleciona somente esta string
                    
                    rpl = _selTemplate('html',data);
                    
                    let _getAllNodes=function(nodes){
                        nodes.childNodes.forEach(function(node){
                            if(node.hasChildNodes()){
                                _getAllNodes(node);
                            }else{
                                allnodes.push(node);
                                let s=node.textContent;//let s=node.innerHTML??node.nodeValue??'';
                                n+=s.length;
                                if(x==0 && n>=p.ps){//o node está aqui
                                    selnode=i;
                                    x=n-s.length;
                                    return false;
                                }
                                i++;
                            }
                        });
                    };
                    
                    let i=0;
                    let x=0;
                    let n=0;
                    let allnodes=[];
                    let selnode=-1;
                    _getAllNodes(f.get(0));
                    //console.log(x,p.ps,allnodes,selnode);return
                    //console.log(allnodes,selnode);return;
                    if(selnode<0)return;//não achou o node
                    
                    //altera a string options.key dentro do node selecionado
                    let random='{'+Math.random().toString(36).substring(6)+'}';
                    x = p.ps>x ? p.ps-x : x-options.key.length;//posição incial dentro do node
                    n = allnodes[selnode].textContent;
                    
                    //console.log(n,rpl, p.ps);
                    //console.log(n.substring(0,x),'|',x,'|',n.substring(x+options.key.length));
                    //return;
                    
                    v = n.substring(0,x) + rpl + n.substring(x+options.key.length);
                    allnodes[selnode].textContent = random;//update node
                    
                    //troca a string 'random' por 'v'
                    f.html( f.html().replace(random,v) );
                    
                    //converte a var rpl que pode estar em html no formato de texto (para setar a posição correta do cursor)
                    let tmp = document.createElement('DIV');
                    tmp.innerHTML = rpl;
                    rpl = tmp.textContent || tmp.innerText || '';
                    
                    f.focus().caret('pos', p.ps+rpl.length, {iframe: options.iframe});//seta o cursor no final da string substituída
                    //console.log(allnodes,selnode,allnodes[selnode]);return
                    
            }else{
                    //***** para input, textarea *****
                    rpl = _selTemplate('text',data);
                    v = v.substring(0,p.ps) + rpl + v.substring(p.ps+options.key.length);
                    let x=p.ps + rpl.length;
                    f.val(v);
                    f.focus();
                    f[0].setSelectionRange(x,x);
                    f.caret('pos', p.ps+rpl.length, {iframe: options.iframe});//seta o cursor no final da string substituída
            }
            
            return {
                cursor:{ps:p.ps, pe:p.ps+rpl.length},
                key:rpl,
                offset:options.offset
            };
    };
    
    this.onselect=function(item,data){ return _fOnSelect(item,data); }
    
    //para este caso, é esperado o retorno (da rota /links/search) no formato: array[json,...]
    //  para cabeçalhos {title,head:true,disable:true}
    //  para resultados {title,code,url}    //o texto em 'code' é que será inserido na página
    
    
    let box_list_opt = $.extend(true,{
        search_onshow:true,
        title:'Pesquisar Links',
        ajax: {
            url: admin_vars.url_app + '/links/search'
        },
        iframe:options.iframe,
        onselect:function(item,data){
            let r = _fOnSelect(item,data);
            options.onselect.call(null,r,item,data);
            return r;
        },
        template:function(data){
            if(data.head){//quer dizer tem sub dados, portanto este é um grupo
                return '<strong>'+ data.title +'</strong>';
            }else{
                return '<span>'+ data.title +'</span> <small style="color:#ccc;margin-left:10px">'+ data.code +'</small>';
            }
        },
    },options.box_list);
    
    const obj = awBoxList(box_list_opt).on('close',function(e,opt2){
        awMentionUILinks__o=null;
        if(opt2 && opt2.isCloseEsc){//foca o cursor novamente no campo
            options.field.focus().caret('pos', options.cursor.pe, {iframe: options.iframe});
        }
    });
    
    awMentionUILinks__o=obj;
    
    const p=options.offset;
    if(p)obj.trigger('show',{ pos:[p.left, p.top + p.height] });
    
};




//Retorna a palavra em que se encontra a posição do cursor
//@param str_compare - se definido irá retornar apenas se o texto for igual a deste parâmetro, caso contrário irá retornar o texto separado por espaços
function getInputWordPosition(input,pos,str_compare){
    var text;
    if(input.prop('nodeName')=='DIV' || input.attr('contentEditable')!=undefined){
        text = input.text();
    }else{
        text = input.val();
    };
    var f=function(text,chr1,chr2){
        text=text.replace(/\s/g,' ');
        const start_index = pos;//input.selectionStart;
        const end_index = pos;//input.selectionEnd;
        const previous_space_index = text ? text.lastIndexOf(chr1, start_index - 1 ) : -1;
        const next_space_index = text ? text.indexOf(chr2, end_index ) : -1;
        const begin = previous_space_index < 0 ? 0 : previous_space_index + 1;
        const end = next_space_index < 0 ? text.length : next_space_index;
        const between_spaces = text.substring( begin, end );
        //console.log(pos,{text:between_spaces,ps:begin,pe:end},text)
        return {text:between_spaces,ps:begin,pe:end};
    };
    
    if(str_compare){
        var chrs=[' ','"',"'",'>','<','/','\\',';',',','|'];//caracteres que podem existir a esquerda de str_compare
        var i,n;
        for(i in chrs){
            n=f(text,chrs[i],'');//a direita será sempre vazio
            if(n.text.toLowerCase()==str_compare.toLowerCase()){
                return n;
            }
        };
        return {text:'',ps:0,pe:0};
    }else{
        return f(text,' ','');//a direita será sempre vazio
    }
};
