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
    Route::post('/chart-penjualan', [WebController::class, 'chart_penjualan']);
    Route::post('/chart-penjualan/harian', [WebController::class, 'chart_penjualan_harian']);

    Route::get('/input-transaksi', function () {
        return view('transaksi');
    });
    Route::post('/transaksi/detail', [WebController::class, 'transaksi_detail']);
    Route::post('/transaksi/update', [WebController::class, 'transaksi_update']);
    Route::post('/transaksi/input', [WebController::class, 'transaksi_input']);
    Route::get('/transaksi/delete/{id}', [WebController::class, 'transaksi_delete']);

    Route::get('/laporan-bulanan', function () {
        return view('laporan-bulanan');
    });
    Route::get('/laporan-bulanan/get', [WebController::class, 'laporan_bulanan']);

    Route::get('/prediksi-stok', [WebController::class, 'wma']);
    Route::post('/process-wma', [WebController::class, 'processWma']);
});
