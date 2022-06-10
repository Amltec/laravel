@php
if(isset($title) && !isset($header)){
    $header='';
}else if(!isset($title) && !isset($header)){
    $header=false;
}
if($header===true)$header='';

echo'<div class="box box-'. ($color??'primary') .' '.  ((isset($is_border) && $is_border===true) || !isset($is_border)?'':'box-widget') .  (isset($is_bg) && $is_bg===true?'box-solid ':'') . ($class??'') .'" '. (isset($id)?'id="'.$id.'"':'') .' >';
        
         if(isset($header) && $header!==false){ 
            echo'<div class="box-header with-border">'.
                    '<h3 class="box-title">'. ($title ?? 'Título') .'</h3>'.
                    '<div class="box-tools pull-right">';
                        echo callstr($header);
                        echo(isset($is_collapse) && $is_collapse ? '<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>' : '').
                            (isset($is_close) && $is_close ? '<button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-remove"></i></button>' : '').
                    '</div>'.
                '</div>';
        }
        
        echo '<div class="box-body '. (isset($is_padding) && $is_padding==false?'no-padding':'') .'">';
            if(isset($content))callstr($content);
        echo '</div>';
         
        if(isset($footer)){ 
            echo'<div class="box-footer">';
                if(is_array($footer)){
                    if(isset($footer['bt'])){
                        echo '<div class="pull-left">';
                        if($footer['bt']===true)$footer['bt']=['title'=> 'Confirmar','color'=>'primary'];
                        echo view('templates.components.button',$footer['bt']);
                        echo '</div>';
                    }
                    if(isset($footer['bt2'])){
                        echo '<div class="pull-right">';
                            if($footer['bt2']===true)$footer['bt2']=['title'=> 'Cancelar'];
                            echo view('templates.components.button',$footer['bt2']);
                        echo '</div>';
                    }
                }else{
                    echo callstr($footer);
                }
            echo '</div>';
        }
        
echo'</div>';
@endphp