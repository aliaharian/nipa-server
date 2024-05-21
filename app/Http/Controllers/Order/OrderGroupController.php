<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderGroup;
use App\Models\ProductStepsRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //list of order groups annotation
    /**
     * @OA\Get(
     *  path="/v1/orderGroup",
     * tags={"OrderGroup"},
     * summary="list of order groups",
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
    public function index()
    {
        //liost all order groups
        $user = Auth::user();
        //permissions
        $roles = $user->roles;
        $user_role_ids = $roles->pluck('id')->toArray(); // Use pluck and toArray to get the IDs

        $permissions = $roles->flatMap(function ($role) {
            return $role->permissions->pluck('slug')->toArray();
        })->toArray();
        $all_order_groups = OrderGroup::orderBy('id', 'DESC')->get();

        //if manage orders exist in permissions
        if (
            in_array('view-orders', $permissions)
        ) {
            $order_groups = $all_order_groups;

        } else {
            //my orders
            $my_order_groups = OrderGroup::where("user_id", $user->id)->orderBy('id', 'DESC')->get();
            //orders that i have access to their step based on their current step
            $accessible_order_groups = collect();  // Initialize a collection to store accessible order groups
            foreach ($all_order_groups as $gp) {
                $has_access = false;
                foreach ($gp->orders as $order) {
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
                        break;  // No need to check further if access is already granted
                    }
                }
                // If the user has access to this order group, add it to the array
                if ($has_access) {
                    $accessible_order_groups->push($gp);
                }
            }
            $order_groups = $my_order_groups->merge($accessible_order_groups);

        }
        //get customer info of each order group only customer_code and user id and get user info only name and last_name and id and mobile
        $order_groups = $order_groups->map(function ($order_group) {
            $order_group->user;
            $order_group->user->makeHidden(['email', 'email_verified_at', 'created_at', 'updated_at', 'mobile_verified_at', 'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes']);
            return $order_group;
        });

        return response()->json($order_groups, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    //create order group annotation
    /**
     * @OA\Post(
     *  path="/v1/orderGroup",
     * tags={"OrderGroup"},
     * summary="create order group",
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
        //validation customer_id is required and from customers table (customers table code column)
        $data = $request->validate([
            'customer_code' => 'required|exists:customers,code',
        ]);
        //find customer id from code
        $customer = Customer::where('code', $data['customer_code'])->first();
        $order_group = OrderGroup::create([
            'user_id' => Auth::user()->id,
            'total_price' => 0,
            'total_off' => 0,
            'customer_id' => $customer->id,

        ]);
        return response()->json($order_group, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    //show order group annotation
    /**
     * @OA\Get(
     *  path="/v1/orderGroup/{id}",
     * tags={"OrderGroup"},
     * summary="show order group",
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

    public function show($id)
    {
        //show order group
        $order_group = OrderGroup::find($id);
        if (!$order_group) {
            return response()->json(['message' => 'order group not found'], 404);
        }
        return response()->json($order_group, 200);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    //update order group annotation
    /**
     * @OA\Put(
     *  path="/v1/orderGroup/{id}",
     * tags={"OrderGroup"},
     * summary="update order group",
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
     * required=true,
     * @OA\JsonContent(
     * required={"user_id" , "total_price" , "total_off"},
     * @OA\Property(property="total_price", type="string", format="string", example="12000"),
     * @OA\Property(property="total_off", type="string", format="string", example="5000"),
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

    public function update(Request $request, $id)
    {
        //update order group
        $order_group = OrderGroup::find($id);
        if (!$order_group) {
            return response()->json(['message' => 'order group not found'], 404);
        }
        $data = $request->validate([
            'total_price' => 'required|numeric',
            'total_off' => 'required|numeric',
        ]);
        $order_group->update($data);
        return response()->json($order_group, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    //delete order group annotation
    /**
     * @OA\Delete(
     *  path="/v1/orderGroup/{id}",
     * tags={"OrderGroup"},
     * summary="delete order group",
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
    public function destroy($id)
    {
        //delete order group
        $order_group = OrderGroup::find($id);
        if (!$order_group) {
            return response()->json(['message' => 'order group not found'], 404);
        }
        $order_group->delete();

        //response json
        return response()->json(['message' => 'order group deleted'], 200);
    }
}
