<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\Donation;
use Veritrans_Config;
use Veritrans_Snap;
use Veritrans_Notification;
use Veritrans_Transaction;

class DonationController extends Controller
{
    /**
     * Make request global.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Class constructor.
     *
     * @param \Illuminate\Http\Request $request User Request
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        // Set midtrans configuration
        Veritrans_Config::$serverKey = config('services.midtrans.serverKey');
        Veritrans_Config::$isProduction = config('services.midtrans.isProduction');
        Veritrans_Config::$isSanitized = config('services.midtrans.isSanitized');
        Veritrans_Config::$is3ds = config('services.midtrans.is3ds');
    }

    /**
     * Show index page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $data['donations'] = Donation::orderBy('id', 'desc')->paginate(8);

        return view('welcome', $data);
    }

    /**
     * Submit donation.
     *
     * @return array
     */
    public function submitDonation()
    {
            // Buat transaksi ke midtrans kemudian save snap tokennya.
            $payload = [
                'transaction_details' => [
                    'order_id'      => uniqid(),
                    'gross_amount'  => $this->request->amount,
                ],
                'customer_details' => [
                    'first_name'    => $this->request->donor_name,
                    'email'         => $this->request->donor_email,
                    'billing_address' => [
                        'address' => 'purigading',
                    ]
                ],
                'item_details' => [
                    [
                        'id'       => $this->request->donation_type,
                        'price'    => $this->request->amount,
                        'quantity' => 1,
                        'name'     => ucwords(str_replace('_', ' ', $this->request->donation_type))
                    ]
                ]
            ];
            $snapToken = Veritrans_Snap::getSnapToken($payload);
           
            // Beri response snap token
            $this->response['snap_token'] = $snapToken;
            

            return response()->json($this->response);

    }

    public function saveDonation(Request $request){
      Donation::create([
        'amount' => $request->amount,
        'note' => $request->note,
        'donation_type' => $request->donation_type,
        'donor_name' => $request->donor_name,
        'donor_email' => $request->donor_email,
        'order_id' => $request->order_id,
        'payment_type' => $request->payment_type,
        'pdf_url' => $request->pdf_url,
        'bank' => $request->bank,
        'va_number' => $request->va_number,
      ]);
      return response()->json(['success' => true]);
    }
    /**
     * Midtrans notification handler.
     *
     * @param Request $request
     * 
     * @return void
     */
    public function notificationHandler(Request $request)
    {
        $notif = new Veritrans_Notification();

          $transaction = $notif->transaction_status;
          $type = $notif->payment_type;
          $orderId = $notif->order_id;
          $fraud = $notif->fraud_status;
          $donation = Donation::where('order_id',$orderId)->first();
          
          if ($transaction == 'capture') {

            // For credit card transaction, we need to check whether transaction is challenge by FDS or not
            if ($type == 'credit_card') {

              if($fraud == 'challenge') {
                // TODO set payment status in merchant's database to 'Challenge by FDS'
                // TODO merchant should decide whether this transaction is authorized or not in MAP
                // $donation->addUpdate("Transaction order_id: " . $orderId ." is challenged by FDS");
                $donation->setPending();
              } else {
                // TODO set payment status in merchant's database to 'Success'
                // $donation->addUpdate("Transaction order_id: " . $orderId ." successfully captured using " . $type);
                $donation->setSuccess();
              }

            }

          } elseif ($transaction == 'settlement') {

            // TODO set payment status in merchant's database to 'Settlement'
            // $donation->addUpdate("Transaction order_id: " . $orderId ." successfully transfered using " . $type);
            $donation->setSuccess();

          } elseif($transaction == 'pending'){

            // TODO set payment status in merchant's database to 'Pending'
            // $donation->addUpdate("Waiting customer to finish transaction order_id: " . $orderId . " using " . $type);
            $donation->setPending();

          } elseif ($transaction == 'deny') {

            // TODO set payment status in merchant's database to 'Failed'
            // $donation->addUpdate("Payment using " . $type . " for transaction order_id: " . $orderId . " is Failed.");
            $donation->setFailed();

          } elseif ($transaction == 'expire') {

            // TODO set payment status in merchant's database to 'expire'
            // $donation->addUpdate("Payment using " . $type . " for transaction order_id: " . $orderId . " is expired.");
            $donation->setExpired();

          } elseif ($transaction == 'cancel') {

            // TODO set payment status in merchant's database to 'Failed'
            // $donation->addUpdate("Payment using " . $type . " for transaction order_id: " . $orderId . " is canceled.");
            $donation->setFailed();

          }

        return;
    }
    public function cancel(Request $request,$orderId){
      Veritrans_Transaction::cancel($orderId);
      return redirect('/');
    }
}
