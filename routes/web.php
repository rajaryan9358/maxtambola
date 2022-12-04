<?php

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

Route::get('/', function () {
    return view('home');
});
Route::get('about', function () {
    return view('aboutus');
});
Route::get('terms', function () {
    return view('terms');
});
Route::get('privacy', function () {
    return view('privacy');
});
Route::get('contact', function () {
    return view('contact');
});
Route::get('faqs', function () {
    return view('faq');
});