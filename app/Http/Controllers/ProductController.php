<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductStep;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

     //get all products
       /**
     * @OA\Get(
     *   path="/v1/products",
     *   tags={"Products"},
     *   summary="show all products",
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
    public function index()
    {
        
        $products = Product::all();
        foreach($products as $product){
            $product->details;
        }
        return response()->json($products, 200);
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

     // Create a new product annotation
 /**
     * @OA\Post(
     *  path="/v1/products",
     * tags={"Products"},
     * summary="create a new product",
     * @OA\RequestBody(
     *   required=true,
     *  @OA\JsonContent(
     *   required={"name","custom", "price", "description"},
     *  @OA\Property(property="name", type="string", format="string", example="product name"),
     * @OA\Property(property="custom", type="number", format="number", example="0"),
     * @OA\Property(property="price", type="number", format="number", example="100"),
     * @OA\Property(property="description", type="string", format="string", example="product description"),
     * ),
     * ),
     * @OA\Response(
     *     response=200,
     *    description="Success",
     * @OA\MediaType(
     *    mediaType="application/json",
     * ),
     * ),
     * security={{ "apiAuth": {} }}
     * )
     * )
     */

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|unique:products,name',
            'custom'=>'required|in:0,1',
        ]);
        if($data['custom']==0){
            $data = $request->validate([
                'name' => 'required|unique:products,name',
                'custom'=>'required|in:0,1',
                'price'=>'required|numeric',
                'description'=>'required|string',
            ]);
        }

        $product = Product::create($data);
        if($data['custom']==0){
            $product->details()->create([
                'price'=>$data['price'],
                'description'=>$data['description'],
            ]);
        }
        //show product details() with products
        $product->details;
        return response()->json($product, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
        //get product by id
    /**
     * @OA\Get(
     *  path="/v1/products/{id}",
     * tags={"Products"},
     * summary="show one product",
     * @OA\Parameter(
     *     name="id",
     *    in="path",
     *   required=true,
     *  @OA\Schema(
     *     type="integer",
     *    format="int64"
     *  )
     * ),
     * @OA\Response(
     *    response=200,
     *  description="Success",
     * @OA\MediaType(
     *   mediaType="application/json",
     * ),
     * ),
     * security={{ "apiAuth": {} }}
     * )
     * )
     */
     
    public function show($id)
    {
        //
        $product = Product::findOrFail($id);
        $product->details;
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

    //update product by id
    /**
     * @OA\Put(
     *  path="/v1/products/{id}",
     * tags={"Products"},
     * summary="update a product",
     * @OA\Parameter(
     *    name="id",
     *  in="path",
     * required=true,
     * @OA\Schema(
     *  type="integer",
     * format="int64"
     * )
     * ),
     * @OA\RequestBody(
     *  required=true,
     * @OA\JsonContent(
     * required={"name","custom", "price", "description"},
     * @OA\Property(property="name", type="string", format="string", example="product name"),
     * @OA\Property(property="custom", type="number", format="number", example="0"),
     * @OA\Property(property="price", type="number", format="number", example="100"),
     * @OA\Property(property="description", type="string", format="string", example="product description"),
     * ),
     * ),
     * @OA\Response(
     *    response=200,
     * description="Success",
     * @OA\MediaType(
     *  mediaType="application/json",
     * ),
     * ),
     * security={{ "apiAuth": {} }}
     * )
     * )
     */
    public function update(Request $request, $id)
    {
        //
        $product = Product::find($id);
        if(!$product){
            return response()->json(['message'=>'product not found'], 404);
        }
        $data = $request->validate([
            'name' => 'required|unique:products,name,'.$product->id,
            'custom'=>'required|in:0,1',
        ]);
        if($data['custom']==0){
            $data = $request->validate([
                'name' => 'required|unique:products,name,'.$product->id,
                'custom'=>'required|in:0,1',
                'price'=>'required|numeric',
                'description'=>'required|string',
            ]);
        }
        $product->update($data);
        if($data['custom']==0){
            $product->details()->update([
                'price'=>$data['price'],
                'description'=>$data['description'],
            ]);
        }
        //show product details() with products
        $product->details;
        return response()->json($product, 200);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    //delete product by id
    /**
     * @OA\Delete(
     *  path="/v1/products/{id}",
     * tags={"Products"},
     * summary="delete a product",
     * @OA\Parameter(
     *   name="id",
     * in="path",
     * required=true,
     * @OA\Schema(
     *  type="integer",
     * format="int64"
     * )
     * ),
     * @OA\Response(
     *   response=200,
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
        $product = Product::find($id);
        if(!$product){
            return response()->json(['message'=>'product not found'], 404);
        }
        $product->delete();
        return response()->json(['message'=>'product deleted'], 200);

    }

  //get steps of specific product
    /**
     * @OA\Get(
     *  path="/v1/products/{id}/steps",
     * tags={"Products"},
     * summary="get steps of a product",
     * @OA\Parameter(
     *   name="id",
     *  in="path",
     * required=true,
     * @OA\Schema(
     * type="integer",
     * format="int64"
     * )
     * ),
     *  @OA\Response(
     *    response=200,
     *   description="Success",
     * @OA\MediaType(
     *  mediaType="application/json",
     * )
     * ),
     * security={{ "apiAuth": {} }}
     * )
     * )
     */
    public function showSteps($id)
    {
        $product = Product::find($id);
        if(!$product){
            return response()->json(['message'=>'product not found'], 404);
        }
        $product->steps;
        return response()->json($product, 200);
    }
}
