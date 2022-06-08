<?php

namespace App\Http\Controllers\RolePermission;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //show all permissions annotation
    /**
     * @OA\Get(
     *   path="/v1/permissions",
     *   tags={"Permissions"},
     *   summary="show all permissions",
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
        $permissions = Permission::all();
        return response()->json($permissions, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

          /**
     * @OA\Post(
     *   path="/v1/permissions",
     *   tags={"Permissions"},
     *   summary="add permission",
        * @OA\RequestBody(
        *    required=true,
        *    @OA\JsonContent(
        *       required={"name","slug"},
        *       @OA\Property(property="name", type="string", format="string", example="add product"),
        *       @OA\Property(property="slug", type="string", format="string", example="add-product"),
        *    ),
        * ),
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
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|unique:permissions',
            'slug' => 'required|unique:permissions',
        ]);

        $permission = Permission::create($data);
        return response()->json($permission, 200);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    //show one permission annotation
    /**
     * @OA\Get(
     *   path="/v1/permissions/{id}",
     *   tags={"Permissions"},
     *   summary="show one permission",
     *   @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *          format="int64"
     *      )
     *   ),
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
    public function show($id)
    {
        $permission = Permission::find($id);
        //show exception if not found
        if(!$permission){
            return response()->json(['message'=>'permission not found'], 404);
        }
        return response()->json($permission, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    //update permission annotation
    /**
     * @OA\Put(
     *   path="/v1/permissions/{id}",
     *   tags={"Permissions"},
     *   summary="update permission",
     *   @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *          format="int64"
     *      )
     *   ),
     *   @OA\RequestBody(
     *    required=true,
     *    @OA\JsonContent(
     *       required={"name","slug"},
     *       @OA\Property(property="name", type="string", format="string", example="add product"),
     *       @OA\Property(property="slug", type="string", format="string", example="add-product"),
     *    ),
     * ),
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
    public function update(Request $request, $id)
    {
        $permission = Permission::find($id);
        //show exception if not found
        if(!$permission){
            return response()->json(['message'=>'permission not found'], 404);
        }
        $data = $request->validate([
            'name' => 'required|unique:permissions,name,'.$id,
            'slug' => 'required|unique:permissions,slug,'.$id,
        ]);
        $permission->update($data);
        return response()->json($permission, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    //delete permission annotation
    /**
     * @OA\Delete(
     *   path="/v1/permissions/{id}",
     *   tags={"Permissions"},
     *   summary="delete permission",
     *   @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *          format="int64"
     *      )
     *   ),
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
    public function destroy($id)
    {
        $permission = Permission::find($id);
        //show exception if not found
        if(!$permission){
            return response()->json(['message'=>'permission not found'], 404);
        }
        $permission->delete();
        return response()->json(['message'=>'permission deleted'], 200);
    }

    //assign permission to role
    /**
     * @OA\Post(
     *   path="/v1/permissions/{permission_id}/assign",
     *   tags={"Permissions"},
     *   summary="assign permission to role",
     *   @OA\Parameter(
     *      name="permission_id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *          format="int64"
     *      )
     *   ),
     *   @OA\RequestBody(
     *    required=true,
     *    @OA\JsonContent(
     *       required={"role_id"},
     *       @OA\Property(property="role_id", type="integer", format="int64", example=1),
     *    ),
     * ),
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
    public function assignPermissionToRole(Request $request, $id)
    {
        $permission = Permission::find($id);
        //show exception if not found
        if(!$permission){
            return response()->json(['message'=>'permission not found'], 404);
        }
        $data = $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);
        $role = Role::find($data['role_id']);
        $permissions = $role->permissions;
        if(!$permissions->contains($permission)){
            $permission->roles()->attach($data['role_id']);
            return response()->json($permission, 200);

        }else{
            return response()->json(['message'=>'permission already assigned to role'], 400);
        }
    }
}
