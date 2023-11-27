<?php

namespace App\Http\Controllers\Factor;

use App\Http\Controllers\Controller;
use App\Models\Factor;
use App\Models\FactorItem;
use App\Models\FactorStatus;
use App\Models\FactorStatusEnum;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class FactorController extends Controller
{
    //create factor with fields: code	order_group_id	expire_date	description	

    /**
     * @OA\Post(
     *  path="/v1/factor",
     * tags={"Factor"},
     * summary="create factor",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"order_group_id","expire_date","description"},
     * @OA\Property(property="order_group_id", type="string", format="string", example="1"),
     * @OA\Property(property="expire_date", type="string", format="string", example="2021-09-01"),
     * @OA\Property(property="description", type="string", format="string", example="description"),
     * ),
     * ),
     * @OA\Response(
     *   response=200,
     *  description="Success",
     * @OA\MediaType(
     * mediaType="application/json",
     * ),
     * ),
     * security={{ "apiAuth": {} }}
     * )
     * )
     */
    public function store(Request $request)
    {
        //vaalidate
        $request->validate([
            'order_group_id' => 'required|exists:order_groups,id',
            'expire_date' => 'required',
            'description' => 'string|nullable',
        ]);
        //create factor but create code randomly
        $factor = Factor::create([
            'code' => //NIPA + order group id+ "_" + user id +"_"+timestamp unix
                "NIPA_" . $request->order_group_id . "_" . auth()->user()->id . "_" . time(),
            'order_group_id' => $request->order_group_id,
            'expire_date' => $request->expire_date,
            'description' => $request->description,
        ]);
        return response()->json($factor, 201);
    }

    //store factor items : factor_id	order_id?	product_id?	code?	name?	count_type?	width?	height?	count?	unit_price?	off_price?	additional_price?	description?
    //? at any item means it is not required
    //write annotation
    /**
     * @OA\Post(
     *  path="/v1/factor/{factor_id}/factorItem",
     * tags={"Factor"},
     * summary="create factor item",
     * @OA\Parameter(
     * name="factor_id",
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
     * required={"code","name","count_type","width","height","count","unit_price","off_price","additional_price","description"},
     * @OA\Property(property="code", type="string", format="string", example="code"),
     * @OA\Property(property="name", type="string", format="string", example="name"),
     * @OA\Property(property="count_type", type="string", format="string", example="count_type"),
     * @OA\Property(property="width", type="string", format="string", example="width"),
     * @OA\Property(property="height", type="string", format="string", example="height"),
     * @OA\Property(property="count", type="string", format="string", example="count"),
     * @OA\Property(property="unit_price", type="string", format="string", example="unit_price"),
     * @OA\Property(property="off_price", type="string", format="string", example="off_price"),
     * @OA\Property(property="additional_price", type="string", format="string", example="additional_price"),
     * @OA\Property(property="description", type="string", format="string", example="description"),
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
    public function storeFactorItem($factor_id, Request $request)
    {
        //validate
        $request->validate([
            // 'order_id' => 'nullable|exists:orders,id',
            // 'product_id' => 'nullable|exists:products,id',
            'code' => 'string|nullable',
            'name' => 'string|nullable',
            'count_type' => 'string|nullable',
            'width' => 'string|nullable',
            'height' => 'string|nullable',
            'count' => 'integer|nullable',
            'unit_price' => 'integer|nullable',
            'off_price' => 'integer|nullable',
            'additional_price' => 'integer|nullable',
            'description' => 'string|nullable',
        ]);
        //check that just one of the order_id or product_id or (code, name, count type ) must be sent
        if ($request->order_id && $request->product_id) {
            return response()->json(['message' => 'just one of the order_id or product_id must be sent'], 400);
        }
        if ($request->order_id && ($request->code || $request->name || $request->count_type)) {
            return response()->json(['message' => 'just one of the order_id or (code, name, count type ) must be sent'], 400);
        }
        if ($request->product_id && ($request->code || $request->name || $request->count_type)) {
            return response()->json(['message' => 'just one of the product_id or (code, name, count type ) must be sent'], 400);
        }
        //set default vars
        $count_type = $request->count_type;
        $product = null;
        $order = null;
        $code = $request->code;
        $name = $request->name;

        $width = $request->width;
        $height = $request->height;
        $count = $request->count ? $request->count : 1;
        $unit_price = $request->unit_price ? $request->unit_price : 0;



        //if (code, name, count type ) was sent, check that all of them are filled
        if ($request->code || $request->name || $request->count_type) {
            if (!$request->code || !$request->name || !$request->count_type) {
                return response()->json(['message' => 'code, name, count type must be filled'], 400);
            }
        }

        //if product_id, find product and get count_type
        if ($request->product_id) {
            $product = Product::find($request->product_id);
            if (!$product) {
                return response()->json(['message' => 'product not found'], 404);
            }
            $count_type = $product->count_type;
        }
        // //if order_id, find order and then find product and get count_type
        if ($request->order_id) {
            $order = Order::find($request->order_id);
            if (!$order) {
                return response()->json(['message' => 'order not found'], 404);
            }
            $product = $order->product;
            if (!$product) {
                return response()->json(['message' => 'product not found'], 404);
            }
            $count_type = $product->count_type;
        }


        //validate count_type
        if ($count_type != "m2" && $count_type != "quantity") {
            return response()->json(['message' => 'count type must be m2 or quantity'], 400);
        }


        if ($count_type == "m2") {
            if ($order) {
                //find initial step 
                $initialForm = $product->initialOrderForm();
                //fields
                $fields = $initialForm->fields;
                //find width and height fields
                $widthField = $this->findObjectByKey($fields, "name", "width");
                $heightField = $this->findObjectByKey($fields, "name", "height");
                //find answers with order id and form field id
                $width = $order->userAnswers()->where('form_field_id', $widthField->id)->first()->answer;
                $height = $order->userAnswers()->where('form_field_id', $heightField->id)->first()->answer;
            } else {
                //check if request sent width and height
                if (!$request->width || !$request->height) {
                    return response()->json(['message' => 'width and height must be sent'], 400);
                }
                $width = $request->width;
                $height = $request->height;
            }
        }
        // fill count if we have order id 
        if ($order) {
            $count = $order->count;
        }
        // fill unit price if we have product id and count type is quantity
        if ($product && $count_type == "quantity") {
            $unit_price = $product->details[0]->price;
        }

        if ($product) {
            $code = $product->code;
            $name = $product->name;
        }

        //create factor item
        $factor_item = FactorItem::create([
            'factor_id' => $factor_id,
            'order_id' => $request->order_id,
            'product_id' => $request->product_id,
            'code' => $code,
            'name' => $name,
            'count_type' => $count_type,

            'width' => $request->width ? $request->width : $width,
            'height' => $request->height ? $request->height : $height,
            'count' => $request->count ? $request->count : $count,
            'unit_price' => $request->unit_price ? $request->unit_price : $unit_price,

            'off_price' => $request->off_price,
            'additional_price' => $request->additional_price,
            'description' => $request->description,
        ]);

        //set log if added item doesnt have order_id or product_id
        $customerAcceptExists = $this->checkCustomerAccept($factor_id);
        if ($customerAcceptExists) {
            $changesMeta = array();
            $user = Auth::user();
            $changesMeta[] = [
                "modifiedType" => "addItem",
                "factorItemId" => $factor_item->id,
                "user" => $user->id,
            ];

            $currectStatusEnum = FactorStatusEnum::where('slug', 'customerResubmitPending')->first();

            $factor_status = FactorStatus::create([
                'factor_id' => $factor_id,
                'factor_status_enum_id' => $currectStatusEnum->id,
                'name' => 'فاکتور در انتظار مشتری',
                'description' => 'فاکتور در انتظار مشتری به علت اضافه شدن ایتم',
                'meta' => json_encode($changesMeta),
            ]);
            //TODO: notify user

        }
        return response()->json($factor_item, 201);
    }

    //update factor item
    //write annotation
    /**
     * @OA\Put(
     *  path="/v1/factor/{factor_id}/factorItem/{factor_item_id}",
     * tags={"Factor"},
     * summary="update factor item",
     * @OA\Parameter(
     * name="factor_id",
     * in="path",
     * required=true,
     * @OA\Schema(
     * type="integer",
     * format="int64"
     * )
     * ),
     * @OA\Parameter(
     * name="factor_item_id",
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
     * required={"code","name","count_type","width","height","count","unit_price","off_price","additional_price","description"},
     * @OA\Property(property="code", type="string", format="string", example="code"),
     * @OA\Property(property="name", type="string", format="string", example="name"),
     * @OA\Property(property="count_type", type="string", format="string", example="count_type"),
     * @OA\Property(property="width", type="string", format="string", example="width"),
     * @OA\Property(property="height", type="string", format="string", example="height"),
     * @OA\Property(property="count", type="string", format="string", example="count"),
     * @OA\Property(property="unit_price", type="string", format="string", example="unit_price"),
     * @OA\Property(property="off_price", type="string", format="string", example="off_price"),
     * @OA\Property(property="additional_price", type="string", format="string", example="additional_price"),
     * @OA\Property(property="description", type="string", format="string", example="description"),
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

    public function updateFactorItem($factor_id, $factor_item_id, Request $request)
    {
        //find factor id
        $factor = Factor::find($factor_id);
        if (!$factor) {
            return response()->json(['message' => 'factor not found'], 404);
        }
        //find factor item
        $factor_item = FactorItem::find($factor_item_id);
        $old_factor_item = FactorItem::find($factor_item_id);
        if (!$factor_item) {
            return response()->json(['message' => 'factor item not found'], 404);
        }
        //check if factor item is for this factor
        if ($factor_item->factor_id != $factor_id) {
            return response()->json(['message' => 'factor item is not for this factor'], 400);
        }

        //reject iif factor item has order id or product id, those are uneditable
        if ($factor_item->order_id || $factor_item->product_id) {
            //in this situation we can just update (unit_price , off_price, additional_price , description)
            $request->validate([
                'unit_price' => 'integer|required',
                'off_price' => 'integer|nullable',
                'additional_price' => 'integer|nullable',
                'description' => 'string|nullable',
            ]);
        } else {
            //here we can change everything but only  (unit_price ) is required
            $request->validate([
                'code' => 'string|nullable',
                'name' => 'string|nullable',
                'count_type' => 'string|in:m2,quantity|nullable',
                'width' => 'string|nullable',
                'height' => 'string|nullable',
                'count' => 'integer|nullable',
                'unit_price' => 'integer|required',
                'off_price' => 'integer|nullable',
                'additional_price' => 'integer|nullable',
                'description' => 'string|nullable',
            ]);
        }

        //check if count type is m2, check if width and height are sent
        if ($request->count_type && $request->count_type == "m2") {
            if (!$request->width || !$request->height) {
                return response()->json(['message' => 'width and height must be sent'], 400);
            }
        } else {
            $request->width = null;
            $request->height = null;
        }

        if ($factor_item->order_id || $factor_item->product_id) {
            //update
            $factor_item->update([
                'unit_price' => $request->unit_price ?? $factor_item->unit_price,
                'off_price' => $request->off_price ?? $factor_item->off_price,
                'additional_price' => $request->additional_price ?? $factor_item->additional_price,
                'description' => $request->description ?? $factor_item->description,
            ]);
        } else {
            //update
            $factor_item->update([
                'code' => $request->code ?? $factor_item->code,
                'name' => $request->name ?? $factor_item->name,
                'count_type' => $request->count_type ?? $factor_item->count_type,
                'width' => $request->width ?? $factor_item->width,
                'height' => $request->height ?? $factor_item->height,
                'count' => $request->count ?? $factor_item->count,
                'unit_price' => $request->unit_price ?? $factor_item->unit_price,
                'off_price' => $request->off_price ?? $factor_item->off_price,
                'additional_price' => $request->additional_price ?? $factor_item->additional_price,
                'description' => $request->description ?? $factor_item->description,
            ]);
        }
        //also need to update status of factor if user at least one time accepted this factor
        $lastFactorStatus = $factor->lastStatus;
        $lastStatusEnum = $lastFactorStatus->factorStatusEnum;
        $customerAcceptExists = $this->checkCustomerAccept($factor_id);
        if ($customerAcceptExists) {
            $changesMeta = array();
            //map request
            $req = $request->all();
            $user = Auth::user();
            foreach ($req as $key => $value) {
                //check if sent value has changed from old value
                if ($old_factor_item[$key] != $factor_item[$key]) {
                    //if changed, add to changes meta
                    $changesMeta[] = [
                        "modifiedType" => "factorItem",
                        "factorItemId" => $factor_item->id,
                        "fieldName" => $key,
                        "oldValue" => $old_factor_item[$key],
                        "newValue" => $factor_item[$key],
                        "user" => $user->id,
                    ];
                }
            }
            //find last factor status

            $currectStatusEnum = null;
            if ($lastStatusEnum->slug == "customerPending") {
                $currectStatusEnum = FactorStatusEnum::where('slug', 'customerResubmitPending')->first();
            } else {
                $currectStatusEnum = FactorStatusEnum::where('slug', 'customerPending')->first();
            }
            //update factor status if json meta filled
            if (count($changesMeta) > 0) {

                $factor_status = FactorStatus::create([
                    'factor_id' => $factor_id,
                    'factor_status_enum_id' => $currectStatusEnum->id,
                    'name' => 'فاکتور در انتظار مشتری',
                    'description' => 'فاکتور در انتظار مشتری به علت تغییر در ایتم ها',
                    'meta' => json_encode($changesMeta),
                ]);
                //TODO:notify user
            }
        } else {
            // if ($lastStatusEnum !== "customerPending") {
            //     $factor_status = FactorStatus::create([
            //         'factor_id' => $factor_id,
            //         'factor_status_enum_id' => FactorStatusEnum::where('slug', 'customerPending')->first()->id,
            //         'name' => 'فاکتور در انتظار مشتری',
            //         'description' => 'فاکتور در انتظار مشتری',
            //     ]);
            // }
            //this is wrong because mabe factor is not ready to accept by user
            //we should handle it by call-to-acton for admin in FRONT
        }



        return response()->json($factor_item, 200);

    }

    //delete factor item
    //write annotation
    /**
     * @OA\Delete(
     *  path="/v1/factor/{factor_id}/factorItem/{factor_item_id}",
     * tags={"Factor"},
     * summary="delete factor item",
     * @OA\Parameter(
     * name="factor_id",
     * in="path",
     * required=true,
     * @OA\Schema(
     * type="integer",
     * format="int64"
     * )
     * ),
     * @OA\Parameter(
     * name="factor_item_id",
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
    public function destroyFactorItem($factor_id, $factor_item_id)
    {
        //find factor id
        $factor = Factor::find($factor_id);
        if (!$factor) {
            return response()->json(['message' => 'factor not found'], 404);
        }
        //find factor item
        $factor_item = FactorItem::find($factor_item_id);
        if (!$factor_item) {
            return response()->json(['message' => 'factor item not found'], 404);
        }
        //check if factor item is for this factor
        if ($factor_item->factor_id != $factor_id) {
            return response()->json(['message' => 'factor item is not for this factor'], 400);
        }
        //prevent to delete if factor item has order id or product id
        if ($factor_item->order_id || $factor_item->product_id) {
            return response()->json(['message' => 'این ایتم قابل حذف نیست'], 400);
        }

        //delete
        $factor_item->delete();

        //also need to update status of factor if user at least one time accepted this factor
        $customerAcceptExists = $this->checkCustomerAccept($factor_id);
        if ($customerAcceptExists) {
            $changesMeta = array();
            $user = Auth::user();
            $changesMeta[] = [
                "modifiedType" => "removeItem",
                "factorItemId" => $factor_item->id,
                "factorItemName" => $factor_item->name,
                "factorItemCode" => $factor_item->code,
                "factorItemUnitPrice" => $factor_item->unit_price,
                "user" => $user->id,
            ];

            $currectStatusEnum = FactorStatusEnum::where('slug', 'customerResubmitPending')->first();

            $factor_status = FactorStatus::create([
                'factor_id' => $factor_id,
                'factor_status_enum_id' => $currectStatusEnum->id,
                'name' => 'فاکتور در انتظار مشتری',
                'description' => 'فاکتور در انتظار مشتری به علت حذف شدن ایتم',
                'meta' => json_encode($changesMeta),
            ]);
            //TODO: notify user

        }

        return response()->json(['message' => 'factor item deleted'], 200);
    }

    function findObjectByKey($array, $key, $id)
    {

        foreach ($array as $element) {
            if ($id == $element[$key]) {
                return $element;
            }
        }

        return false;
    }

    //set factor status : factor_id	factor_status_enum_id	name	description
//write annotation
    /**
     * @OA\Post(
     *  path="/v1/factor/{factor_id}/factorStatus",
     * tags={"Factor"},
     * summary="set factor status",
     * @OA\Parameter(
     * name="factor_id",
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
     * required={"factor_status_enum_id","name","description"},
     * @OA\Property(property="factor_status_enum", type="string", format="string", example="salesPending"),
     * @OA\Property(property="name", type="string", format="string", example="name"),
     * @OA\Property(property="description", type="string", format="string", example="description"),
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
    public function setFactorStatus($factor_id, Request $request)
    {
        //validate
        $request->validate([
            'factor_status_enum' => 'required|exists:factor_status_enums,slug',
            'name' => 'string|nullable',
            'description' => 'string|nullable',
        ]);
        //create factor status
        $factor_status = FactorStatus::create([
            'factor_id' => $factor_id,
            'factor_status_enum_id' => FactorStatusEnum::where('slug', $request->factor_status_enum)->first()->id,
            'name' => $request->name,
            'description' => $request->description,
            'meta' => $request->meta ? $request->meta : null,
        ]);
        //TODO:notify user or admin
        return response()->json($factor_status, 201);
    }

    //view factor
//write annotation
    /**
     * @OA\Get(
     *  path="/v1/factor/{factor_id}",
     * tags={"Factor"},
     * summary="view factor",
     * @OA\Parameter(
     * name="factor_id",
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
    public function show($factor_id)
    {
        $factor = Factor::find($factor_id);
        if (!$factor) {
            return response()->json(['message' => 'factor not found'], 404);
        }
       
        $permissions = Auth::user()->roles->flatMap(function ($role) {
            return $role->permissions->pluck('slug')->toArray();
        })->toArray();
      
        //check if user has permission "can-view-all-invoices"
        if (!in_array("can-view-all-invoices", $permissions)) {
            //check if user is owner of this factor
            //check if user_id or customer_id is equal to auth user id
            $factor_user_id = $factor->orderGroup->user_id;
            $factor_customer_id = $factor->orderGroup->customer_id;
            $user_id = auth()->user()->id;
            $user_customer_id = auth()->user()->customer->id;
            if ($factor_user_id != $user_id && $factor_customer_id != $user_customer_id) {
                return response()->json(['message' => 'you dont have permission to view this factor'], 403);
            }
        }
        // $factor->factorPaymentSteps;
        $factor->lastStatus->factorStatusEnum;
        $factor->factorItems;
        //return factor
        return response()->json([
            'data' => $factor,
            'message' => 'factor retrieved successfully',
            'success' => true,
            'code' => 200
        ], 200);
    }

    function checkCustomerAccept($factor_id)
    {
        $customerAcceptEnumId = FactorStatusEnum::where('slug', 'customerAccept')->first()->id;
        $customerAcceptExists = FactorStatus::where('factor_id', $factor_id)->where('factor_status_enum_id', $customerAcceptEnumId)->first();
        return $customerAcceptExists;
    }
}




