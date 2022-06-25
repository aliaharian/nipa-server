<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductStepController;
use App\Http\Controllers\RolePermission\PermissionController;
use App\Http\Controllers\RolePermission\RoleController;
use App\Http\Controllers\Form\FormController;

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
        Route::prefix('roles')->group(function () {
                //show my roles
                Route::get('/my', [RoleController::class, 'myRoles']);
        }); 


        //product apis
        Route::apiResource('products', ProductController::class)->middleware('permission:manage-products');
        Route::middleware(['permission:manage-products'])->prefix('products')->group(function () {
            //show steps
            Route::get('/{id}/steps', [ProductController::class, 'showSteps']);
        }); 
        //product steps apis
        Route::apiResource('product/steps', ProductStepController::class)->middleware('permission:manage-products');
        Route::middleware(['permission:manage-products'])->prefix('product/steps')->group(function () {
            //show steps
            Route::post('/bulk', [ProductStepController::class, 'storeBulk']);
        }); 

        Route::middleware(['permission:manage-roles'])->prefix('roles')->group(function () {
            //assign role to user
            Route::post('/{role_id}/assign', [RoleController::class, 'assignRoleToUser']);
            //show user roles
            Route::get('/user/{user_id}', [RoleController::class, 'userRoles']);
            //show my roles
            Route::get('/my', [RoleController::class, 'myRoles']);
        });   
        Route::apiResource('roles', RoleController::class)->middleware('permission:manage-roles');

        Route::middleware(['permission:manage-permissions'])->prefix('permissions')->group(function () {
            //assign permission to role
            Route::post('/{permission_id}/assign', [PermissionController::class, 'assignPermissionToRole']);
        });   
        Route::apiResource('permissions', PermissionController::class)->middleware('permission:manage-permissions');

        //form resource
        Route::apiResource('forms', FormController::class)->middleware('permission:manage-forms');
    });

});
