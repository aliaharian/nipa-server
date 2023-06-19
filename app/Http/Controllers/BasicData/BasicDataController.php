<?php

namespace App\Http\Controllers\BasicData;

use App\Http\Controllers\Controller;
use App\Models\BasicData;
use App\Models\BasicDataItem;
use Illuminate\Http\Request;

class BasicDataController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    //list of basic datas annotation
    /**
     * @OA\Get(
     * path="/v1/basicData",
     * tags={"BasicData"},
     * summary="list of basic datas",
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
    public function index()
    {
        //
        $basicDatas = BasicData::orderBy('id', 'desc')->get();
        foreach ($basicDatas as $basicData) {
            $basicData->items_count = count($basicData->items);
        }
        return response()->json($basicDatas, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    //create basic data annotation
    /**
     * @OA\Post(
     * path="/v1/basicData",
     * tags={"BasicData"},
     * summary="create basic data",
     * @OA\RequestBody(
     *   required=true,
     *  description="Pass basic data properties",
     * @OA\MediaType(
     *   mediaType="application/json",
     * @OA\Schema(
     * @OA\Property(
     * property="name",
     * type="string",
     * description="name of basic data",
     * example="basic data name",
     * ),
     * @OA\Property(
     * property="type",
     * type="string",
     * description="type of basic data",
     * example="basic data type",
     * ),
     * 
     * ),
     * ),
     * ),
     * @OA\Response(
     * response=200,
     * description="Success",
     * @OA\MediaType(
     * mediaType="application/json",
     * ),
     * ),
     * 
     * 
     * 
     * security={{ "apiAuth": {} }}
     * )
     * )
     */

    public function store(Request $request)
    {
        //validate request
        $request->validate([
            'name' => 'required',
            'type' => 'required',
        ]);

        //create basic data
        $basicData = BasicData::create([
            'name' => $request->name,
            'type' => $request->type,
        ]);


        return response()->json($basicData, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */


    //show basic data annotation
    /**
     * @OA\Get(
     * path="/v1/basicData/{id}",
     * tags={"BasicData"},
     * summary="show basic data",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="basic data id",
     * required=true,
     * @OA\Schema(
     * type="integer",
     * ),
     * ),
     * @OA\Response(
     * response=200,
     * description="Success",
     * @OA\MediaType(
     * mediaType="application/json",
     * ),
     * ),
     * 
     * security={{ "apiAuth": {} }}
     * )
     * )
     */
    public function show($id)
    {
        //
        $basicData = BasicData::find($id);
        if (!$basicData) {
            return response()->json(['message' => 'basic data not found'], 404);
        }
        $basicData->items;
        return response()->json($basicData, 200);
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    //update basic data annotation
    /**
     * @OA\Put(
     * path="/v1/basicData/{id}",
     * tags={"BasicData"},
     * summary="update basic data",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="basic data id",
     * required=true,
     * @OA\Schema(
     * type="integer",
     * ),
     * ),
     * @OA\RequestBody(
     *   required=true,
     *  description="Pass basic data properties",
     * @OA\MediaType(
     *   mediaType="application/json",
     * @OA\Schema(
     * 
     * @OA\Property(
     * property="name",
     * type="string",
     * description="name of basic data",
     * example="basic data name",
     * ),
     * @OA\Property(
     * property="type",
     * type="string",
     * description="type of basic data",
     * example="basic data type",
     * ),
     * 
     * ),
     * ),
     * ),
     * @OA\Response(
     * response=200,
     * description="Success",
     * @OA\MediaType(
     * mediaType="application/json",
     * ),
     * ),
     * 
     * 
     * 
     * security={{ "apiAuth": {} }}
     * )
     * )
     */
    public function update(Request $request, $id)
    {
        //
        $basicData = BasicData::find($id);
        if (!$basicData) {
            return response()->json(['message' => 'basic data not found'], 404);
        }
        $request->validate([
            'name' => 'required',
            'type' => 'required',
        ]);
        $basicData->update([
            'name' => $request->name,
            'type' => $request->type,
        ]);
        return response()->json($basicData, 200);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    //update status of basic data item annotation
    /**
     * @OA\Post(
     * path="/v1/basicData/item/{id}/updateStatus",
     * tags={"BasicData"},
     * summary="update status basic data item",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="basic data id",
     * required=true,
     * @OA\Schema(
     * type="integer",
     * ),
     * ),
     * @OA\Response(
     * response=200,
     * description="Success",
     * @OA\MediaType(
     * mediaType="application/json",
     * ),
     * ),
     * 
     * 
     * 
     * security={{ "apiAuth": {} }}
     * )
     * )
     */
    public function updateStatus(Request $request, $id)
    {
        //
        $basicData = BasicDataItem::find($id);
        if (!$basicData) {
            return response()->json(['message' => 'basic data item not found'], 404);
        }

        $basicData->update([
            'status' => $basicData->status == 1 ? 0 : 1
        ]);
        return response()->json($basicData, 200);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Delete(
     * path="/v1/basicData/{id}",
     * tags={"BasicData"},
     * summary="delete basic data",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="basic data id",
     * required=true,
     * @OA\Schema(
     * type="integer",
     * ),
     * ),
     * @OA\Response(
     * response=200,
     * description="Success",
     * @OA\MediaType(
     * mediaType="application/json",
     * ),
     * ),
     * 
     * security={{ "apiAuth": {} }}
     * )
     * )
     */
    public function destroy($id)
    {
        //
        $basicData = BasicData::find($id);
        if (!$basicData) {
            return response()->json(['message' => 'basic data not found'], 404);
        }
        $basicData->delete();
        return response()->json($basicData, 200);
    }


 /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Delete(
     * path="/v1/basicData/item/{id}",
     * tags={"BasicData"},
     * summary="delete basic data item ",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="basic data item id",
     * required=true,
     * @OA\Schema(
     * type="integer",
     * ),
     * ),
     * @OA\Response(
     * response=200,
     * description="Success",
     * @OA\MediaType(
     * mediaType="application/json",
     * ),
     * ),
     * 
     * security={{ "apiAuth": {} }}
     * )
     * )
     */
    public function destroyItem($id)
    {
        //
        $basicData = BasicDataItem::find($id);
        if (!$basicData) {
            return response()->json(['message' => 'basic data item not found'], 404);
        }
        $basicData->delete();
        return response()->json($basicData, 200);
    }



    //add item to basic data annotation
    /**
     * @OA\Post(
     * path="/v1/basicData/{id}/addItem",
     * tags={"BasicData"},
     * summary="add item to basic data",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="basic data id",
     * required=true,
     * @OA\Schema(
     * type="integer",
     * ),
     * ),
     * @OA\RequestBody(
     *   required=true,
     *  description="Pass item properties",
     * @OA\MediaType(
     *   mediaType="application/json",
     * @OA\Schema(
     *   * @OA\Property(
     * property="code",
     * type="string",
     * description="code of item",
     * example="item code",
     * ),
     * @OA\Property(
     * property="name",
     * type="string",
     * description="name of item",
     * example="item name",
     * ),
     *  @OA\Property(
     * property="status",
     * type="string",
     * description="status of item",
     * example="1",
     * ),
     * 
     * ),
     * ),
     * ),
     * @OA\Response(
     * response=200,
     * description="Success",
     * @OA\MediaType(
     * mediaType="application/json",
     * ),
     * ),
     * 
     * 
     * 
     * security={{ "apiAuth": {} }}
     * )
     * )
     */
    public function addItem(Request $request, $id)
    {
        //
        $basicData = BasicData::find($id);
        if (!$basicData) {
            return response()->json(['message' => 'basic data not found'], 404);
        }


        $request->validate([
            'name' => 'required',
            'code' => 'required|unique:basic_data_items,code',
            'status' => 'required',
        ]);
        $item = $basicData->items()->create([
            'name' => $request->name,
            'code' => $request->code,
            'status' => $request->status,
        ]);
        return response()->json($item, 200);
    }
    //update basic data item annotation
    /**
     * @OA\Put(
     * path="/v1/basicData/item/{id}",
     * tags={"BasicData"},
     * summary="update basic data item",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="basic data item id",
     * required=true,
     * @OA\Schema(
     * type="integer",
     * ),
     * ),
     * @OA\RequestBody(
     *   required=true,
     *  description="Pass basic data properties",
     * @OA\MediaType(
     *   mediaType="application/json",
     * @OA\Schema(
     * 
     * @OA\Property(
     * property="name",
     * type="string",
     * description="name of basic data item",
     * example="basic data item name",
     * ),
       * @OA\Property(
     * property="code",
     * type="string",
     * description="name of basic data item",
     * example="basic data item code",
     * ),
     * @OA\Property(
     * property="status",
     * type="integer",
     * description="status of basic data item",
     * example="1",
     * ),
     * 
     * ),
     * ),
     * ),
     * @OA\Response(
     * response=200,
     * description="Success",
     * @OA\MediaType(
     * mediaType="application/json",
     * ),
     * ),
     * 
     * 
     * 
     * security={{ "apiAuth": {} }}
     * )
     * )
     */
    public function editItem(Request $request, $id)
    {
        //
        $basicData = BasicDataItem::find($id);
        if (!$basicData) {
            return response()->json(['message' => 'basic data item not found'], 404);
        }
        $request->validate([
            'name' => 'required',
            'code' => 'required',
            'status' => 'required',
        ]);
        $basicData->update([
            'name' => $request->name,
            'code' => $request->code,
            'status' => $request->status,
        ]);
        return response()->json($basicData, 200);
    }

}