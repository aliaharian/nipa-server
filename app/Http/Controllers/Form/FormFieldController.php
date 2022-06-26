<?php

namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Models\FormField;
use App\Models\Product;
use Illuminate\Http\Request;

class FormFieldController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    // get all form fields
    /**
     * @OA\Get(
     *   path="/v1/formFields",
     *   tags={"FormFields"},
     *   summary="show all form fields",
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
       
        //get form fields of specific product
        $formFields = FormField::all();
        //return json
        return response()->json($formFields);
    }

    //get form fields from product id
    /**
     * @OA\Get(
     *   path="/v1/formFields/product/{product_id}",
     *   tags={"FormFields"},
     *   summary="show form fields of specific product",
     *   @OA\Parameter(
     *      name="product_id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *           type="integer",
     *           format="int64"
     *      )
     *   ),
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
    public function getFieldsFromProduct($product_id)
    {
       
        //check if product exists
        $product = Product::find($product_id);
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }
        //get form fields of specific product
        $formFields = FormField::where('product_id', $product_id)->get();
        //return json
        return response()->json($formFields);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    //create form field
  /**
     * @OA\Post(
     *  path="/v1/formFields",
     * tags={"FormFields"},
     * summary="create a new form field",
     * @OA\RequestBody(
     *  required=true,
     * @OA\JsonContent(
     *  required={"product_id" , "name" , "form_field_type_id" , "label" , "placeholder","help_text","validation","required","min" , "max"},
     * @OA\Property(property="product_id", type="integer", format="integer", example="19"),
     * @OA\Property(property="name", type="string", format="string", example="mobile"),
     * @OA\Property(property="form_field_type_id", type="integer", format="integer", example="7"),
     * @OA\Property(property="label", type="string", format="string", example="phone number"),
     * @OA\Property(property="placeholder", type="string", format="string", example="enter your phone number"),
     * @OA\Property(property="help_text", type="string", format="string", example="helper text"),
     * @OA\Property(property="required", type="boolean", format="integer", example="true"),
     * @OA\Property(property="min", type="integer", format="integer", example="1"),
     * @OA\Property(property="max", type="integer", format="integer", example="100")
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
        //validate
        $this->validate($request, [
            'product_id' => 'required|integer|exists:products,id',
            'name' => 'required|string',
            'form_field_type_id' => 'required|integer|exists:form_field_types,id',
            'label' => 'required|string',
            'placeholder' => 'string',
            'helper_text' => 'string',
            'required' => 'boolean',
            'min' => 'integer',
            'max' => 'integer',
        ]);
        //store new form field
        $formField = FormField::create($request->all());
        //return json
        return response()->json($formField, 200);
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
