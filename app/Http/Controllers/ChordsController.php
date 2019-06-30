<?php

namespace App\Http\Controllers;

use App\Libraries\ChordsClass;
use App\Libraries\Wikidata;
use Illuminate\Http\Request;
use DB;
use App\Chords;
use App\DiscogsArtists;
use App\Chords_Review;
use App\Events\ViewSongEvent;
use Storage;
use App\Http\Controllers\ArtistController;

class ChordsController extends Controller
{
  public function __construct(){
      $this->middleware("auth",["except"=>["show","display"]]);
  }
  public function destroy(Chords $song)
	{
		$song->delete();
		return redirect('song/list')->with('ok', trans('back/blog.destroyed'));
	}
  public function list(){
    $tb = Chords::get(['artist','title','id','hits','updated_at','hits','open','discogs_artist','entity']);
    //dd($tb[0]->discogs);
    return view("back.chords.list",compact('tb'));
  }
  public function update_published(Request $request){
    $id=$request->id;
if(!$id){return ["succes"=>false];}
    $song= Chords::where("id",$id);
    if(@$request->create){
      return $this->create_entity($song->first());
    }
    if(@$request->open){
    $song->update(["open"  => $publish]);
  }elseif(@$request->entity){
    $song->update(["entity"  => $request->entity]);
  }
    return ["success"=>true,"publish"=>""];
  }
  public function create(Request $request){
    return $this->store($request);
    $get = array(
      'artist'=>request()->artist,
      'title'=>request()->r,
    );
    //dd($get);
    //return view("admin.createsong");
    return view("back.chords.create");
  }

    public function update(Request $request,Chords $song){

      $data=$request->all();
      $this->validate($request,
        [//'entity' => 'required',
         'body'  => 'required']);

        $song->update([
        "body"  => $data["body"],
        "open"  => isset($data["open"]),

        ]);
        return view("back.chords.edit",compact('song'));//->with('message', 'You have successfully updated');

    }
     public function store(Request $request){
       $this->validate($request,
         ['entity' => 'required',
          'body'  => 'required']);
          $id = $request->entity;
          if(Chords::find($id)){
            dd("already exists");
          }
        $wikidata = $this->song_by_id($id);

        $song = new Chords;
        $song->title = @$wikidata["song"]->getlabel();
        $song->artist = @$wikidata["artist"]->getlabel();
        $song->body = $request->body;
        $song->entity = $request->entity;
        $song->user_id =  Auth::id();
        $song->hits = 0;
        $song->category = "";

        $song->save();

      return redirect()->route('song.'.$id)
                            ->with('success','Item created successfully');

     }


 public function parse($body,$rows=2){
   $chordclass = new ChordsClass();
   $parsed= $chordclass->Chord_preparse($body);
   $chord = $chordclass->ChordPro_Parse(explode("\n",$parsed['body']),["linebreak"=>$rows]);
   $chord["json"]=json_encode($chord['define']);
   return $chord;
 }


