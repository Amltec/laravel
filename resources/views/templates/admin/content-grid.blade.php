@php
$grid_top = View::getSection('grid_top');
$grid_left = View::getSection('grid_left');
$grid_center = View::getSection('grid_center');
$grid_right = View::getSection('grid_right');
$grid_bottom = View::getSection('grid_bottom');

$is_navbar = $dashboard['navbar'];

$grid_border = $grid_border??true;

$grid_sizes=array_merge([
    'top'=>50,
    'left'=>300,
    'right'=>300,
    'bottom'=>30,
],($grid_sizes??[]));

if(!$grid_top)$grid_sizes['top']=0;
if(!$grid_left)$grid_sizes['left']=0;
if(!$grid_right)$grid_sizes['right']=0;
if(!$grid_bottom)$grid_sizes['bottom']=0;


if(!$grid_center){
    //carrega o conteúdo padrão
    
    $n = View::getSection('title');
    if($n){
        $grid_center.='<section class="content-header"><h1>'. $n .'<small>'. View::getSection('description') .'</small></h1></section>';
    }
    
    $n = View::getSection('content-view');
    if($n){
        $grid_center.='<section class="text-left content">'. $n .'</section>';
    }
}


@endphp

<div class="content-wrapper" id="content-wrapper">
    <section class="container-fluid">
        <div class="awgrid-main">
            @php
            if($grid_top){
                echo '<div class="awgrid-top awgrid-content">'. $grid_top .'</div>';
            }
            if($grid_left){
                echo '<div class="awgrid-left awgrid-content scrollmin">'. $grid_left .'</div>';
            }
            if($grid_center){
                echo '<div class="awgrid-center awgrid-content scrollmin">'. $grid_center .'</div>';
            }
            if($grid_right){
                echo '<div class="awgrid-right awgrid-content scrollmin">'. $grid_right .'</div>';
            }
            if($grid_bottom){
                echo '<div class="awgrid-bottom awgrid-content">'. $grid_bottom .'</div>';
            }
            @endphp
        </div>
    </section>
</div>
<style>
.content:before,.content:after,.awgrid:before,.awgrid:after{display:none;}
.awgrid-main{height:calc(100vh - {{$is_navbar?'50px':'0px'}} - {{$grid_sizes['top'] - $grid_sizes['bottom']}}px + 20px);margin:0 -15px;}
.awgrid-top{height:{{$grid_sizes['top']}}px;}
.awgrid-bottom{clear:left;height:{{$grid_sizes['bottom']}}px;}
.awgrid-left,.awgrid-center,.awgrid-right{float:left;height:calc(100% - {{$grid_sizes['top'] + $grid_sizes['bottom']}}px);overflow:auto;}
.awgrid-left{width:{{$grid_sizes['left']}}px;}
.awgrid-right{width:{{$grid_sizes['right']}}px;}
.awgrid-center{width:calc(100% - {{ $grid_left && $grid_right ? ($grid_sizes['left']+$grid_sizes['right']).'px' : ($grid_left || $grid_right ? ($grid_left ? $grid_sizes['left'] : $grid_sizes['right']).'px' : '0px') }});}
.awgrid-content > div{height:100%;margin-bottom:0 !important;}

@if($grid_border)
/*border*/
.awgrid-top{border-bottom:1px solid #e2e2e2;}
.awgrid-left{border-right:1px solid #e2e2e2;}
.awgrid-right{border-left:1px solid #e2e2e2;}
.awgrid-bottom{border-top:1px solid #e2e2e2;}
@endif

@media screen and (max-width: 1000px){
    .wrapper{overflow:visible !important;}
    .awgrid-top,.awgrid-left,.awgrid-center,.awgrid-right,.awgrid-bottom{float:none;width:auto;height:auto;overflow:visible;}
}
</style>
