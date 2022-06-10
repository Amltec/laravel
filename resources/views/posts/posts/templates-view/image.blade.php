@php

$postImages = $post->getFiles('image');
if(!$postImages)return;

//colunas
    $cols=$cols??5;
    if($cols<1)$cols=1;
    if($cols>5)$cols=5;
    if(count($postImages)<$cols)$cols=count($postImages);
    
    $cols_cls=['1'=>'12','2'=>'6','3'=>'4','4'=>'3','5'=>'2x4','6'=>'2'];
    
//miniaturas
    $thumbnail = $thumbnail??'auto';
    if($thumbnail=='auto'){
        $thumbnail=['1'=>'large','2'=>'large','3'=>'medium','4'=>'medium','5'=>'small','6'=>'small'][$cols];
        if($thumbnail=='large' && isMobile())$thumbnail='medium';
    }


//monta o html

    Form::loadScript('photoswipe','',true);//carrega no final da página
    
    echo '<div class="post-image aw-gallery" aw-gallery="on" id="aw-gallery-'. $post->id .'">';
        echo '<div class="row equal-height">';
        foreach($postImages as $img){
            echo '<div class="col-sm-'. $cols_cls[$cols] .' margin-bottom">', 
                    $img->htmlImg([
                        'thumbnail'=>'medium',
                    ]), 
                '</div>';
        }
        echo '</div>';
    echo '</div>';



@endphp