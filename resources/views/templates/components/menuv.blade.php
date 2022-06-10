@include('templates.components.tree',[
    'class_menu'=>'ui-menuv-sty1',
    'icon_def'=>'fa-folder-o',
    'sub_icon_def'=>'fa-angle-right',
    'pos_caret'=>'right',
    'pos_caret_def'=>'right',
    'sub'=>$sub
])


@php
static $once=false;
if(!$once){
$once=true;
@endphp
<style>
    .ui-menuv-sty1{}
    .ui-menuv-sty1 > li > .ui-tree-item > a:hover{background:#3c8dbc;color:#fff;}
    .ui-menuv-sty1 > li > .ui-tree-menu{background:#00000008;}
    .ui-menuv-sty1 a{color:#333;}
    .ui-tree-item[aria-expanded=true] > a{}
    .ui-menuv-sty1 .ui-tree-menu a:hover{background:none;color:#3c8dbc;}
    .ui-menuv-sty1 > li{border-bottom:1px solid #ccc;}
    .ui-tree-header{background:#00000033;color:#fff;padding:3px 15px;}
</style>
@php
} //.endif
@endphp