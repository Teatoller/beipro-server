<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MpesaTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class MpesaController extends Controller
{
    /**
     * Generate Mpesa password.
     *
     * @return \Illuminate\Http\Response
     */
    public function lipaNaMpesaPassword()
    {
        $timestamp = Carbon::rawParse('now')->format('YmdHms');
        $passKey = config('app.pass_key');
        $businessShortCode = config('app.short_Code');
        $mpesaPassword = base64_encode($businessShortCode . $passKey . $timestamp);

        return $mpesaPassword;

    }

    /**
     * Generate Mpesa access token.
     *
     * @return \Illuminate\Http\Response
     */
    public function generateAccessToken()
    {
        $consumer_key = config('app.consumer_key');
        $consumer_secret = config('app.consumer_secret');
        $credentials = base64_encode($consumer_key . ":" . $consumer_secret);
        $url = config('app.saf_access_token_url');

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Basic " . $credentials, "Content-Type:application/json"));
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $curl_response = curl_exec($curl);
        $access_token = json_decode($curl_response);
        curl_close($curl);

        return $access_token->access_token;

    }

    /**
     * Mpesa stk push and callback.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function stkPush(Request $request)
    {
        $request->validate([
            'phoneNumber' => 'required|string|min:10|max:10',
            'amount' => 'required|numeric|between:0, 9999.99',
        ]);

        $phone = $request->phoneNumber;
        $amount = $request->amount;
        $formatedPhone = substr($phone, 1); //722
        $code = "254";
        $phoneNumber = $code . $formatedPhone; //254722....

        $url = config('app.stk_url');
        $callback_url = config('app.callback_url');
        $shortCode = config('app.short_Code');

        $curl_post_data = [
            'BusinessShortCode' => $shortCode,
            'Password' => $this->lipaNaMpesaPassword(),
            'Timestamp' => Carbon::rawParse('now')->format('YmdHms'),
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $phoneNumber,
            'PartyB' => $shortCode,
            'PhoneNumber' => $phoneNumber,
            'CallBackURL' => $callback_url,
            'AccountReference' => "Beipro",
            'TransactionDesc' => "lipa Na M-PESA",
        ];

        $data_string = json_encode($curl_post_data);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Bearer ' . $this->generateAccessToken()));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

        if ($curl_response = curl_exec($curl)) {
            return $curl_response;
        } else {
            return "Stk-push failed!";
        }

    }

    /**
     * Store a newly created Mpesa response in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function MpesaApiResponse(Request $request)
    {
        $response = json_decode($request->getContent());
        $user = Auth::user();

        $trn = new MpesaTransaction([
            'TransactionType' => $response->TransactionType,
            'TransID' => $response->TransID,
            'TransTime' => $response->TransTime,
            'TransAmount' => $response->TransAmount,
            'BusinessShortCode' => $response->BusinessShortCode,
            'BillRefNumber' => $response->BillRefNumber,
            'InvoiceNumber' => $response->InvoiceNumber,
            'OrgAccountBalance' => $response->OrgAccountBalance,
            'ThirdPartyTransID' => $response->ThirdPartyTransID,
            'MSISDN' => $response->MSISDN,
            'FirstName' => $response->FirstName,
            'MiddleName' => $response->MiddleName,
            'LastName' => $response->LastName,
        ]);
        $trn->user_id = $user->id;
        $trn->email = $user->email;
        $trn->response = json_encode($response);

        $trn->save();

        return response()->json([
            "Success" => "Mpesa transaction has been added",
        ], 201);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function durationConfirmation()
    {
        if (auth()->check()) {
            $user = auth()->user();
            $id = $user->id;

            if (MpesaTransaction::where('user_id', $id)->exists()) {
                $usertrxns = MpesaTransaction::where('user_id', $id)->get();

                $remainingDurationArr = $usertrxns->map(function ($trxn, $key) {
                    $transAmount = $trxn['TransAmount'];
                    // $inputTime = $trxn['TransTime']; //  "20170727104247"
                    // $date = strtotime($inputTime);
                    // $transTime = date('Y-m-d H:i:s'); // 2015-12-19 10:10:54
                    // echo $transTime;

                    $transTime = $trxn['created_at'];

                    $dt = Carbon::now();

                    $dayAmount = 15;
                    $weekAmount = 65;
                    $monthAmount = 240;
                    $newDayAmount = 25;
                    $newWeekAmount = 100;
                    $newMonthAmount = 375;

                    $cumTimeRemaining = 0;

                    if (($transAmount == ($dayAmount || $newDayAmount)) && ($transTime <= ($trxn['created_at']->isEndOfDay()))) {
                        $subscriptionExpires = $trxn['created_at']->isEndOfDay();

                        $timeRemaining = $dt->diffInMinutes($subscriptionExpires);
                        $cumTimeRemaining += $timeRemaining;

                        return $cumTimeRemaining;
                    } elseif (($transAmount == ($weekAmount || $newWeekAmount)) && ($transTime <= ($trxn['created_at']->addHours(168)))) {
                        $subscriptionExpires = $trxn['created_at']->addHours(168);

                        $timeRemaining = $dt->diffInMinutes($subscriptionExpires);
                        $cumTimeRemaining += $timeRemaining;

                        return $cumTimeRemaining;
                    } elseif (($transAmount == ($monthAmount || $newMonthAmount)) && ($transTime <= ($trxn['created_at']->addHours(730)))) {
                        $subscriptionExpires = $trxn['created_at']->addHours(730);

                        $timeRemaining = $dt->diffInMinutes($subscriptionExpires);
                        $cumTimeRemaining += $timeRemaining;

                        return $cumTimeRemaining;
                    }
                });

                $totalTimeRemaining = $remainingDurationArr->sum();

                return response()->json([
                    'timeRemaining' => $totalTimeRemaining,
                    "message" => "Subscription valid",
                    "status" => "valid",
                ], 200);

            }

            return response()->json([
                'timeRemaining' => 0,
                "message" => "The subscription has expired",
                "status" => "invalid",
            ], 200);
        }
        return "Failed: User details not found";

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $clientpayments = MpesaTransaction::all();
        return response($clientpayments, 200);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
