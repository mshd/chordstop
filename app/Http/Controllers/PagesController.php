<?php

namespace App\Http\Controllers;

use DB;
use App\Music;
use App\DiscogsArtists;

use App;
use Illuminate\Http\Request;

class PagesController extends Controller
{
  public function setl($locale) {
      App::setLocale($locale);
      return "Locale set to ".$locale;
  }
 public function home(){

    //$songs = DB::table('music')->get();
    $popular = $this->songs_mosthits();
    return view("home.home",compact('popular'));
    //return view("about",compact('songs'));

 }
 public function songs_mosthits(){
	//database query for only the artist colums
  //    $songsbyartist= DB::table('chords')->where('artist',$song->artist)->whereNotIn('id', [ $song->id])->select('title','id')->orderBy('title')->get();
    $tb = DB::table('chords')->select('title','artist','id','entity')->orderBy('hits','desc')->limit(8)->get();
    //get(['artist']);
    return $tb;

 }
 public function displayartists(){
	//database query for only the artist colums
/*
    $tb = \App\Chords::orderBy('artist')->get(['artist','discogs_artist']);
    $artist=[];
    //count songs by artist in an array
    foreach($tb as $k){
		if(!isset($artist[$k->artist])){
			$artist[$k->artist] = array(
        "songs"=>0,
        "data"=> $k->discogs->data,
      );
		}
		$artist[$k->artist]["songs"]++;
    }
    //dd($artist);
    return view("home.listartists",compact('artist'));*/
    $artist = DiscogsArtists::orderBy('name')->get();//['artist','id']
    //dd($artist);
    return view("home.artists",compact('artist'));
 }




}
