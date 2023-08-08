<?php

namespace App\Http\Controllers\Files;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\File;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Auth;

class FileController extends Controller
{
    //
    /**
     * @OA\Post(
     *      path="/v1/files",
     *      summary="Upload a file",
     *     tags={"Files"},
     *      description="Upload a file to the server",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="file",
     *                      description="The file to upload",
     *                      type="file"
     *                  ),
     *                  required={"file"}
     *              )
     *          )
     *      ),
     * @OA\Response(
     *     response=200,
     *     description="Success",
     *    @OA\MediaType(
     *        mediaType="application/json",
     *   )
     * ),
     * security={{ "apiAuth": {} }}
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        //check MAX_FILE_SIZE from .env
        $maxFileSize = env('MAX_FILE_SIZE', 1024 * 1024 * 10);
        if ($request->file('file')->getSize() > $maxFileSize) {
            return response()->json([
                'error' => 'File size is too large',
                'maxFileSize' => round($maxFileSize / 1024 / 1024) . ' MB'
            ], Response::HTTP_BAD_REQUEST);
        }
        //check FORBIDDEN_UPLOAD_FORMATS from .env
        $forbiddenUploadFormats = explode(',', env('FORBIDDEN_UPLOAD_FORMATS', 'exe,php,js'));
        if (in_array($request->file('file')->getClientOriginalExtension(), $forbiddenUploadFormats)) {
            return response()->json([
                'error' => 'File format is not allowed',
            ], Response::HTTP_BAD_REQUEST);
        }
        // $path = $request->file('file')->store('public/files');
        $path = Storage::disk('public')->put(
            'files', $request->file('file')
        );
        //save in db
        $file = File::create([
            'name' => $request->file('file')->getClientOriginalName(),
            'path' => $path,
            'type' => $request->file('file')->getMimeType(),
            'hash_code' => md5(md5_file($request->file('file')->getRealPath()) . time() . rand())
        ]);
        return response()->json([
            'message' => 'File uploaded successfully',
            'file' => $file
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *      path="/v1/files/{hashCode}",
     *      summary="Get a file",
     *     tags={"Files"},
     *      description="Get a file from the server",
     *      @OA\Parameter(
     *          name="hashCode",
     *          description="The hashCode of the file to retrieve",
     *          required=true,
     *          in="path",
     *          example="e9c721b17d447656204334118ef23c80",
     *     @OA\Schema(
     *         type="string",
     *     )
     *      ),
     * @OA\Parameter(
     *         name="thumbnail",    
     *        in="query",
     *        description="Get a thumbnail of the file",
     *       required=false,
     *      @OA\Schema(
     *        type="boolean",
     *    )
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Success",
     *    @OA\MediaType(
     *        mediaType="application/json",
     *   )
     * ),
     * )
     */
    public function read($hashCode, Request $request)
    {
        //find file from db
        $file = File::where('hash_code', $hashCode)->first();

        if (!$file) {
            return response()->json(['message' => 'File not found!'], 404);
        }
        $storagePath = Storage::disk('public')->getDriver()->getAdapter()->getPathPrefix();

        $filename = $file->path;
        $file = Storage::disk('public')->get($filename);


        if ($request->has('thumbnail') && $request->get('thumbnail') == "true") {
            $img = Image::make($file);
            $img->resize(200, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            return $img->response('jpg');
        }
        return response($file, 200)->header('Content-Type', Storage::disk('public')->getMimeType($filename));
    }
    /**
     * @OA\Delete(
     *     path="/v1/files/{hashCode}",
     *     tags={"Files"},
     *     summary="Delete a file",
     *     description="Deletes a file by its ID",
     *     operationId="deleteFile",
     *     security={{"apiAuth": {}}},
     *     @OA\Parameter(
     *         name="hashCode",
     *         in="path",
     *         description="hashCode of the file to delete",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="File deleted successfully",
     *    @OA\MediaType(
     *        mediaType="application/json",
     *   )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="File not found",
     *    @OA\MediaType(
     *        mediaType="application/json",
     *   )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *    @OA\MediaType(
     *        mediaType="application/json",
     *   )
     *     ),
     * )
     */
    public function destroy($hashCode)
    {
        $user = Auth::user();
        //permissions
        $permissions = $user->roles->flatMap(function ($role) {
            return $role->permissions->pluck('slug')->toArray();
        })->toArray();

        //if manage orders exist in permissions
        if (
            in_array('manage-files', $permissions)
        ) {
            $file = File::where('hash_code', $hashCode)->first();

        } else {

            //TODO:add user id to files table and define condition
            $file = File::where('hash_code', $hashCode)->first();

        }

        if (!$file) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $filename = $file->path;
        $fileFile = Storage::disk('public')->delete($filename);

        // Delete the file from the database
        $file->delete();



        return response()->json([
            'message' => 'File deleted successfully',
            'file' => $file
        ], 200);

    }

}