 public function edit(Chords $song){
   return view("back.chords.edit",compact('song','id'));
 }
 public function lastslash($string,$limit = PHP_INT_MAX,$second=false){
   if($second){$string=str_replace($second,"/",$string);}
   $parameter = explode("/",$string,$limit);
   return $parameter[ (count($parameter)-1) ];
 }
  public function testfunction(){
    $wikidata = new Wikidata();
    $tb = Chords::all();
    foreach($tb as $chord){
      //$w = Chords::where('id', $chord->discogs_artist)->select("title")->first();
      //Chords::where('id', $chord->id)->update([ "artist_id" => $w->wikidata ]);
      $search = $wikidata->search($chord->title);
      //dd($wikidata_search);
      if(count($search)){
        @Chords::where('id', $chord->id)->update([
          "entity" => substr($search[0]["id"],1),
          "label" => @$search[0]["label"],
          "description" => @$search[0]["description"],
         ]);
       }
     }

  }
  public function addArtistWikidataID(){
    //$file = app_path()."/../../../compareit/app/Http/Libraries/Wikidatalib/Wikidata.php";
    //require($file);
    $wikidata = new Wikidata();
    $tb = DiscogsArtists::all();
    foreach($tb as $artist){
      $query = $wikidata->sparqlquery('SELECT ?item ?itemLabel ?itemDescription WHERE
{
 ?item wdt:P1953 "'.$artist->id.'" .
  SERVICE wikibase:label { bd:serviceParam wikibase:language "[AUTO_LANGUAGE],en". }
}');
if(count($query)){
DiscogsArtists::where('id', $artist->id)->update([
      "wikidata"=>substr($this->lastslash($query[0]["item"]["value"]),1),
      //"artist"=> $data["artist"],
      "label" => $query[0]["itemLabel"]["value"],
      "description"  => $query[0]["itemDescription"]["value"],
      ]);
//return;
    }
  }
    return $tb;
  }
  public function create_entity($song){
    $label = $song->title;
    $performer = $song->artist_id;
    $entity = [

  "labels" => [
    "en" => $label,
  ],
  "descriptions" => [
    "en" => 'song by '.$song->artist,
    //"de" => 'Lied',
  ],
  "claims" => [
    "P31" => 'Q7366',
    "P175" => "Q".$performer,
  ]
];
    $script = '
    const config = {
      username: "Germartin1",
      password: "jim50@@XJT",
    }
    console.log("start");
    const wdEdit = require("wikidata-edit")(config)
    var entityData = '.json_encode($entity).';
    var output = wdEdit.entity.create(entityData);
    console.log(output);
    console.log(wdEdit);
    ';
    Storage::disk('local')->put("temp/script.js", $script);
    $path = Storage::path("temp/script.js");
    exec("nodejs ".$path, $output);
    return $output;
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
  public function song_by_id($id){
    $wikidata = new Wikidata();
    $file = 'song/'.$id.'.json';
    if(Storage::exists($file)){
      $wiki = json_decode(Storage::disk('local')->get($file),true);
      $wikidata->loadarray([$id=>$wiki]);
    }else{
      $wikidata->loadentity($id);
      $wiki = $wikidata->entities[$id];
      Storage::disk('local')->put($file, json_encode($wiki));
    }
    $wikidata->entityId = $id;
    $song = $wikidata;

    $artistid = @substr(@$wikidata->getclaim("P175")[0],1);
    if($artistid){
      $artist = $this->artist_by_id($artistid);
    }
    return [
      "song" => $song,
      "artist" => $artist,
    ];
  }
public function show($id){

  $song = Chords::find($id);
  $rows = (request()->rows?(int)request()->rows:2);
  $output = (request()->output?request()->output:"css");

  $id = intval($id);
  $info = $this->song_by_id($id);
  //$artist = $info["artist"];
  //$ = $info["song"];
  //unset($info);
  $title = $info["song"]->getlabel();
  $artist = $info["artist"]->getlabel();

  if(!$song){
    return view("home.unknownsong",compact('id','info',"title","artist"));
  }
  //$song = Chords::where("entity",$id)->first();
  $chordclass = new ChordsClass();
  $parsed= $chordclass->Chord_preparse($song->body);
  if($parsed['error']){return $parsed['error'];}


    if($output=="json"){
      return $song;
    }elseif($output=="raw"){
      return '<pre>'.$song->body;
    }elseif($output=="parsed"){
      return '<pre>'.
      //print_r($parsed["directives"],true).''.
      $parsed["body"];
    }


  //$parsed['body']=str_replace($parsed['body'],"\r", "\n");
  //return ($parsed['body']);
  //$parsed['body']=str_replace($parsed['body'],"\r\n", "\n");
  $chord = $chordclass->ChordPro_Parse(explode("\n",$parsed['body']),["linebreak"=>$rows,"output"=>$output]);
  $chord["json"]=json_encode($chord['define']);
  /*
  if($song->discogs_master){
    $album = $this->discogs_master($song->discogs_master);
    dd($album);
  }*/
  event(new ViewSongEvent($song));


  return view("home.displaysong",compact('artist','song','chord',"title","info"));

}
/*
 public function show(Chords $song){
    $rows = (request()->rows?(int)request()->rows:2);
    $output = (request()->output?request()->output:"css");
    if(!$song){
      //abort(404);
      return view("errors.list");//->error('Song not found');
    }
    //$songsbyartist= DB::table('chords')->where('artist',$song->artist)->whereNotIn('id', [ $song->id])->select('title','id')->limit(6)->orderBy('title')->get();

    $chordclass = new ChordsClass();
    $parsed= $chordclass->Chord_preparse($song->body);
    if($parsed['error']){return $parsed['error'];}


      if($output=="json"){
        return $song;
      }elseif($output=="raw"){
        return '<pre>'.$song->body;
      }elseif($output=="parsed"){
        return '<pre>'.
        //print_r($parsed["directives"],true).''.
        $parsed["body"];
      }


    //$parsed['body']=str_replace($parsed['body'],"\r", "\n");
    //return ($parsed['body']);
    //$parsed['body']=str_replace($parsed['body'],"\r\n", "\n");
    $chord = $chordclass->ChordPro_Parse(explode("\n",$parsed['body']),["linebreak"=>$rows,"output"=>$output]);
    $chord["json"]=json_encode($chord['define']);

    event(new ViewSongEvent($song));


    return view("home.displaysong",compact('song','chord'));

 }
*/


}
