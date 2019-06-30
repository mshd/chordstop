<?php
public function update(Request $request,Chords $song){

 $data=$request->all();
 $this->validate($request,
   ['title' => 'required',
    'discogs_artist'=> 'required',
    'body'  => 'required']);

 if($this->isadmin()){
   $song=\App\Chords::find($id);

       //dd(isset($data["open"]));
    $song->update([
    "discogs_artist"=>$data["discogs_artist"],
    //"artist"=> $data["artist"],
    "title" => $data["title"],
    "body"  => $data["body"],
    "open"  => isset($data["open"]),
    ]);
    return view("admin.editsong",compact('song','success','id'));//->with('message', 'You have successfully updated');

}else{
  $song = Chords_Review::where('user_id',Auth::user()->id)->where('song_id',$id) ;
  if(count($song->get()) ){

    $song->update([
    "discogs_artist"=>$data["discogs_artist"],
    //"artist"=> $data["artist"],
    "title" => $data["title"],
    "body"  => $data["body"]
    ]);
    $song=$song->first();
  }else{
  $song = new Chords_Review;
  $song->title = $request->title;
  $song->artist = $request->artist;
  $song->body = $request->body;
  $song->user_id = Auth::user()->id;
  $song->song_id = $id;
  $song->save();
}
    //dd($request->hits);
//  \App\Chords::create($request->all());
return view('admin.editsong',compact('song','success','id'));
                // ->with('success','Item edited successfully, please wait for it to be reviewed');

}

}
 public function storesong(Request $request){
   $this->validate($request,
     ['title' => 'required',
      //'discogs_artist'=> 'required',
      'body'  => 'required']);
    if($this->isadmin()){

    $song = new Chords;
    $song->title = $request->title;
    $song->artist = @$request->artist;
    $song->body = $request->body;
    $song->discogs_artist = $request->discogs_artist;
    $song->discogs_master = @$request->discogs_master;
    $song->hits = 0;
    $song->category = "";

    $song->save();
      //dd($request->hits);
//  \App\Chords::create($request->all());
  return redirect()->route('admin.createsong')
                        ->with('success','Item created successfully');

    }else{
      $song = new Chords_Review;
      $song->title = $request->title;
      $song->artist = @$request->artist;
      $song->body = $request->body;
      $song->discogs_artist = $request->discogs_artist;
      $song->discogs_master = @$request->discogs_master;
      $song->user_id = Auth::user()->id;
      $song->song_id = 0;

      $song->save();
        //dd($request->hits);
  //  \App\Chords::create($request->all());
    return redirect()->route('admin.createsong')
                          ->with('success','Item created successfully, please wait for it be reviewed');
    }
  //return view('admin.create',['song'=>DB::table('chords')]);
 }
