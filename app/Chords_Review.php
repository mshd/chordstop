<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Chords_Review extends Model
{
    //
    protected $table  = "chords_review";
    protected $fillable = [
        'title',
        'artist',
        'body',
        'song_id',
        'user_id',
        'comment',
        'status',
        'admin_comment',
        'discogs_artist',
        'discogs_master'
    ];
    protected $hidden = [
        //'password', 'remember_token',
    ];
    public $timestamps = true;

    private $rules = array(
        'title' => 'required',
        //'artist'  => 'required',
        'body'  => 'required'
        // .. more rules here ..
    );
    public function user(){
      return $this->belongsTo('App\User');
    }
    public function original(){
      return $this->belongsTo('App\Chords', 'song_id','id');
    }
    public function discogs(){
      return $this->hasOne('App\DiscogsArtists', 'id','discogs_artist');
    }
    public function validate($data)
    {
        // make a new validator object
        $v = Validator::make($data, $this->rules);
        // return the result
        return $v->passes();
    }
    public function getRouteKeyName()
    {
        return 'id';
    }
}
