@extends('layouts.master')

@section('title', 'Edit '.$song->title.' - '.$song->artist)

@section('sidebar')
    @parent
    <p>View options</p>
    <div class="list-group">
      {!! Form::submit('Submit', ['class' => 'btn btn-default list-group-item']) !!}
      {{ Form::close() }}
    	<a href="/song/{{ $song->id }}" class="list-group-item" target="_blank">View song</a>
      <a href="/song/{{ ($song->id)+1 }}/edit" class="list-group-item">Next Song</a>

    </div>
    <br />
    <p>Export options</p>
    <div class="list-group">
    	<a href="#" class="list-group-item">Download as PDF</a>
    </div>
    <br />
@stop
@section('javascript')
$(document).ready(function () {
  $('.form-header').toggle();
  $('h1').click(function () {
		$('.form-header').toggle();
  });
});
@stop

@section('content')
<h1>Edit Song {{ $song->title  }} - 	{{ $song->artist }}</h1>
 <div>
 @if (count($errors) > 0)
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif


{{ Form::model($song, ['action' => ['AdminController@update','id'=>$id],'method'=>'PUT']) }}
  <div class="form-header">
  <div class="form-group">
      <?php /*
  {!! Form::text('artist', $song->artist, ['class' => 'form-control']) !!} */ ?>
      {!! Form::label('discogs_artist', __('chords.artist') .':') !!}
      <select name="discogs_artist" class="form-control">
         <?php
         echo "req".request()->discogs_artist;
       $artists = \App\DiscogsArtists::orderBy('name')->get(["name","id"]);
       $oldartist = (old('discogs_artist')?old('discogs_artist'):($song->discogs_artist?$song->discogs_artist:$song->artist));
//echo $oldartist;
       for($i=0;$i<count($artists);$i++){
         echo '<option value="'.$artists[$i]["id"].'" ';
         if($oldartist==$artists[$i]["id"] || $oldartist==$artists[$i]["name"]){echo 'selected';}
         echo '>'.$artists[$i]["name"].'</option>';
       }
          ?>
       </select>

    </div>
  <div class="form-group">
      {!! Form::label('title', 'Title:') !!}
      {!! Form::text('title', $song->title, ['class' => 'form-control']) !!}
  </div>
  <div class="checkbox">
      {!! Form::label('open', 'Published:') !!}
      {!! Form::checkbox('open', 1, @$song->open, ['class' => '']) !!}
  </div>
</div>
  <div class="form-group">
      <!-- {!! Form::label('body', 'body:') !!} -->
      {!! Form::textarea('body', $song->body, ['class' => 'form-control','rows'=>30]) !!}
	  </div>


 </div><!-- end body -->
@stop
