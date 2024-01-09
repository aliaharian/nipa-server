<?php

namespace App\Http\Controllers\Factor;

use App\Http\Controllers\Controller;
use App\Models\Factor;
use App\Models\FactorPaymentStep;
use App\Models\File;
use App\Models\PaymentStatus;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FactorPaymentStepController extends Controller
{

    //view all factor payment steps
    /**
     * @OA\Get(
     *  path="/v1/factor/paymentStep",
     * tags={"Factor"},
     * summary="view factor all payment steps",
     * @OA\Parameter(
     * name="factor_id",
     * in="query",
     * required=false,
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

    public function index(Request $request)
    {
        //
        $user_id = auth()->user()->id;
        $user_customer_id = auth()->user()->customer->id;

        $factor = Factor::find($request->factor_id);
        $factor_user_id = $factor->orderGroup->user_id;
        $factor_customer_id = $factor->orderGroup->customer_id;


        $canPay = true;
        if ($factor_user_id != $user_id && $factor_customer_id != $user_customer_id) {
            $canPay = false;
        }

        $permissions = Auth::user()->roles->flatMap(function ($role) {
            return $role->permissions->pluck('slug')->toArray();
        })->toArray();

        //check if user has permission "can-view-all-invoices"
        if (!in_array("can-view-all-payment-steps", $permissions)) {
            //check if user is owner of this factor
            //check if user_id or customer_id is equal to auth user id


            if ($factor_user_id != $user_id && $factor_customer_id != $user_customer_id) {
                return response()->json(['message' => 'you dont have permission to view payment steps of this factor'], 403);
            }
        }


        $factorTotalPrice = $factor->totalPrice();
        $resp = $factorTotalPrice->getData();
        $factorTotalPrice = $resp->data;
        $allHavePrice = $resp->allHavePrice;


        $factor_payment_steps = FactorPaymentStep::query();
        if ($request->factor_id) {
            $factor_payment_steps = $factor_payment_steps->where('factor_id', $request->factor_id)->orderBy('step_number', 'asc');
        }
        $factor_payment_steps = $factor_payment_steps->get();

        $warning = "";
        //check how many steps we have
        $count = $factor_payment_steps->count();
        if (!$allHavePrice) {
            $warning = __('validation.custom.factor_payment_steps.allHavePrice');
        } else
            if ($count == 0) {

                $warning =  //get errors based on locale
                    __('validation.custom.factor_payment_steps.count0');
            } else if ($count == 1) {
                //check if step 1 is not equal to factor total price
                if ($factor_payment_steps[0]->payable_price != $factorTotalPrice) {
                    $warning = __('validation.custom.factor_payment_steps.count1');
                }
            } else {
                //check if step 1 + step 2 is not equal to factor total price
                if ($factor_payment_steps[0]->payable_price + $factor_payment_steps[1]->payable_price != $factorTotalPrice) {
                    $warning = __('validation.custom.factor_payment_steps.count2');
                }
            }


        //update pending payment statuses
        $this->updatePendingPaymentStatuses();

        //check payments
        foreach ($factor_payment_steps as $factor_payment_step) {
            $factor_payment_step->status = $factor_payment_step->status()->makeHidden(['id', 'created_at', 'updated_at', 'meta']);
            //
            $factor_payment_step->pay_status = "unknown";

            $factor_payment_step->last_payment = $factor_payment_step->payments()->orderBy('id', 'desc')->first();
            //check if user owner or customer of this factor
            // $factor_payment_step->canPay = $canPay ?
            //     //check if this is second payment step first one was paied
            //     ($factor_payment_step->step_number == 2 && $factor_payment_step->last_payment && $factor_payment_step->last_payment->status->slug == "paid") ?
            //     false : true
            //     : false;
            if ($canPay) {
                if ($factor_payment_step->step_number == 2) {
                    if ($factor_payment_step->last_payment) {
                        if ($factor_payment_step->last_payment->status->slug != "paid") {
                            $factor_payment_step->canPay = true;
                        } else {
                            $factor_payment_step->canPay = false;
                        }
                    } else {
                        //check if step 1 paid or not
                        $firstStep = $factor_payment_steps->where('factor_id', $request->factor_id)->where('step_number', 1)->first();
                        $firstStepPayment = $firstStep->payments()->orderBy('id', 'desc')->first();
                        if ($firstStepPayment) {
                            if ($firstStepPayment->status->slug == "paid") {
                                $factor_payment_step->canPay = true;
                            } else {
                                $factor_payment_step->canPay = false;
                            }
                        } else {
                            $factor_payment_step->canPay = false;
                        }
                    }
                } else {
                    $factor_payment_step->canPay = true;
                }
            } else {
                $factor_payment_step->canPay = false;
            }

            if ($factor_payment_step->last_payment) {
                $factor_payment_step->last_payment->status = PaymentStatus::find($factor_payment_step->last_payment->payment_status_id)->makeHidden(['id', 'created_at', 'updated_at', 'meta']);
                $factor_payment_step->pay_status = $factor_payment_step->last_payment->status->slug;
                $factor_payment_step->last_payment->demotransaction = Transaction::find($factor_payment_step->last_payment->transaction_id)->makeHidden(['id', 'created_at', 'updated_at', 'meta']);
            }

            if ($factor_payment_step->canPay) {
                //calculate wallet balance and credit to determine that how much user must pay in addition to wallet balance
                $user = auth()->user();
                $wallet = $user->wallet;
                $totalBalance = 0;
                $remainingPayablePrice = 0;
                if ($wallet && $wallet->active) {
                    $totalBalance = $wallet->balance + $wallet->credit - $wallet->blocked;
                    $remainingPayablePrice = $factor_payment_step->payable_price - $totalBalance;
                    if ($remainingPayablePrice < 0) {
                        $remainingPayablePrice = 0;
                    }
                } else {
                    $remainingPayablePrice = $factor_payment_step->payable_price;
                }

                //add remainingPayablePrice and totalBalance to factor_payment_step object
                $factor_payment_step->remaining_payable_price = $remainingPayablePrice;
                $factor_payment_step->total_balance = $totalBalance;
            }
        }
        //response
        return response()->json([
            'data' => $factor_payment_steps,
            'message' => 'factor payment steps retrieved successfully',
            'status' => $warning == "" ? 'success' : "warning",
            'warning' => $warning,
            'factor_total_price' => $factorTotalPrice,
            'factor_sum_price' => $resp->sumPrice,
            'factor_sum_off_price' => $resp->sumOffPrice,
            'factor_sum_additional_price' => $resp->sumAdditionalPrice,
            'success' => true,
            'allHavePrice' => $allHavePrice,
            'code' => 200
        ], 200);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    //store factor payment step by factor id
    //annotation
    /**
     * @OA\Post(
     *  path="/v1/factor/paymentStep",
     * tags={"Factor"},
     * summary="create factor payment step",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"factor_id","step_number","payable_price","allow_online","allow_offline","pay_time"},
     * @OA\Property(property="factor_id", type="number", format="number", example=1),
     * @OA\Property(property="step_number", type="number", format="number", example=1),
     * @OA\Property(property="payable_price", type="number", format="number", example=1000),
     * @OA\Property(property="allow_online", type="boolean", format="boolean", example=true),
     * @OA\Property(property="allow_offline", type="boolean", format="boolean", example=true),
     * @OA\Property(property="pay_time", type="string", format="string", example="2021-09-01 00:00:00"),
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
    public function store(Request $request)
    {
        //validate
        $request->validate([
            'factor_id' => 'required|integer|exists:factors,id',
            //step number unique:factor_payment_steps,step_numberonly when factor_id is same
            'step_number' => 'required|integer|in:1,2|unique:factor_payment_steps,step_number,NULL,id,factor_id,' . $request->factor_id,
            'payable_price' => 'required|integer|min:10000',
            'allow_online' => 'required|boolean',
            'allow_offline' => 'required|boolean',
            'pay_time' => 'required|date_format:Y-m-d H:i:s',
        ]);

        $factor = Factor::find($request->factor_id);
        $factorTotalPrice = $factor->totalPrice(true);
        $resp = $factorTotalPrice->getData();
        if ($resp->success == false) {
            return $factorTotalPrice;
        } else {
            $factorTotalPrice = $resp->data;
        }


        //if step number is 1 , check that payable price is equal to or less than factor total price
        if ($request->step_number == 1) {

            if ($request->payable_price > $factorTotalPrice) {
                return response()->json(
                    [
                        'message' => //persian
                            'قیمت قابل پرداخت بیشتر از مبلغ کل است',
                        'status' => 'error',
                        'success' => false,
                        'data' => [
                            'factor_total_price' => $factorTotalPrice
                        ],
                        'code' => 404
                    ],
                    404
                );
            }
        }


        //if step number is 2, check if step number 1 exists
        if ($request->step_number == 2) {
            $factorPaymentStep = FactorPaymentStep::where('factor_id', $request->factor_id)->where('step_number', 1)->first();
            if (!$factorPaymentStep) {
                return response()->json([
                    'message' => //persian
                        'مرحله یک پرداخت وجود ندارد',
                    'status' => 'error',
                    'success' => false,
                    'code' => 404
                ], 404);
            }
        }

        //if step number is 2 , check that sum price of step 1 and step 2 is equal to factor total price
        if ($request->step_number == 2) {
            $factorPaymentStep = FactorPaymentStep::where('factor_id', $request->factor_id)->where('step_number', 1)->first();
            if ($factorPaymentStep->payable_price + $request->payable_price != $factorTotalPrice) {
                return response()->json([
                    'message' => //persian
                        'مجموع قیمت مرحله یک و دو با قیمت کل فاکتور برابر نیست',
                    'status' => 'error',
                    'success' => false,
                    'data' => [
                        'factor_total_price' => $factorTotalPrice,
                        'step_1_payable_price' => $factorPaymentStep->payable_price,
                        'step_2_payable_price' => $request->payable_price,
                    ],
                    'code' => 404
                ], 404);
            }
        }


        //create
        $factorPaymentStep = FactorPaymentStep::create([
            'factor_id' => $request->factor_id,
            'step_number' => $request->step_number,
            'payable_price' => $request->payable_price,
            'allow_online' => $request->allow_online,
            'allow_offline' => $request->allow_offline,
            'pay_time' => $request->pay_time,
        ]);

        $factorController = new FactorController();
        $checkValidity = $factorController->checkAndUpdateFactorStatus($request->factor_id);


        //response
        return response()->json([
            'data' => $factorPaymentStep,
            'message' => 'factor payment step created successfully',
            'note' => //if step is 1 and not equal to factor total price, you must define step 2
                ($factorPaymentStep->payable_price < $factorTotalPrice && $factorPaymentStep->step_number == 1) ? 'قیمت قابل پرداخت کمتر از مبلغ کل است، لطفا مرحله دوم پرداخت را تعریف کنید' : "",

            'status' => 'success',
            'success' => true,
            'code' => 200
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     *  path="/v1/factor/paymentStep/{id}",
     * tags={"Factor"},
     * summary="view factor payment step",
     * @OA\Parameter(
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
    public function show($id)
    {
        //
        $factor_payment_step = FactorPaymentStep::find($id);
        if (!$factor_payment_step) {
            return response()->json([
                'message' => //persian
                    'مرحله پرداخت وجود ندارد',
                'status' => 'error',
                'success' => false,
                'code' => 404
            ], 404);
        }
        $factor = $factor_payment_step->factor;
        //check permission!
        $permissions = Auth::user()->roles->flatMap(function ($role) {
            return $role->permissions->pluck('slug')->toArray();
        })->toArray();

        //check if user has permission "can-view-all-invoices"
        if (!in_array("can-view-all-payment-steps", $permissions)) {
            //check if user is owner of this factor
            //check if user_id or customer_id is equal to auth user id
            $factor_user_id = $factor->orderGroup->user_id;
            $factor_customer_id = $factor->orderGroup->customer_id;
            $user_id = auth()->user()->id;
            $user_customer_id = auth()->user()->customer->id;
            if ($factor_user_id != $user_id && $factor_customer_id != $user_customer_id) {
                return response()->json(['message' => 'you dont have permission to view this payment step'], 403);
            }
        }


        $warning = "";
        $factorTotalPrice = $factor->totalPrice(true);
        $resp = $factorTotalPrice->getData();
        if ($resp->success == false) {
            $warning = "این فاکتور به علت ناقص بودن اطلاعات قابل پرداخت نیست";
        } else {
            $factorTotalPrice = $resp->data;
        }

        $factor_payment_steps = FactorPaymentStep::where('factor_id', $factor_payment_step->factor_id)->orderBy('step_number', 'asc')->get();
        //check how many steps we have
        $count = $factor_payment_steps->count();
        if ($count == 1) {
            //check if step 1 is not equal to factor total price
            if ($factor_payment_steps[0]->payable_price != $factorTotalPrice) {
                $warning = "این فاکتور به علت صحیح نبودن قیمت ها قابل پرداخت نیست";
            }
        } else {
            //check if step 1 + step 2 is not equal to factor total price
            if ($factor_payment_steps[0]->payable_price + $factor_payment_steps[1]->payable_price != $factorTotalPrice) {
                $warning = "این فاکتور به علت صحیح نبودن قیمت ها قابل پرداخت نیست";
            }
        }

        //calculate user wallet balance and credit to determine that how much user must pay in addition to wallet balance
        $user = auth()->user();
        $wallet = $user->wallet;
        $totalBalance = 0;
        $remainingPayablePrice = 0;
        if ($wallet && $wallet->active) {
            $totalBalance = $wallet->balance + $wallet->credit - $wallet->blocked;
            $remainingPayablePrice = $factorTotalPrice - $totalBalance;
            if ($remainingPayablePrice < 0) {
                $remainingPayablePrice = 0;
            }
        } else {
            $remainingPayablePrice = $factor_payment_step->payable_price;
        }

        //add remainingPayablePrice and totalBalance to factor_payment_step object
        $factor_payment_step->remaining_payable_price = $remainingPayablePrice;
        $factor_payment_step->total_balance = $totalBalance;

        //show payments
        $payments = [];
        foreach ($factor_payment_step->payments as $payment) {
            $pay = new \stdClass();
            $pay->paid_price = $payment->transaction->price;
            $pay->payable_price = $factor_payment_step->payable_price;
            $pay->wallet_payment_amount = $payment->wallet_payment_amount;
            $pay->payment_status = $payment->status;
            $pay->pay_method = $payment->transaction->payment_method;
            $pay->created_at = $payment->transaction->created_at;
            $transaction_meta = $payment->transaction->meta ? json_decode($payment->transaction->meta) : null;
            if ($transaction_meta && $payment->transaction->payment_method == "offline" && $transaction_meta->fileId) {
                $file = File::find($transaction_meta->fileId);
                $pay->file_hash_code = $file->hash_code;
            }
            $pay->tracking_code = "";
            $pay->admin_description = $payment->description;
            $payments[] = $pay;
        }
        $response = new \stdClass();
        $response->id = $factor_payment_step->id;
        $response->payable_price = $factor_payment_step->payable_price;
        $response->step_number = $factor_payment_step->step_number;
        $response->factor_id = $factor_payment_step->factor_id;

        $response->payments = $payments;
        //response
        return response()->json([
            'data' => $response,
            'message' => 'factor payment step retrieved successfully',
            'status' => $warning == "" ? 'success' : "warning",
            'success' => true,
            'warning' => $warning,
            'code' => 200
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Put(
     *  path="/v1/factor/paymentStep/{id}",
     * tags={"Factor"},
     * summary="update factor payment step",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * @OA\Schema(
     * type="integer",
     * format="int64"
     * )
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"step_number","payable_price","allow_online","allow_offline","pay_time"},
     * @OA\Property(property="payable_price", type="number", format="number", example=1000),
     * @OA\Property(property="allow_online", type="boolean", format="boolean", example=true),
     * @OA\Property(property="allow_offline", type="boolean", format="boolean", example=true),
     * @OA\Property(property="pay_time", type="string", format="string", example="2021-09-01 00:00:00"),
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
    public function update(Request $request, $id)
    {
        $factor_payment_step = FactorPaymentStep::find($id);
        if (!$factor_payment_step) {
            return response()->json([
                'message' => //persian
                    'مرحله پرداخت وجود ندارد',
                'status' => 'error',
                'success' => false,
                'code' => 404
            ], 404);
        }

        if ($factor_payment_step->status()->slug == "paid" || $factor_payment_step->status()->slug == "pendingVerify") {
            return response()->json([
                'message' => //persian
                    "مرحله قابل ویرایش نیست",
                'status' => 'error',
                'success' => false,
                'code' => 422
            ], 422);
        }

        //validate
        $request->validate([
            //step number is readonly and cant change
            // 'step_number' => 'required|integer|in:1,2|unique:factor_payment_steps,step_number,' . $id . ',id,factor_id,' . $request->factor_id,
            'payable_price' => 'required|integer|min:10000',
            'allow_online' => 'required|boolean',
            'allow_offline' => 'required|boolean',
            'pay_time' => 'required|date_format:Y-m-d H:i:s',
        ]);


        $factor = $factor_payment_step->factor;
        $factorTotalPrice = $factor->totalPrice(true);
        $resp = $factorTotalPrice->getData();
        if ($resp->success == false) {
            return $factorTotalPrice;
        } else {
            $factorTotalPrice = $resp->data;
        }

        //update
        $factor_payment_step->update([
            'payable_price' => $request->payable_price,
            'allow_online' => $request->allow_online,
            'allow_offline' => $request->allow_offline,
            'pay_time' => $request->pay_time,
        ]);

        $factorController = new FactorController();
        $checkValidity = $factorController->checkAndUpdateFactorStatus($factor->id);


        //response
        return response()->json([
            'data' => $factor_payment_step,
            'message' => 'factor payment step updated successfully',
            'status' => 'success',
            'success' => true,
            'code' => 200
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Delete(
     *  path="/v1/factor/paymentStep/{id}",
     * tags={"Factor"},
     * summary="delete factor step",
     * @OA\Parameter(
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
    public function destroy($id)
    {
        //
        $factor_payment_step = FactorPaymentStep::find($id);
        if (!$factor_payment_step) {
            return response()->json([
                'message' => //persian
                    'مرحله پرداخت وجود ندارد',
                'status' => 'error',
                'success' => false,
                'code' => 404
            ], 404);
        }
        //prevent to delete step 1
        if ($factor_payment_step->step_number == 1) {
            return response()->json([
                'message' => //persian
                    'مرحله اول پرداخت قابل حذف نیست',
                'status' => 'error',
                'success' => false,
                'code' => 404
            ], 404);
        } else {
            $factor_id = $factor_payment_step->factor_id;
            $factor = Factor::find($factor_id);
            $factorTotalPrice = $factor->totalPrice();
            $resp = $factorTotalPrice->getData();
            $factorTotalPrice = $resp->data;
            $firstStep = FactorPaymentStep::where('factor_id', $factor_id)->where("step_number", 1)->first();
            if ($factorTotalPrice > $firstStep->payable_price) {
                //only can delete if none of steps are not paid or not at pendingVerify
                $all_steps = FactorPaymentStep::where('factor_id', $factor_id)->get();
                foreach ($all_steps as $step) {
                    if ($step->status()->slug == "paid" || $step->status()->slug == "pendingVerify") {
                        return response()->json([
                            'message' => //persian
                                "عملیات قابل انجام نیست",
                            'status' => 'error',
                            'success' => false,
                            'code' => 404
                        ], 404);
                    }
                }
            }
            //delete
            $factor_payment_step->delete();

            $factorController = new FactorController();
            $checkValidity = $factorController->checkAndUpdateFactorStatus($factor_id);


            //response
            return response()->json([
                'data' => $factor_payment_step,
                'message' => 'factor payment step deleted successfully',
                'status' => 'success',
                'success' => true,
                'code' => 200
            ], 200);
        }
    }

    public
    function updatePendingPaymentStatuses()
    {
        $factor_payment_steps = FactorPaymentStep::all();
        foreach ($factor_payment_steps as $factor_payment_step) {
            $payments = $factor_payment_step->payments;
            //check if any payment has "pendingForPayment" status and 10 minutes passed from its updated_at
            //if there is , change its status to "timedOut" unblock blocked amount from wallet from field "wallet_payment_amount"
            //update its transaction status to "timedOut"
            foreach ($payments as $payment) {
                if ($payment->status->slug == "pendingForPayment") {
                    $now = now();
                    $updated_at = $payment->updated_at;
                    $diff = $now->diffInMinutes($updated_at);
                    if ($diff >= 2) {
                        //change status to timedOut
                        $payment->update([
                            'payment_status_id' => $payment->status->where('slug', 'timedOut')->first()->id
                        ]);
                        //unblock blocked amount from wallet
                        $wallet = $payment->transaction->wallet;
                        $wallet->update([
                            'blocked' => $wallet->blocked - $payment->wallet_payment_amount
                        ]);
                        //update transaction status to timedOut
                        $payment->transaction->update([
                            'status_id' => $payment->transaction->status->where('slug', 'timedOut')->first()->id
                        ]);


                        $factorController = new FactorController();
                        $factor = $factor_payment_step->factor;
                        $factorController->setFactorStatus(
                            $factor->id,
                            new Request([
                                'factor_status_enum' => "customerAccept",
                                'name' => 'فاکتور تایید مشتری',
                                'description' => 'فاکتور تایید مشتری و آماده پرداخت',
                            ])
                        );

                    }
                }
            }
        }
    }
}
