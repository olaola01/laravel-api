<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->middleware('guest');

// Login Routes...
Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('login', 'Auth\LoginController@login');

// Logout Routes...
Route::post('logout', 'Auth\LoginController@logout')->name('logout');

// Registration Routes...
Route::get('register', 'Auth\RegisterController@showRegistrationForm')->name('register');
//Route::post('register', 'Auth\RegisterController@register');

Route::get('/home', 'HomeController@index')->name('home');
Route::get('/home/authorized-clients', 'HomeController@getAuthorizedTokens')->name('authorized-clients');
Route::get('/home/my-clients', 'HomeController@getClients')->name('personal-clients');
Route::get('/home/my-tokens', 'HomeController@getTokens')->name('personal-tokens');
