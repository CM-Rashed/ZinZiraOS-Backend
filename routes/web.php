<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Public Auth Routes
|--------------------------------------------------------------------------
*/
Route::get('/',function(){
    return response()->json([
        'message' => 'Welcome to the API',
        'status' => 'success'
    ]);
});



