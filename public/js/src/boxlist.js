/*
Funções do arquivo:
    awBoxListShow()
    awBoxList()
Requer:
    
*/

//Atrasa para executar a função awBoxList()
function awBoxListShow(opts,show_opt){
    setTimeout(function(){ awBoxList(opts).trigger("show",show_opt); },1);
};

/**
 * Janela de lista de resultados via ajax com pesquisa de conteúdo
 * Eventos: show, close
 * @return object container
 * Exemplo de resultado da lista (parâmetro ajax):
 *      [ [title=>...,link=>...], ... ] e quaisquer outros campos, pois serão personalizados pelo parâmetro opt.template
 *      
 *      campos adicionais considerados:
 *          disabled - se true desativa o click no item da lista
 *          class    - classes adicionais
 *          attr     - atributos adicionais
 */
function awBoxList(opts){
    var opt = $.extend(true,{
        title:'Título',       //se =false não exibe
        is_title:true,
        is_search:true,
        ajax: {
            url:    '',     //url carregamento ajax
            data:   {},     //dados adicionais informados ao ajax
            once:   false,  //se true carrega apenas uma única vez a lista em ajax
        },                     //url carregamento ajax
        search_onshow:false,   //carrega a lista ao exibir o plugin
        max_height:300,
        hide_onselect:false,
        notfound:'Não encontrado',
        onselect:function(){},  //ao clicar/selecionar um item. Parâmetros recebidos: (jquery) item clicked, (json) data loop form ajax
        template:function(){},  //template do item da lista. Parâmetros recebidos: (json) data loop form ajax //ex: function(data){ return '<strong>'+ data.title +'</strong>'; }
        iframe:null,            //object dom iframe
    },opts);
    if(!opt.title)opt.is_title=false;
    const this_id='awboxlist_'+Math.random().toString(36).substring(6)
    
    const base = $(
        '<div class="ui-box-list ui-box-popup box box-primary">'+
            (opt.is_title ? '<div class="box-header with-border"><h3 class="box-title">'+ opt.title +'</h3></div>' : '')+
            '<div class="box-body">'+
                (opt.is_search ? '<div class="ui-box-list-search"><input type="text" placeholder="Pesquisar" autocomplete="no"><span class="fa fa-search"></span></div>' : '')+
                '<div class="form-group scrollmin" style="max-height:'+ opt.max_height +'px;overflow:auto;margin:0 -10px -8px -10px;"></div>'+
            '</div>'+
        '</div>')
        .appendTo(document.body)
        .on('click',function(e){
            e.stopPropagation();
        });
    const body=base.find('.form-group:eq(0)')
        .on('click','.ui-box-list-item',function(){
            if(opt.onselect)opt.onselect.call(null,$(this),$.parseJSON($(this).attr('data-opt')));
            if(opt.hide_onselect)base.trigger('close');
        });

    var oItems=null;
    
    //search
    if(opt.is_search){
        let last_q='';
        var oSearch=base.find('.ui-box-list-search input').on({
            'focus':function(){
                oSearch.closest('.ui-box-list-search').addClass('focus');
            },
            'focusout':function(){
                oSearch.closest('.ui-box-list-search').removeClass('focus');
            },
            'keyup':function(e){
                const k=e.keyCode;
                //console.log(k)
                if($.inArray(k,[9,16,17,18,37,39])!==-1)return;//9 tab, 16 shift, 17 ctrl, 18 alt, 37 left, 39 right
                
                var active=oItems ? oItems.filter('.ui-box-list-li-active.item-ok:eq(0)') : $();
                
                if(k==13 && active.length>0){
                    active.find('>a').click();
                    return;
                };
                
                if(oItems && (k==38 || k==40)){//up,down
                    if(active.length==0)active=oItems.filter('.item-ok').first();
                    if(active.length==0)return;
                    active.removeClass('ui-box-list-li-active');
                    if(k==38){//up
                        active=active.prevAll('.item-ok:eq(0)');
                        if(active.length==0)active=oItems.filter('.item-ok').first();
                    }else{//down
                        active=active.nextAll('.item-ok:eq(0)');
                        if(active.length==0)active=oItems.filter('.item-ok').last();
                    };
                    active.addClass('ui-box-list-li-active');
                    
                    body.scrollTop(0);//set to top
                    body.scrollTop(active.position().top - body.height());
                    return;
                };
                
                var q=this.value;
                if(q==last_q)return;
                last_q=q;
                fSearchAjax(q);
            }
        });
        
        body.on('mouseenter','.ui-box-list-item',function(){
                if(oItems)oItems.removeClass('ui-box-list-li-active');
            })
            .on('click','.ui-box-list-item',function(){
                oSearch.focus();
            });
    };
    
    var ajax_is_load=false;
    const fSearchAjax=function(q){
        if(opt.ajax.once && ajax_is_load){
            oItems.show();
            if(q){
                body.find('li.item-not').remove();
                //pesquisa pelo texto do item
                var x=0;
                q=q.toLowerCase();
                oItems.each(function(){
                    var o=$(this);
                    if(o.text().toLowerCase().indexOf(q)==-1){o.hide();}else{x++;}
                });
                if(x==0)body.find('>ul').append(fSearchItem('not'));
            }
            _fAdjustPos();//ajusta a posição
        }else{
            
            let dd=opt.ajax.data;dd.q=$.trim(q);
            
            awAjax({
                url: opt.ajax.url,
                processData: true,
                data:dd,
                type:'GET',
                success: function(links){
                    ajax_is_load=true;
                    body.find('>*').remove();
                    oItems=null;
                    var r='';
                    if(links.length==0){
                        if(oSearch.val()!='')r=fSearchItem('not');
                    }else{
                        for(var i in links){
                            r+=fSearchItem('item',links[i]);
                        }
                    }
                    body.html('<ul class="nav nav-stacked no-margin padding-sm">'+ r +'</div>');
                    oItems = body.find('li');
                    _fAdjustPos();//ajusta a posição
                }
            });
        }
    };
        
    
    //@param type - valores:item,not
    //@param data - o mesmo do item da lista retornado do ajax
    //                      parâmetros adicionais em data considerados:
    //                              disabled    - se true desativa o click no item da lista
    //                              head        - se true indica que é um cabeçalho
    //                              class       - classes adicionais
    //                              attr        - atributos adicionais
    const fSearchItem=function(type,data){
        let r='';
        if(type=='not'){
            r= '<li class="item-not" style="cursor:default;"><a>'+ opt.notfound +'</a></li>';
        }else{
            let n=opt.template.call(null,data);
            if(n){
                let c = (data.head || data.disabled?'':'ui-box-list-item ') +
                        (data.head?'head ':'') +
                        (data.class??'');
                r= '<li class="item-'+ (data.head?'head':(data.head?'disabled':'ok')) +'"><a class="'+ c +'" data-opt=\''+ JSON.stringify(data) +'\' '+  (data.attr??'')  +'>'+ n +'</a></li>';
            }
        }
        return r;
    };
    
    
    //remove ao clicar no fundo da página
    if(!window._awBoxList__events){
        window._awBoxList__events=true;
        const _fCloseAll=function(is_esc){
            $(document.body).find('>.ui-box-list').trigger('close',{isCloseEsc:is_esc===true});
        };
        $(document.body)
            .on('click',_fCloseAll)
            .on('keydown',function(e){ 
                if(e.keyCode==27)_fCloseAll(true);//esc
            });
        $('iframe').contents().on('click',_fCloseAll);//ao clicar em qualquer frame
    };
    
    $(window).on('scroll.'+this_id,function(){ _fAdjustPos(); });
    
    if(opt.search_onshow)setTimeout(fSearchAjax,10);
    
    let _current_pos={};
    //ajusta a posição considerando a altura do elemente
    const _fAdjustPos=function(){
        const sT=$(window).scrollTop();
        const sL=$(window).scrollLeft();
        var l=_current_pos.left-sL;
        var t=_current_pos.top-sT;
        var h=base.height();
        var w=base.width();
        //verifica se olemento ficará escondido abaixo da tela na posição informada, e neste caso inverte exibindo para acima
        if(t + h > document.body.scrollHeight) t = t - h;
        if(l + w > document.body.scrollWidth) l = l - w;
        base.css({left:l,top:t});
    };
    
    //eventos
    base.on('show',function(e,opt2){ //json opt2 - pos=[x,y] || pos=$(objRef)
        if(!opt2)opt2={};
        base.show();
        
        if($.type(opt2.pos)=='object'){
            let o = opt2.pos instanceof jQuery !== false ? opt2.pos : $(opt2.pos);
            let p = o.offset();
            _current_pos={left:p.left,top:p.top + o.outerHeight()};
            
        }else{//array [x,y]
            if(opt2.pos)_current_pos={left:opt2.pos[0],top:opt2.pos[1]};
        };
        
        _fAdjustPos();
        if(opt.is_search)oSearch.focus();
    }).on('close',function(){
        base.remove();
        $(window).off('.'+this_id);
    });
    
    return base;
};
