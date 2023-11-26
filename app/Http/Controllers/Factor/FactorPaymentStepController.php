<?php

namespace App\Http\Controllers\Factor;

use App\Http\Controllers\Controller;
use App\Models\Factor;
use App\Models\FactorPaymentStep;
use Illuminate\Http\Request;

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
        $factor = Factor::find($request->factor_id);
        $factorTotalPrice = $factor->totalPrice();
        $resp = $factorTotalPrice->getData();
        $factorTotalPrice = $resp->data;


        $factor_payment_steps = FactorPaymentStep::query();
        if ($request->factor_id) {
            $factor_payment_steps = $factor_payment_steps->where('factor_id', $request->factor_id)->orderBy('step_number', 'asc');
        }
        $factor_payment_steps = $factor_payment_steps->get();

        $warning = "";
        //check how many steps we have
        $count = $factor_payment_steps->count();
        if ($count == 0) {
            $warning = "هیچ مرحله پرداختی تعریف نشده است";
        } else if ($count == 1) {
            //check if step 1 is not equal to factor total price
            if ($factor_payment_steps[0]->payable_price != $factorTotalPrice) {
                $warning = "قیمت قابل پرداخت مجموع مراحل با قیمت کل فاکتور برابر نیست";
            }
        } else {
            //check if step 1 + step 2 is not equal to factor total price
            if ($factor_payment_steps[0]->payable_price + $factor_payment_steps[1]->payable_price != $factorTotalPrice) {
                $warning = "قیمت قابل پرداخت مجموع مراحل با قیمت کل فاکتور برابر نیست";
            }
        }
        //response
        return response()->json([
            'data' => $factor_payment_steps,
            'message' => 'factor payment steps retrieved successfully',
            'status' => $warning == "" ? 'success' : "warning",
            'warning' => $warning,
            'factor_total_price' => $factorTotalPrice,
            'success' => true,
            'code' => 200
        ], 200);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
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
     * @param  int  $id
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

        //response
        return response()->json([
            'data' => $factor_payment_step,
            'message' => 'factor payment step retrieved successfully',
            'status' => 'success',
            'success' => true,
            'code' => 200
        ], 200);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
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
     * @param  int  $id
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
        }

        //delete
        $factor_payment_step->delete();

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
