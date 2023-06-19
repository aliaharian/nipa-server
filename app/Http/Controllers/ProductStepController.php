<?php

namespace App\Http\Controllers;

use App\Models\GlobalStep;
use App\Models\Product;
use App\Models\ProductStep;
use Illuminate\Http\Request;
use stdClass;

class ProductStepController extends Controller
{



    //view product steps
    /**
     * @OA\Get(
     *  path="/v1/product/{code}/steps",
     * tags={"ProductSteps"},
     * summary="get product steps",
     * @OA\Parameter(
     *    description="code of product to return",
     *    in="path",
     *    name="code",
     *    required=true,
     *    example="1",
     *    @OA\Schema(
     *       type="string"
     *    )
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
    public function index($code)
    {
        $product = Product::where('code', $code)->first();
        if (!$product) {
            return response()->json(['message' => 'product not found'], 404);
        }
        // $steps = $product->steps;
        $globalSteps = GlobalStep::all();
        //check if all global steps exist in product steps and add if not exist
        foreach ($globalSteps as $globalStep) {
            $exist = false;
            foreach ($product->steps as $step) {
                if ($step->step_name == $globalStep->name) {
                    $exist = true;
                }
            }
            if (!$exist) {
                $product->steps()->create([
                    'step_name' => $globalStep->name,
                    'global_step_id' => $globalStep->id,
                ]);
            }
        }
        $steps = ProductStep::where('product_id', $product->id)->get();
        $res = array();
        foreach ($steps as $step) {
            $originalStep = GlobalStep::find($step->global_step_id);
            $step->parent_id = $originalStep->parent_id;
            $step->forms;
            $step->conditions;

            foreach ($step->forms as $form) {
                $fields = $form->form->fields;
                $optionalFields = array();
                //check if field has option and if has, add it to array
                foreach ($fields as $field) {
                    if ($field->type->has_options) {
                        $field->options;
                        $optionalFields[] = $field;
                    }
                }
                $form->optionalFields = $optionalFields;
            }
            $step->roles;
            $res[] = $step;

        }


        return response()->json($res, 200);
    }
    //view product step info
    /**
     * @OA\Get(
     *  path="/v1/productSteps/{id}",
     * tags={"ProductSteps"},
     * summary="get product step info",
     * @OA\Parameter(
     *    description="code of product to return",
     *    in="path",
     *    name="id",
     *    required=true,
     *    example="1",
     *    @OA\Schema(
     *       type="integer"
     *    )
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
        $step = ProductStep::find($id);
        $step->global_step = GlobalStep::find($step->global_step_id);
        $step->global_step_parent = GlobalStep::find($step->global_step->parent_id);
        $step->product;
        $step->forms;
        $step->conditions;
        return response()->json($step, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    // Create a new product step annotation
    /**
     * @OA\Post(
     *  path="/v1/product/steps/bulk",
     * tags={"ProductSteps"},
     * summary="create a new product step bulk",
     * @OA\RequestBody(
     *  required=true,
     * @OA\JsonContent(
     *  required={"steps","product_id"},
     * @OA\Property(property="steps", type="string", format="string", example="step1,step2,step3"),
     * @OA\Property(property="product_id", type="integer", format="integer", example="1"),
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
    public function storeBulk(Request $request)
    {
        $data = $request->validate([
            'steps' => 'required',
            'product_id' => 'required',
        ]);
        //
        $product = Product::find($request->product_id);
        if (!$product) {
            return response()->json(['message' => 'product not found'], 404);
        }
        if ($product->custom == 0) {
            return response()->json(['message' => 'product is not custom'], 404);
        }


        $steps = explode(',', $data['steps']);
        foreach ($steps as $step) {
            //check if product doesnt have this step
            $product_step = ProductStep::where('step_name', $step)->where('product_id', $product->id)->first();
            if (!$product_step) {
                $product->steps()->create([
                    'step_name' => $step,
                ]);
            }
        }
        $product->steps;
        return response()->json($product, 200);
    }


    //add a new step to a product
    /**
     * @OA\Post(
     *  path="/v1/product/steps",
     * tags={"ProductSteps"},
     * summary="create a new product step",
     * @OA\RequestBody(
     *  required=true,
     * @OA\JsonContent(
     *  required={"step_name","product_id" , "parent_step_id"},
     * @OA\Property(property="step_name", type="string", format="string", example="step1"),
     * @OA\Property(property="product_id", type="integer", format="integer", example="1"),
     * @OA\Property(property="parent_step_id", type="integer", format="integer", example="1"),
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
            'step_name' => 'required',
            'product_id' => 'required',
            'parent_step_id' => 'integer',
        ]);
        //
        $product = Product::find($request->product_id);
        if (!$product) {
            return response()->json(['message' => 'product not found'], 404);
        }
        if ($product->custom == 0) {
            return response()->json(['message' => 'product is not custom'], 404);
        }

        //check if product doesnt have this step
        $product_step = ProductStep::where('step_name', $data['step_name'])->where('product_id', $product->id)->first();
        if (!$product_step) {
            $product->steps()->create([
                'step_name' => $data['step_name'],
                'parent_step_id' => $data['parent_step_id'],
            ]);
        }
        $product->steps;
        return response()->json($product, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // Update a product step annotation
    /**
     * @OA\Put(
     *  path="/v1/product/steps/{id}",
     * tags={"ProductSteps"},
     * summary="update a product step",
     * @OA\Parameter(
     *   name="id",
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
     * required={"step_name"},
     * @OA\Property(property="step_name", type="string", format="string", example="step name"),
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
    public function update(Request $request, $id)
    {
        //
        $product_step = ProductStep::find($id);
        if (!$product_step) {
            return response()->json(['message' => 'product step not found'], 404);
        }
        $data = $request->validate([
            'step_name' => 'required',
        ]);
        $product_step->update($data);
        $product_step->product;
        return response()->json($product_step, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // Delete a product step annotation
    /**
     * @OA\Delete(
     *  path="/v1/product/steps/{id}",
     * tags={"ProductSteps"},
     * summary="delete a product step",
     * @OA\Parameter(
     *  name="id",
     * in="path",
     * required=true,
     * @OA\Schema(
     * type="integer",
     * format="int64"
     * )
     * ),
     * @OA\Response(
     *  response=200,
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
        $product_step = ProductStep::find($id);
        if (!$product_step) {
            return response()->json(['message' => 'product step not found'], 404);
        }
        $product_step->delete();
        return response()->json(['message' => 'product step deleted'], 200);
    }

    /**
     * @OA\Put(
     *  path="/v1/product/steps/{id}/setCreateStep",
     * tags={"ProductSteps"},
     * summary="set step for create step",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * @OA\Schema(
     * type="integer",
     * format="int64"
     * )
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
    public function setCreateStep($id)
    {
        $step = ProductStep::find($id);
        if (!$step) {
            return response()->json(['message' => 'product step not found'], 404);
        }
        //clear all metas of steps with same product id
        $tmp = ProductStep::where('product_id', $step->product_id)->get();
        foreach ($tmp as $t) {
            $tTmp = ProductStep::find($t->id);
            $tTmp->update([
                'meta' => null,
            ]);
        }

        $meta = new \stdClass();
        $meta->first_step = "true";

        $step->update([
            'meta' => json_encode($meta),
        ]);
        //return response()->json($step, 200);

        return response()->json($step, 200);
    }

    //set roles to step with post method and get step id from query param as {id} and get roles as array of role ids in body
    /**
     * @OA\Post(
     *  path="/v1/product/steps/{id}/setRoles",
     * tags={"ProductSteps"},
     * summary="set roles to step",
     * @OA\Parameter(
     * name="id",
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
     * required={"roles"},
     * @OA\Property(property="roles", type="string", format="string", example="1,2,3"),
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
    public function setRoles(Request $request, $id)
    {
        $step = ProductStep::find($id);
        if (!$step) {
            return response()->json(['message' => 'product step not found'], 404);
        }
        $data = $request->validate([
            'roles' => 'required',
        ]);
        //convert string to array by ,
        $roles = explode(',', $data['roles']);
        $step->roles()->sync($roles);
        $step->roles;
        return response()->json($step, 200);
    }
}