@php
$postAttach = $post->getFiles('attach');
if(!$postAttach)return;


if(!isset($attr))$attr=[];
if(!is_array($attr))$attr=[$attr];

//força o download
    if($force??false)$attr['download']='';

$attr['class']='strong '.($class??'');

//title
$title = (!isset($title) || $title===true)?'Arquivos':false;

//monta o html
    echo '<div class="post-attach">';
        if($title)echo '<h4>'.$title.'</h4>';
        echo '<ul>';
        foreach($postAttach as $file){
            $a = $file->htmlFile([
                'attr'=>$attr,
                'icon'=>isset($icon) ? $icon : '',
            ]);
            echo '<li>'. $a .'</li>';
        }
        echo '</ul>';
    echo '</div>';


@endphp