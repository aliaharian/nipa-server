<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\FormFieldOptions;
use App\Models\Order;
use App\Models\File;
use App\Models\OrderGroup;
use App\Models\Product;
use App\Models\ProductStep;
use App\Models\ProductStepsCondition;
use App\Models\UserAnswer;
use Illuminate\Http\Request;
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
        $additional_cols = [];
        $listPermissions = new stdClass();
        // $listPermissions->canEdit = true;
        // $listPermissions->canDelete = true;

        //check if user role is admin
        $user = Auth::user();
        //permissions
        $permissions = $user->roles->flatMap(function ($role) {
            return $role->permissions->pluck('slug')->toArray();
        })->toArray();


        //if manage orders exist in permissions
        if (
            in_array('view-orders', $permissions)
        ) {
            $orders = Order::orderBy('id', 'desc')->get();
            $ordersPure = Order::orderBy('id', 'desc')->get();

            $listPermissions->canEdit = in_array('edit-orders', $permissions);
            $listPermissions->canDelete = in_array('delete-orders', $permissions);
            $listPermissions->canCompelete = in_array('compelete-orders', $permissions);
        } else {
            $orders = Order::orderBy('id', 'desc')->get();
            $ordersPure = Order::where('user_id', $user->id)->orderBy('id', 'desc')->get();
        }

        foreach ($ordersPure as $or) {
            $or->orderGroup;
            $or->product;
            $or->step;
            $or->step->globalStep;

            //find default next step
            $nextStep = ProductStep::where('product_id', $or->product->id)->where("id", ">", $or->step->id)->orderBy("id", "asc")->first();
            $nextStep->globalStep;
            $nextStep->roles;
            //check if next step has condition
            $stepCond = ProductStepsCondition::where("product_step_id", $or->step->id)->first();
            if ($stepCond) {

                //check user answer to form_field_id
                $userAnswer = UserAnswer::where("user_id", $or->user->id)->where("order_id", $or->id)->where("form_field_id", $stepCond->form_field_id)->first();
                if ($userAnswer) {
                    $condOption = FormFieldOptions::find($stepCond->form_field_option_id);
                    // echo $condOption;
                    // echo $or->user->id . '//';
                    // echo $stepCond->form_field_id . '//';
                    // echo $userAnswer;
                    if ($userAnswer->answer == $condOption->option) {
                        // echo "now user passed condition!";
                        // next step is :
                        $nextStep = ProductStep::find($stepCond->next_product_step_id);
                        $nextStep->globalStep;
                        $nextStep->roles;

                    }
                }
            }
            $or->nextStep = $nextStep;
            $or->canEdit = $or->step->globalStep->description == "initialOrder" ? true : false;
            $or->canDelete = $or->step->globalStep->description == "initialOrder" ? true : false;
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

                    $orderPure->customer = $orderPure->orderGroup[0]->customer->user->name ? $orderPure->orderGroup[0]->customer->user->name . " " . $orderPure->orderGroup[0]->customer->user->last_name : $orderPure->orderGroup[0]->customer->user->mobile;
                }
            }
            //find form of first step

        }
        return response()->json(["orders" => $ordersPure, 'cols' => $additional_cols, 'permissions' => $listPermissions], 200);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
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
     *  required={"product_id" , "order_group_id"},
     * @OA\Property(property="product_id", type="integer", format="integer", example="20"),
     * @OA\Property(property="order_group_id", type="integer", format="integer", example="4"),
     * @OA\Property(property="customer_name", type="string", format="string", example="علی اهاریان"),
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
        //
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'order_group_id' => 'required|exists:order_groups,id',
            'customer_name' => 'required',
            'count' => 'required'
        ]);

        $orderGroup = OrderGroup::find($data['order_group_id']);
        //check if user if mach
        $user = Auth::user();
        if ($orderGroup->user_id != $user->id) {
            return response()->json(['message' => 'user not allowed to create order for this order group'], 403);
        }

        //find product first step that related to first form that must be filled

        //first, get product
        $product = Product::find($data['product_id']);
        $firstStep = null;
        //crawl product steps to find the step that its global step desc is initialOrder
        foreach ($product->steps as $stp) {
            $global = $stp->globalStep;
            if ($global->description == "initialOrder") {
                $firstStep = $stp;
            }
        }
        $order = Order::create([
            'product_id' => $data['product_id'],
            'user_id' => $user->id,
            'customer_name' => $data['customer_name'],
            'count' => $data['count'],
            'product_step_id' => $firstStep ? $firstStep->id : null
        ]);

        $orderGroup->orders()->attach($order->id);


        return response()->json($order, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
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
        // $order->orderGroup;
        // $order->product;
        // $order->user;
        // $order->product->details;
        // $order->product->images;
        //find initial form
        $initialForm = $order->product->initialOrderForm();

        //return
        $order->initialForm = $initialForm;
        //get user answers of initial form
        $userAnswers = (array) $this->groupObjectsByItem($order->userAnswers, "form_field_id");

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

        return response()->json(['order' => $orderResult, 'userAnswers' => $ansArray], 200);

    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
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
        $userAnswers = (array) $this->groupObjectsByItem($order->userAnswers, "form_field_id");

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
}