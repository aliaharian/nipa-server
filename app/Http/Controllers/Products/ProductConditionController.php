<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\FormField;
use App\Models\ProductStepsCondition;
use Illuminate\Http\Request;

class ProductConditionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }


    //add a new condition to a product step

    /**
     * @OA\Post(
     *  path="/v1/product/steps/conditions",
     * tags={"ProductStepsConditions"},
     * summary="Add a new condition to a product step",
     * @OA\RequestBody(
     *  required=true,
     * @OA\JsonContent(
     *  required={"step_id","field_id" , "option_id","next_step_id"},
     * @OA\Property(property="step_id", type="integer", example="1"),
     * @OA\Property(property="field_id", type="integer", example="1"),
     * @OA\Property(property="option_id", type="integer", example="1"),
     * @OA\Property(property="next_step_id", type="integer", example="1"),
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
        //

        $data = $request->validate([
            'step_id' => 'required|exists:product_steps,id',
            'field_id' => 'required|exists:form_fields,id',
            'option_id' => 'nullable',
            'next_step_id' => 'required|exists:product_steps,id'
        ]);
        if (!$data['option_id']) {
//delete cond.
            ProductStepsCondition::where("product_step_id", $data['step_id'])->where("form_field_id", $data['field_id'])->delete();
            return response()->json([
                'message' => 'Condition deleted successfully',
            ], 200);
        } else {
            $productCondArray = array();
            //check if field is from basic data or not
            $field = FormField::find($data['field_id']);

            foreach ($data['option_id'] as $optArray) {

                $condition = ProductStepsCondition::updateOrCreate([
                    'product_step_id' => $data['step_id'],
                    'form_field_id' => $data['field_id'],
                    'form_field_option_id' => $field->basic_data_id ? null : $optArray,
                    'basic_data_item_id' => $field->basic_data_id ? $optArray : null,
                    'next_product_step_id' => $data['next_step_id']
                ]);

                $productCondArray[] = $condition->id;
            }
        }

        ProductStepsCondition::where('product_step_id', $data['step_id'])->whereNotIn('id', $productCondArray)->delete();


        return response()->json([
            'message' => 'Condition created successfully',
            'condition' => $condition
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
}
