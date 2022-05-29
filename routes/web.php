<?php

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\Route;

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

Route::middleware(['guest'])->group(function () {
    Route::get('/', function () {
        return view('login');
    })->name('index');
    Route::post('/login-attempt', [WebController::class, 'login_attempt']);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/logout', [WebController::class, 'logout']);

    Route::get('/dashboard', [WebController::class, 'dashboard']);
    Route::get('/data-pendapatan', [WebController::class, 'data_pendapatan']);

    // Route::get('/kelola-data-penjualan', [WebController::class, 'kelola_data_penjualan']);
    // Route::get('/data-penjualan', [WebController::class, 'data_penjualan']);

    // Route::post('/input-data-penjualan', [WebController::class, 'input']);
    // Route::post('/edit-data-penjualan', [WebController::class, 'edit']);

    // Route::get('/prediksi-penjualan', [WebController::class, 'wma']);
    // Route::post('/wma-terjual', [WebController::class, 'wmaTerjual']);
    // Route::post('/wma-pendapatan', [WebController::class, 'wmaPendapatan']);
    Route::get('/input-data-penjualan', function () {
        return view('transaksi');
    });
    Route::post('/transaksi/detail', [WebController::class, 'transaksi_detail']);
    Route::post('/transaksi/input', [WebController::class, 'transaksi_input']);

    Route::get('/prediksi-stok-akhir', [WebController::class, 'wma']);
    Route::post('/process-wma', [WebController::class, 'processWma']);
});
