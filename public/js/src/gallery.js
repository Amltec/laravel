/*** Funções utilizados para a galeria de imagens ***/
function awGalleryImageInit(){
    var r='<div class="pswp" tabindex="-1" role="dialog" aria-hidden="true">'+
                '<div class="pswp__bg"></div>'+
                '<div class="pswp__scroll-wrap">'+
                    '<div class="pswp__container"><div class="pswp__item"></div><div class="pswp__item"></div><div class="pswp__item"></div></div>'+
                    '<div class="pswp__ui pswp__ui--hidden">'+
                            '<div class="pswp__top-bar">'+
                                    '<div class="pswp__counter"></div>'+
                                    '<button class="pswp__button pswp__button--close" title="Fechar (Esc)"></button>'+
                                    '<button class="pswp__button pswp__button--share" title="Compartilhar ou Download"></button>'+
                                    '<button class="pswp__button pswp__button--fs" title="Tela cheia"></button>'+
                                    '<button class="pswp__button pswp__button--zoom" title="Zoom +/-"></button>'+
                                    '<div class="pswp__preloader"><div class="pswp__preloader__icn"><div class="pswp__preloader__cut"><div class="pswp__preloader__donut"></div></div></div></div>'+
                            '</div>'+
                            '<div class="pswp__share-modal pswp__share-modal--hidden pswp__single-tap"><div class="pswp__share-tooltip"></div></div>'+
                            '<button class="pswp__button pswp__button--arrow--left" title="Anterior"></button>'+
                            '<button class="pswp__button pswp__button--arrow--right" title="Próximo"></button>'+
                            '<div class="pswp__caption"><div class="pswp__caption__center"></div></div>'+
                    '</div>'+
                '</div>'+
        '</div>';
    var pswpElement = $(r).appendTo(document.body);
    var options = {
            history: true,
            focus: false,
            showAnimationDuration: 100,
            //hideAnimationDuration: 0,
            showHideOpacity:true,
            closeOnScroll:false,
            //closeOnVerticalDrag:false,	//alterado mais abaixo no evento on click
            //pinchToClose:false,
            /*getThumbBoundsFn: function(index){
                    if(items[index].el){
                            var thumbnail = items[index].el.getElementsByTagName('img')[0];
                            if(thumbnail){
                                    var pageYScroll = window.pageYOffset || document.documentElement.scrollTop;
                                    var rect = thumbnail.getBoundingClientRect(); 
                                    return {x:rect.left, y:rect.top + pageYScroll, w:rect.width};
                            };
                    };
            }*/
    };
    $('[aw-gallery=on]').each(function(){
        var items = [];
        $(this).find('a').each(function(i){
            var img=$(this).data('i',i);
            items.push({
                src:img.attr('href'),
                w:parseInt(img.attr('data-width')),
                h:parseInt(img.attr('data-height'))
            });
        }).on('click',function(e){
            e.preventDefault();
            var jdDoc=$(document.body).addClass('pswp__noscroll');
            pswpElement.css({zIndex:2999});
            options.index=$(this).data('i');
            
            let gallery = new PhotoSwipe(pswpElement[0], PhotoSwipeUI_Default, items, options);
            gallery.init();
            gallery.listen('close',function(){$(document.body).removeClass('pswp__noscroll');});//disable page scroll
        });
   });
};
fnExists(['PhotoSwipe','PhotoSwipeUI_Default'],awGalleryImageInit);
