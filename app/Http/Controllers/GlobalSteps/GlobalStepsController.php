<?php

namespace App\Http\Controllers\GlobalSteps;

use App\Http\Controllers\Controller;
use App\Models\GlobalStep;
use Illuminate\Http\Request;

class GlobalStepsController extends Controller
{

    //add a new global step
    /**
     * @OA\Post(
     *  path="/v1/globalSteps",
     * tags={"GlobalSteps"},
     * summary="create a new global step",
     * @OA\RequestBody(
     *  required=true,
     * @OA\JsonContent(
     *  required={"name","description" , "parent_id"},
     * @OA\Property(property="name", type="string", format="string", example="step1"),
     * @OA\Property(property="description", type="string", format="integer", example="optional"),
     * @OA\Property(property="parent_id", type="integer", format="integer", example=""),
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
            'name' => 'required|unique:global_steps',
            'parent_id' => 'integer|exists:global_steps,id',
        ]);
        //

        //check if product doesnt have this step
        $step = GlobalStep::create($data);

        return response()->json($step, 200);
    }
}