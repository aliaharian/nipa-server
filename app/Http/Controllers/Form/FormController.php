<?php

namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\Product;
use App\Models\ProductStep;
use App\Models\Role;
use Illuminate\Http\Request;

class FormController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

          /**
     * @OA\Get(
     *   path="/v1/forms",
     *   tags={"Forms"},
     *   summary="show all forms",
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
       //get all forms
         $forms = Form::all();
         //return json
        return response()->json($forms);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    // Create a new form annotation
    /**
     * @OA\Post(
     *  path="/v1/forms",
     * tags={"Forms"},
     * summary="create a new form",
     * @OA\RequestBody(
     *  required=true,
     * @OA\JsonContent(
     *  required={"name","product_id" , "roles" , "product_steps"},
     * @OA\Property(property="name", type="string", format="string", example="form1"),
     * @OA\Property(property="product_id", type="integer", format="integer", example="20"),
     * @OA\Property(property="roles", type="integer", format="integer", example="3,4"),
     * @OA\Property(property="product_steps", type="string", format="string", example="1,2,3"),
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
            'name' => 'required|unique:forms,name',
            'product_id' => 'required|exists:products,id',
            'roles'=>'required',
            'product_steps'=>'required',
        ]);

        //check if product not custom
        $product = Product::find($data['product_id']);
        if($product->custom==0){
            return response()->json(['message'=>'product is not custom'], 404);
        }

        $steps = explode(',', $data['product_steps']);
        foreach ($steps as $step) {
            //check if step exists and related to product
            $product_step = ProductStep::where('id', $step)->where('product_id', $data['product_id'])->first();
            if(!$product_step){
                return response()->json(['message'=>'product step not found' , "step" => $step], 404);
            }
        }

        $roles = explode(',', $data['roles']);
        foreach ($roles as $role) {
            //check if role exists
            $role = Role::find($role)->first();
            if(!$role){
                return response()->json(['message'=>'role not found'], 404);
            }
        }

        $form = Form::create($data);
        $form->roles()->sync($roles);
        $form->productSteps()->sync($steps);

        $form->roles;
        $form->productSteps;
        return response()->json($form, 200);


    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    // Get a form by id
    /**
     * @OA\Get(
     *  path="/v1/forms/{id}",
     * tags={"Forms"},
     * summary="get a form by id",
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="id of form",
     *     required=true,
     *     @OA\Schema(
     *         type="integer",
     *         format="int64",
     *     )
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
    public function show($id)
    {
        //show form
        $form = Form::find($id);
        if(!$form){
            return response()->json(['message'=>'form not found'], 404);
        }
        $form->roles;
        $form->productSteps;
        return response()->json($form, 200);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // Update a form by id
    /**
     * @OA\Put(
     *  path="/v1/forms/{id}",
     * tags={"Forms"},
     * summary="update a form by id",
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="id of form",
     *     required=true,
     *     @OA\Schema(
     *         type="integer",
     *         format="int64",
     *     )
     * ),
     * @OA\RequestBody(
     *  required=true,
     * @OA\JsonContent(
     *  required={"name","product_id" , "roles" , "product_steps"},
     * @OA\Property(property="name", type="string", format="string", example="form1"),
     * @OA\Property(property="product_id", type="integer", format="integer", example="20"),
     * @OA\Property(property="roles", type="integer", format="integer", example="3,4"),
     * @OA\Property(property="product_steps", type="string", format="string", example="1,2,3"),
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

    public function update(Request $request, $id)
    {
        //edit form
        $data = $request->validate([
            'name' => 'required|unique:forms,name',
            'product_id' => 'required|exists:products,id',
            'roles'=>'required',
            'product_steps'=>'required',
        ]);

         //check if product not custom
         $product = Product::find($data['product_id']);
         if($product->custom==0){
             return response()->json(['message'=>'product is not custom'], 404);
         }
 
         $steps = explode(',', $data['product_steps']);
         foreach ($steps as $step) {
             //check if step exists and related to product
             $product_step = ProductStep::where('id', $step)->where('product_id', $data['product_id'])->first();
             if(!$product_step){
                 return response()->json(['message'=>'product step not found' , "step" => $step], 404);
             }
         }
 
         $roles = explode(',', $data['roles']);
         foreach ($roles as $role) {
             //check if role exists
             $role = Role::find($role)->first();
             if(!$role){
                 return response()->json(['message'=>'role not found'], 404);
             }
         }
 
         $form = Form::updateOrCreate($data);
         $form->roles()->sync($roles);
         $form->productSteps()->sync($steps);
 
         $form->roles;
         $form->productSteps;
         return response()->json($form, 200);

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
