@extends('layouts.master_full')

@section('title', 'Chords list')

<?php
View::share ( 'addons', ["data"] );
?>
@section('styles')
<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.13/css/jquery.dataTables.css">
@stop

@section('scripts')
<script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.13/js/jquery.dataTables.js"></script>
@stop
@section('javascript')
$(document).ready( function () {
    $('#table_list').DataTable();
} );
@stop
@section('content')
<h1>List of songs</h1>
 <p></p>
 <div><!-- start body -->



   <table id="table_list" data-toggle="table">
    <thead>
        <tr>
            <th data-field="id" data-sortable="true">Id</th>
            <th data-field="date" data-sortable="true">Artist</th>
            <th data-field="type" data-sortable="true">Title</th>
            <th data-field="type" data-sortable="true">Hits</th>
        </tr>
    </thead>
    <tbody>
      @foreach($tb as $song)
        <tr>
          <td>{{ $song->id }}</td>
          <td>{{ $song->artist }}</td>
          <td><a href="/song/{{ $song->id }}/edit" target="_blank">{{ $song->title }}</a></td>
          <td>{{ $song->hits }}</td>
        </tr>
      @endforeach

    </tbody>
</table>



 </div><!-- end body -

   <th data-field="type" data-sortable="true">Last Edit</th>
   <td>{{ $song->updated_at }}</td>


{ if($song->open) 'font-style: italic':'') }}

   -->
@stop
