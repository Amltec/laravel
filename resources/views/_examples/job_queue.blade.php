Teste de adição de classes na fila de trabalhos.<br>
Veja mais detalhes no código fonte.<br>

@php
use App\Services\JobService;
    $class  = \App\....;
    $params=[];
    JobService::send($class,$params);
    //JobService::send($class,$params)->delay( now()->addMinutes($q) );


        
@endphp