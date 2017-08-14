<?php

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
    return view('welcome');
});
//Route::resource('/threads', 'ThreadController');
Route::post('/threads', 'ThreadController@store');
Route::get('/threads', 'ThreadController@index');
Route::get('/threads/create', 'ThreadController@create');
Route::get('/threads/{channel}', 'ThreadController@index');
Route::get('/threads/{channel}/{thread}', 'ThreadController@show')->name('thread');
Route::post('/threads/{channel}/{thread}/replies', 'ReplyController@store')->name('reply_to_thead');

Route::post('/replies/{reply}/favorite', 'FavoriteController@store');

Route::get('/profile/{user}', 'ProfileController@show')->name('profile');

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');