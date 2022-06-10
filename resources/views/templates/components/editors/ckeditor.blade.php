@php
if(empty($attr))$attr='';
$editor_opt=[];
if(!isset($height))$height=300;
if(!isset($toolbar_fixed))$toolbar_fixed=false;

$template=$template??null;

if($template){
    $editor_opt['template']=$template;
    if(strpos($template,'short')!==false)$height=100;
}
if($mention??false){
    $editor_opt['mention']=$mention;
}



$editor_opt['height']=$height;
if(isset($filemanager)){
    if(is_array($filemanager)){
        $editor_opt['filemanager']=$filemanager;
    }else{
        //abre nas configurações padrões
        $editor_opt['filemanager']=$filemanager===false?false:[];
    }
}

$extraPlugins=[];

if($auto_height??false){
    unset($editor_opt['height']);
    $extraPlugins[]='autogrow';
    $editor_opt['autoGrow_onStartup']=true;
    $editor_opt['autoGrow_minHeight'] = $height;
    if(is_int($auto_height))$editor_opt['autoGrow_maxHeight'] = $auto_height;
}

if($toolbar_fixed)$extraPlugins[]='fixed';

if($extraPlugins)$editor_opt['extraPlugins']=join(',',$extraPlugins);

$editor_opt=empty($editor_opt)?'':'data-editor-opt=\''.json_encode($editor_opt)."'";


Form::loadScript('ckeditor');
if($mention??false)Form::loadScript('mentionjs');

static $load_js=false;

@endphp
<div class="form-group form-group-{{$name}} {{$class_group ?? ''}} editor-{{$template}}" id='form-group-{{$name}}'>
    @if(!empty($label))
    <label {!! !empty($id) ? 'for="'.$id.'"':'' !!} class="control-label {{$class_label ?? ''}}">{!!$label!!}</label>
    @endif

    <div class="control-div {{$class_div ?? ''}}" >
        <textarea data-type="editor" data-plugin-js="ckeditor" data-template="{{$template}}" class="form-control {{$class_field ?? ''}}" id="{{$id ?? $name ?? "ckeditor1"}}" {!!$editor_opt!!} name="{{$name}}" data-label="{{$data_label??$label??''}}" {!!$attr!!} >{!! data_get($autodata??null,$name) ?? $value ?? Form::getValueAttribute($name) !!}</textarea>
        @if(!empty($info_html))'<div class="control-html">'. $info_html .'</div>'@endif
        <span class="help-block"></span>
    </div>
</div>

@if($toolbar_fixed)
<script>
CKEDITOR.plugins.add('fixed', {
    init: function (editor) {
        var toolbar_obj1;
        window.addEventListener('scroll', function () {
            const getOffset = (element, horizontal = false) => {
                if (!element) return 0;
                return getOffset(element.offsetParent, horizontal) + (horizontal ? element.offsetLeft : element.offsetTop);
            }
            const dashboard_bar_h=70;

            var toolbar = document.getElementsByClassName('cke_top').item(0);
            var editor = document.getElementsByClassName('cke').item(0);
            var inner = document.getElementsByClassName('cke_inner').item(0);
            if(!toolbar_obj1)toolbar_obj1=$('<span style="position:fixed;height:40px;background:#ecf0f5;box-shadow:-10px 0 0 #ecf0f5,10px 0 0 #ecf0f5;border-bottom:1px solid rgb(209, 209, 209);z-index:9;margin:-40px 0 0 0px;"></span>').prependTo(toolbar);
            toolbar_obj1.css('display','none');

            var scrollvalue = document.documentElement.scrollTop > document.body.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop;

            toolbar.style.width = editor.clientWidth + "px";
            toolbar.style.boxSizing = "border-box";

            if (getOffset(editor) <= scrollvalue + dashboard_bar_h) {
                toolbar.style.position = "fixed";
                toolbar.style.top = dashboard_bar_h+"px";console
                inner.style.paddingTop = toolbar.offsetHeight + "px";
                toolbar_obj1.css({display:'block',top:dashboard_bar_h,width:editor.clientWidth,left:$(toolbar).position().left});
            }

            if (getOffset(editor)-dashboard_bar_h > scrollvalue && (getOffset(editor) + editor.offsetHeight) >= (scrollvalue + toolbar.offsetHeight)) {
                toolbar.style.position = "relative";
                toolbar.style.top = "auto";
                inner.style.paddingTop = "0px";
            }

            const minContentHeight = (toolbar.offsetHeight * 2);

            if ((getOffset(editor) + editor.offsetHeight) < (scrollvalue + minContentHeight + dashboard_bar_h)) {
                toolbar.style.position = "absolute";
                toolbar.style.top = "calc(100% - " + (minContentHeight) + "px)";
                inner.style.position = "relative";
            }
            
            if ((getOffset(editor) + editor.offsetHeight) < (scrollvalue + minContentHeight)) {
                toolbar_obj1.css('display','none');
            }
        }, false);
    }
});
</script>
@endif

@if(!$load_js)
@php
$load_js=true;
@endphp
<style>
.editor-border.control-div{background:#fff;border:1px solid rgb(209, 209, 209);padding:0px 15px;}
.cke_editable_inline{outline:none !important;}
</style>
@endif
