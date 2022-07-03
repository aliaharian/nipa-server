<?php

namespace App\Http\Controllers\Translation;

use App\Http\Controllers\Controller;
use App\Models\Translation;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    //
      /**
     * @OA\Post(
     *  path="/v1/translations",
     * tags={"Translations"},
     * summary="create a new translation",
     * @OA\RequestBody(
     *  required=true,
     * @OA\JsonContent(
     *  required={"Keyword_id","language_id","translation"},
     * @OA\Property(property="keyword_id", type="integer", format="integer", example="1"),
     * @OA\Property(property="language_id", type="integer", format="integer", example="1"),
     * @OA\Property(property="translation", type="string", format="string", example="hello"),
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
    public function addTranslation(Request $request){
        //validation for language id and keyword id and translation
        $data = $request->validate([
            'language_id' => 'required|integer|exists:languages,id',
            'keyword_id' => 'required|integer|exists:keywords,id',
            'translation' => 'required|string',
        ]);
        //create new translation
        $translation = Translation::updateOrCreate([
            'language_id' => $data['language_id'],
            'keyword_id' => $data['keyword_id'],
        ],[
            'translation' => $data['translation'],
        ]);
        //return response
        return response()->json($translation);
    }
}
