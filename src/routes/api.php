
<?php

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

$namespace = 'InsenseSMS\Http\Controllers';

Route::group(['prefix' => '/', 'namespace' =>  $namespace], function () {
    Route::any(
        '/sms/report/{driver}',
     'SMSController@deliveryReceipt'
    )->name('sms.deliveryReport');

    
    Route::any('sms/test', 'SMSController@index');
});
