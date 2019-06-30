<?php

namespace App\Listeners;

use App\Events\ViewSongEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use Illuminate\Support\Facades\Session;


class ChordsHitListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  ViewSongEvent  $event
     * @return void
     */
     public function handle(ViewSongEvent $event)
     {
       $chord = $event->chord;
       if ( ! $this->isPostViewed($chord))
       {
           //$post->increment('view_counter');
           //$post->hits += 1;
           //Session::put('variableName', 'tes');

           //dd(Session::all());

           $this->storePost($chord);
           //dd("increase");

       }
     }

     private function isPostViewed($post)
     {
         // Get all the viewed posts from the session. If no
         // entry in the session exists, default to an
         // empty array.
         $viewed = Session::get('viewed_posts', []);

         // Check the viewed posts array for the existance
         // of the id of the post
         return in_array($post->id, $viewed);
     }

     private function storePost($post)
     {
         // Push the post id onto the viewed_posts session array.
         Session::push('viewed_posts', $post->id);
     }

}
