<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BasicData;
use App\Models\Factor;
use App\Models\FactorItem;
use App\Models\FactorStatus;
use App\Models\FactorStatusEnum;
use App\Models\Form;
use App\Models\GlobalStep;
use App\Models\Order;
use App\Models\UserAnswer;
use Auth;
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
        /// write alterntive for  $user = $request->user();
        $user = Auth::user();
        //check if user role inside form role
        $user_roles = $user->roles()->pluck('role_id')->toArray();
        $product_step = $form->productSteps[0];
        $form_roles = $product_step->roles()->pluck('role_id')->toArray();
        $intersect = array_intersect($user_roles, $form_roles);

        //check if this form is for initial step
        //find global step
        $form_global_step = GlobalStep::find($product_step->global_step_id);

        if (count($intersect) == 0 && $form_global_step->description != "initialOrder") {
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
        //find form step
        $globalStep = GlobalStep::find($formStep->global_step_id);
        if ($globalStep->description !== "initialOrder") {
            $isFirstStep = false;
        } else {
            $isFirstStep = true;
        }
        // $formStepMeta = json_decode($formStep->meta);
        // if($formStepMeta->first_step == 'true'){
        //     ///////create order
        //     $order = Order::create([

        //     ]);
        // }
        $orderGroup = "";
        $factor = "";
        $needUpdateFactor = false;
        $describerText = "";
        $changesMeta = array();
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
                if (!$isFirstStep) {
                    $currOrder = Order::find($data['order_id']);
                    $orderGroup = $currOrder->orderGroup[0];
                    $factor = Factor::where("order_group_id", $orderGroup->id)->first();

                    //check if field name id width or height
                    if ($field->name == "width" || $field->name == "height") {
                        $existingAnswer = UserAnswer::where("user_id", $user->id)->where("form_field_id", $field->id)->where("order_id", $data['order_id'])->first();
                        //if exist and new answer not equal to existing
                        if ($existingAnswer && $existingAnswer->answer != $request[$field->name]) {
                            //update factor status
                            $needUpdateFactor = true;
                            $factorItem = FactorItem::where("factor_id", $factor->id)->where("order_id", $request->order_id)->first();
                            if ($field->name == "width") {
                                $factorItem->width = $request[$field->name];
                            } else if ($field->name == "height") {
                                $factorItem->height = $request[$field->name];
                            }
                            $factorItem->save();

                            //describe the name of changed field and its current and new value in description
                            $describerText .=
                                "در محصول " .
                                $existingAnswer->order->product->name .
                                " مقدار "
                                . $field->name . " از "
                                . $existingAnswer->answer . " به " . $request[$field->name] . " تغییر کرد"
                                . " | "
                            ;
                            //fill changes meta with conventional values
                            $changesMeta[] = [
                                "modifiedType" => "field",
                                "fieldName" => $field->name,
                                "fieldId" => $field->id,
                                "oldValue" => $existingAnswer->answer,
                                "newValue" => $request[$field->name],
                                "user" => $user->id,
                                "form" => $form->id,
                                "order" => $data['order_id'],
                            ];

                            //TODO:notify  admin about this change

                        }
                    }
                }
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


        ///update factor if needed
        if ($needUpdateFactor) {

            if ($factor) {
                //find factor enum of "salesResubmitPending"
                $factorEnumStatus = FactorStatusEnum::where("slug", "salesResubmitPending")->first();
                $factorStatus = FactorStatus::create([
                    "name" => "status",
                    'factor_id' => $factor->id,
                    'factor_status_enum_id' => $factorEnumStatus->id,
                    'description' => $describerText,
                    'meta' => json_encode($changesMeta),
                ]);
            }

        }
        return response()->json(
            [
                "userAnswer" => $userAnswer,
                "orderGroup" => $orderGroup,
                "isFirstStep" => $isFirstStep,
                "factor" => $factor,
                "description" => $describerText,
            ],
            200
        );
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