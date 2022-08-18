<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VoipLog
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $parameters = $request->all();
        // unset some of those we don't use at all.
        unset($parameters['ToState'], $parameters['CallerCountry'], $parameters['CallerState'], $parameters['ToZip'], $parameters['ToCountry']);
        unset($parameters['CallerZip'], $parameters['CalledZip'], $parameters['ApiVersion'], $parameters['CalledCity'], $parameters['CalledCountry']);
        unset($parameters['CallerCity'], $parameters['ToCity'], $parameters['FromCountry'], $parameters['FromCity'], $parameters['CalledState']);
        unset($parameters['FromZip'], $parameters['FromState']);

        Log::debug($request->path(), [
            'request' => $parameters,
            'request_headers' => $request->headers->all(),
        ]);
        // do your thing
        return $next($request);
    }

    /**
     * Log the response we gave them
     *
     * @param $request
     * @param $response
     * @return null
     * @throws Exception
     */
    public function terminate($request, $response)
    {
        Log::debug($request->path(), ['response' => $response->content()]);
    }
}
