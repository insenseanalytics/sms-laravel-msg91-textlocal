<?php

namespace Insense\LaravelSMS\Http\Controllers;

use Insense\LaravelSMS\Http\Controllers\Controller;
use SMS;
use Illuminate\Http\Request;

class SMSController extends Controller
{
    public function deliveryReceipt($driver, Request $request)
    {
        SMS::driver($driver)->deliverReport($request);
    }
}
