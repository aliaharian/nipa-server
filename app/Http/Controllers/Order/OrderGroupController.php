<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\OrderGroup;
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
        $order_groups = OrderGroup::orderBy('id','DESC')->get();
        return response()->json($order_groups, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
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
        $order_group = OrderGroup::create([
            'user_id' => Auth::user()->id,
            'total_price' => 0,
            'total_off' => 0,
        ]);
        return response()->json($order_group, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
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
        if(!$order_group){
            return response()->json(['message'=>'order group not found'], 404);
        }
        return response()->json($order_group, 200);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
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
        if(!$order_group){
            return response()->json(['message'=>'order group not found'], 404);
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
     * @param  int  $id
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
        if(!$order_group){
            return response()->json(['message'=>'order group not found'], 404);
        }
        $order_group->delete();

        //response json
        return response()->json(['message'=>'order group deleted'], 200);
    }
}
