<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;

class PaymentController extends Controller
{
  public function createPayment(Request $request)
  {
      try {
          $request->validate([
              'amount' => 'required|numeric',
          ]);
  
          $provider = \Srmklive\PayPal\Facades\PayPal::setProvider();
          $provider->setApiCredentials(config('paypal'));
          $token = $provider->getAccessToken();   
  
          $response = $provider->createOrder([
              "intent" => "CAPTURE",
              "purchase_units" => [
                  [
                      "amount" => [
                          "currency_code" => "USD",
                          "value" => $request->amount,
                      ],
                  ],
              ],
          ]);
  
          return response()->json(['order' => $response], 200);
      } catch (\Exception $e) {
          Log::error('Error creating payment: ' . $e->getMessage());
          return response()->json(['error' => $e->getMessage()], 500);
      }
  }

  public function executePayment(Request $request)
  {
      try {
          $request->validate([
              'orderID' => 'required|string',
          ]);
  
          $provider = \Srmklive\PayPal\Facades\PayPal::setProvider();
          $provider->setApiCredentials(config('paypal'));
          $token = $provider->getAccessToken();
          $provider->setAccessToken($token);
  
          $response = $provider->capturePaymentOrder($request->orderID);
          
          // Check if response is an array and has 'status'
          if (is_array($response) && isset($response['status']) && $response['status'] === 'COMPLETED') {
              $payment = Payment::create([
                  'fullname' => $response['payer']['name']['given_name'].' '.$response['payer']['name']['surname'],
                  'paymentstatus' => $response['status'],
                  'amount' => $response['purchase_units'][0]['payments']['captures'][0]['amount']['value']
              ]);
              return response()->json(['message' => 'Payment successful', 'payment' => $payment], 200);
          }
  
          return response()->json([ 'error' => 'Payment not completed',  'full_response' => $response ], 400);
      } catch (\Exception $e) {
          return response()->json(['error' => $e->getMessage()], 500);
      }
  }
}
