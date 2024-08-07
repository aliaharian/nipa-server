<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Factor\FactorController;
use App\Http\Controllers\Form\FormController;
use App\Http\Controllers\User\UserAnswerController;
use App\Models\BasicDataItem;
use App\Models\Customer;
use App\Models\Factor;
use App\Models\FormFieldOptions;
use App\Models\Order;
use App\Models\File;
use App\Models\OrderGroup;
use App\Models\OrderState;
use App\Models\Product;
use App\Models\ProductStep;
use App\Models\ProductStepsCondition;
use App\Models\ProductStepsRole;
use App\Models\RejectedOrderDetail;
use App\Models\UserAnswer;
use App\Models\UsersRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use stdClass;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    //orders list annotation
    /**
     * @OA\Get(
     * path="/v1/order",
     * tags={"Order"},
     * summary="list of orders",
     * @OA\Response(
     *  response=200,
     * description="Success",
     * @OA\MediaType(
     * mediaType="application/json",
     * ),
     * ),
     * security={{ "apiAuth": {} }}
     * )
     * )
     */
    public function index()
    {
        $lang = App::getLocale();

        $additional_cols = [];
        $listPermissions = new stdClass();

        //check if user role is admin
        $user = Auth::user();
        $roles = $user->roles;
        $user_role_ids = $roles->pluck('id')->toArray(); // Use pluck and toArray to get the IDs

        //permissions
        $permissions = $user->roles->flatMap(function ($role) {
            return $role->permissions->pluck('slug')->toArray();
        })->toArray();

        $orders = Order::orderBy('id', 'desc')->get();

        //if manage orders exist in permissions
        if (
            in_array('view-orders', $permissions)
        ) {
            $ordersPure = Order::orderBy('id', 'desc')->get();

            $listPermissions->canEdit = in_array('edit-orders', $permissions);
            $listPermissions->canDelete = in_array('delete-orders', $permissions);
            $listPermissions->canCompelete = in_array('compelete-orders', $permissions);
        } else {
            //my orders
            $my_orders = Order::where('user_id', $user->id)->orderBy('id', 'desc')->get();
            $customerId = Customer::where("user_id", $user->id)->first()->id;
            //my orders that i am their customer in order_group
            $my_orders_customer = Order::whereHas('orderGroup', function ($query) use ($customerId) {
                $query->where('customer_id', $customerId);
            })->get();

            $accessible_orders = collect();

            //orders that I have access
            foreach ($orders as $order) {
                $has_access = false;
                $current_step = $order->product_step_id;
                $step_verified_roles = ProductStepsRole::where("product_step_id", $current_step)->get();
                // Check if user has access to this step
                foreach ($step_verified_roles as $step_verified_role) {
                    if (in_array($step_verified_role->role_id, $user_role_ids)) {
                        $has_access = true;
                        break;  // Break the loop as we found a matching role
                    }
                }
                if ($has_access) {
                    $accessible_orders->push($order);
                }
            }
            $ordersPure = $my_orders->merge($accessible_orders)->merge($my_orders_customer);
        }

        foreach ($ordersPure as $or) {
            $or->orderGroup;
            $or->product;
            $or->step;
            $or->state;

            if ($or->step) {
                $or->step->globalStep;
                $or->step->roles;

                //find default next step
                $nextStep = $this->findNextStep($or);

                $or->nextStep = $nextStep;
                $or->canEdit = $or->step->globalStep->description == "initialOrder" ? true : false;
                $or->canDelete = $or->step->globalStep->description == "initialOrder" ? true : false;
            }
        }
        $forms = [];
        foreach ($orders as $order) {
            foreach ($order->product->steps as $step) {
                //check if step is first step, find it from meta column
                if ($step->meta && json_decode($step->meta)->first_step == true) {
                    $first_step_id = $step->id;
                    $forms = $step->forms;
                    break;
                }
            }


            //make additional cols
            $additional_data = [];
            $additional_cols = [];
            foreach ($forms as $form) {
                $form->form;
                $form->form->fields;
                foreach ($form->form->fields as $field) {
                    $defaultValue = $field->defaultValue($form->form_id);
                    $field->default_value = $defaultValue;
                    if ($defaultValue && $field->show_in_table == 1) {
                        $tmp = new stdClass();
                        $tmp->field_id = $field->id;
                        $tmp->fild_name = $field->name;
                        $tmp->field_label = $field->label;
                        $tmp->field_value = $defaultValue;

                        $tmp2 = new stdClass();
                        $tmp2->field_id = $field->id;
                        $tmp2->fild_name = $field->name;
                        $tmp2->field_label = $field->label;
                        $additional_data[] = $tmp;
                        $additional_cols[] = $tmp2;
                    }
                }
            }
            //add tmp to ordersPure
            foreach ($ordersPure as $orderPure) {
                if ($orderPure->id == $order->id) {
                    $orderPure->additional_data = $additional_data;
                    $jalaliDate = \Morilog\Jalali\Jalalian::fromCarbon($orderPure->created_at)->format('Y/m/d'); // output is a jalali date string like 1399/08/06

                    $orderPure->jalali_date = $jalaliDate;
                    $orderPure->user;

                    foreach ($orderPure->orderGroup as $group) {
                        $group->customer->user->makeHidden(['email', 'email_verified_at', 'created_at', 'updated_at', 'mobile_verified_at', 'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes']);
                        $group->customer->makeHidden(['created_at', 'updated_at', 'phone', 'postal_code', 'national_code', 'address', 'phone', 'city_id']);
                    }

//                    $orderPure->customer = $orderPure->orderGroup[0]->customer->user->name ? $orderPure->orderGroup[0]->customer->user->name . " " . $orderPure->orderGroup[0]->customer->user->last_name : $orderPure->orderGroup[0]->customer->user->mobile;
                    $orderPure->customer = "";
                }
            }
            //find form of first step

        }
        return response()->json([
            "orders" => $ordersPure,
            'cols' => $additional_cols,
            'permissions' => $listPermissions,
            'lang' => $lang,
        ], 200);
    }


    /**
     * @OA\Get(
     * path="/v1/order/rejected",
     * tags={"Order"},
     * summary="list of orders",
     * @OA\Response(
     *  response=200,
     * description="Success",
     * @OA\MediaType(
     * mediaType="application/json",
     * ),
     * ),
     * security={{ "apiAuth": {} }}
     * )
     * )
     */
    public function rejectedOrdersList()
    {
        //get all orders with rejected state
        $rejected_state = OrderState::where("slug", "rejected")->first();
        $orders = Order::where("order_state_id", $rejected_state->id)->get();

        foreach ($orders as $order) {
            $order->orderGroup;
            $order->product;
            $order->step;
            $order->state;

            $jalaliDate = \Morilog\Jalali\Jalalian::fromCarbon($order->created_at)->format('Y/m/d'); // output is a jalali date string like 1399/08/06

            $order->jalali_date = $jalaliDate;
            $order->user;

            foreach ($order->orderGroup as $group) {
                $group->customer->user->makeHidden(['email', 'email_verified_at', 'created_at', 'updated_at', 'mobile_verified_at', 'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes']);
                $group->customer->makeHidden(['created_at', 'updated_at', 'phone', 'postal_code', 'national_code', 'address', 'phone', 'city_id']);
            }

//            $order->customer = $order->orderGroup[0]->customer->user->name ? $order->orderGroup[0]->customer->user->name . " " . $order->orderGroup[0]->customer->user->last_name : $order->orderGroup[0]->customer->user->mobile;
//            return $order->orderGroup;
            $order->customer = "";

        }
        return response()->json([
            "orders" => $orders,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    //create order annotation
    /**
     * @OA\Post(
     *  path="/v1/order",
     * tags={"Order"},
     * summary="create order",
     * @OA\RequestBody(
     *  required=true,
     * @OA\JsonContent(
     *  required={"customer_code","orders"},
     * @OA\Property(property="customer_code", type="string", format="string", example="NIPA9988584"),
     * @OA\Property(property="orders", type="string", format="string", example="[{count:1,product_id:1,form_id:1,data:{}}]"),
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
        //this is what client passes to api:
        // request: {
        //     customer_code: string;
        //     orders?: {
        //       count: number;
        //       product_id: number;
        //       form_id?: number | null;
        //       data?: any;
        //     }[];
        //   }
        $data = $request->validate([
            'customer_code' => 'required|string',
            'orders' => 'required|array',
            'orders.*.count' => 'required|integer',
            'orders.*.product_id' => 'required|integer',
            'orders.*.form_id' => 'nullable|integer',
            'orders.*.data' => 'nullable|array',
        ]);
        //create order group with the method in OrderGroupController
        $orderGroupController = new OrderGroupController();
        $orderGroupResponse = $orderGroupController->store(new Request([
            'customer_code' => $data['customer_code'],
        ]));

        // Check if the response is successful (HTTP status code 2xx)
        if (!$orderGroupResponse->isSuccessful()) {

            // Handle the case where the request was not successful
            // You might want to return an error message or take appropriate action
            return response()->json(['error' => 'Failed to create order group'], $orderGroupResponse->status());
        }


        // Extract the JSON data in the  \Illuminate\Http\Response type not use ->json() it gots error
        $orderGroupData = $orderGroupResponse->getData();

        //get group order from db
        $orderGroup = OrderGroup::find($orderGroupData->id);

        //check if user if match
        $user = Auth::user();
        if ($orderGroup->user_id != $user->id) {
            return response()->json(['message' => 'user not allowed to create order for this order group'], 403);
        }

        //loop inside orders
        foreach ($data['orders'] as $order) {
            //find product first step that related to first form that must be filled

            //first, get product
            $product = Product::find($order['product_id']);
            $firstStep = null;
            //crawl product steps to find the step that its global step desc is initialOrder
            foreach ($product->steps as $stp) {
                $global = $stp->globalStep;
                if ($global->description == "initialOrder") {
                    $firstStep = $stp;
                }
            }
            $orderData = Order::create([
                'product_id' => $order['product_id'],
                'user_id' => $user->id,
                'customer_name' => $orderGroup->customer->user->name . " " . $orderGroup->customer->user->last_name,
                'count' => $order['count'],
                'product_step_id' => $firstStep ? $firstStep->id : null,
            ]);

            $orderGroup->orders()->attach($orderData->id);

            //create user answers for orders that has form
            //use userAnswerForm method in UserAnswerController
            if ($order['form_id']) {
                $formData = $order['data'];
                $userAnswerController = new UserAnswerController();
                $userAnswerResponse = $userAnswerController->userAnswerForm(
                    $order['form_id'],
                    new Request(
                        array_merge(['order_id' => $orderData->id], $formData)
                    )
                );
                // Check if the response is successful (HTTP status code 2xx)
                if (!$userAnswerResponse->isSuccessful()) {
                    // Handle the case where the request was not successful
                    // You might want to return an error message or take appropriate action
                    //roll back all things that do
                    $order->delete();
                    $orderGroup->delete();
                    return response()->json(['error' => 'Failed to create user answer'], $userAnswerResponse->status());
                }
            }
        }

        //create factor from factorController store method
        $factorController = new FactorController();
        $factorResponse = $factorController->store(new Request([
            'order_group_id' => $orderGroup->id,
            //expire date is 7 days later
            'expire_date' => date('Y-m-d', strtotime('+7 days')),
        ]));
        if (!$factorResponse->isSuccessful()) {
            // Handle the case where the request was not successful
            // You might want to return an error message or take appropriate action
            //roll back all things that do
            $orderGroup->delete();
            return response()->json(['error' => 'Failed to create factor'], $factorResponse->status());
        }
        $changesMeta = array();
        $changesMeta[] = [
            "modifiedType" => "createFactor",
            "user" => $user->id,
        ];

        //set factor status from factorController setFactorStatus method
        $factorData = $factorResponse->getData();
        $factorController->setFactorStatus(
            $factorData->id,
            new Request([
                'factor_status_enum' => "salesPending",
                'name' => "status",
                'meta' => json_encode($changesMeta),
            ])
        );

        //TODO: notify admin about new order

        if (!$factorResponse->isSuccessful()) {
            // Handle the case where the request was not successful
            // You might want to return an error message or take appropriate action
            //roll back all things that do
            $orderGroup->delete();
            return response()->json(['error' => 'Failed to set factor status'], $factorResponse->status());
        }

        //find all orders of this order group
        $orders = $orderGroup->orders;
        foreach ($orders as $order) {
            //save factor items from factorController and storeFactorItem method
            $factorController->storeFactorItem($factorData->id, new Request([
                'order_id' => $order->id,
            ]));
        }

        if ($firstStep) {
            $this->createOrderStepFactor($firstStep, $orderGroup);
        }

        return response()->json(['order' => 'done!'], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     *  path="/v1/order/{id}",
     * tags={"Order"},
     * summary="show order info",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="id of order",
     * required=true,
     * @OA\Schema(
     * type="integer",
     * format="int64",
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
    public function show($id)
    {
        //
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'order not found'], 404);
        }

        //find initial form
        $initialForm = $order->product->initialOrderForm();

        //return
        $order->initialForm = $initialForm;
        //get user answers of initial form
        $userAnswers = (array)$this->groupObjectsByItem($order->userAnswers, "form_field_id");

        foreach ($order->userAnswers as $userAnswer) {
            if ($userAnswer->formField->type->has_options) {
                if ($userAnswer->formField->basic_data_id) {
                    $basicDataItems = $userAnswer->formField->basicData->items;
                    //find item
                    foreach ($basicDataItems as $item) {
                        if ($item->code == $userAnswer->answer) {
                            $userAnswer->answerObject = $item;
                            break;
                        }
                    }
                } else {
                    $options = $userAnswer->formField->options;
                    //find option
                    foreach ($options as $option) {
                        if ($option->option == $userAnswer->answer) {
                            $userAnswer->answerObject = $option;
                            break;
                        }
                    }
                }
            }
            // $userAnswer->formField->form;
            // $userAnswer->formField->form->fields;
        }

        $orderResult = new stdClass();
        $orderResult->id = $order->id;
        $orderResult->customer_name = $order->customer_name;

        $orderResult->created_at = $order->created_at;
        $orderResult->state = $order->state;
        $orderResult->product_name = $order->product->name;
        $orderResult->product_code = $order->product->code;
        $orderResult->product_images = $order->product->images;
        $orderResult->user = $order->user;
        $orderResult->rejectedInfo = $order->rejectedInfo;

        $orderResult->count = $order->count;
        $next_step = $this->findNextStep($order);
        $orderResult->step = $order->step;
        $orderResult->next_step = $next_step;
        $prev_steps = $this->findPrevSteps($order);
        $orderResult->prev_steps = $prev_steps;
        $orderResult->step->answers = $this->findOrderStepAnswers($order, $orderResult->step);

        $user = Auth::user();
        $permissions = $user->roles->flatMap(function ($role) {
            return $role->permissions->pluck('slug')->toArray();
        })->toArray();

        $orderResult->can_accept = false;
        $orderResult->can_reject = false;

        //if manage orders exist in permissions
        if (
            in_array('reject-order', $permissions)
        ) {
            $orderResult->can_reject = true;
        }

        if (
            in_array('accept-order', $permissions)
        ) {
            if ($order->step->globalStep->description == "initialOrder") {
                $orderResult->can_accept = true;
            }
        }


        return response()->json(['order' => $orderResult], 200);
    }


    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     *  path="/v1/order/{id}/complete",
     * tags={"Order"},
     * summary="show order complete info",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="id of order",
     * required=true,
     * @OA\Schema(
     * type="integer",
     * format="int64",
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

    public function showComplete($id)
    {
        //
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'order not found'], 404);
        }

        $initialForm = $order->product->initialOrderForm();
        $completeForm = $order->product->completeOrderForm();

        //return
        $order->initialForm = $initialForm;


        //get user answers of initial form
        $userAnswers = (array)$this->groupObjectsByItem($order->userAnswers, "form_field_id");

        foreach ($order->userAnswers as $userAnswer) {
            if ($userAnswer->formField->type->has_options) {
                if ($userAnswer->formField->basic_data_id) {
                    $basicDataItems = $userAnswer->formField->basicData->items;
                    //find item
                    foreach ($basicDataItems as $item) {
                        if ($item->code == $userAnswer->answer) {
                            $userAnswer->answerObject = $item;
                            break;
                        }
                    }
                } else {
                    $options = $userAnswer->formField->options;
                    //find option
                    foreach ($options as $option) {
                        if ($option->option == $userAnswer->answer) {
                            $userAnswer->answerObject = $option;
                            break;
                        }
                    }
                }
            }
            // $userAnswer->formField->form;
            // $userAnswer->formField->form->fields;
        }

        $orderResult = new stdClass();
        $orderResult->id = $order->id;
        $orderResult->customer_name = $order->customer_name;

        $orderResult->created_at = $order->created_at;
        $orderResult->product_name = $order->product->name;
        $orderResult->product_code = $order->product->code;
        $orderResult->product_details = $order->product->details;
        $orderResult->product_images = $order->product->images;
        $orderResult->user = $order->user;

        $ansArray = array();
        foreach ($userAnswers as $key => $value) {
            array_push($ansArray, $value);
        }

        return response()->json(['order' => $orderResult, 'userAnswers' => $ansArray, 'form' => $completeForm], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    function groupObjectsByItem($objects, $itemKey)
    {
        $groupedObjects = [];
        foreach ($objects as $object) {
            $key = $object->{$itemKey};
            if (!array_key_exists($key, $groupedObjects)) {
                $groupedObjects[$key] = [];
            }
            $groupedObjects[$key][] = $object;
        }
        return $groupedObjects;
    }

    //search in orders of specific order group by name

    /**
     * @OA\Get(
     *  path="/v1/orderGroup/{id}/search",
     * tags={"Order"},
     * summary="search in orders of specific order group by name",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="id of order group",
     * required=true,
     * @OA\Schema(
     * type="integer",
     * format="int64",
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
    public function search($id)
    {
        //validate id
        $orderGroup = OrderGroup::find($id);
        if (!$orderGroup) {
            return response()->json(['message' => 'order group not found'], 404);
        }
        //find all orders
        $orders = $orderGroup->orders;
        foreach ($orders as $order) {
            $order->product;
        }

        //return only product name and id of orders
        $ordersRes = array();
        foreach ($orders as $order) {
            $tmp = new stdClass();
            $tmp->id = $order->id;
            $tmp->product_id = $order->product->id;
            $tmp->name = $order->product->name;
            $tmp->count_type = $order->product->count_type;
            $tmp->count = $order->count;
            if ($tmp->count_type == "quantity") {
                $tmp->price = $order->product->details[0]->price;
            } else {
                //find width and height
                $tmp->width = $order->product->fieldValue("width");
                $tmp->height = $order->product->fieldValue("height");
            }
            //search in $ordersRes for this name
            $exist = false;
            foreach ($ordersRes as $orderRes) {
                if ($orderRes->name == $tmp->name) {
                    $exist = true;
                    break;
                }
            }
            if (!$exist)
                $ordersRes[] = $tmp;
        }

        //return orders
        return response()->json(['orders' => $ordersRes], 200);
    }

    /**
     * @OA\Post(
     *  path="/v1/order/{id}/gotoNextStep",
     * tags={"Order"},
     * summary="search in orders of specific order group by name",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="id of order group",
     * required=true,
     * @OA\Schema(
     * type="integer",
     * format="int64",
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
    public function gotoNextStep($id)
    {
        $order = Order::find($id);

        $nextStep = $this->findNextStep($order);

        $user = Auth::user();
        //permissions
        $permissions = $user->roles->flatMap(function ($role) {
            return $role->permissions->pluck('slug')->toArray();
        })->toArray();

        //if current step badged as initialOrder, only who has access can do that
        $currentStep = $order->step;
        if ($currentStep->globalStep->description == "initialOrder") {
            if (
                !in_array('accept-order', $permissions)
            ) {
                return response()->json(['message' => 'you dont have enough access to accept this order'], 403);
            }
        }

//        if ($currentStep->globalStep->description != "initialOrder") {
//            //otherwise, check that current user has access to current step
//            $step_roles = ProductStepsRole::where("product_step_id", $currentStep->id)->get();
//            $my_roles = UsersRole::where("user_id", $user->id)->get();
//            $has_access = $step_roles->intersect($my_roles)->isNotEmpty();
//
//            if (!$has_access) {
//                // User has access to the current step
//                return response()->json(['message' => 'you dont have enough access to pass this step'], 403);
//            }
//        }

        //check if this step has payment or not

        if ($currentStep->has_payment) {
            //find related factor
            $factor = Factor::where("order_group_id", $order->orderGroup[0]->id)->where("product_step_id", $currentStep->id)->first();
            if ($factor) {
                //check if factor has been fully paid or not
                $factorTotalPrice = $factor->totalPrice(false, true);
                $resp = $factorTotalPrice->getData();
                $factorTotalPrice = $resp->data;
                $factorPaidPrice = $resp->paid;
                $factor_status_slug = $factor->lastStatus->factorStatusEnum->slug;

                if ($factor_status_slug != "paymentDone") {
                    return response()->json(['message' => 'به علت عدم پرداخت فاکتور امکان ادامه وجود ندارد', 'detail' => [
                        "factorTotalPrice" => $resp->data,
                        "factorPaidPrice" => $resp->paid,
                    ]], 406);
                }
            }
        }

        $order->update([
            "product_step_id" => $nextStep->id
        ]);

        //create factor for next step if next step has_payment
        $this->createOrderStepFactor($nextStep, $order->orderGroup[0]);


        return response()->json(['order' => $order], 200);

    }


    public function findNextStep($or, $step = null)
    {
        $stepId = $step ? $step->next_step_id : $or->step->next_step_id;
        $nextStep = ProductStep::with(['globalStep', 'roles'])->find($stepId);

        if (!$nextStep) {
            return null;
        }

        $stepCond = ProductStepsCondition::where('product_step_id', $step ? $step->id : $or->step->id)->first();

        if ($stepCond) {
            $userAnswer = UserAnswer::where([
                ['user_id', $or->user->id],
                ['order_id', $or->id],
                ['form_field_id', $stepCond->form_field_id]
            ])->first();

            if ($userAnswer) {
                $condOption = $stepCond->form_field_option_id
                    ? FormFieldOptions::find($stepCond->form_field_option_id)
                    : BasicDataItem::find($stepCond->basic_data_item_id);

                if ($userAnswer->answer == ($condOption->option ?? $condOption->code)) {
                    $nextStep = ProductStep::with(['globalStep', 'roles'])->find($stepCond->next_product_step_id);
                }
            }
        }

        return $nextStep;
    }


    public function findOrderStepAnswers($order, $step)
    {
        $form = $step->forms[0];
        $formController = new FormController();
        $request = new Request(["orderId" => $order->id]);
        $response = $formController->show($form->id, $request);
        $formFields = $response->original->fields;

        $fields = array();

        foreach ($formFields as $field) {
            $tmp = new stdClass();
            $tmp->id = $field->id;
            $tmp->name = $field->name;
            $tmp->label = $field->label;
            $tmp->form_field_type = $field->type->type;

            if ($field->userAnswer) {
                if ($field->type->has_options) {
                    $items = $field->basic_data_id ? $field->basicDataItems : $field->options;

                    foreach ($items as $item) {
                        if ($item->option == $field->userAnswer) {
                            $tmp->answerObject = $item;
                            break;
                        }
                    }
                } else {
                    $tmp->answer = $field->userAnswer;
                }
            } else {
                $tmp->answer = null;
            }

            $fields[] = $tmp;
        }

        return $fields;
    }

    public function findPrevSteps($order, $data = null)
    {
        if ($data) {
            $next_step = $this->findNextStep($order, $data->current_step);
            if (!$next_step || $next_step->id === $data->order_step->id) {
                return $data->steps;
            }

            $tmp = new stdClass();
            $tmp->id = $next_step->id;
            $tmp->global_step_id = $next_step->global_step_id;
            $tmp->product_id = $next_step->product_id;
            $tmp->step_name = $next_step->step_name;
            $tmp->answers = $this->findOrderStepAnswers($order, $next_step);

            $data->steps[] = $tmp;
            $data->current_step = $next_step;

            return $this->findPrevSteps($order, $data);
        } else {
            $order_step = ProductStep::find($order->product_step_id);
            $product = $order->product;
            $first_step = $product->steps->firstWhere('globalStep.description', 'initialOrder');

            if ($first_step->id === $order_step->id) {
                return null;
            }

            $steps = [];

            $tmp = new stdClass();
            $tmp->id = $first_step->id;
            $tmp->global_step_id = $first_step->global_step_id;
            $tmp->product_id = $first_step->product_id;
            $tmp->step_name = $first_step->step_name;
            $tmp->answers = $this->findOrderStepAnswers($order, $first_step);
            $steps[] = $tmp;

            $data = new stdClass();
            $data->order_step = $order_step;
            $data->current_step = $first_step;
            $data->steps = $steps;

            return $this->findPrevSteps($order, $data);
        }
    }


    /**
     * @OA\Post(
     *  path="/v1/order/{id}/reject",
     * tags={"Order"},
     * summary="reject order",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="id of order group",
     * required=true,
     * @OA\Schema(
     * type="integer",
     * format="int64",
     * ),
     * ),
     * @OA\RequestBody(
     *  required=true,
     * @OA\JsonContent(
     *  required={"sales_description","has_refund"},
     * @OA\Property(property="sales_description", type="string", format="string", example="test"),
     * @OA\Property(property="has_refund", type="boolean", format="boolean", example="true"),
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
    public function rejectOrder($id, Request $request)
    {
        $data = $request->validate([
            'has_refund' => 'required|boolean',
            'sales_description' => 'required|string'
        ]);

        $order = Order::find($id);
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $rejected_state = OrderState::where("slug", "rejected")->first();
        if ($order->order_state_id == $rejected_state->id) {
            return response()->json(['error' => 'Order already rejected'], 406);
        }
        $order->update([
            'order_state_id' => $rejected_state->id,
        ]);
        $data = RejectedOrderDetail::create([
            "order_id" => $order->id,
            "has_refund" => $data["has_refund"],
            "sales_description" => $data["sales_description"],

        ]);
        return response()->json(['message' => 'Order rejected successfully', "data" => $data]);

    }

    public function createOrderStepFactor($step, $orderGroup)
    {
        if ($step->has_payment) {
            $factorController = new FactorController();
            $user = Auth::user();
            //if first step has payment, then create its factor
            $step_factor = Factor::create([
                'code' => //NIPA + order group id+ "_" +product step id+ "_" + user id +"_"+timestamp unix
                    "NIPA_" . $orderGroup->id . "_" . $step->id . "_" . auth()->user()->id . "_" . time(),
                'order_group_id' => $orderGroup->id,
                'product_step_id' => $step->id,
                'expire_date' => date('Y-m-d', strtotime('+7 days')),
                'description' => "",
            ]);

            $changesMeta = array();
            $changesMeta[] = [
                "modifiedType" => "createFactor",
                "user" => $user->id,
            ];

            $factorController->setFactorStatus(
                $step_factor->id,
                new Request([
                    'factor_status_enum' => "salesPending",
                    'name' => "status",
                    'meta' => json_encode($changesMeta),
                ])
            );

            //store one item in factor
            $factorController->storeFactorItem($step_factor->id, new Request([
                'code' => "1",
                'name' => "خدمات " . $step->step_name,
                'count_type' => "quantity",
                'count' => 1,
                'unit_price' => 1
            ]));
        }
    }

}
