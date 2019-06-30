<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Chords extends Model
{
    //
    protected $primaryKey = 'entity';
    protected $fillable = [
        'entity',
        'title',
        'artist',
        'body',
        'hits',
        'open',
        'category',
        'discogs_artist',
        'discgos_master',
    ];
    protected $hidden = [
        //'password', 'remember_token',
    ];
    public $timestamps = true;
    /*public function show($id){

        $song = find($id);
        //return $song;
      }*/
    private $rules = array(
        'title' => 'required',
        //'artist'  => 'required',
        'discogs_artist'  => 'required',
        'body'  => 'required'
        // .. more rules here ..
    );
    public function discogs(){
      return $this->hasOne('App\DiscogsArtists', 'id','discogs_artist')->select(["id","name"]);
    }
    public function validate($data)
    {
        // make a new validator object
        $v = Validator::make($data, $this->rules);
        // return the result
        return $v->passes();
    }
}
