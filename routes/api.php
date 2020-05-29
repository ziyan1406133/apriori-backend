<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/produk', 'ProdukController@index')->name('api.product.index');
Route::get('/produk/{id}', 'ProdukController@show')->name('api.product.show');

Route::get('/transaksi', 'TransaksiController@index')->name('api.transaksi.index');
Route::get('/transaksi-detail', 'TransaksiDetailController@index')->name('api.transaksi-detail.index');

Route::post('/rules_and_recommendations', 'AprioriController@getRulesandRecommendations')->name('api.rules-recommendations');