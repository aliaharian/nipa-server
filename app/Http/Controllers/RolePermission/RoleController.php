<?php

namespace App\Http\Controllers\RolePermission;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\UsersRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

       /**
     * @OA\Get(
     *   path="/v1/roles",
     *   tags={"Roles"},
     *   summary="show all roles",
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
        $roles = Role::all();
        foreach ($roles as $role) {
            $role->permissions;
            $usersCount = UsersRole::where("role_id",$role->id)->count();
            $role->users_count = $usersCount;
        }
        return response()->json($roles, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    /**
     * @OA\Post(
     *   path="/v1/roles",
     *   tags={"Roles"},
     *   summary="add role",
        * @OA\RequestBody(
        *    required=true,
        *    @OA\JsonContent(
        *       required={"name","slug"},
        *       @OA\Property(property="name", type="string", format="string", example="admin"),
        *       @OA\Property(property="slug", type="string", format="string", example="admin"),
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
            'name' => 'required|unique:roles',
            'slug' => 'required|unique:roles',
        ]);

        $role = Role::create($data);
        return response()->json($role, 200);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

       /**
     * @OA\Get(
     *   path="/v1/roles/{id}",
     *   tags={"Roles"},
     *   summary="show specific role",
     *  @OA\Parameter(
     *     name="id",
     *    in="path",
     *   description="id of role",
     *  required=true,
     * @OA\Schema(
     *             type="integer",
     *            format="int64"
     *        )
     *  ),
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
        $role = Role::find($id);
        //return exception if role not exitsts
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        return response()->json($role, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    //update role swagger annotation
    /**
     * @OA\Put(
     *   path="/v1/roles/{id}",
     *   tags={"Roles"},
     *   summary="update role",
     *  @OA\Parameter(
     *     name="id",
     *    in="path",
     *   description="id of role",
     *  required=true,
     * @OA\Schema(
     *             type="integer",
     *            format="int64"
     *        )
     *  ),
     * @OA\RequestBody(
     *    required=true,
     *    @OA\JsonContent(
     *       required={"name","slug"},
     *       @OA\Property(property="name", type="string", format="string", example="admin"),
     *       @OA\Property(property="slug", type="string", format="string", example="admin"),
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
        //update role
        $role = Role::find($id);
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }
        
        $data = $request->validate([
            'name' => 'required|unique:roles,name,'.$id,
            'slug' => 'required|unique:roles,slug,'.$id,
        ]);

        $role->update($data);
        return response()->json($role, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    //delete role swagger annotation
    /**
     * @OA\Delete(
     *   path="/v1/roles/{id}",
     *   tags={"Roles"},
     *   summary="delete role",
     *  @OA\Parameter(
     *     name="id",
     *    in="path",
     *   description="id of role",
     *  required=true,
     * @OA\Schema(
     *             type="integer",
     *            format="int64"
     *        )
     *  ),
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
        //delete specific role
        $role = Role::find($id);
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }
        $role->delete();
        return response()->json(['message' => 'Role deleted successfully'], 200);
    }

    //assign role to user
    /**
     * @OA\Post(
     *   path="/v1/roles/{role_id}/assign",
     *   tags={"Roles"},
     *   summary="assign role to user",
     *  @OA\Parameter(
     *     name="role_id",
     *    in="path",
     *   description="id of role",
     *  required=true,
     * @OA\Schema(
     *             type="integer",
     *            format="int64"
     *        )
     *  ),
     * @OA\RequestBody(
     *    required=true,
     *    @OA\JsonContent(
     *       required={"user_id"},
     *       @OA\Property(property="user_id", type="integer", format="int64", example="1"),
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
    public function assignRoleToUser(Request $request, $role_id)
    {
        //assign role to user
        $role = Role::find($role_id);
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);
        $user = User::find($data['user_id']);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $user->roles()->attach($role);
        return response()->json(['message' => 'Role assigned successfully'], 200);
    }

    //show user roles
    /**
     * @OA\Get(
     *   path="/v1/roles/user/{user_id}",
     *   tags={"Roles"},
     *   summary="show user roles",
     *  @OA\Parameter(
     *     name="user_id",
     *    in="path",
     *   description="id of user",
     *  required=true,
     * @OA\Schema(
     *             type="integer",
     *            format="int64"
     *        )
     *  ),
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
    public function userRoles($user_id)
    {
        //show current user roles
        $user = User::find($user_id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $roles = $user->roles;
        return response()->json($roles, 200);
    }

    //my roles
    /**
     * @OA\Get(
     *   path="/v1/roles/my",
     *   tags={"Roles"},
     *   summary="show my roles",
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
    public function myRoles()
    {
        //show current user roles
        
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $roles = $user->roles;
        return response()->json($roles, 200);
    }
}

