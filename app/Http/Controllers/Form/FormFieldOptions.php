<?php

namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Models\FormField;
use App\Models\FormFieldOptions as ModelsFormFieldOptions;
use Illuminate\Http\Request;

class FormFieldOptions extends Controller
{
    //create option annotation
    /**
     * @OA\Post(
     *   path="/v1/formFieldOptions",
     *   tags={"FormFieldOptions"},
     *   summary="create form field option",
      * @OA\RequestBody(
     *  required=true,
     * @OA\JsonContent(
     *  required={"form_field_id" , "option" , "label"},
     * @OA\Property(property="form_field_id", type="integer", format="integer", example="1"),
     * @OA\Property(property="option", type="string", format="string", example="male"),
     * @OA\Property(property="label", type="integer", format="integer", example="مرد"),
     * ),
     * ),
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
    public function create(Request $request)
    {
        //add option
        //validation
        $request->validate([
            'form_field_id' => 'required|integer|exists:form_fields,id',
            'option' => 'required|string',
            'label' => 'required|string',
        ]);

        //check if form field type is dropdown or radio or checkbox
        $formField = FormField::find($request->form_field_id);
        if ($formField->type->type != 'dropdown' && $formField->type->type != 'radio' && $formField->type->type != 'checkbox'  ) {
            return response()->json(['error' => 'form field type is not dropdown or radio or checkbox'], 400);
        }
        //check if we have same option with same form field
        $tmp = ModelsFormFieldOptions::where('option',$request->option)->where('form_field_id',$request->form_field_id)->first();
        if($tmp){
            return response()->json(['error' => 'duplicate option for field'], 400);
        }

        //add option
        $formFieldOption = ModelsFormFieldOptions::create($request->all());
        //return json
        return response()->json($formFieldOption);
    }

     /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // Delete a option by id
    /**
     * @OA\Delete(
     * path="/v1/formFieldOptions/{id}",
     * tags={"FormFieldOptions"},
     * summary="delete a form by id",
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="id of option",
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

    public function destroy($id){
        //delete option
        $option = ModelsFormFieldOptions::find($id);
        if(!$option){
            return response()->json(['message'=>'option not found'], 404);
        }
        $option->delete();
        return response()->json(['message'=>'option deleted'], 200);

    }

    //get options of field
    /**
     * @OA\Get(
     *   path="/v1/formFieldOptions/field/{field_id}",
     *   tags={"FormFieldOptions"},
     *   summary="get options of field",
     * @OA\Parameter(
     *     name="field_id",
     *     in="path",
     *     description="id of field",
     *     required=true,
     *     @OA\Schema(
     *         type="integer",
     *         format="int64",
     *     )
     * ),
     * @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   security={{ "apiAuth": {} }}
     *)
     **/

    public function optionsOfField ($field_id){
        //get options of field  
        $field = FormField::find($field_id);
        //check if field exists
        if(!$field){
            return response()->json(['message'=>'field not found'], 404);
        }
        $options = ModelsFormFieldOptions::where('form_field_id',$field_id)->get();
        return response()->json(['field'=>$field , "options"=>$options]);
        
    }
}
