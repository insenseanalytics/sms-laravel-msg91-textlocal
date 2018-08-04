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

    public function index(Request $request)
    {
        $defaultDriver = SMS::driver();
        $textlocalDriver = SMS::driver('textlocal');
        $isTransactional = true;
        $paras = "09717415666";
        $amit = "8373948588";
        $to = [$paras, $amit];
        $msg91 = "Promotional MSG Testing: This is testing message from msg91";
        $msgTextlocal = "Promotional MSG Testing: This is testing message from textLocal";
        $msg91Tran = "Transactional MSG Testing: This is testing message from msg91";
        $msgTextlocalTran = "Transactional MSG Testing: This is testing message from textLocal";
        if ($isTransactional) {
            $report= $defaultDriver->sendSms($to, $msg91Tran, true);
        } else {
            $report= $defaultDriver->sendSms($to, $msg91);
            //$report1= $textlocalDriver->sendSms($to, $msgTextlocal);
        }
        
        
        //$report1= $textlocalDriver->sendSms($to, $msgTextlocal);
        return [$report];
    }
}
