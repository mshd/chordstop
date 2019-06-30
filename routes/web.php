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
//App::setLocale('de');
//$load_intern = true;
View::share ( 'load_intern', false );
View::share ( 'addons', [] );
Route::get('set/{locale}', 'PagesController@setl');


Route::get('/', 'PagesController@home');
Route::get('/home', 'PagesController@home')->name("index");
Route::get('about', function () {
    return view('home.about');//view('welcome');
});
Route::get('privacy', function () {
    return view('home.privacy');//view('welcome');
});
Route::get('/testfunction' , 'ChordsController@testfunction');
//Route::get('song/id/{id}' , 'ChordsController@display' );

Route::get('song/list' , 'ChordsController@list' )->name("admin.songlist");
Route::resource("song",'ChordsController',["names"=>[
  "index"=>"song",
  ]]);

//API
//mark as published or not
Route::post('api/song/open', 'ChordsController@update_published');
Route::get('api/search/{query}', 'ApiController@search_suggest');
Route::get('api/search/artist/{query}', 'ApiController@search_artist');
Route::post('api/artist/new', 'ApiController@artist_new');

Route::post('admin/ajax', 'AdminController@ajax');

Route::get('admin/database/songartists', 'AdminController@updateChordArtists');
Route::get('admin/convert','AdminController@converter');//function(){return view("admin.converter");}  );



Route::get('artist/list', 'ArtistController@list');
Route::resource("artist","ArtistController");

Route::resource("review","ChordsReviewController",[
  "names"=>[
    //"admin_review"=>"review.admin_review",
    "index"=>"review",
  ]
]);

Route::get('admin/myaccount' , 'UserController@myaccount' )->name("back.users.myaccount");
Route::resource("user",'UserController',["as"=>"admin"]);//->name("admin.user");


Route::get('admin' , 'AdminController@home' )->name("admin");
Route::get('admin/phpinfo' , 'AdminController@phpinfo' );
Route::get('admin/welcome' , 'AdminController@welcome' );
Route::get('admin/d_update','ArtistController@discogs_assign_id_to_table_chords');
Route::get('admin/d_update2','ArtistController@discogs_fetch_missing_artists');


Route::get('admin/db' , 'AdminController@dbextend' );
Route::get('admin/ip' , 'AdminController@ip' );
Route::get("user",function(){
  dd(Auth::user());
});

Route::get('test' ,  function () {
    return view('home.test');//view('welcome');
});


Auth::routes();

Route::get('/redirect/{service}', 'SocialAuthController@redirect' );
Route::get('/social/handle/{service}', 'SocialAuthController@callback');
