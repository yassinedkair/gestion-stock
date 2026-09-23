<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GestionController;
Route::get('/',[GestionController::class,'index']);
Route::prefix('gestion')->controller(GestionController::class)->group(function(){
 Route::get('/state','state');
 Route::post('/products','productStore'); Route::put('/products/{product}','productUpdate'); Route::delete('/products/{product}','productDelete');
 Route::put('/products/{product}/stock','stockUpdate');
 Route::post('/clients','clientStore'); Route::put('/clients/{client}','clientUpdate'); Route::delete('/clients/{client}','clientDelete');
 Route::post('/suppliers','supplierStore'); Route::put('/suppliers/{supplier}','supplierUpdate'); Route::delete('/suppliers/{supplier}','supplierDelete');
 Route::post('/purchases','purchaseStore'); Route::put('/purchases/{purchase}','purchaseUpdate'); Route::delete('/purchases/{purchase}','purchaseDelete');
 Route::post('/sales','saleStore'); Route::put('/sales/{sale}','saleUpdate'); Route::delete('/sales/{sale}','saleDelete');
 Route::post('/charges','chargeStore'); Route::put('/charges/{charge}','chargeUpdate'); Route::delete('/charges/{charge}','chargeDelete');
 Route::post('/salaries','salaryStore'); Route::put('/salaries/{salary}','salaryUpdate'); Route::delete('/salaries/{salary}','salaryDelete');
 Route::post('/capital','capitalStore'); Route::put('/capital/{capitalOperation}','capitalUpdate'); Route::delete('/capital/{capitalOperation}','capitalDelete');
});
