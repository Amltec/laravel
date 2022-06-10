<div class="alert alert-{{ ($type===true?'success':  ($type===false?'danger': (empty($type)?'danger':$type) )  ) }} alert-dismissible {{$class??'alert-form'}}" role="alert">
    @if((isset($close) && $close===true) || !isset($close))
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    @endif
    <p class="alert-msg">{!!$msg??'Mensagem de erro não definida'!!}</p>
    @if(!empty($content))
    <a href="#" class="small alert-link-content" onclick="$(this).next().fadeToggle('fast');return false;" >mais</a>
    <div class="hiddenx alert-content" >{{$content}}</div>
    @endif
</div>