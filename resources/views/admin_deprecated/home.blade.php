@extends('layouts.master',["addons"=>array("datatables")])

@section('title', 'Admin Home')
@section('javascript')

$(".song_action").click(function (e) {

$.ajax({
   url: "/admin/ajax",
   data: { id: $(this).attr("data-id"), type: $(this).attr("id").substr(5) },
   //cache: false,
   //contentType: false,
   //processData: false,
   //mimeType: "multipart/form-data",
   type: "Post",
   dataType: "Json",
   success: function(result) {
       if (result.Success) {
        //   $(this).closest("tr").remove(); // You can remove row like this
       }
       //eval(result.Script);
       console.log(result);
   },
   error: function() {
       alert("Error");
   }
});
});
@stop
@section('content')
<h1>Admin Dashboard</h1>
<p>Welcome {{ Auth::user()->name }}</p>
Your role {{ Auth::user()->role }}
@if(Auth::user()->role == "admin")
admin you are
@endif
<a href="/admin/list">List Songs</a>
<h2>Songs edited by me</h2>
<table id="table_list" data-toggle="table">
 <thead>
     <tr>
         <th data-field="id" data-sortable="true">Id</th>
         <th data-field="type" data-sortable="true">Artist</th>
         <th data-field="type" data-sortable="true">Title</th>
         <th data-field="date" data-sortable="true">Created At</th>
         <th data-field="type" data-sortable="true">Status</th>
         <th data-field="type" data-sortable="true">User</th>
         <th data-field="type" data-sortable="true">Review</th>
     </tr>
 </thead>
 <tbody>
   @foreach($usersongs as $song)
     <tr>
       <td>{{ $song->id }}</td>
       <td>{{ $song->artist }}</td>
       <td><a href="/admin/song/{{ $song->song_id }}" target="_blank">{{ $song->title }}</a></td>
       <td>{{ $song->created_at }}</td>
       <td><?php
       $array = [
         "" =>         '<span class="badge badge-pill badge-primary">In Review</span>',
         "accepted" => '<span class="badge badge-pill badge-success">'.__('chords.accepted').'</span>',
         "rejected" => '<span class="badge badge-pill badge-danger">'. __('chords.rejected').'</span>',
         "modified" => '<span class="badge badge-pill badge-warning">'.__('chords.modified').'</span>',
       ];
       if(array_key_exists($song->status,$array)){
       echo $array[$song->status];
     }else{
       echo $song->status;
     }
       ?></td>
       <td>{{ $song->user->name }},{{ $song->user->email }}</td>
       <td><a href="/admin/review/{{ $song->id }}">Review</a></td>
       <td>
<a href="javascript:void(0)" data-id="{{ $song->id }}" id="song_edit" class="song_action"><span class="fa fa-edit"></span></a>
<a href="javascript:void(0)" data-id="{{ $song->id }}" id="song_delete" class="song_action"><span class="fa fa-remove"></span></a>
<a href="javascript:void(0)" data-id="{{ $song->id }}" id="song_accept" class="song_action"><span class="fa fa-check"></span></a></td>

     </tr>
   @endforeach

 </tbody>
</table>
<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">

@stop
