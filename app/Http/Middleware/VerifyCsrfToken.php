<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //
        //rota de acesso do robô (as validações da rota estão dentro deste método)
        'wsrobot/data',
        'wsfilesextract/data',
    ];
    
    
}
