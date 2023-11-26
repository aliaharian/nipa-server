<?php

namespace App\Http\Controllers\Factor;

use App\Http\Controllers\Controller;
use App\Models\FactorPaymentStep;
use App\Models\PaymentStatus;
use Illuminate\Http\Request;

class FactorPaymentController extends Controller
{
    //pay function
    //annotation
    /**
     * @OA\Post(
     *  path="/v1/factor/payment/pay",
     * tags={"FactorPayment"},
     * summary="pay a step",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"factor_payment_step_id","method"},
     * @OA\Property(property="factor_payment_step_id", type="number", format="number", example=1),
     * @OA\Property(property="method", type="string", format="string", example="online"),
     * ),
     * ),
     * @OA\Response(
     *   response=200,
     *  description="Success",
     * @OA\MediaType(
     * mediaType="application/json
     * "),
     * ),
     * security={{ "apiAuth": {} }}
     * )
     * )
     */
    public function pay(Request $request)
    {
        //validate
        $request->validate([
            "factor_payment_step_id" => "required|exists:factor_payment_steps,id",
            "method" => "required|in:online,offline"
        ]);
        //get factor payment step
        $factorPaymentStep = FactorPaymentStep::find($request->factor_payment_step_id);
        //check if factor payment step is already paid
        //find "paid" payment status

        //update factor payment step status
        $payStep = new FactorPaymentStepController();
        $payStep->updatePendingPaymentStatuses();

        if ($factorPaymentStep->status()->slug == "paid") {
            return response()->json([
                "message" => //persian
                    "این مرحله قبلا پرداخت شده است",
                'status' => 'error',
                'success' => false,
                'code' => 400
            ], 400);
        }
        if ($factorPaymentStep->status()->slug == "pendingForPayment") {
            return response()->json([
                "message" => //persian
                    "این مرحله در حال پرداخت است",
                'status' => 'error',
                'success' => false,
                'code' => 400
            ], 400);
        }
        //check if method is online and allow online
        if ($request->method == "online" && !$factorPaymentStep->allow_online) {
            return response()->json([
                "message" => //persian
                    "این مرحله از فاکتور قابل پرداخت آنلاین نیست",
                'status' => 'error',
                'success' => false,
                'code' => 400
            ], 400);
        }
        //check if method is offline and allow offline
        if ($request->method == "offline" && !$factorPaymentStep->allow_offline) {
            return response()->json([
                "message" => //persian
                    "این مرحله از فاکتور قابل پرداخت آفلاین نیست",
                'status' => 'error',
                'success' => false,
                'code' => 400
            ], 400);
        }
        //check if pay time is over
        if ($factorPaymentStep->pay_time < now()) {
            return response()->json([
                "message" => //persian
                    "زمان پرداخت به پایان رسیده است",
                'status' => 'error',
                'success' => false,
                'code' => 400
            ], 400);
        }


        //get user wallet 
        $userWallet = auth()->user()->wallet;
        //check if user wallet is enough
        $walletTotal = $userWallet->active ? $userWallet->balance + $userWallet->credit - $userWallet->blocked : 0;
        if ($walletTotal < $factorPaymentStep->payable_price) {
            //pay from gateway or offline
            //calculate remaining amount
            $remainingAmount = $factorPaymentStep->payable_price - $walletTotal;
            //TODO: pay from gateway or offline

            //block wallet
            $blockedAmount = $walletTotal;
            $userWallet->blocked += $blockedAmount;
            $userWallet->save();
            //add transaction
            //find pending status id
            $pendingStatusId = PaymentStatus::where('slug', 'pendingForPayment')->first()->id;
            $transaction = $userWallet->transactions()->create([
                'wallet_id' => $userWallet->id,
                'payment_method' => $request->method,
                'price' => $remainingAmount,
                'status_id' => $pendingStatusId,
                'description' => 'افزایش موجودی کیف پول بابت مرحله ' . $factorPaymentStep->step_number . ' فاکتور ' . $factorPaymentStep->factor->code,
                'meta' => json_encode([
                    'factor_id' => $factorPaymentStep->factor_id,
                    'factor_payment_step_id' => $factorPaymentStep->id,
                    //add fileId and payer description if offline
                    //add gatewayId and tracking code ... if online
                ]),
                'transaction_type' => 'increaseBalance',
                'isValid' => false,
            ]);
            //add payment
            $payment = $this->addPayment($factorPaymentStep, $transaction, $blockedAmount, "pendingForPayment");
            if ($request->method == "online") {
                return response()->json([
                    "message" => //persian
                        "در حال انتقال به درگاه...",
                    'status' => 'success',
                    'data' => [
                        'transaction' => $transaction,
                        'payment' => $payment
                    ],
                    'success' => true,
                    'code' => 200
                ], 200);
            }

        } else {
            //pay from wallet
            $transaction = $this->payFactorFromWallet(auth()->user(), $factorPaymentStep);
            //add payment
            $payment = $this->addPayment($factorPaymentStep, $transaction, $factorPaymentStep->payable_price, 'paid');

            return response()->json([
                "message" => //persian
                    "پرداخت با موفقیت انجام شد",
                'status' => 'success',
                'success' => true,
                'code' => 200,
                'data' => [
                    'transaction' => $transaction
                ]
            ], 200);
        }

    }

    public function addPayment($factorPaymentStep, $transaction, $walletPaymentAmount, $status)
    {
        $statusId = PaymentStatus::where('slug', $status)->first()->id;
        $payment = $factorPaymentStep->payments()->create([
            'payment_step_id' => $factorPaymentStep->id,
            'transaction_id' => $transaction ? $transaction->id : null,
            'description' => null,
            'meta' => null,
            'payment_status_id' => $statusId,
            'wallet_payment_amount' => $walletPaymentAmount
        ]);
        return $payment;

    }


    public function payFactorFromWallet($user, $factorPaymentStep, $relatedPayment = null)
    {

        //TODO: اگر از درگاه بیاد یا دستی پرداخت کنه باید بلوکه بررسی بشه
        if ($relatedPayment) {

        }
        $userWallet = $user->wallet;

        //reduce wallet first from credit and then if credit is not enough reduce from balance
        if ($userWallet->credit >= $factorPaymentStep->payable_price) {
            //reduce from credit
            $userWallet->credit -= $factorPaymentStep->payable_price;
            $userWallet->save();
        } else {
            //reduce from balance
            $userWallet->balance -= $factorPaymentStep->payable_price - $userWallet->credit;
            $userWallet->credit = 0;
            $userWallet->save();
        }
        //transaction record
        $transaction = $userWallet->transactions()->create([
            'wallet_id' => $userWallet->id,
            'payment_method' => 'online',
            'price' => $factorPaymentStep->payable_price,
            'status_id' => 1,
            'description' => 'کسر از کیف پول بابت مرحله ' . $factorPaymentStep->step_number . ' فاکتور ' . $factorPaymentStep->factor->code,
            'meta' => json_encode([
                'factor_id' => $factorPaymentStep->factor_id,
                'factor_payment_step_id' => $factorPaymentStep->id,
            ]),
            'transaction_type' => 'Withdrawal',
            'isValid' => true,
        ]);
        return $transaction;
    }
}
