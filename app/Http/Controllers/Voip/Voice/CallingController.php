<?php

namespace App\Http\Controllers\Voip\Voice;

use App\Helpers\VoipHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;

class CallingController extends Controller
{
    public function answer(Request $request, string $call_sid = null)
    {
        $response = new VoiceResponse();
        $response->say('Hello World');
        $response->enqueue(null, [
            'workflowSid' => config('services.twilio.workflow_sid'),
        ])->task(json_encode([
            'ring_group_id' => 315,
            'area_code' => VoipHelper::getAreaCode($request->input('From')),
        ]));

        return $response;
    }

    public function status(Request $request, string $call_sid = null)
    {
        //
    }

    public function fallback(Request $request, string $call_sid = null)
    {
        //
    }
}
