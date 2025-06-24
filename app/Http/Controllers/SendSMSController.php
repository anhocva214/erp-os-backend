<?php

namespace App\Http\Controllers;

use Exception;
use Twilio\Rest\Client;
use App\Models\SmsConfig;
use App\Models\SMSPurchase;
use Illuminate\Http\Request;

class SendSMSController extends Controller
{

    public function sendSms(Request $request)
    {
        try {
            $data = SMSPurchase::first();
            if(!$data){
                return response()->json(['success' => false, 'message' => 'You have no SMS balance.'], 404);
            }
            if($data->purchaseTotal > $data->sendTotal){
                $this->validate($request, [
                    'phone' => 'required',
                ]);
    
                ////////////////////////////sms code///////////////////////////
                $to = $request->phone;
                $message = $request->message;
                    
                $token = env('BBSMS_TOKEN');
                $url = "http://api.greenweb.com.bd/api.php?json";
    
                    $data = array(
                        'to' => "$to",
                        'message' => "$message",
                        'token' => "$token"
                    );
                    
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                    curl_setopt($ch, CURLOPT_ENCODING, '');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $smsresult = curl_exec($ch);
                    curl_close($ch);

                    if($smsresult){
                        $data = SMSPurchase::first();
                        $data->sendTotal = $data->sendTotal + 1;
                        $data->save();
                        return response()->json(['success' => true], 200);
                    } else {
                        return response()->json(['success' => false], 400);
                    }
                
            }
            else{
                return response()->json(['success' => false, 'message' => 'You have no SMS balance.'], 400);
            }
            
        } catch (Exception $e) {
            echo $e->getMessage();
            return response()->json(['error' => "An error occurred while sending the SMS"], 500);
        }
    }
}
