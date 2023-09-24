<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TransactionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware(['jwt.auth'])->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/login', [AuthController::class, 'login']);
Route::post('/users', [UserController::class, 'store']);

Route::middleware(['jwt.auth'])->group(function(){
    Route::get('/', [TransactionController::class, 'index']);
    Route::get('/deposit', [TransactionController::class, 'deposit']);
    Route::post('/deposit', [TransactionController::class, 'storeDeposit']);
    Route::get('/withdrawal', [TransactionController::class, 'withdrawal']);
    Route::post('/withdrawal', [TransactionController::class, 'storeWithdrawal']);
});