<?php

namespace App\Http\Controllers\Factor;

use App\Http\Controllers\Controller;
use App\Models\FactorPayment;
use App\Models\FactorPaymentStep;
use App\Models\PaymentStatus;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use App\Models\UserWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        //get factor payment step
        $factorPaymentStep = FactorPaymentStep::find($request->factor_payment_step_id);

        $permissions = Auth::user()->roles->flatMap(function ($role) {
            return $role->permissions->pluck('slug')->toArray();
        })->toArray();

        if (!in_array('can-pay-all-factor-payments', $permissions)) {
            //check if owner
            $factor_user_id = $factorPaymentStep->factor->orderGroup->user_id;
            $factor_customer_id = $factorPaymentStep->factor->orderGroup->customer_id;
            $user_id = auth()->user()->id;
            $user_customer_id = auth()->user()->customer->id;

            if ($factor_user_id != $user_id && $factor_customer_id != $user_customer_id) {
                return response()->json([
                    "message" => //persian
                        "شما اجازه پرداخت این مرحله را ندارید",
                    'status' => 'error',
                    'success' => false,
                    'code' => 403
                ], 403);
            }
        }

        //validate
        $request->validate([
            "factor_payment_step_id" => "required|exists:factor_payment_steps,id",
            "method" => "required|in:online,offline"
        ]);

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
        if ($factorPaymentStep->status()->slug == "pendingVerify") {
            return response()->json([
                "message" => //persian
                    "این مرحله در حال بررسی است",
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

        //if offline , fileId and payer description is required
        if ($request->method == "offline") {
            $request->validate([
                "fileId" => "required|exists:files,id",
//                "payerDescription" => "required|string"
            ]);
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
            $pendingStatusId = PaymentStatus::where('slug', $request->method == "offline" ? 'pendingVerify' : 'pendingForPayment')->first()->id;
            $transaction = $userWallet->transactions()->create([
                'wallet_id' => $userWallet->id,
                'payment_method' => $request->method,
                'price' => $remainingAmount,
                'status_id' => $pendingStatusId,
                'description' => 'افزایش موجودی کیف پول بابت مرحله ' . $factorPaymentStep->step_number . ' فاکتور ' . $factorPaymentStep->factor->code,
                'meta' => json_encode([
                    'factor_id' => $factorPaymentStep->factor_id,
                    'factor_payment_step_id' => $factorPaymentStep->id,
                    'fileId' => $request->fileId ?? null,
                    'payerDescription' => $request->payerDescription ?? null,
                    //add fileId and payer description if offline
                    //add gatewayId and tracking code ... if online
                ]),
                'transaction_type' => 'increaseBalance',
                'isValid' => false,
            ]);
            //add payment
            $payment = $this->addPayment($factorPaymentStep, $transaction, $blockedAmount, $request->method == "offline" ? 'pendingVerify' : 'pendingForPayment');
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
            } else if ($request->method == "offline") {
                return response()->json([
                    "message" => //persian
                        "درخواست شما جهت بررسی ثبت شد و پس از تایید به اطلاع شما خواهد رسید",
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

    /**
     * @OA\Post(
     *  path="/v1/factor/payment/verify",
     * tags={"FactorPayment"},
     * summary="verify a payment",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"factor_payment_id","gateway_verify_code"},
     * @OA\Property(property="factor_payment_id", type="number", format="number", example=1),
     * @OA\Property(property="gateway_verify_code", type="string", format="string", example="123456"),
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
    //online payment
    public function verifyPayment(Request $request)
    {
        //when payment is online
        //validate
        $request->validate([
            "factor_payment_id" => "required|exists:factor_payments,id",
            "gateway_verify_code" => "required|string"
            // "method" => "required|in:online,offline"
        ]);
        //check if payment is online
        $payment = FactorPayment::find($request->factor_payment_id);

        $permissions = Auth::user()->roles->flatMap(function ($role) {
            return $role->permissions->pluck('slug')->toArray();
        })->toArray();

        if (!in_array('can-verify-all-factor-payments', $permissions)) {
            //check if owner
            $factor_user_id = $payment->payment_step->factor->orderGroup->user_id;
            $factor_customer_id = $payment->payment_step->factor->orderGroup->customer_id;
            $user_id = auth()->user()->id;
            $user_customer_id = auth()->user()->customer->id;

            if ($factor_user_id != $user_id && $factor_customer_id != $user_customer_id) {
                return response()->json([
                    "message" => //persian
                        "شما اجازه بررسی پرداخت این مرحله را ندارید",
                    'status' => 'error',
                    'success' => false,
                    'code' => 403
                ], 403);
            }
        }


        if ($payment->transaction->payment_method != "online") {
            return response()->json([
                "message" => //persian
                    "این پرداخت آنلاین نیست",
                'status' => 'error',
                'success' => false,
                'code' => 400
            ], 400);
        }
        //verify only if in "pendingForPayment" status
        if ($payment->status->slug != "pendingForPayment") {
            return response()->json([
                "message" => //persian
                    "این پرداخت قابل تایید نیست",
                'status' => 'error',
                'success' => false,
                'code' => 400
            ], 400);
        } else {
            //verify payment
            //TODO:LOGIC: verify payment from gateway
            $flag = $request->gateway_verify_code == "123456" ? true : false;
            if ($flag) {
                //update transaction status
                $payment->transaction->status_id = TransactionStatus::where('slug', 'paid')->first()->id;
                $payment->transaction->isValid = true;
                $payment->transaction->save();
                //increase wallet balance
                $userWallet = $payment->transaction->wallet;
                $userWallet->balance += $payment->transaction->price;
                //unblock wallet
                $userWallet->blocked -= $payment->wallet_payment_amount;
                $userWallet->save();

                //pay from wallet
                $transaction = $this->payFactorFromWallet(auth()->user(), $payment->payment_step, $payment);

                //update payment status
                $payment->payment_status_id = PaymentStatus::where('slug', 'paid')->first()->id;
                $payment->description = "پرداخت از طریق درگاه";
                $payment->transaction_id = $transaction->id;
                $payment->meta = json_encode([
                    'increaseBalanceTransactionId' => $payment->transaction->id,
                    'trackingCode' => '123456',
                ]);
                $payment->save();
                //update factor payment step status
                $payStep = new FactorPaymentStepController();
                $payStep->updatePendingPaymentStatuses();

                //response
                return response()->json([
                    "message" => //persian
                        "پرداخت با موفقیت تایید شد",
                    'status' => 'success',
                    'success' => true,
                    'code' => 200
                ], 200);
            } else {
                //update transaction status
                $payment->transaction->status_id = TransactionStatus::where('slug', 'failed')->first()->id;
                $payment->transaction->isValid = false;
                $payment->transaction->save();
                //update payment status
                $payment->payment_status_id = PaymentStatus::where('slug', 'failed')->first()->id;
                $payment->save();
                //unblock wallet
                $userWallet = $payment->transaction->wallet;
                $userWallet->blocked -= $payment->wallet_payment_amount;
                $userWallet->save();
                //update factor payment step status
                $payStep = new FactorPaymentStepController();
                $payStep->updatePendingPaymentStatuses();

                //response
                return response()->json([
                    "message" => //persian
                        "پرداخت با خطا مواجه شد",
                    'status' => 'error',
                    'success' => false,
                    'code' => 400
                ], 400);
            }
        }
    }


    //view payment

    /**
     * @OA\Get(
     *  path="/v1/factor/payment/{id}",
     * tags={"FactorPayment"},
     * summary="view a payment",
     *    * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * @OA\Schema(
     * type="integer",
     * format="int64"
     * )
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
    public function viewPayment(Request $request)
    {
        //view payment
        $payment = FactorPayment::find($request->id);
        if (!$payment) {
            return response()->json([
                "message" => //persian
                    "پرداختی یافت نشد",
                'status' => 'error',
                'success' => false,
                'code' => 404
            ], 404);
        }

        $permissions = Auth::user()->roles->flatMap(function ($role) {
            return $role->permissions->pluck('slug')->toArray();
        })->toArray();

        if (!in_array('can-view-all-factor-payments', $permissions)) {
            //check if owner
            $factor_user_id = $payment->payment_step->factor->orderGroup->user_id;
            $factor_customer_id = $payment->payment_step->factor->orderGroup->customer_id;
            $user_id = auth()->user()->id;
            $user_customer_id = auth()->user()->customer->id;

            if ($factor_user_id != $user_id && $factor_customer_id != $user_customer_id) {
                return response()->json([
                    "message" => //persian
                        "شما اجازه بررسی پرداخت این مرحله را ندارید",
                    'status' => 'error',
                    'success' => false,
                    'code' => 403
                ], 403);
            }
        }

        $payment->load('status', 'transaction', 'payment_step');
        //transaction file
        // if ($payment->transaction->meta) {
        //     $payment->transaction->file = $payment->transaction->file();
        // }
        return response()->json([
            "message" => //persian
                "payment retrieved successfully",
            'status' => 'success',
            'success' => true,
            'code' => 200,
            'data' => [
                'payment' => $payment
            ]
        ], 200);
    }


    /**
     * @OA\Post(
     *  path="/v1/factor/payment/verifyOffline",
     * tags={"FactorPayment"},
     * summary="verify an offline payment",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"factor_payment_id","verified","adminDescription"},
     * @OA\Property(property="factor_payment_id", type="number", format="number", example=1),
     * @OA\Property(property="verified", type="boolean", format="boolean", example=true),
     * @OA\Property(property="adminDescription", type="string", format="string", example="تایید شد"),
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
    public function verifyOfflinePayment(Request $request)
    {
        //when payment is offline
        //validate
        $request->validate([
            "factor_payment_id" => "required|exists:factor_payments,id",
//            "gateway_verify_code" => "required|string"
            // "method" => "required|in:online,offline"
        ]);
        //check if payment is offline
        $payment = FactorPayment::find($request->factor_payment_id);
        if ($payment->transaction->payment_method != "offline") {
            return response()->json([
                "message" => //persian
                    "این پرداخت آفلاین نیست",
                'status' => 'error',
                'success' => false,
                'code' => 400
            ], 400);
        }
        //verify only if in "pendingForPayment" status
        if ($payment->status->slug != "pendingVerify") {
            return response()->json([
                "message" => //persian
                    "این پرداخت قابل تایید نیست",
                'status' => 'error',
                'success' => false,
                'code' => 400
            ], 400);
        } else {
            //TODO:verify payment
            $payment->description = $request->adminDescription;
            if ($request->verified) {
                $payment->payment_status_id = PaymentStatus::where("slug", "paid")->first()->id;
            } else {
                $payment->payment_status_id = PaymentStatus::where("slug", "failed")->first()->id;
            }
            $payment->save();

            $transaction = Transaction::find($payment->transaction_id);
            if ($request->verified) {
                //TODO:minus wallet blocked and also minus price from wallet balance and credit
                $transaction->status_id = TransactionStatus::where("slug", "done")->first()->id;
                $wallet = UserWallet::find($transaction->wallet_id);
                $wallet->blocked = $wallet->blocked - $payment->wallet_payment_amount;
                if ($payment->wallet_payment_amount <= $wallet->credit) {
                    $wallet->credit = $wallet->credit - $payment->wallet_payment_amount;
                } else {
                    $wallet->credit = 0;
                    $wallet->balance = $wallet->balance - ($payment->wallet_payment_amount - $wallet->credit);
                }
                $wallet->save();
            } else {
                //TODO:release wallet amount
                $transaction->status_id = TransactionStatus::where("slug", "failed")->first()->id;
                $wallet = UserWallet::find($transaction->wallet_id);
                $wallet->blocked = $wallet->blocked - $payment->wallet_payment_amount;
                $wallet->save();
            }
            $transaction->save();
            return response()->json([
                "message" => //persian
                    "وضعیت تاییدیه شما ثبت شد",
                'status' => 'error',
                'success' => false,
                'code' => 400
            ], 400);
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
