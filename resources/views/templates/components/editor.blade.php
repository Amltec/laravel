@php

//*** nome do plugin padrão ***
$plugin_default='ckeditor';


@endphp
@include('templates.components.editors.'. ( $plugin ?? $plugin_default ) )