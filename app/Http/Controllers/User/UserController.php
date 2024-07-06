<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\File;
use App\Models\Role;
use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *   path="/v1/users",
     *   tags={"Users"},
     *   summary="show all users",
     *   description="only for howm has access",
     * @OA\Parameter(
     * name="searchParam",
     * description="search param",
     * in="query",
     * required=false,
     * @OA\Schema(
     * type="string"
     * )
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
    public function index(Request $request)
    {
        //
        $searchParam = $request->searchParam ?? "";
        $users = User::where("name", "like", "%" . $searchParam . "%")->orWhere("last_name", "like", "%" . $searchParam . "%")->orWhere("mobile", "like", "%" . $searchParam . "%")->orderBy("id", "desc")->paginate(10);
        foreach ($users as $user) {
            $user->customer;
            $user->avatar;
            $user->roles;

        }
        return response()->json($users);
    }

    /**
     * @OA\Post(
     *  path="/v1/users",
     * tags={"Users"},
     * summary="add user",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"name","last_name","email","mobile","code","avatar_id"},
     * @OA\Property(property="name", type="string", format="string", example="ali"),
     * @OA\Property(property="last_name", type="string", format="string", example="aharian"),
     * @OA\Property(property="email", type="string", format="string", example="ali@gmail.com"),
     * @OA\Property(property="mobile", type="string", format="string", example="09307473703"),
     * @OA\Property(property="code", type="string", format="string", example="NIPA12345"),
     * @OA\Property(property="avatar_hash_code", type="string", format="string", example="f7569755f197b9e21881808ddfd0d425"),
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
    public function store(Request $request)
    {
        //user,customer,role,wallet
        $data = $request->validate([
            'mobile' => 'iran_mobile|required|unique:users,mobile',
            'name' => 'required',
            'last_name' => 'required',
            'email' => 'nullable',
            'avatar_hash_code' => 'nullable|exists:files,hash_code',
            'code' => [
                'required',
                'regex:/^NIPA[a-zA-Z0-9]*$/',
                'unique:customers,code'
            ]]);

        $avatar_id = null;
        if ($data["avatar_hash_code"]) {
            $avatar_id = File::where("hash_code", $data["avatar_hash_code"])->first()->id;
        }

        $user = User::create([
            "name" => $data["name"],
            "last_name" => $data["last_name"],
            "email" => $data["email"],
            "mobile" => $data["mobile"],
            "avatar_id" => $avatar_id,
        ]);
        $customerRole = Role::where('slug', 'customer')->first();
        if (!$customerRole) {
            $customerRole = Role::create([
                'name' => "مشتری",
                "slug" => "customer"
            ]);
        }
        $user->roles()->attach($customerRole);

        Customer::create([
            'user_id' => $user->id,
            'code' => $data['code'],
        ]);
        UserWallet::create([
            'user_id' => $user->id,
            'balance' => 0,
            'credit' => 0,
            'blocked' => false,
            'active' => true,
        ]);

        return response()->json(["message" => "user created successfully"]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * @OA\Post(
     *  path="/v1/users/{id}",
     * tags={"Users"},
     * summary="update user",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="id of user",
     * required=true,
     * @OA\Schema(
     * type="integer",
     * format="int64",
     * ),
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"name","last_name","email","mobile","code","avatar_id"},
     * @OA\Property(property="name", type="string", format="string", example="ali"),
     * @OA\Property(property="last_name", type="string", format="string", example="aharian"),
     * @OA\Property(property="email", type="string", format="string", example="ali@gmail.com"),
     * @OA\Property(property="mobile", type="string", format="string", example="09307473703"),
     * @OA\Property(property="code", type="string", format="string", example="NIPA12345"),
     * @OA\Property(property="avatar_hash_code", type="string", format="string", example="f7569755f197b9e21881808ddfd0d425"),
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

        $user = User::find($id);
        if (!$user) {
            return response()->json(["message" => "user not found"], 404);
        }
        $customer = $user->customer;

//        return $customer->code;
        if (!$customer) {
            $customer = Customer::create([
                'user_id' => $user->id,
                'code' => "NIPA" . $user->id . rand(100000, 999999),
            ]);
        }
        //user,customer,role,wallet
        $data = $request->validate([
            'mobile' => [
                'required',
                'iran_mobile',
                Rule::unique('users', 'mobile')->ignore($id)
            ],
            'name' => 'required',
            'last_name' => 'required',
            'email' => 'nullable',
            'avatar_hash_code' => 'nullable|exists:files,hash_code',
            'code' => [
                'required',
                'regex:/^NIPA[a-zA-Z0-9]*$/',
                Rule::unique('customers', 'code')->ignore($customer->id)
            ]
        ]);

        $avatar_id = null;
        if ($data["avatar_hash_code"]) {
            $avatar_id = File::where("hash_code", $data["avatar_hash_code"])->first()->id;
        }

        $user->update([
            "name" => $data["name"],
            "last_name" => $data["last_name"],
            "email" => $data["email"],
            "mobile" => $data["mobile"],
            "avatar_id" => $avatar_id,
        ]);

        $customer->update([
            'code' => $data['code'],
        ]);

        return response()->json(["message" => "user updated successfully"]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
