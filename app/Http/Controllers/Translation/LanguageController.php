<?php

namespace App\Http\Controllers\Translation;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

          /**
     * @OA\Get(
     *   path="/v1/languages",
     *   tags={"Languages"},
     *   summary="show all languages",
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
        //get all languages
        $languages = Language::all();
        return response()->json($languages);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Post(
     *  path="/v1/languages",
     * tags={"Languages"},
     * summary="create a new language",
     * @OA\RequestBody(
     *  required=true,
     * @OA\JsonContent(
     *  required={"language"},
     * @OA\Property(property="language", type="string", format="string", example="persian"),
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
            'language' => 'required|unique:languages,language',
        ]);
        $language = Language::create($data);
        return response()->json($language);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
   /**
     * @OA\Get(
     *  path="/v1/languages/{id}",
     * tags={"Languages"},
     * summary="get a language by id",
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="id of language",
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
    public function show($id)
    {
        //
        $language = Language::find($id);
        if(!$language){
            return response()->json(['message'=>'language not found'], 404);
        }
        return response()->json($language);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
     /**
     * @OA\Put(
     *  path="/v1/languages/{id}",
     * tags={"Languages"},
     * summary="update a language by id",
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="id of language",
     *     required=true,
     *     @OA\Schema(
     *         type="integer",
     *         format="int64",
     *     )
     * ),
     * @OA\RequestBody(
     *  required=true,
     * @OA\JsonContent(
     *  required={"language"},
     * @OA\Property(property="language", type="string", format="string", example="english"),
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
        $language = Language::find($id);
        if(!$language){
            return response()->json(['message'=>'language not found'], 404);
        }
        $data = $request->validate([
            'language' => 'required|unique:languages,language,'.$id,
        ]);
        $language->update($data);
        return response()->json($language);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
        /**
     * @OA\Delete(
     *  path="/v1/languages/{id}",
     * tags={"Languages"},
     * summary="delete a language by id",
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="id of language",
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
        //
        $language = Language::find($id);
        if(!$language){
            return response()->json(['message'=>'language not found'], 404);
        }
        $language->delete();
        return response()->json(['message'=>'language deleted']);
    }
}
