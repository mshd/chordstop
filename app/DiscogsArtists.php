<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiscogsArtists extends Model
{
  protected $table  = "discogs_artists";
  public $timestamps = false;
  protected $fillable = [
      'name',
      'id',
      'data',
      'image',
  ];
  protected $casts = [
     'data' => 'array',
 ];
 public function songs(){
   return $this->hasMany('App\Chords','artist_id','id');
 }
 public function idname(){
    $artists= $this->orderBy('name')->get(["name","id"]);
    $ar=array();
    for($i=0;$i<count($artists);$i++){
      $ar[$artists[$i]["id"]]=$artists[$i]["name"];
    }
    return $ar;
  }
}
