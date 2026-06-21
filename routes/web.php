<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/hello', function () {
    return 'Hello, CI/CD!';
});

Route::get('/static-test', function () {
    $post = new \App\Models\Post();
    return $post->thisMethodDoesNotExist();
});
