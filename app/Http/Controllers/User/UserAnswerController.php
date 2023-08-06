<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BasicData;
use App\Models\Form;
use App\Models\Order;
use App\Models\UserAnswer;
use Illuminate\Http\Request;

class UserAnswerController extends Controller
{
    //
    /**
     * @OA\Post(
     *  path="/v1/userAnswer/{form_id}/answer",
     * tags={"UserAnswer"},
     * summary="answer user to form",
     * @OA\Parameter(
     * name="form_id",
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
     * required={"order_id"},
     * @OA\Property(property="order_id", type="string", format="string", example="4"),
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
    public function userAnswerForm($form_id, Request $request)
    {
        $form = Form::find($form_id);
        if (!$form) {
            return response()->json(['message' => 'form not found'], 404);
        }
        $user = $request->user();
        //check if user role inside form role
        $user_roles = $user->roles()->pluck('role_id')->toArray();
        $product_step = $form->productSteps[0];
        $form_roles = $product_step->roles()->pluck('role_id')->toArray();
        $intersect = array_intersect($user_roles, $form_roles);
        if (count($intersect) == 0) {
            return response()->json(['message' => 'user not allowed to answer this form'], 403);
        }
        //check if user created this order if not admin

        $fields = $form->fields;
        $conditions = $form->conditions;
        //validate fields if required  
        $requirements = array();
        $requirements['order_id'] = 'required|exists:orders,id';
        foreach ($fields as $field) {
            $tmp = "";
            $checkValidate = true;
            $cond = $this->findObjectByKey($conditions, "relational_form_field_id", $field->id);
            if ($cond) {
                $checkValidate = false;
                $mainField = $this->findObjectByKey($fields, "id", $cond->form_field_id);
                if ($mainField) {
                    //TODO:check like REACT
                }

            }

            if ($checkValidate) {
                if ($field->required) {
                    $tmp .= "required";
                }
                if ($field->type->validations) {
                    $tmp .= '|' . $field->type->validations;
                }
                if ($field->type->has_options == 1) {
                    $options = array();
                    //check if from basic datas or from options?
                    if ($field->basic_data_id) {
                        $basicData = BasicData::find($field->basic_data_id);
                        foreach ($basicData->items as $option) {
                            $options[] = $option->code;
                        }
                    } else {
                        foreach ($field->options as $option) {
                            $options[] = $option->option;
                        }
                    }
                    if ($field->type->type == 'checkbox') {
                        $tmp .= '|array';
                        $requirements[$field->name . '.*'] = 'in:' . implode(',', $options);

                        // 'items.*' => 'in:' . implode(',', $allowed_items),


                    } else {
                        $tmp .= '|in:' . implode(',', $options);

                    }
                }


                $requirements[$field->name] = $tmp;
            }

        }

        // return $requirements;
        $data = $request->validate($requirements);

        $formStep = $form->productSteps()->first();
        if (!$formStep) {
            return response()->json(['message' => 'form has no steps'], 404);
        }
        // $formStepMeta = json_decode($formStep->meta);
        // if($formStepMeta->first_step == 'true'){
        //     ///////create order
        //     $order = Order::create([

        //     ]);
        // }
        //create user answer
        foreach ($fields as $field) {
            if (gettype($request[$field->name]) == 'array') {
                //remove prev...
                UserAnswer::where("user_id", $user->id)->where("form_field_id", $field->id)->where("order_id", $data['order_id'])->delete();
                foreach ($request[$field->name] as $fieldValue) {
                    $userAnswer = UserAnswer::create([
                        'user_id' => $user->id,
                        'form_field_id' => $field->id,
                        'order_id' => $data['order_id'],
                        'answer' => $field->type->type == "uploadFile" ? $fieldValue["hashCode"] : $fieldValue
                    ]);

                }

            } else {
                $userAnswer = UserAnswer::updateOrCreate([
                    'user_id' => $user->id,
                    'form_field_id' => $field->id,
                    'order_id' => $data['order_id'],
                ], [
                    'answer' => $request[$field->name],
                ]);
            }
        }

        $userAnswer = UserAnswer::where('user_id', $user->id)->where('order_id', $data['order_id'])->get();

        return response()->json(["userAnswer" => $userAnswer], 200);
    }

    function findObjectByKey($array, $key, $id)
    {

        foreach ($array as $element) {
            if ($id == $element[$key]) {
                return $element;
            }
        }

        return false;
    }

}