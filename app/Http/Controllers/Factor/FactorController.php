<?php

namespace App\Http\Controllers\Factor;

use App\Http\Controllers\Controller;
use App\Models\Factor;
use App\Models\FactorItem;
use App\Models\FactorPayment;
use App\Models\FactorPaymentStep;
use App\Models\FactorStatus;
use App\Models\FactorStatusEnum;
use App\Models\Order;
use App\Models\PaymentStatus;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use stdClass;

class FactorController extends Controller
{
    /**
     * @OA\Get(
     *   path="/v1/factor",
     *   tags={"Factor"},
     *   summary="show all factors",
     *   description="show all factors with related filters if user has access or only shows user factors",
     *   @OA\Parameter(
     *     name="user_id",
     *    description="user id",
     *    in="query",
     *   required=false,
     *   @OA\Schema(
     *  type="integer"
     *  )
     * ),
     *  @OA\Parameter(
     *     name="factor_status_id",
     *    description="factor status id",
     *    in="query",
     *   required=false,
     *   @OA\Schema(
     *  type="integer"
     *  )
     * ),
     * @OA\Parameter(
     *   name="date_from",
     * description="date from",
     * in="query",
     * required=false,
     * @OA\Schema(
     * type="string",
     * format="date"
     * )
     * ),
     * @OA\Parameter(
     *  name="date_to",
     * description="date to",
     * in="query",
     * required=false,
     * @OA\Schema(
     * type="string",
     * format="date"
     * )
     * ),
     * @OA\Parameter(
     * name="page",
     * description="page",
     * in="query",
     * required=false,
     * @OA\Schema(
     * type="integer"
     * )
     * ),
     *   @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   security={{ "apiAuth": {} }}
     *)
     **/
    public function invoicesList(Request $request, $export = false)
    {

        $permissions = Auth::user()->roles->flatMap(function ($role) {
            return $role->permissions->pluck('slug')->toArray();
        })->toArray();
        $canEdit = false;

        if (in_array('can-view-all-invoices', $permissions)) {
            $filters = request()->all();
            $query = Factor::query();
            $canEdit = true;

            if (isset($filters['user_id'])) {
                //query in order groups that user is its user_id of customer of order group
                // i have only order_group_id in this table
                //the name of neede tables is order_groups and customers
                $query->whereHas('orderGroup', function ($query) use ($filters) {
                    $query->where('user_id', $filters['user_id'])->orWhereHas('customer', function ($query) use ($filters) {
                        $query->where('user_id', $filters['user_id']);
                    });
                });

                // $query->where('wallet_id', $filters['user_id']);
            }

            if (isset($filters['date_from'])) {
                $query->whereDate('created_at', '>=', Carbon::parse($filters['date_from'])->format('Y-m-d'));
            }

            if (isset($filters['date_to'])) {
                $query->whereDate('created_at', '<=', Carbon::parse($filters['date_to'])->format('Y-m-d'));
            }

            if (isset($filters['factor_status_id'])) {
                //find in factor statuses where last factor status is this factor status id
                //the neede table name is factor_statuses
                $query->whereHas('lastStatus', function ($query) use ($filters) {
                    $query->where('factor_status_enum_id', $filters['factor_status_id']);
                });
                // $query->where('status_id', $filters['factor_status_id']);
            }

            // Retrieve factors
            $factors =
                $query->orderBy('updated_at', 'DESC')->paginate(10);
        } else {
            //fetch factors that eather  user is owner of them or customer of them
            //we have only orderGroupId inside factors and we should check that order group is owned by user or customer
            $user_id = auth()->user()->id;
            $customer_id = auth()->user()->customer->id;
            $factors = Factor::whereHas('orderGroup', function ($query) use ($user_id, $customer_id) {
                $query->where('user_id', $user_id)->orWhere('customer_id', $customer_id);
            })->orderBy('updated_at', 'DESC')->paginate(10);
            // $factors = Factor::where('wallet_id', $wallet->id)->orderBy('updated_at', 'DESC')->paginate(10);
        }
        foreach ($factors as $factor) {
            $factorTotalPrice = $factor->totalPrice();
            $resp = $factorTotalPrice->getData();
            $factorTotalPrice = $resp->strictData;
            $factor->total_price = $factorTotalPrice;
            $factor->validity = $this->checkAndUpdateFactorStatus($factor->id);
            $factor->lastStatus->factorStatusEnum;
            $factor->orderGroup->customer->user;
            $factor->orderGroup->user;
            $factor->customer_full_name = "";
            if ($factor->orderGroup->customer) {
                if ($factor->orderGroup->customer->user->name) {
                    $factor->customer_full_name = $factor->orderGroup->customer->user->name . " " . $factor->orderGroup->customer->user->last_name;
                } else {
                    //mobile
                    $factor->customer_full_name = $factor->orderGroup->customer->user->mobile;
                }
            } else {
                if ($factor->orderGroup->user->name) {
                    $factor->customer_full_name = $factor->orderGroup->user->name . " " . $factor->orderGroup->user->last_name;
                } else {
                    //mobile
                    $factor->customer_full_name = $factor->orderGroup->user->mobile;
                }
            }
        }
        $pagination = $export ? [] : [
            'total' => $factors->total(),
            'count' => $factors->count(),
            'per_page' => $factors->perPage(),
            'current_page' => $factors->currentPage(),
            'total_pages' => $factors->lastPage(),
        ];

        $accessAll = in_array('can-view-all-invoices', $permissions);

        //remove factor_items from each factor
        foreach ($factors as $factor) {
            unset($factor->factorItems);
        }
        $filters = request()->all(); //except page
        unset($filters['page']);
        $finalResult = [];
        foreach ($factors as $factor) {
            $finalResult[] = $factor;
        }
        return response()->json([
            'factors' => $finalResult,
            'accessAll' => $accessAll,
            'pagination' => $pagination,
            'filters' => $filters,
            'canEdit' => $canEdit
        ], 200);
    }

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

