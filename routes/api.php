<?php

use App\Http\Controllers\BasicData\BasicDataController;
use App\Http\Controllers\Factor\FactorController;
use App\Http\Controllers\Factor\FactorPaymentStepController;
use App\Http\Controllers\GlobalSteps\GlobalStepsController;
use App\Http\Controllers\Products\ProductConditionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductStepController;
use App\Http\Controllers\RolePermission\PermissionController;
use App\Http\Controllers\RolePermission\RoleController;
use App\Http\Controllers\Form\FormController;
use App\Http\Controllers\Form\FormFieldController;
use App\Http\Controllers\Form\FormFieldOptions;
use App\Http\Controllers\Form\FormFieldTypeController;
use App\Http\Controllers\Invoice\InvoiceController;
use App\Http\Controllers\Order\OrderController;
use App\Http\Controllers\Order\OrderGroupController;
use App\Http\Controllers\Translation\KeywordController;
use App\Http\Controllers\Translation\LanguageController;
use App\Http\Controllers\Translation\TranslationController;
use App\Http\Controllers\User\UserAnswerController;
use App\Http\Controllers\Files\FileController;

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
Route::get("/phpinfo", function () {
    echo phpinfo();
});
Route::prefix('/v1')->group(function () {

    //global apis
    Route::post('/register', [UserAuthController::class, 'register']);
    Route::post('/login', [UserAuthController::class, 'login']);
    Route::post('/sendOtp', [UserAuthController::class, 'sendOtp']);
    //confirm otp
    Route::post('/confirmOtp', [UserAuthController::class, 'confirmOtp']);

    //user apis
    Route::middleware(['auth:api'])->group(function () {
        //completeProfile
        Route::post('/completeProfile', [UserAuthController::class, 'completeProfile']);
        //get user profile
        Route::get('/profile', [UserAuthController::class, 'profile']);
        Route::prefix('roles')->group(function () {
            //show my roles
            Route::get('/my', [RoleController::class, 'myRoles']);
        });


        //customers
        Route::get('customers', [UserAuthController::class, 'customers'])->middleware('permission:add-order-as-another');

        //factor payment steps api
        ///v1/factor/paymentStep
        //api route
        Route::apiResource('factor/paymentStep', FactorPaymentStepController::class);

        //factor
        Route::post('factor', [FactorController::class, 'store']);
        // ->middleware('permission:manage-factors');
        ///v1/factor/{factor_id}/factorItem"
        Route::post('factor/{factor_id}/factorItem', [FactorController::class, 'storeFactorItem']);
        //  path="/v1/factor/{factor_id}/factorItem/{factor_item_id}",
        Route::put('factor/{factor_id}/factorItem/{factor_item_id}', [FactorController::class, 'updateFactorItem']);
        Route::delete('factor/{factor_id}/factorItem/{factor_item_id}', [FactorController::class, 'destroyFactorItem']);

        ///v1/factor/{factor_id}/factorStatus
        Route::post('factor/{factor_id}/factorStatus', [FactorController::class, 'setFactorStatus']);
        //"/v1/factor/{factor_id}
        Route::get('factor/{factor_id}', [FactorController::class, 'show']);

        //product apis
        Route::apiResource('products', ProductController::class);
        // ->middleware('permission:manage-products');
        ///v1/products/search/{name}
        Route::get('products/search/{name}', [ProductController::class, 'search']);
        Route::middleware(['permission:manage-products'])->prefix('products')->group(function () {
            //show steps
            Route::get('/{id}/steps', [ProductController::class, 'showSteps']);
        });
        //product steps apis
        Route::apiResource('product/steps', ProductStepController::class)->middleware('permission:manage-products');
        Route::get('product/{code}/steps', [ProductStepController::class, 'index'])->middleware('permission:manage-products');
        Route::get('productSteps/{id}', [ProductStepController::class, 'show'])->middleware('permission:manage-products');
        //set step roles
        Route::post('product/steps/{id}/setRoles', [ProductStepController::class, 'setRoles'])->middleware('permission:manage-products');

        //globalSteps
        Route::apiResource('globalSteps', GlobalStepsController::class)->middleware('permission:manage-products');

        //basic data
        Route::apiResource('basicData', BasicDataController::class)->middleware('permission:manage-basic-data');
        Route::post('basicData/{id}/addItem', [BasicDataController::class, 'addItem'])->middleware('permission:manage-basic-data');
        Route::post('basicData/item/{id}/updateStatus', [BasicDataController::class, 'updateStatus'])->middleware('permission:manage-basic-data');
        Route::delete('basicData/item/{id}', [BasicDataController::class, 'destroyItem'])->middleware('permission:manage-basic-data');
        Route::put('basicData/item/{id}', [BasicDataController::class, 'editItem'])->middleware('permission:manage-basic-data');

        //productStepConditions
        Route::apiResource('product/steps/conditions', ProductConditionController::class)->middleware('permission:manage-products');



        Route::middleware(['permission:manage-products'])->prefix('product/steps')->group(function () {
            //show steps
            Route::post('/bulk', [ProductStepController::class, 'storeBulk']);
            Route::put('/{id}/setCreateStep', [ProductStepController::class, 'setCreateStep']);
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
        Route::post('forms/{id}/fields', [FormController::class, 'assignFieldToForm']);
        Route::get('forms/{id}/fields', [FormController::class, 'showFormFields']);
        Route::apiResource('forms', FormController::class);
        // ->middleware('permission:manage-forms');

        //form field type resource
        Route::apiResource('formFieldTypes', FormFieldTypeController::class)->middleware('permission:manage-forms');

        //form field resource
        Route::get('/formFields/product/{product_id}', [FormFieldController::class, 'getFieldsFromProduct']);
        Route::apiResource('formFields', FormFieldController::class)->middleware('permission:manage-forms');

        //form field options resource
        Route::post('formFieldOptions', [FormFieldOptions::class, 'create']);
        Route::delete('formFieldOptions/{id}', [FormFieldOptions::class, 'destroy']);
        //options of form
        Route::get('formFieldOptions/field/{field_id}', [FormFieldOptions::class, 'optionsOfField']);


        //user answer form
        Route::prefix('userAnswer')->group(function () {
            //assign role to user
            Route::post('/{form_id}/answer', [UserAnswerController::class, 'userAnswerForm']);
        });

        Route::get('order/{id}/complete', [OrderController::class, 'showComplete']);
        Route::get('step/{id}/{orderId}/complete', [ProductStepController::class, 'showComplete']);
        Route::apiResource('order', OrderController::class);
        Route::apiResource('orderGroup', OrderGroupController::class);
        ///v1/orderGroup/{id}/search/{name}
        Route::get('orderGroup/{id}/search', [OrderController::class, 'search']);

        Route::apiResource('languages', LanguageController::class)->middleware('permission:manage-translation');
        Route::apiResource('keywords', KeywordController::class)->middleware('permission:manage-translation');
        Route::post('translations', [TranslationController::class, 'addTranslation'])->middleware('permission:manage-translation');

        Route::middleware(['permission:manage-invoices'])->prefix('invoices')->group(function () {
            //show invoice by order group id
            Route::get('/{order_group_id}', [InvoiceController::class, 'show']);
            Route::post('/{invoice_id}', [InvoiceController::class, 'create']);
        });
        Route::
            // middleware(['permission:manage-files'])->
            prefix('files')->group(function () {
                Route::post('', [FileController::class, 'store'])->name('files.store');
                Route::delete('/{hashCode}', [FileController::class, 'destroy'])->name('files.delete');
            });


    });
    Route::get('/files/{hashCode}', [FileController::class, 'read'])->name('files.read');

});