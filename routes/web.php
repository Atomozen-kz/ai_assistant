<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/report/ping', function () {
    return DB::connection('report')->select('SELECT 1 as ok');
});
