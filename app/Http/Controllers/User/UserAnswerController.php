<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
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
     * required={"form_id"},
     * @OA\Property(property="form_id", type="string", format="string", example="4"),
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
    public function userAnswerForm($form_id , Request $request){
        $form = Form::find($form_id);
        if(!$form){
            return response()->json(['message'=>'form not found'], 404);
        }
        $user = $request->user();
        //check if user role inside form role
        $user_roles = $user->roles()->pluck('role_id')->toArray();
        $form_roles = $form->roles()->pluck('role_id')->toArray();
        $intersect = array_intersect($user_roles , $form_roles);
        if(count($intersect) == 0){
            return response()->json(['message'=>'user not allowed to answer this form'], 403);
        }
        $fields = $form->fields;
        //validate fields if required  
        $requirements = array();
        $requirements['order_id'] = 'required|exists:orders,id';
        foreach($fields as $field){
            $tmp="";
            if($field->required){
                $tmp.="required";
            }
            if($field->type->validations){
                $tmp.= '|'.$field->type->validations;
            }
            if($field->type->has_options==1){
                $options=array();
                foreach($field->options as $option){
                    $options[]=$option->option;
                }
                $tmp.= '|in:'.implode(',' , $options);
            }

            $requirements[$field->name] = $tmp;
        }

        $data = $request->validate($requirements);
       
        $formStep = $form->productSteps()->first();
        if(!$formStep){
            return response()->json(['message'=>'form has no steps'], 404);
        }
        // $formStepMeta = json_decode($formStep->meta);
        // if($formStepMeta->first_step == 'true'){
        //     ///////create order
        //     $order = Order::create([

        //     ]);
        // }


        return response()->json(["userrole"=>$fields , "formRole"=>$form_roles]);
    }
}
