<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Auth;

class UserController extends Controller
{


    public function __construct()
  	{
  		$this->middleware('auth');
  	}

  	/**
  	 * Display a listing of the resource.
  	 *
  	 * @return Response
  	 */
  	public function index()
  	{
  		return $this->indexSort('total');
  	}

  	/**
  	 * Display a listing of the resource.
  	 *
       * @param  string  $role
  	 * @return Response
  	 */
  	public function indexSort($role)
  	{
  		$users = User::all();

  		return view('back.users.index', compact('users'));
  	}
  	/**
  	 * Show the form for creating a new resource.
  	 *
  	 * @return Response
  	 */
  	public function create()
  	{
  		return view('back.users.create');
  	}

  	/**
  	 * Store a newly created resource in storage.
  	 *
  	 * @param  App\requests\UserCreateRequest $request
  	 *
  	 * @return Response
  	 */
  	public function store(
  		UserCreateRequest $request)
  	{
  		$this->user_gestion->store($request->all());

  		return redirect('user')->with('ok', trans('back/users.created'));
  	}

  	/**
  	 * Display the specified resource.
  	 *
  	 * @param  App\Models\User
  	 * @return Response
  	 */
  	public function show(User $user)
  	{
  		return view('back.users.show',  compact('user'));
  	}

  	/**
  	 * Show the form for editing the specified resource.
  	 *
  	 * @param  App\Models\User
  	 * @return Response
  	 */
  	public function edit(User $user)
  	{
      $acc = User::find($user->id);
      //dd($acc);
  		return view('back.users.edit', array_merge(compact('acc')));
  	}

  	/**
  	 * Update the specified resource in storage.
  	 *
  	 * @param  App\requests\UserUpdateRequest $request
  	 * @param  App\Models\User
  	 * @return Response
  	 */
  	public function update(
  		Request $request,
  		User $user)
  	{
      $current = Auth::user();
      $r=$request->all();
      //$user    = User::find($current->id);
      //$r["id"]
      if($current->id != $user->id &&  $current->role != "admin"){
        return "No permission";
      }
      if ($request->input('password') != null) {
        $user->password = bcrypt($request->input('password'));
      }
      $user->name = $r["name"];

      //return $request->all();
  		//$this->user_gestion->update($request->all(), $user);
      $user->save();

  		return back()->with('ok', trans('back/users.updated'));
  	}

  	/**
  	 * Update the specified resource in storage.
  	 *
  	 * @param  Illuminate\Http\Request $request
  	 * @param  App\Models\User $user
  	 * @return Response
  	 */
  	public function updateSeen(
  		Request $request,
  		User $user)
  	{
  		$this->user_gestion->update($request->all(), $user);

  		return response()->json();
  	}

  	/**
  	 * Remove the specified resource from storage.
  	 *
  	 * @param  App\Models\user $user
  	 * @return Response
  	 */
  	public function destroy(User $user)
  	{
    	$user->delete();
  		return redirect('user')->with('ok', trans('back/users.destroyed'));
  	}

    public function myaccount(){
      $acc = User::find(Auth::user()->id);
      $locale = \App::getLocale();
      return view("back.users.myaccount",compact('acc','locale'));
    }
}
