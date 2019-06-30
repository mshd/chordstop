<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Socialite;
use Auth;
use App\User;

class SocialAuthController extends Controller
{
       public function redirect($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback($provider)
    {
/*
      $user = Socialite::with ( $service )->user ();
      return view ( 'home' )->withDetails ( $user )->withService ( $service );*/
      try{
        $user = Socialite::driver($provider)->user();
      } catch (\Exception $e) {
          return redirect('/login')->with('status', 'Something went wrong or You have rejected the app!');
      }
      $authUser = $this->findOrCreateUser($user, $provider);
      Auth::login($authUser, true);
      //return view ( 'home.home' )->withDetails ( $user )->withService ( $provider );
      return redirect("/");
    }
   /**
    * If a user has registered before using social auth, return the user
    * else, create a new user object.
    * @param  $user Socialite user object
    * @param $provider Social auth provider
    * @return  User
    */
   public function findOrCreateUser($user, $provider)
   {
       $authUser = User::where($provider.'_id', $user->id)->first();
       if ($authUser) {
           return $authUser;
       }
       $authUser = User::where('email', $user->email)->first();
       if ($authUser) {
          User::where('email', $user->email)->update([
            $provider.'_id' => $user->id
          ]);
          return $authUser;
       }
       return User::create([
           'name'     => $user->name,
           'email'    => $user->email,
           $provider.'_id' => $user->id,
           'ip_create' =>  request()->ip(),
       ]);
   }
}
