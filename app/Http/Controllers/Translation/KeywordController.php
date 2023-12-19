<?php

namespace App\Http\Controllers\Translation;

use App\Http\Controllers\Controller;
use App\Models\Keyword;
use Illuminate\Http\Request;

class KeywordController extends Controller
{
   /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

          /**
     * @OA\Get(
     *   path="/v1/keywords",
     *   tags={"Keywords"},
     *   summary="show all keywords",
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
        //get all keywords
        $keywords = Keyword::orderBy('id','DESC')->get();
        foreach($keywords as $keyword){
            $keyword->translations;
        }
        return response()->json($keywords);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Post(
     *  path="/v1/keywords",
     * tags={"Keywords"},
     * summary="create a new Keyword",
     * @OA\RequestBody(
     *  required=true,
     * @OA\JsonContent(
     *  required={"Keyword"},
     * @OA\Property(property="keyword", type="string", format="string", example="hello"),
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
            'keyword' => 'required|unique:keywords,Keyword',
        ]);
        $keyword = Keyword::create($data);
        return response()->json($keyword);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
   /**
     * @OA\Get(
     *  path="/v1/keywords/{id}",
     * tags={"Keywords"},
     * summary="get a Keyword by id",
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="id of Keyword",
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
        $keyword = Keyword::find($id);
        if(!$keyword){
            return response()->json(['message'=>'Keyword not found'], 404);
        }
        return response()->json($keyword);
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
     *  path="/v1/keywords/{id}",
     * tags={"Keywords"},
     * summary="update a Keyword by id",
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="id of Keyword",
     *     required=true,
     *     @OA\Schema(
     *         type="integer",
     *         format="int64",
     *     )
     * ),
     * @OA\RequestBody(
     *  required=true,
     * @OA\JsonContent(
     *  required={"Keyword"},
     * @OA\Property(property="keyword", type="string", format="string", example="english"),
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
        $keyword = Keyword::find($id);
        if(!$keyword){
            return response()->json(['message'=>'Keyword not found'], 404);
        }
        $data = $request->validate([
            'keyword' => 'required|unique:keywords,Keyword,'.$id,
        ]);
        $keyword->update($data);
        return response()->json($keyword);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
        /**
     * @OA\Delete(
     *  path="/v1/keywords/{id}",
     * tags={"Keywords"},
     * summary="delete a Keyword by id",
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="id of Keyword",
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
        $keyword = Keyword::find($id);
        if(!$keyword){
            return response()->json(['message'=>'Keyword not found'], 404);
        }
        $keyword->delete();
        return response()->json(['message'=>'Keyword deleted']);
    }
}
