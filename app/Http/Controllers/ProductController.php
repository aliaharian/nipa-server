<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Files\FileController;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductStep;
use Illuminate\Support\Facades\Auth;
use App\Models\File;
use App\Models\ProductImage;

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
        foreach ($products as $product) {
            $product->details;
            $product->initialFormId = $product->initialOrderForm()?$product->initialOrderForm()->id:null;

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
     *  @OA\Property(property="code", type="string", format="string", example="product code"),
     * @OA\Property(property="custom", type="number", format="number", example="0"),
     * @OA\Property(property="price", type="number", format="number", example="100"),
     * @OA\Property(property="status", type="number", format="number", example="1"),
     * @OA\Property(property="count_type", type="string", format="string", example="quantity"),
     * @OA\Property(property="description", type="string", format="string", example="product description"),
     * @OA\Property(property="images", type="array", format="array", example="['hashcode1','hashcode2']", @OA\Items(
     * @OA\Property(property="images", type="string", format="string", example="hashcode"),
     * ),
     * ),
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
            'custom' => 'required|in:0,1',
            'code' => "required|unique:products,code",
            'count_type'=>"required|in:quantity,m2",
            'description' => 'required|string',
        ]);
        if ($data['custom'] == 0) {
            $data = $request->validate([
                'name' => 'required|unique:products,name',
                'code' => "required|unique:products,code",
                'custom' => 'required|in:0,1',
                'price' => 'required|numeric',
                'description' => 'required|string',
            ]);
        }

        $product = Product::create($data);
        $product->details()->create([
            'description' => $data['description'],
        ]);
        if ($request->has('images')) {
            $images = $request->images;
            foreach ($images as $image) {
                $product->images()->create([
                    'file_id' => File::where('hash_code', $image)->first()->id,
                ]);
            }
        }

        if ($data['custom'] == 0) {
            $product->details()->update([
                'price' => $data['price'],
            ]);
        }
        //show product details() with products
        $product->details;
        $product->images;
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
        $product->images;
        foreach($product->images as $image){
            $image->hashCode = $image->hashcode;
        }
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
     * @OA\Property(property="code", type="string", format="string", example="product code"),
     * @OA\Property(property="custom", type="number", format="number", example="0"),
     * @OA\Property(property="price", type="number", format="number", example="100"),
     * @OA\Property(property="status", type="number", format="number", example="1"),
     * @OA\Property(property="count_type", type="string", format="string", example="quantity"),
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
        if (!$product) {
            return response()->json(['message' => 'product not found'], 404);
        }
        $data = $request->validate([
            'name' => 'required|unique:products,name,' . $product->id,
            'custom' => 'required|in:0,1',
            'code' => 'required|unique:products,code,' . $product->id,
            'count_type'=>"required|in:quantity,m2",
        ]);
        if ($data['custom'] == 0) {
            $data = $request->validate([
                'name' => 'required|unique:products,name,' . $product->id,
                'custom' => 'required|in:0,1',
                'price' => 'required|numeric',
                'description' => 'required|string',
                'count_type'=>"required|in:quantity,m2"
            ]);
        }
        $product->update($data);
        if ($request->has('images')) {
            $images = $request->images;
            foreach ($images as $image) {
               $fileId = File::where('hash_code', $image)->first()->id;
               $exists = ProductImage::where("file_id",$fileId)->where("product_id",$id)->count();
               if(!$exists){
                $product->images()->create([
                    'file_id' => File::where('hash_code', $image)->first()->id,
                ]);
            }
            }

        }
        if ($data['custom'] == 0) {
            $product->details()->update([
                'price' => $data['price'],
                'description' => $data['description'],
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
        if (!$product) {
            return response()->json(['message' => 'product not found'], 404);
        }
        //delete images of product
        $images = $product->images;
        $file = new FileController();
        foreach ($images as $image) {
            $result = $file->destroy($image->hash_code);
            // $image->delete();
        }
        //delete details of product
        $details = $product->details;
        foreach ($details as $detail) {
            $detail->delete();
        }

        $product->delete();
        return response()->json(['message' => 'product deleted', 'product' => $product], 200);

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
        if (!$product) {
            return response()->json(['message' => 'product not found'], 404);
        }
        $product->steps;
        return response()->json($product, 200);
    }

    //all products list but with search at least 3 characters
    /**
     * @OA\Get(
     *  path="/v1/products/search/{name}",
     * tags={"Products"},
     * summary="search products",
     * @OA\Parameter(
     *   name="name",
     *  in="path",
     * required=true,
     * @OA\Schema(
     * type="string",
     * format="string"
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
    public function search($name)
    {
        //check if at least 3 characters
        if (mb_strlen($name,'utf-8') < 3) {
            return response()->json(['message' => 'at least 3 characters'], 400);
        }
        $products = Product::where('name', 'like', '%' . $name . '%')->get();
      
        // create an object and only return name and id
        $products = $products->map(function ($product) {
            $product->details;
            $result = new \stdClass();
            $result->id = $product->id;
            $result->name = $product->name;
            $result->count_type = $product->count_type;
            $result->price = $product->details[0]->price;
            $result->description = $product->details[0]->description;
            return $result;
        });
        return response()->json($products, 200);
    }
}