<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/token', function () {
    $token = bin2hex(random_bytes(32));
    return $token;
});
