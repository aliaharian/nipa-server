<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderGroup;
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
        //check if user role is admin
        $user = Auth::user();
        if ($user->role == 'admin') {
            $orders = Order::orderBy('id', 'desc')->get();
            $ordersPure = Order::orderBy('id', 'desc')->get();

        } else {
            $orders = Order::orderBy('id', 'desc')->get();
            $ordersPure = Order::where('user_id', $user->id)->orderBy('id', 'desc')->get();

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
                    $jalaliDate=\Morilog\Jalali\Jalalian::fromCarbon($orderPure->created_at)->format('Y/m/d'); // output is a jalali date string like 1399/08/06

                    $orderPure->jalali_date = $jalaliDate;
                    $orderPure->user;
                }
            }

            //find form of first step

        }
        return response()->json(["orders" => $ordersPure, 'cols' => $additional_cols], 200);

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
            'customer_name' => 'required'
        ]);

        $orderGroup = OrderGroup::find($data['order_group_id']);
        //check if user if mach
        $user = Auth::user();
        if ($orderGroup->user_id != $user->id) {
            return response()->json(['message' => 'user not allowed to create order for this order group'], 403);
        }
        $order = Order::create([
            'product_id' => $data['product_id'],
            'user_id' => $user->id,
            'customer_name' => $data['customer_name']
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
    public function show($id)
    {
        //
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
}