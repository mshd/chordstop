<?php

namespace App\Http\Controllers;

use App\Libraries\Wikidata;
use App\DiscogsArtists;
use App\Chords;

use Illuminate\Http\Request;
use DB;
use Storage;
use App\Libraries\DiscogsClass;

class ArtistController extends Controller
{
  public function __construct(){
      $this->middleware("auth",["except"=>["index","show"]]);
  }

  public function lookup($name){
    $api_key='AIzaSyCdtXEM6osR50VtoGIh2WqcYF1zylkn5hA';
    $service_url = 'https://kgsearch.googleapis.com/v1/entities:search';
    $params = array(
    'query' => $name,
    'limit' => 1,
    'indent' => TRUE,
    'key' => $api_key);
    $url = $service_url . '?' . http_build_query($params);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);
    if(isset($respone["error"])){dd("error");}
    print_r($response);
    foreach($response['itemListElement'] as $element) {
    echo $element['result']['name'] . '<br/><img src="'.$element['result']['image']["contentUrl"] .'"" />';
    }
    //Consumer Key	OYrGCqkxEhZoOmEbrUTl
    //Consumer Secret	jhQNmlpTENwxPNzKTWpPGfgJDKWUCRXz
    // https://api.discogs.com/database/search?release_title=nevermind&artist=nirvana&per_page=3&page=1
  }
  public function artistupdate2(){
    return 19;

  }

  //This function assign every song in "chords" the first resulted artist id
  public function discogs_assign_id_to_table_chords(){
    $tb = DB::table('chords')->orderBy('artist')->get(['artist','discogs_artist']);
    $artist=array();
    foreach($tb as $k){
      if(!in_array($k->artist,$artist) && $k->discogs_artist==null){
        $artist[] = $k->artist;
      }
    }
    //dd($artist);
    $n="";
    $discogs = new DiscogsClass();
    for ($i=0; $i < count($artist); $i++) {
      //$artist[$i]="beatles";
    $search = $discogs->discogs_search($artist[$i]);
      if($search){
      \App\Chords::where('artist', $artist[$i])->whereNull('discogs_artist')->update(['discogs_artist' => $search["results"][0]["id"]]);
      }
    else{
      $n.=$artist[$i].",";
    }
    }
    return "done not found=".$n;
  }
  function artist_by_id($id){
    $id = intval($id);
    $file = 'artistsongs/'.$id.'.json';
    $wikidata = new Wikidata();
    $file = 'artist/'.$id.'.json';
    if(Storage::exists($file)){
      $wiki = json_decode(Storage::disk('local')->get($file),true);
      $wikidata->loadarray([$id=>$wiki]);
    }else{
      $wikidata->loadentity($id);
      $wiki = $wikidata->entities[$id];
      Storage::disk('local')->put($file, json_encode($wiki));
    }
    $wikidata->entityId = $id;
    return $wikidata;
  }
  function songs_by_artist($id){
    $id = intval($id);
    $file = 'artistsongs/'.$id.'.json';
    $wikidata = new Wikidata();

    if(Storage::exists($file)){
      $query = json_decode(Storage::disk('local')->get($file),true);
    }else{
      $restriction = "";
      if($id == 133405){
      $restriction .='?article schema:about ?item .
      ?article schema:inLanguage "en" .
      ?article schema:isPartOf <https://en.wikipedia.org/> . ';
      }
      $query = $wikidata->sparqlquery('SELECT DISTINCT ?item ?itemLabel
  WHERE
  {
  {?item wdt:P31/wdt:P279* wd:Q134556 } UNION {?item wdt:P31/wdt:P279* wd:Q7366 }
    ?item wdt:P175 wd:Q'.$id.' . '.$restriction.'
      FILTER(NOT EXISTS { ?item wdt:P31/wdt:P279* wd:Q482994. })
    SERVICE wikibase:label { bd:serviceParam wikibase:language "[AUTO_LANGUAGE],en". }
  }');
      Storage::disk('local')->put($file, json_encode($query));
    }

  $songs = [];
  foreach($query as $song){
    $songs[intval(substr($wikidata->lastslash($song["item"]["value"]),1)) ] = $song["itemLabel"]["value"];
  }
  return $songs;
}

  //fetch all the missing artists
  public function discogs_fetch_missing_artists(){
    $tb = DB::table('chords')->get(['discogs_artist']);
    $artist=array();
    foreach($tb as $k){
    if(!in_array($k->discogs_artist,$artist) ){
      if(! DiscogsArtists::where("id",$k->discogs_artist)->count() ) {
        $artist[] = $k->discogs_artist;
      }
    }
    }
    $artist = array_unique($artist);
    $discogs = new DiscogsClass();
    $n="";
    for ($i=0; $i < count($artist); $i++) {
    $search = $discogs->discogs_artist_by_id($artist[$i]);
      if($search){
        $song = new DiscogsArtists;
        $song->id = $artist[$i];
        $song->name = $search["name"];
        $song->data = $search;
        $song->save();
      }else{
        $n.=$artist[$i].",";
      }
  }
    return "done successfully<br>not found=".$n;
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      $artist = DiscogsArtists::has('songs')->orderBy('name')->get();//['artist','id']
      return view("home.artists",compact('artist'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      return view("back.artists.create");
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\DiscogsArtists  $discogsArtists
     * @return \Illuminate\Http\Response
     */
    public function show(DiscogsArtists $artist)
    {
      //$songs = $artist->songs;
      $discogs = $artist->data;
      $songs = $this->songs_by_artist($artist->id);
      $found = [];
      foreach($songs as $songid => $song){
        if(Chords::find($songid)) {
          $found[$songid] = $song;
          unset($songs[$songid]);
        }
      }
      //dd($artist->data);
      return view("home.displayartist",compact('found','songs','artist','discogs'));
    }

    public function list(){
      $tb = DiscogsArtists::all();
      return view("back.artists.list",compact('tb'));

    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\DiscogsArtists  $discogsArtists
     * @return \Illuminate\Http\Response
     */
    public function edit(DiscogsArtists $discogsArtists)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\DiscogsArtists  $discogsArtists
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DiscogsArtists $discogsArtists)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\DiscogsArtists  $discogsArtists
     * @return \Illuminate\Http\Response
     */
    public function destroy(DiscogsArtists $discogsArtists)
    {
        //
    }
}
