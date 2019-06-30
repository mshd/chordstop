@extends('layouts.master')

@section('title', 'Review '.$song->title.' - '.@$song->discogs->name)


@section('content')
<h1>Review Song {{ $song->title  }} - 	{{ @$song->discogs->name }}</h1>
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


<table>
<tr><th>original</th><th>edited</th></tr>
  <tr><td><pre>{{ $song->original->body }}</pre>
  </td><td><pre>{{ $song->body }}</pre>
  </td></tr>
 </div><!-- end body -->
@stop
