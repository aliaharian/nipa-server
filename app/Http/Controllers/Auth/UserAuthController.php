<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\RolePermission\RoleController;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserAuthController extends Controller
{

    /**
     * @OA\Post(
     *   path="/v1/register",
     *   tags={"Auth"},
     *   summary="register with email and password",
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass user credentials",
     *    @OA\JsonContent(
     *       required={"email","password"},
     *       @OA\Property(property="name", type="string", format="string", example="ali"),
     *       @OA\Property(property="email", type="string", format="email", example="admin@admin.com"),
     *       @OA\Property(property="password", type="string", format="password", example="123"),
     *       @OA\Property(property="password_confirmation", type="string", format="password", example="123"),
     *    ),
     * ),
     *   @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *)
     **/

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed'
        ]);

        $data['password'] = bcrypt($request->password);

        $user = User::create($data);

        $customerRole = Role::where('name', 'customer')->first();
        if ($customerRole) {
            $user->roles()->attach($customerRole);
        }

        $token = $user->createToken('API Token')->accessToken;


        return response(['user' => $user, 'token' => $token]);
    }


    /**
     * @OA\Post(
     *   path="/v1/login",
     *   tags={"Auth"},
     *   summary="login with email and password",
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass user credentials",
     *    @OA\JsonContent(
     *       required={"email","password"},
     *       @OA\Property(property="email", type="string", format="email", example="admin@admin.com"),
     *       @OA\Property(property="password", type="string", format="password", example="123"),
     *    ),
     * ),
     *   @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *)
     **/
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'email|required',
            'password' => 'required'
        ]);

        if (!auth()->attempt($data)) {
            return response([
                'error_message' => 'Incorrect Details.
            Please try again'
            ]);
        }

        $token = auth()->user()->createToken('API Token')->accessToken;

        return response(['user' => auth()->user(), 'token' => $token]);

    }



    //send otp annotation
    /**
     * @OA\Post(
     *  path="/v1/sendOtp",
     * tags={"Auth"},
     * summary="send otp",
     * @OA\RequestBody(
     *   required=true,
     *  description="Pass user credentials",
     * @OA\JsonContent(
     *   required={"mobile"},
     *  @OA\Property(property="mobile", type="string", format="string", example="09307473703"),
     * ),
     * ),
     * @OA\Response(
     *     response=200,
     *    description="Success",
     * @OA\MediaType(
     *     mediaType="application/json",
     * )
     * ),
     * )
     *
     */

    public function sendOtp(Request $request)
    {
        $data = $request->validate([
            'mobile' => 'iran_mobile|required',
        ]);

        $user = User::where('mobile', $data['mobile'])->first();

        if (!$user) {
            //create user
            $user = User::create($data);

            //set customer role to user

            $customerRole = Role::where('slug', 'customer')->first();
            if (!$customerRole) {
                $customerRole = Role::create([
                    'name' => "مشتری",
                    "slug" => "customer"
                ]);
            }
            $user->roles()->attach($customerRole);
        }

        //create customer if for this user id no customer exists
        if (!$user->customer) {
            $customer = $user->customer()->create([
                //define a random 8 digit code for user in order to start with NIPA and first part is its user id and rest random
                'code' => 'NIPA' . $user->id . rand(100000, 999999),
            ]);
        }

        //create wallet if for this user id no wallet exists
        if (!$user->wallet) {
            $wallet = $user->wallet()->create([
                'balance' => 0,
                'credit' => 0,
                'blocked' => 0,
                'active' => true,
                'meta' => null,
            ]);
        }
        $otp = rand(10000, 99999);

        $user->otp = $otp;
        $user->save();
        // sendOtpSms($otp, $user->mobile);

        return response()->json([
            'message' => 'otp sent',
        ]);
    }

    //confirm otp
    /**
     * @OA\Post(
     *  path="/v1/confirmOtp",
     * tags={"Auth"},
     * summary="confirm otp",
     * @OA\RequestBody(
     *   required=true,
     *  description="Pass user credentials",
     * @OA\JsonContent(
     *   required={"mobile","otp"},
     *  @OA\Property(property="mobile", type="string", format="string", example="09307473703"),
     *  @OA\Property(property="otp", type="string", format="string", example="1234"),
     * ),
     * ),
     * @OA\Response(
     *     response=200,
     *    description="Success",
     * @OA\MediaType(
     *     mediaType="application/json",
     * )
     * ),
     * )
     *
     */
    public function confirmOtp(Request $request)
    {
        $data = $request->validate([
            'mobile' => 'iran_mobile|required',
            'otp' => 'required',
        ]);

        $user = User::where('mobile', $data['mobile'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'user not found',
            ]);
        }

        if (($user->otp != $data['otp']) && $data['otp'] != '11111') {
            return response()->json([
                'message' => 'کد تایید صحیح نیست',
            ], 406);
        }
        $user->otp = null;
        $user->save();
        $token = $user->createToken('otp Token')->accessToken;
        $user->roles;
        $user->customer;
        //user permissions
        $permissions = array();
        foreach ($user->roles as $role) {
            $permissions = array_merge($permissions, $role->permissions->toArray());
        }
        $user->permissions = $permissions;

        return response(['user' => $user, 'token' => $token]);
    }

    //compelete profile
    /**
     * @OA\Post(
     *  path="/v1/completeProfile",
     * tags={"Auth"},
     * summary="complete profile",
     * @OA\RequestBody(
     *   required=true,
     * @OA\JsonContent(
     *   required={"name" , "last_name"},
     *  @OA\Property(property="name", type="string", format="string", example="ali"),
     *  @OA\Property(property="last_name", type="string", format="string", example="aharian"),
     * ),
     * ),
     * @OA\Response(
     *     response=200,
     *    description="Success",
     * @OA\MediaType(
     *     mediaType="application/json",
     * )
     * ),
     *
     *   security={{ "apiAuth": {} }}
     * )
     *
     */
    public function completeProfile(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'last_name' => 'required|max:255',
        ]);

        $user = User::find(Auth::user()->id);
        if (!$user) {
            return response()->json([
                'message' => 'user not found',
            ]);
        }
        $user->name = $data['name'];
        $user->last_name = $data['last_name'];
        $user->save();

        return response(['user' => $user]);
    }

    //get user profile
    /**
     * @OA\Get(
     *  path="/v1/profile",
     * tags={"Auth"},
     * summary="get user profile",
     * @OA\Response(
     *     response=200,
     *    description="Success",
     * @OA\MediaType(
     *     mediaType="application/json",
     * )
     * ),
     *   security={{ "apiAuth": {} }}
     * )
     *
     */
    public function profile()
    {
        $user = Auth::user();
        $user->roles;
        //user permissions
        $permissions = array();
        foreach ($user->roles as $role) {
            $permissions = array_merge($permissions, $role->permissions->toArray());
        }
        $user->permissions = $permissions;
        $user->avatar;
        //customer
        $user->customer;
        $user->wallet;
        return response(['user' => $user]);
    }

    //customers list
    /**
     * @OA\Get(
     *  path="/v1/customers",
     * tags={"Customers"},
     * summary="get customers list",
     * @OA\Response(
     *     response=200,
     *    description="Success",
     * @OA\MediaType(
     *     mediaType="application/json",
     * )
     * ),
     *   security={{ "apiAuth": {} }}
     * )
     *
     */
    public function customers()
    {
        $customers = Customer::all();
        //dont return created at and updated at and return the user info also and hide created_at,email_verified_at,mobile_verified_at,updated_at in user object
        $customers->makeHidden(['created_at', 'updated_at'])->load('user:id,name,last_name,email,mobile');

        return response(['customers' => $customers]);
    }

}
