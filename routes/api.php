<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RolePermission\PermissionController;
use App\Http\Controllers\RolePermission\RoleController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::prefix('/v1')->group(function () {

    //global apis
    Route::post('/register', [UserAuthController::class, 'register']);
    Route::post('/login', [UserAuthController::class, 'login']);
    Route::post('/sendOtp', [UserAuthController::class, 'sendOtp']);
    //confirm otp
    Route::post('/confirmOtp', [UserAuthController::class, 'confirmOtp']);
  
    //user apis
    Route::middleware(['auth:api'])->group(function() {
        //completeProfile
        Route::post('/completeProfile', [UserAuthController::class, 'completeProfile']);
        //get user profile
        Route::get('/profile', [UserAuthController::class, 'profile']);
        Route::apiResource('products', ProductController::class);
        Route::prefix('roles')->group(function () {
                //show my roles
                Route::get('/my', [RoleController::class, 'myRoles']);
        }); 
        
        //only admin apis
        Route::middleware(['role:admin'])->group(function() {
            Route::prefix('roles')->group(function () {
                //assign role to user
                Route::post('/{role_id}/assign', [RoleController::class, 'assignRoleToUser']);
                    //show user roles
                Route::get('/user/{user_id}', [RoleController::class, 'userRoles']);
                //show my roles
                Route::get('/my', [RoleController::class, 'myRoles']);
            });   
            Route::apiResource('roles', RoleController::class);

            Route::prefix('permissions')->group(function () {
                //assign permission to role
                Route::post('/{permission_id}/assign', [PermissionController::class, 'assignPermissionToRole']);
            });   
            Route::apiResource('permissions', PermissionController::class);
        });
        
    });

});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
