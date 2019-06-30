@extends('layouts.master_full')

@section('title', 'create new song')

@section('javascript')

@stop


@section('content')
<h1>{{ __('chords.createnewsong') }}</h1>
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


@if(isset($success))
 @if($success)
  <div class="alert alert-success">
  <strong>Success!</strong> Saved!
  </div>
 @else
   <div class="alert alert-warning">
  <strong>Error!</strong> Data couldn't be saved!
  </div>
 @endif
@endif
<!-- // Form::open(array('route' => 'admin.storesong','method'=>'POST')) !!} -->
{!! Form::open(array('action' => 'AdminController@storesong','method'=>'POST')) !!}

<div class="row">
  <div class="form-group form_artist_search col">
      {!! Form::label('discogs_artist', __('chords.artist') .':') !!}
      <?php /*

      {!! Form::hidden('discogs_artist',  request()->discogs_artist, ['id'=>'discogs_artist']) !!}
      {!! Form::text('artist',  request()->artist, ['required',
           'class'=>'form-control typeahead',//form-control mr-sm-2
           'id'  => 'artist_search',
           'placeholder'=>'Search...']) !!} */ ?>
           {!! Form::text('artist',  request()->artist, [ 'class'=>'form-control',]) !!}
      <select name="discogs_artist" class="form-control">
        <option value="">Select Artist</option>
         <?php
         echo "req".request()->discogs_artist;
       $artists = \App\DiscogsArtists::orderBy('name')->get(["name","id"]);
       $oldartist = (old('discogs_artist')?old('discogs_artist'):request()->discogs_artist);
       for($i=0;$i<count($artists);$i++){
         echo '<option value="'.$artists[$i]["id"].'" ';
         if($oldartist==$artists[$i]["id"]){echo 'selected';}
         echo '>'.$artists[$i]["name"].'</option>';
       }
          ?>
       </select>

    </div>

  <div class="form-group col">
      {!! Form::label('title', __('chords.title') .':') !!}
      {!! Form::text('title', request()->title, ['class' => 'form-control']) !!}
  </div>
</div>
  <div class="checkbox hide">
      {!! Form::label('open', __('chords.published') .':') !!}
      {!! Form::checkbox('open', '', ['class' => 'form-control']) !!}
  </div>
  <div class="form-group">
      {!! Form::label('body', __('chords.content') .':') !!}
      {!! Form::textarea('body', '', ['class' => 'form-control','rows'=>20]) !!}
	  </div>
  {!! Form::submit(__('chords.submit'), ['class' => 'btn btn-default']) !!}

{{ Form::close() }}

 </div><!-- end body -->

@stop
