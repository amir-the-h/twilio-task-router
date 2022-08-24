<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Twilio\Jwt\ClientToken;
use Twilio\Jwt\TaskRouter\WorkerCapability;

class WorkersController extends Controller
{
    /**
     * Login an agent by worker SID
     * 
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $worker_capabilities = new WorkerCapability(
            config('services.twilio.account_sid'),
            config('services.twilio.auth_token'),
            config('services.twilio.workspace_sid'),
            $request->input('workerSid')
        );
        $worker_capabilities->allowFetchSubresources();
        $worker_capabilities->allowActivityUpdates();
        $worker_capabilities->allowReservationUpdates();

        // generate token for 1 day
        $worker_token = $worker_capabilities->generateToken(60 * 60 * 24);

        // get the token
        $capability = new ClientToken(config('services.twilio.account_sid'), config('services.twilio.auth_token'));
        // allow incoming
        $capability->allowClientIncoming($request->get('workerName'));
        // allow outgoing
        $capability->allowClientOutgoing(config('services.twilio.twiml_app_sid'));

        // make the token
        $webrtc_token = $capability->generateToken(60 * 60 * 24);

        return response()->json(compact('worker_token', 'webrtc_token'));
    }
}
