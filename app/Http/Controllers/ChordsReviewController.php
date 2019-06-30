<?php

namespace App\Http\Controllers;

use App\Libraries\ChordsClass;

use Auth;
use App\Chords_Review;
use Illuminate\Http\Request;

class ChordsReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      $tb = Chords_Review::all();
      return view("back.review.list",compact('tb'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      return view("back.review.create");
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
      $this->validate($request,
        ['title' => 'required',
         'discogs_artist'=> 'required',
         'body'  => 'required']);
      $song = new Chords_Review;
      $song->title = $request->title;
      $song->artist = @$request->artist;
      $song->body = $request->body;
      $song->discogs_artist = $request->discogs_artist;
      $song->discogs_master = @$request->discogs_master;
      $song->user_id = \Auth::user()->id;
      $song->song_id = @$request->song_id;

      $song->save();
        //dd($request->hits);
        //  \App\Chords::create($request->all());
      return redirect()->route('review.create')->with('success','Item created successfully, please wait for it be reviewed');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Chords_Review  $chords_Review
     * @return \Illuminate\Http\Response
     */
    public function show(Chords_Review $review)
    {
      $song = $review;
      $chordclass = new ChordsClass();
      $chord = $chordclass->parse($review->body);
      //dd($song->title);
      //return $review;
      return view("home.displaysong",compact('song','chord'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Chords_Review  $chords_Review
     * @return \Illuminate\Http\Response
     */
    public function edit(Chords_Review $review)
    {

      //$songr = Chords_Review::where('user_id',Auth::user()->id)->where('song_id',$id)->select('id')->get();
      if($review->user_id==Auth::user()->id || Auth::user()->isAdmin()){
        $song=$review;
        return view("back.review.edit",compact('song','id'));
      }else{
        abort(403, 'Unauthorized action.');

      }
    }


    public function list(){



    }
    public function admin_review(Chords_Review $song){
      //$song = Chords_Review::find($id);
      return view("back.review.see",compact('song'));

    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Chords_Review  $chords_Review
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Chords_Review $review)
    {
      $data=$request->all();
      $this->validate($request,
        ['title' => 'required',
         'discogs_artist'=> 'required',
         'body'  => 'required']);

      $review->update([
      "discogs_artist"=>$data["discogs_artist"],
      //"artist"=> $data["artist"],
      "title" => $data["title"],
      "body"  => $data["body"],
      "open"  => isset($data["open"]),
      ]);
      $song=$review;
      return view("back.review.edit",compact('song'))->with('success','Item updated successfully');;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Chords_Review  $chords_Review
     * @return \Illuminate\Http\Response
     */
    public function destroy(Chords_Review $chords_Review)
    {
        //
    }
}
