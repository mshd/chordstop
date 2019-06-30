<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Auth;
use App\Chords;
use App\Chords_Review;
use App\User;
use App\Services\Google_Service_Books;
use App\DiscogsArtists;

class AdminController extends Controller
{
  public function __construct(){
      $this->middleware("auth");
  }
  public function phpinfo(){
    phpinfo();
    return;
  }

  
 public function create(){


    return view("admin.create");

 }
 public function converter(){


    return view("admin.converter");

 }

 public function updateChordArtists(){
 //basically replace all artist names with the names from the discogs_artist table
   $all= Chords::get(['artist','title','id','discogs_artist']);
   foreach($all as $song ){
     //echo ($song->discogs->name);
     $song->update([
       "artist"=>$song->discogs->name,
     ]);
   }
    return "done";

 }
 public function home(){
   session("test","key");
   if(!session()->exists('viewedsongs')){
     session('viewedsongs',array());

   }
   $all = session("test");
   $vars = ['id','title','artist','updated_at','song_id','user_id','created_at','status','admin_comment','comment'];
   if(!$this->isadmin()){
     $usersongs = Chords_Review::where('user_id',Auth::user()->id)->get($vars);
   }else{
     $usersongs = Chords_Review::get($vars);
   }
//dd($usersongs);
  return view("back.index");
  return view("admin.home",compact("all","usersongs"));

 }
 public function ip(){
 	return var_dump(geoip($ip = "56.23.34.4"));
 }
 public function dbextend(){
   $all =DB::table('chords')->get();
   foreach($all as $key => $val){
     //dd($val);
   }
   //$song = new DB::table('chords_extented')->get();
   //DB::table('chords_extented')->save();
 }
 public function allsongs(){
   return DB::table('chords')->get();
 }
 public function welcome(){
   return view("admin.home");
 }
 public function show(){
   return "test";
 }



 public function createsong(){
   $get = array(
     'artist'=>request()->artist,
     'title'=>request()->r,
   );
   return view("back.chords.create");
 }
 private function isadmin(){
   return Auth::user()->role == "admin";
 }
 public function review($id){

   $song = Chords_Review::find($id);
   return view("admin.review",compact('song'));

 }
 public function ajax(Request $request){
   $id  =$request->id;
   $type=$request->type;
   $song = Chords_Review::find($id);
   $song->update([
     "status"=>$type
   ]);
   return array("error"=>false,"done"=>true);
 }



}
