@php

if(!$file['is_image']){
    echo 'Miniaturas não disponíveis';
    return;
}

echo '<div class="bg"></div>';
$thumbnails=$file['file_thumbnail_all'];
foreach($thumbnails as $th){
    echo '<img src="'. $th[0] .'" />';
}

@endphp

<style>
html,body{margin:0;}
.bg{position:fixed;height:100%;width:100%;top:0;left:0;z-index:-1;
background: rgba(49,62,63,1);
background: -moz-radial-gradient(center, ellipse cover, rgba(49,62,63,1) 0%, rgba(49,62,63,1) 16%, rgba(0,0,0,1) 100%);
background: -webkit-gradient(radial, center center, 0px, center center, 100%, color-stop(0%, rgba(49,62,63,1)), color-stop(16%, rgba(49,62,63,1)), color-stop(100%, rgba(0,0,0,1)));
background: -webkit-radial-gradient(center, ellipse cover, rgba(49,62,63,1) 0%, rgba(49,62,63,1) 16%, rgba(0,0,0,1) 100%);
background: -o-radial-gradient(center, ellipse cover, rgba(49,62,63,1) 0%, rgba(49,62,63,1) 16%, rgba(0,0,0,1) 100%);
background: -ms-radial-gradient(center, ellipse cover, rgba(49,62,63,1) 0%, rgba(49,62,63,1) 16%, rgba(0,0,0,1) 100%);
background: radial-gradient(ellipse at center, rgba(49,62,63,1) 0%, rgba(49,62,63,1) 16%, rgba(0,0,0,1) 100%);
filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#313e3f', endColorstr='#000000', GradientType=1 );
}
img{display:block;margin:15px auto;border:1px solid rgba(255,255,255,0.5);}
</style>