            //check factor validity
            // $factor_status = FactorStatus::create([
            //     'factor_id' => $factor_id,
            //     'factor_status_enum_id' => $currectStatusEnum->id,
            //     'name' => 'فاکتور در انتظار مشتری',
            //     'description' => 'فاکتور در انتظار مشتری به علت اضافه شدن ایتم',
            //     'meta' => json_encode($changesMeta),
            // ]);
            //TODO: notify user

        }
        $checkValidity = $this->checkAndUpdateFactorStatus($factor_id);
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
                'unit_price' => 'numeric|required',
                'off_price' => 'numeric|nullable',
                'additional_price' => 'numeric|nullable',
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
                'unit_price' => 'string|required',
                'off_price' => 'string|nullable',
                'additional_price' => 'string|nullable',
                'description' => 'string|nullable',
            ]);
        }

        //check if count type is m2, check if width and height are sent
        if ($request->count_type && $request->count_type == "m2") {
            if (!$request->width || !$request->height) {
                return response()->json(['message' => __("validation.custom.factor.required_width_and_height")], 400);
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

                // $factor_status = FactorStatus::create([
                //     'factor_id' => $factor_id,
                //     'factor_status_enum_id' => $currectStatusEnum->id,
                //     'name' => 'فاکتور در انتظار مشتری',
                //     'description' => 'فاکتور در انتظار مشتری به علت تغییر در ایتم ها',
                //     'meta' => json_encode($changesMeta),
                // ]);
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


        $checkValidity = $this->checkAndUpdateFactorStatus($factor_id);

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

            // $factor_status = FactorStatus::create([
            //     'factor_id' => $factor_id,
            //     'factor_status_enum_id' => $currectStatusEnum->id,
            //     'name' => 'فاکتور در انتظار مشتری',
            //     'description' => 'فاکتور در انتظار مشتری به علت حذف شدن ایتم',
            //     'meta' => json_encode($changesMeta),
            // ]);
            //TODO: notify user

        }

        $checkValidity = $this->checkAndUpdateFactorStatus($factor_id);
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
     *  path="/v1/factor/{factor_code}",
     * tags={"Factor"},
     * summary="view factor",
     * @OA\Parameter(
     * name="factor_code",
     * in="path",
     * required=true,
     * @OA\Schema(
     * type="string",
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
    public function show($factor_code)
    {
        $factor = Factor::where('code', $factor_code)->first();
        $factorOwner = false;
        if (!$factor) {
            return response()->json(['message' => 'factor not found'], 404);
        }

        $permissions = Auth::user()->roles->flatMap(function ($role) {
            return $role->permissions->pluck('slug')->toArray();
        })->toArray();

        $factor_user_id = $factor->orderGroup->user_id;
        $factor_customer_id = $factor->orderGroup->customer_id;
        $user_id = auth()->user()->id;
        $user_customer_id = auth()->user()->customer->id;
        if ($factor_user_id == $user_id || $factor_customer_id == $user_customer_id) {
            $factorOwner = true;
        }

        //check if user has permission "can-view-all-invoices"
        if (!in_array("can-view-all-invoices", $permissions)) {
            //check if user is owner of this factor
            //check if user_id or customer_id is equal to auth user id
            if ($factor_user_id != $user_id && $factor_customer_id != $user_customer_id) {
                return response()->json(['message' => 'you dont have permission to view this factor'], 403);
            }
        }
        $this->checkAndUpdateFactorStatus($factor->id);
        // $factor->factorPaymentSteps;
        $factor->lastStatus->factorStatusEnum;
        $factor->factorItems;
        $factor->orderGroup->customer->user;
        $factor->orderGroup->user;
        $factor->customer_full_name = "";
        if ($factor->orderGroup->customer) {
            if ($factor->orderGroup->customer->user->name) {
                $factor->customer_full_name = $factor->orderGroup->customer->user->name . " " . $factor->orderGroup->customer->user->last_name;
            } else {
                //mobile
                $factor->customer_full_name = $factor->orderGroup->customer->user->mobile;
            }
        } else {
            if ($factor->orderGroup->user->name) {
                $factor->customer_full_name = $factor->orderGroup->user->name . " " . $factor->orderGroup->user->last_name;
            } else {
                //mobile
                $factor->customer_full_name = $factor->orderGroup->user->mobile;
            }
        }
        $allStatuses = FactorStatus::where('factor_id', $factor->id)->orderBy("id", "desc")->get();
        // $factor->statuses = [];
        $statArray = [];
        // [{"modifiedType":"field","fieldName":"width","fieldId":27,"oldValue":"12","newValue":"10","user":1,"form":14,"order":83}]
        foreach ($allStatuses as $status) {
            $tmp = new stdClass();
            $jsonArray = json_decode($status->meta);
            if ($jsonArray) {
                foreach ($jsonArray as $json) {
                    // $tmp->meta = $json;
                    $tmp->modifiedType = $json->modifiedType ?? null;
                    $tmp->fieldName = $json->fieldName ?? null;
                    // $tmp->fieldId = $json->fieldId;
                    $tmp->oldValue = $json->oldValue ?? null;
                    $tmp->newValue = $json->newValue ?? null;
                    // $tmp->user = $json->user;
                    // $tmp->form = $json->form;
                    // $tmp->order = $json->order;
                    $user = User::find($json->user ?? null);
                    $order = Order::find($json->order ?? null);
                    $tmp->user_full_name = $user ? $user->name ? $user->name . " " . $user->last_name : $user->mobile : null;
                    $tmp->product = $order ? $order->product->name : null;
                    $tmp->created_at = $status->created_at;
                    $statusEnum = FactorStatusEnum::find($status->factor_status_enum_id);
                    $tmp->status = $statusEnum;
                    array_push($statArray, $tmp);
                }
            }
        }
        $factor->statuses = $statArray;
        $factor->owner = $factorOwner;

        //find first payment step of factor
        $first_payment_step = $factor->factorPaymentSteps->where("step_number", 1)->first();
        if ($first_payment_step) {
            $factor->expire_date = $first_payment_step->pay_time;
        } else {
            $factor->expire_date = null;

        }
        //return factor
        return response()->json([
            'data' => $factor,
            'message' => 'factor retrieved successfully',
            'success' => true,
            'st' => $first_payment_step,
            'code' => 200
        ], 200);
    }

    function checkCustomerAccept($factor_id)
    {
        $customerAcceptEnumId = FactorStatusEnum::where('slug', 'customerAccept')->first()->id;
        $customerAcceptExists = FactorStatus::where('factor_id', $factor_id)->where('factor_status_enum_id', $customerAcceptEnumId)->first();
        return $customerAcceptExists;
    }

    //get factor statuses
    //write annotation
    /**
     * @OA\Get(
     *  path="/v1/factor/status",
     * tags={"Factor"},
     * summary="get factor statuses",
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

    public function getFactorStatuses()
    {
        $factorStatuses = FactorStatusEnum::all();
        return response()->json(['statuses' => $factorStatuses], 200);
    }

    //update factor
    //write annotation
    /**
     * @OA\Put(
     *  path="/v1/factor/{factor_id}",
     * tags={"Factor"},
     * summary="update factor",
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
     * required={"expire_date","description"},
     * @OA\Property(property="expire_date", type="string", format="string", example="2021-09-01"),
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

    public function update($factor_id, Request $request)
    {
        //validate
        $request->validate([
            'expire_date' => 'required',
            'description' => 'string|nullable',
        ]);
        //find factor
        $factor = Factor::find($factor_id);
        if (!$factor) {
            return response()->json(['message' => 'factor not found'], 404);
        }
        //update
        $factor->update([
            'expire_date' => $request->expire_date,
            'description' => $request->description,
        ]);
        return response()->json($factor, 200);
    }

    public function checkAndUpdateFactorStatus($factor_id)
    {
        $factorPaymentStepController = new FactorPaymentStepController();
        $factorPaymentSteps = $factorPaymentStepController->index(new Request(['factor_id' => $factor_id]));
        $factorValidity = $factorPaymentSteps->getData()->status;
        $lastStatusEnum = Factor::find($factor_id)->lastStatus->factorStatusEnum;
        $factorStatus = null;
        $validity = false;
        if ($factorValidity != 'success') {
            if (
                $lastStatusEnum->slug == "salesPending" ||
                $lastStatusEnum->slug == "completePending" ||
                $lastStatusEnum->slug == "salesResubmitPending"
            ) {
            } else {
                $factorStatus = $this->setFactorStatus(
                    $factor_id,
                    new Request([
                        'factor_status_enum' => "completePending",
                        'name' => 'فاکتور در انتظار تکمیل',
                        'description' => 'فاکتور در انتظار تکمیل',
                    ])
                );
            }
        } else {
            //now we can check if the factor expire time exceeded if factor not paid
            $factor = Factor::find($factor_id);
            $price = $factor->totalPrice(true, true);
            $resp = $price->getData();
            if ($resp->data > $resp->paid) {
                $now = Carbon::now();
                if ($now->gt($factor->expire_date)) {
                    if (
                        $lastStatusEnum->slug != "salesResubmitPending"
                    ) {
                        $factorStatus = $this->setFactorStatus(
                            $factor_id,
                            new Request([
                                'factor_status_enum' => "salesResubmitPending",
                                'name' => 'فاکتور در انتظار تایید مجدد واحد فروش',
                                'description' => 'فاکتور در انتظار تایید مجدد واحد فروش به علت منقضی شدن',
                            ])
                        );
                    }
                } else {
                    $hasUnpaidExpiredPayment = false;
                    $paidStatus = PaymentStatus::where("slug", "paid")->first();
                    //check each unpaid payment step expire date
                    $paymentSteps = FactorPaymentStep::where("factor_id", $factor_id)->where("pay_time", "<", Carbon::now())->get();
                    if (count($paymentSteps) > 0) {
                        $hasUnpaidExpiredPayment = true;
                        foreach ($paymentSteps as $step) {
                            $hasUnpaidExpiredPayment = true;
                            $payments = FactorPayment::where("payment_step_id", $step)->where("payment_status_id", $paidStatus->id)->count();
                            if ($payments > 0) {
                                $hasUnpaidExpiredPayment = false;
                            }
                        }
                    }
                    if ($hasUnpaidExpiredPayment) {
                        if (
                            $lastStatusEnum->slug != "salesResubmitPending"
                        ) {
                            $factorStatus = $this->setFactorStatus(
                                $factor_id,
                                new Request([
                                    'factor_status_enum' => "salesResubmitPending",
                                    'name' => 'فاکتور در انتظار تایید مجدد واحد فروش',
                                    'description' => 'فاکتور در انتظار تایید مجدد واحد فروش به علت عدم پرداخت در تاریخ مقرر',
                                ])
                            );
                        }
                    } else {
                        $validity = true;
                    }
                }
            } else {
                $validity = true;
            }
        }


        return [
            'validity' => $validity,
            'factorStatus' => $factorStatus,
            'gt' => Carbon::now()->gt(Factor::find($factor_id)->expire_date),
            'last' => $lastStatusEnum,
            'now' => Carbon::now(),
//            'exp'=>$factor->expire_date,
//            'gt'=>$now->gt($factor->expire_date),
//            'ps'=>$factorPaymentSteps->getData()

        ];
    }

    //acceptFactor
    //write annotation
    /**
     * @OA\Post(
     *  path="/v1/factor/{factor_id}/accept",
     * tags={"Factor"},
     * summary="accept factor",
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
     *   response=201,
     *  description="Success",
     * @OA\MediaType(
     * mediaType="application/json
     * "),
     * ),
     * security={{ "apiAuth": {} }}
     * )
     * )
     */

    public function acceptFactor($factor_id)
    {
        //find factor
        $factor = Factor::find($factor_id);
        if (!$factor) {
            return response()->json(['message' => 'factor not found'], 404);
        }
        //check if factor is valid
        $resp = $this->checkAndUpdateFactorStatus($factor_id);
        $factorValidity = $resp["validity"];
        if (!$factorValidity) {
            return response()->json(['message' => "تاریخ سررسید فاکتور یا مراحل پرداخت را چک کنید", "re" => $resp], 400);
        }
        $lastStatusEnum = $factor->lastStatus->factorStatusEnum;
        if (
            $lastStatusEnum->slug !== 'customerPending' &&
            $lastStatusEnum->slug != 'customerResubmitPending'
        ) {
            //set factor status
            $this->setFactorStatus(
                $factor_id,
                new Request([
                    'factor_status_enum' => "customerPending",
                    'name' => 'فاکتور تایید شده توسط مالی',
                    'description' => 'فاکتور تایید شده توسط مالی',
                ])
            );
        }

        //return
        return response()->json(['message' => 'factor accepted'], 201);
    }

    //acceptFactorByCustomer
    //write annotation
    /**
     * @OA\Post(
     *  path="/v1/factor/{factor_id}/acceptByCustomer",
     * tags={"Factor"},
     * summary="accept factor by customer",
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
     *   response=201,
     *  description="Success",
     * @OA\MediaType(
     * mediaType="application/json
     * "),
     * ),
     * security={{ "apiAuth": {} }}
     * )
     * )
     */

    public function acceptFactorByCustomer($factor_id)
    {
        //find factor
        $factor = Factor::find($factor_id);
        if (!$factor) {
            return response()->json(['message' => 'factor not found'], 404);
        }
        $factorTotalPrice = $factor->totalPrice(true, true);
        $resp = $factorTotalPrice->getData();
        $paidPrice = $resp->paid;
        $totalPrice = $resp->data;
        if (!$resp->success) {
            return response()->json(['message' => 'factor is not complete'], 422);
        }
        //check if user owner or customer of factor
        $user_id = auth()->user()->id;
        $factor_user_id = $factor->orderGroup->user_id;
        $factor_customer_id = $factor->orderGroup->customer_id;
        if ($factor_user_id != $user_id && $factor_customer_id != $user_id) {
            return response()->json(['message' => 'you dont have permission to accept this factor'], 403);
        }
        //check if factor is valid
        $resp = $this->checkAndUpdateFactorStatus($factor_id);
        $factorValidity = $resp["validity"];
        if (!$factorValidity) {
            return response()->json(['message' => 'factor is not valid'], 400);
        }
        $lastStatusEnum = $factor->lastStatus->factorStatusEnum;
        if (
            $lastStatusEnum->slug !== 'paymentPending'
        ) {
            //check if customer paid all factor or not
            if ($paidPrice >= $totalPrice) {
                //set factor status
                $this->setFactorStatus(
                    $factor_id,
                    new Request([
                        'factor_status_enum' => "paymentSuccess",
                        'name' => 'فاکتور تایید شده توسط مشتری و پرداخت شده',
                        'description' => 'فاکتور تایید شده توسط مشتری و پرداخت شده',
                    ])
                );
            } else {
                //set factor status
                $this->setFactorStatus(
                    $factor_id,
                    new Request([
                        'factor_status_enum' => "paymentPending",
                        'name' => 'فاکتور تایید شده توسط مشتری',
                        'description' => 'فاکتور تایید شده توسط مشتری',
                    ])
                );
            }
        }

        //return
        return response()->json(['message' => 'factor accepted'], 201);
    }
}
