<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductStep;
use Illuminate\Http\Request;

class ProductStepController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    // Create a new product step annotation
    /**
     * @OA\Post(
     *  path="/v1/product/steps/bulk",
     * tags={"ProductSteps"},
     * summary="create a new product step bulk",
     * @OA\RequestBody(
     *  required=true,
     * @OA\JsonContent(
     *  required={"steps","product_id"},
     * @OA\Property(property="steps", type="string", format="string", example="step1,step2,step3"),
     * @OA\Property(property="product_id", type="integer", format="integer", example="1"),
     * ),
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Success",
     *    @OA\MediaType(
     *        mediaType="application/json",
     *   )
     * ),
     * security={{ "apiAuth": {} }}
     * )
     * )
     */
    public function storeBulk(Request $request)
    { 
        $data = $request->validate([
            'steps' => 'required',
            'product_id' => 'required',
        ]);
        //
        $product = Product::find($request->product_id);
        if(!$product){
            return response()->json(['message'=>'product not found'], 404);
        }
        if($product->custom==0){
            return response()->json(['message'=>'product is not custom'], 404);
        }

       
        $steps = explode(',', $data['steps']);
        foreach($steps as $step){
            //check if product doesnt have this step
            $product_step = ProductStep::where('step_name', $step)->where('product_id', $product->id)->first();
            if(!$product_step){
                $product->steps()->create([
                    'step_name'=>$step,
                ]);
            }
        }
        $product->steps;
        return response()->json($product, 200);
    }


    //add a new step to a product
       /**
     * @OA\Post(
     *  path="/v1/product/steps",
     * tags={"ProductSteps"},
     * summary="create a new product step",
     * @OA\RequestBody(
     *  required=true,
     * @OA\JsonContent(
     *  required={"step_name","product_id" , "parent_step_id"},
     * @OA\Property(property="step_name", type="string", format="string", example="step1"),
     * @OA\Property(property="product_id", type="integer", format="integer", example="1"),
     * @OA\Property(property="parent_step_id", type="integer", format="integer", example="1"),
     * ),
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Success",
     *    @OA\MediaType(
     *        mediaType="application/json",
     *   )
     * ),
     * security={{ "apiAuth": {} }}
     * )
     * )
     */
    public function store(Request $request)
    { 

        $data = $request->validate([
            'step_name' => 'required',
            'product_id' => 'required',
            'parent_step_id' => 'integer',
        ]);
        //
        $product = Product::find($request->product_id);
        if(!$product){
            return response()->json(['message'=>'product not found'], 404);
        }
        if($product->custom==0){
            return response()->json(['message'=>'product is not custom'], 404);
        }
       
        //check if product doesnt have this step
        $product_step = ProductStep::where('step_name', $data['step_name'])->where('product_id', $product->id)->first();
        if(!$product_step){
            $product->steps()->create([
                'step_name'=>$data['step_name'],
                'parent_step_id'=>$data['parent_step_id'],
            ]);
        }
        $product->steps;
        return response()->json($product, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // Update a product step annotation
    /**
     * @OA\Put(
     *  path="/v1/product/steps/{id}",
     * tags={"ProductSteps"},
     * summary="update a product step",
     * @OA\Parameter(
     *   name="id",
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
     * required={"step_name"},
     * @OA\Property(property="step_name", type="string", format="string", example="step name"),
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
        //
        $product_step = ProductStep::find($id);
        if(!$product_step){
            return response()->json(['message'=>'product step not found'], 404);
        }
        $data = $request->validate([
            'step_name' => 'required',
        ]);
        $product_step->update($data);
        $product_step->product;
        return response()->json($product_step, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // Delete a product step annotation
    /**
     * @OA\Delete(
     *  path="/v1/product/steps/{id}",
     * tags={"ProductSteps"},
     * summary="delete a product step",
     * @OA\Parameter(
     *  name="id",
     * in="path",
     * required=true,
     * @OA\Schema(
     * type="integer",
     * format="int64"
     * )
     * ),
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
    public function destroy($id)
    {
        //
        $product_step = ProductStep::find($id);
        if(!$product_step){
            return response()->json(['message'=>'product step not found'], 404);
        }
        $product_step->delete();
        return response()->json(['message'=>'product step deleted'], 200);
    }
}
