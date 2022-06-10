<?php

namespace App\Http\Middleware;

/**
 * Customiza a proteção de tentativas de logins por força bruta
 */

class ThrottleRequestsApp extends \Illuminate\Routing\Middleware\ThrottleRequests{
    
}