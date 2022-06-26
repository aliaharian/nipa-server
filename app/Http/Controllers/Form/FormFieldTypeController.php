<?php

namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Models\FormFieldType;
use Illuminate\Http\Request;

class FormFieldTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    // Create a new form annotation
    /**
     * @OA\Get(
     *   path="/v1/formFieldTypes",
     *   tags={"FormFieldTypes"},
     *   summary="show all form field types",
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
        //all form field types
        $formFieldTypes = FormFieldType::all();
        //return json
        return response()->json($formFieldTypes);
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
     *  path="/v1/formFieldTypes",
     * tags={"FormFieldTypes"},
     * summary="create a new form field type",
     * @OA\RequestBody(
     *  required=true,
     * @OA\JsonContent(
     *  required={"type" , "label" , "has_options"},
     * @OA\Property(property="label", type="string", format="string", example="متن تک خطی"),
     * @OA\Property(property="type", type="string", format="string", example="text"),
     * @OA\Property(property="has_options", type="integer", format="integer", example="0"),
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
            'type' => 'required|unique:form_field_types,type',
            'label' => 'required|unique:form_field_types,type',
            'has_options' => 'integer',
        ]);
        $formFieldType = FormFieldType::create($data);
        return response()->json($formFieldType);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // get a form field type by id
    /**
     * @OA\Get(
     *   path="/v1/formFieldTypes/{id}",
     *   tags={"FormFieldTypes"},
     *   summary="show a form field type by id",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="id of form field type",
     *     required=true,
     *     @OA\Schema(
     *       type="integer",
     *       format="int64"
     *     )
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

    public function show($id)
    {
        //
        $formFieldType = FormFieldType::find($id);
        //error if not found
        if (!$formFieldType) {
            return response()->json(['error' => 'not found'], 404);
        }
        return response()->json($formFieldType);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // update a form field type by id
    /**
     * @OA\Put(
     *  path="/v1/formFieldTypes/{id}",
     * tags={"FormFieldTypes"},
     * summary="update a form field type by id",
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="id of form field type",
     *     required=true,
     *     @OA\Schema(
     *       type="integer",
     *       format="int64"
     *     )
     *   ),
     * @OA\RequestBody(
     *  required=true,
     * @OA\JsonContent(
     *  required={"type" , "label" , "has_options"},
     * @OA\Property(property="label", type="string", format="string", example="متن تک خطی"),
     * @OA\Property(property="type", type="string", format="string", example="text"),
     * @OA\Property(property="has_options", type="integer", format="integer", example="0"),
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
        //
        $formFieldType = FormFieldType::find($id);
        if (!$formFieldType) {
            return response()->json(['error' => 'not found'], 404);
        }
        $data = $request->validate([
            'type' => 'required|unique:form_field_types,type,' . $id,
            'label' => 'required|unique:form_field_types,type,' . $id,
            'has_options' => 'integer',
        ]);
        $formFieldType->update($data);
        return response()->json($formFieldType);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // delete a form field type by id
    /**
     * @OA\Delete(
     *  path="/v1/formFieldTypes/{id}",
     * tags={"FormFieldTypes"},
     * summary="delete a form field type by id",
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="id of form field type",
     *     required=true,
     *     @OA\Schema(
     *       type="integer",
     *       format="int64"
     *     )
     *   ),
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
    public function destroy($id)
    {
        //
        $formFieldType = FormFieldType::find($id);
        if (!$formFieldType) {
            return response()->json(['error' => 'not found'], 404);
        }
        $formFieldType->delete();
        return response()->json(['success' => 'deleted']);
    }
}
