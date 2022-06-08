<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\FormController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/create/type', [FormController::class, 'createType']);
Route::post('/create/type', [FormController::class, 'createTypePost']);
Route::get('/show/type', [FormController::class, 'showTypes']);

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
