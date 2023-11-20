<?php

namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormCondition;
use App\Models\FormField;
use App\Models\FormFieldForm;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductStep;
use App\Models\Role;
use App\Models\UserAnswer;
use Illuminate\Http\Request;
use App\Http\Controllers\Form\FormFieldController;
use Illuminate\Support\Facades\Auth;


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
        $user = Auth::user();
        //permissions
        $permissions = $user->roles->flatMap(function ($role) {
            return $role->permissions->pluck('slug')->toArray();
        })->toArray();

        //if manage orders exist in permissions
        if (
            in_array('manage-forms', $permissions)
        ) {
            //get all forms
            $forms = Form::all();
            //return json
            return response()->json($forms);
        } else {
            return response()->json(['error' => "Access Denied!"], 403);
        }
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

        $user = Auth::user();
        //permissions
        $permissions = $user->roles->flatMap(function ($role) {
            return $role->permissions->pluck('slug')->toArray();
        })->toArray();

        //if manage orders exist in permissions
        if (
            in_array('add-form', $permissions)
        ) {

            $data = $request->validate([
                'name' => 'required|unique:forms,name',
                'product_id' => 'required|exists:products,id',
                // 'roles'=>'required',
                'product_steps' => 'required',
            ]);

            //check if product not custom
            $product = Product::find($data['product_id']);
            if ($product->custom == 0) {
                return response()->json(['message' => 'product is not custom'], 404);
            }

            $steps = explode(',', $data['product_steps']);
            foreach ($steps as $step) {
                //check if step exists and related to product
                $product_step = ProductStep::where('id', $step)->where('product_id', $data['product_id'])->first();
                if (!$product_step) {
                    return response()->json(['message' => 'product step not found', "step" => $step], 404);
                }
                if ($product_step->product_id !== $data['product_id']) {
                    return response()->json(['message' => 'product step not related to form', "step" => $step], 404);
                }
            }

            // $roles = explode(',', $data['roles']);
            // foreach ($roles as $role) {
            //     //check if role exists
            //     $role = Role::find($role)->first();
            //     if(!$role){
            //         return response()->json(['message'=>'role not found'], 404);
            //     }
            // }

            $form = Form::create($data);
            // $form->roles()->sync($roles);
            $form->productSteps()->sync($steps);

            // $form->roles;
            $form->productSteps;
            return response()->json($form, 200);
        } else {
            return response()->json(['error' => "Access Denied!"], 403);

        }

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
     *     name="language",
     *     in="header",
     *     description="language",
     *     required=true,
     *     @OA\Schema(
     *         type="string",
     *         format="int64",
     *     )
     * ),    
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
    public function show($id, Request $request)
    {
        //show form
        $form = Form::find($id);
        if (!$form) {
            return response()->json(['message' => 'form not found'], 404);
        }
        $form->productSteps;
        $form->roles = $form->productSteps[0]->roles;
        $form->fields;
        $form->conditions;
        foreach ($form->fields as $field) {
            $field->type;
            $field->options;
            $field->originForm;
            if (count($field->originForm) > 0) {
                //fetch answers
                if ($request->orderId) {
                    $order = Order::find($request->orderId);
                    if ($order) {
                        $answer = UserAnswer::where('order_id', $order->id)->where('form_field_id', $field->id)->first();
                        if ($answer) {
                            $field->userAnswer = $answer->answer;
                        } else {
                            $field->userAnswer = null;
                        }
                    }
                }
                $relatedFieldTmpForm = new \stdClass();
                $relatedFieldTmpForm->id = $field->originForm[0]->id;
                $relatedFieldTmpForm->name = $field->originForm[0]->name;
                $relatedFieldTmpForm->step = $field->originForm[0]->productSteps[0];
                $field->form = $relatedFieldTmpForm;

            } else {
                //also here, we need to load user answer
                if ($request->orderId) {
                    $order = Order::find($request->orderId);
                    if ($order) {
                        $answer = UserAnswer::where('order_id', $order->id)->where('form_field_id', $field->id)->first();
                        if ($answer) {
                            $field->userAnswer = $answer->answer;
                        } else {
                            $field->userAnswer = null;
                        }
                    }
                }
            }
            $field->basicData;
            if ($field->basicData) {
                $basicDataItems = array();
                foreach ($field->basicData->items as $item) {
                    $tmp = new \stdClass;
                    $tmp->id = $item->id;
                    $tmp->form_field_id = $field->id;
                    $tmp->label = $item->name;
                    $tmp->option = $item->code;
                    array_push($basicDataItems, $tmp);
                }
                $field->basicDataItems = $basicDataItems;
            }
        }


        //retrive all fields of forms of this product
        $relatedFields = $this->getRelatedFields($form->product_id, $form->id);

        // $relatedForms = Form::where('product_id', $form->product_id)->where('id', '<>', $form->id)->get();
        // $relatedFields = array();
        // foreach ($relatedForms as $related) {
        //     $relatedFieldsTmp = $related->fields;
        //     foreach ($relatedFieldsTmp as $relatedFieldTmp) {
        //         $relatedFieldTmp->type;
        //         $relatedFieldTmp->options;
        //         $relatedFieldTmp->basicData;
        //         $relatedFieldTmpForm = new \stdClass();
        //         $relatedFieldTmpForm->id = $related->id;
        //         $relatedFieldTmpForm->name = $related->name;
        //         $relatedFieldTmpForm->step = $related->productSteps[0];
        //         $relatedFieldTmp->form = $relatedFieldTmpForm;

        //         if ($relatedFieldTmp->basicData) {
        //             $basicDataItems = array();
        //             foreach ($relatedFieldTmp->basicData->items as $item) {
        //                 $tmp = new \stdClass;
        //                 $tmp->id = $item->id;
        //                 $tmp->form_field_id = $relatedFieldTmp->id;
        //                 $tmp->label = $item->name;
        //                 $tmp->option = $item->code;
        //                 array_push($basicDataItems, $tmp);
        //             }
        //             $relatedFieldTmp->basicDataItems = $basicDataItems;
        //         }
        //         array_push($relatedFields, $relatedFieldTmp);
        //     }
        // }

        $form->relatedFields = $relatedFields;

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

        $user = Auth::user();
        //permissions
        $permissions = $user->roles->flatMap(function ($role) {
            return $role->permissions->pluck('slug')->toArray();
        })->toArray();

        //if manage orders exist in permissions
        if (
            in_array('edit-form', $permissions)
        ) {
            //edit form
            $data = $request->validate([
                'name' => 'required|unique:forms,name,' . $id,
                'fields' => 'nullable',
                'conditions' => "nullable"
                // 'product_id' => 'required|exists:products,id',
                // 'roles' => 'required',
                // 'product_steps' => 'required',
            ]);

            $form = Form::updateOrCreate(['id' => $id], $data);
            $formFieldController = new FormFieldController();

            if (isset($data['fields'])) {
                foreach ($data['fields'] as $fieldArray) {
                    $field = new \stdClass();
                    $field = (object) $fieldArray;
                    if (@$field->server_id) {
                        //convert fieldArray to Request instance
                        $fieldArray = new Request($fieldArray);
                        $result = $formFieldController->update($fieldArray, $field->server_id);
                        $field->server_id = $result->original['id'];

                    } else {
                        $fieldArray = new Request($fieldArray);
                        // return($fieldArray);
                        $result = $formFieldController->store($fieldArray);
                        $field->server_id = $result->original['id'];
                    }
                    // return $field;
                    //sync form fields
                    // $form->fields()->sync([$field->server_id => ['form_id' => $id]]);
                    $form->fields()->syncWithoutDetaching([$field->server_id => ['form_id' => $id, 'origin_form_id' => $field->origin_form_id]]);
                }
            }

            if (isset($data['conditions'])) {
                $formCondArray = [];
                foreach ($data['conditions'] as $condArray) {
                    $cond = new \stdClass();
                    $cond = (object) $condArray;
                    //delete all form conditions
                    // FormCondition::where('form_id', $id)->delete();
                    //convert condArray to Request instance
                    $condArray = new Request($condArray);

                    $formField = FormField::find($cond->form_field_id);

                    foreach ($cond->form_field_options_id as $option) {

                        $formCond = FormCondition::updateOrCreate([
                            'form_id' => $id,
                            'form_field_id' => $cond->form_field_id,
                            'form_field_option_id' => $formField->basic_data_id ? null : $option,
                            'basic_data_item_id' => $formField->basic_data_id ? $option : null,
                            'operation' => $cond->operation,
                            'relational_form_field_id' => $cond->relational_form_field_id,
                        ]);
                        $formCondArray[] = $formCond->id;
                    }
                }
            }

            //delete all form conditions that not in formCondArray
            FormCondition::where('form_id', $id)->whereNotIn('id', $formCondArray)->delete();

            $form->fields;
            $form->conditions;

            foreach ($form->fields as $field) {
                $field->type;
                $field->options;
                $field->originForm;
                if (count($field->originForm) > 0) {
                    $relatedFieldTmpForm = new \stdClass();
                    $relatedFieldTmpForm->id = $field->originForm[0]->id;
                    $relatedFieldTmpForm->name = $field->originForm[0]->name;
                    $relatedFieldTmpForm->step = $field->originForm[0]->productSteps[0];
                    $field->form = $relatedFieldTmpForm;
                }
                $field->basicData;
                if ($field->basicData) {
                    $basicDataItems = array();
                    foreach ($field->basicData->items as $item) {
                        $tmp = new \stdClass;
                        $tmp->id = $item->id;
                        $tmp->form_field_id = $field->id;
                        $tmp->label = $item->name;
                        $tmp->option = $item->code;
                        array_push($basicDataItems, $tmp);
                    }
                    $field->basicDataItems = $basicDataItems;

                }

            }

            $relatedFields = $this->getRelatedFields($form->product_id, $form->id);
            $form->relatedFields = $relatedFields;

            return response()->json($form, 200);
        } else {
            return response()->json(['error' => "Access Denied!"], 403);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // Delete a form by id
    /**
     * @OA\Delete(
     *  path="/v1/forms/{id}",
     * tags={"Forms"},
     * summary="delete a form by id",
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
    public function destroy($id)
    {
        $user = Auth::user();
        //permissions
        $permissions = $user->roles->flatMap(function ($role) {
            return $role->permissions->pluck('slug')->toArray();
        })->toArray();

        //if manage orders exist in permissions
        if (
            in_array('delete-form', $permissions)
        ) {
            //delete form
            $form = Form::find($id);
            if (!$form) {
                return response()->json(['message' => 'form not found'], 404);
            }
            $form->delete();
            return response()->json(['message' => 'form deleted'], 200);
        } else {
            return response()->json(['error' => "Access Denied!"], 403);
        }

    }

    // assign field to form
    /**
     * @OA\Post(
     *  path="/v1/forms/{id}/fields",
     * tags={"Forms"},
     * summary="assign field to form",
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
     *  required={"field_id"},
     * @OA\Property(property="field_id", type="integer", format="integer", example="20"),
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
    public function assignFieldToForm($id, Request $request)
    {
        $data = request()->validate([
            'field_id' => 'required|exists:form_fields,id',
        ]);
        $form = Form::find($id);
        if (!$form) {
            return response()->json(['message' => 'form not found'], 404);
        }
        $field = FormField::find($data['field_id']);
        if (!$field) {
            return response()->json(['message' => 'field not found'], 404);
        }

        // if($form->product_id != $field->product_id){
        //     return response()->json(['message'=>'field not related to form'], 406);
        // }

        $formFieldForms = FormFieldForm::updateOrcreate([
            'form_id' => $form->id,
            'form_field_id' => $field->id,
        ], []);

        $formFieldForms->form;
        $formFieldForms->field->type;

        return response()->json(['message' => 'field assigned to form', 'data' => $formFieldForms], 200);
    }

    // show form fields
    /**
     * @OA\Get(
     *  path="/v1/forms/{id}/fields",
     * tags={"Forms"},
     * summary="show form fields",
     * @OA\Parameter(
     *     name="language",
     *     in="header",
     *     description="language",
     *     required=true,
     *     @OA\Schema(
     *         type="string",
     *         format="int64",
     *     )
     * ),  
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

    public function showFormFields($id)
    {
        $form = Form::find($id);
        if (!$form) {
            return response()->json(['message' => 'form not found'], 404);
        }
        $form->fields;
        foreach ($form->fields as $field) {
            $field->type;
            $field['default_value'] = $field->defaultValue($form->id);
            if ($field->type->type == 'radio' || $field->type->type == 'checkbox' || $field->type->type == 'dropdown') {
                $field->options;
            }
        }
        return response()->json($form, 200);
    }


    function getRelatedFields($product_id, $form_id)
    {
        //retrive all fields of forms of this product
        $relatedForms = Form::where('product_id', $product_id)->where('id', '<>', $form_id)->get();
        $relatedFields = array();
        foreach ($relatedForms as $related) {
            $relatedFieldsTmp = $related->fields;
            foreach ($relatedFieldsTmp as $relatedFieldTmp) {
                if ($this->findObjectById($relatedFieldTmp->id, $relatedFields) == false) {
                    $relatedFieldTmp->type;
                    $relatedFieldTmp->options;
                    $relatedFieldTmp->basicData;
                    $relatedFieldTmpForm = new \stdClass();
                    $relatedFieldTmpForm->id = $related->id;
                    $relatedFieldTmpForm->name = $related->name;
                    $relatedFieldTmpForm->step = $related->productSteps[0];
                    $relatedFieldTmp->form = $relatedFieldTmpForm;

                    if ($relatedFieldTmp->basicData) {
                        $basicDataItems = array();
                        foreach ($relatedFieldTmp->basicData->items as $item) {
                            $tmp = new \stdClass;
                            $tmp->id = $item->id;
                            $tmp->form_field_id = $relatedFieldTmp->id;
                            $tmp->label = $item->name;
                            $tmp->option = $item->code;
                            array_push($basicDataItems, $tmp);
                        }
                        $relatedFieldTmp->basicDataItems = $basicDataItems;
                    }
                    array_push($relatedFields, $relatedFieldTmp);
                }
            }
        }

        return $relatedFields;
    }

    function findObjectById($id, $array)
    {

        foreach ($array as $element) {
            if ($id == $element->id) {
                return $element;
            }
        }

        return false;
    }
}