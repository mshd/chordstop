<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use App\Chords;
use App\DiscogsArtists;
use App\Chords_Review;

use App\Libraries\DiscogsClass;


class ApiController extends Controller
{
  public function search_artist($query){
    $db=DiscogsArtists::whereRaw("LOWER(name) like ?",'%'.strtolower($query).'%')->select('name','id');
    $db=$db->paginate(10)->toArray();
    return $db["data"];
  }
    public function artist_new(Request $request){
      $d= new DiscogsClass();
      $search = $d->discogs_artist($request->name);
      //return $search;
        //echo $search["results"][0]["id"];die;
        if($search){
        if(DiscogsArtists::find($search["id"])){
          return ["success"=>true,"artist"=>$search["name"],"error"=>"already"];
        }
        $song = new DiscogsArtists;
        $song->id = $search["id"];
        $song->name = $search["name"];
        $song->data = $search;
        $song->save();
        return ["success"=>true,"error"=>false,"artist"=>$search["name"]];
      }
      return ["success"=>false];
    }
 public function search_suggest($query){

   $word = explode(" ",$query);
   $db= DB::table('chords')
   //->select(DB::raw('CONCAT(artist," ",title) as song'))
   ->whereRaw("LOWER(CONCAT(artist,' ',title)) like ?",'%'.strtolower($word[0]).'%')
   //raw('CONCAT(First_Name, " ", Last_Name) AS full_name'
    //>orderBy('hits')
    ->select('title','artist','entity');
    //->orWhere('content_raw', 'LIKE', "%{$search}%")strtolower
    for($i=0;$i<count($word);$i++){
      $db=$db->whereRaw("LOWER(CONCAT(artist,' ',title)) like ?",'%'.strtolower($word[$i]).'%');
    }

    $db=$db->paginate(10)->toArray();
    //why only show the data, because the Bloodhoun javascript needs the data directly in an array
    return $db['data'];//automatically converted in json
 }
}
