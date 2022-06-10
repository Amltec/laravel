<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if($request->wantsJson()){
            if(!$request->input('token') && !$request->header('x-csrf-token'))//token não encontrado
                return response()->json(['success'=>false,'msg'=>'Token inválido. Recarregue a página.','reload'=>true]);
            
            if($exception instanceof \Illuminate\Http\Exceptions\PostTooLargeException)
                return response()->json(['success'=>false,'msg'=>'Erro ao enviar: arquivo maior que '.ini_get('post_max_size')]);
        }
        //dd($request->all(),$exception);
        /*//analisando...
        dd('ok|'.csrf_token(),'err|'.$request->input('_token'));
        if($request->wantsJson())
            if ($exception instanceof \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException)
                return response()->json(['success'=>false,'msg'=>'Token inválido']);
        */  
            
        return parent::render($request, $exception);
    }
    
    

}
