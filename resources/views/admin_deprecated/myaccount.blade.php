@extends('layouts.master')

@section('title', 'Admin Home')

@section('content')
<h1>{{ __('auth.update_account') }}</h1>

{{ Form::model($acc, ['action' => ['AdminController@myaccount','id'=>$acc->id],'method'=>'PUT','class'=>"form-inline"]) }}
<div class="container">

  <div class="form-group row">
      {!! Form::label('email', __('auth.email') .':', ['class' => 'col-form-label col-sm-2']) !!}
      <div class="col-sm-10">
        {{  $acc->email }}
      </div>
  </div>
  <div class="form-group row">
      {!! Form::label('name', __('auth.role') .':', ['class' => 'col-form-label col-sm-2']) !!}
      <div class="col-sm-10">
        {{  $acc->role }}
      </div>
  </div>
  <div class="form-group row">
      {!! Form::label('name','Locale:', ['class' => 'col-form-label col-sm-2']) !!}
      <div class="col-sm-10">
        {{ $locale }}
      </div>
  </div>
<div class="form-group row">
    {!! Form::label('name', __('auth.name') .':', ['class' => 'col-form-label col-sm-2']) !!}
    <div class="col-sm-10">
    {!! Form::text('name', $acc->name, ['class' => 'form-control']) !!}
    </div>
</div>
<div class="form-group row">
    {!! Form::label('password', __('auth.password') .':',['class' => 'col-form-label col-sm-2']) !!}
    <div class="col-sm-10">
    {!! Form::password('password',  ['class' => 'form-control']) !!}
  </div>
</div>
<div class="form-group row">
  <div class="offset-sm-2 col-sm-10">
    {!! Form::submit('Submit', ['class' => 'btn btn-primary']) !!}
  </div>
</div>
</div>

</form>
@stop
