<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\ViewSongEvent' => [
            'App\Listeners\ChordsHitListener',
        ],
        'auth.login' => [
            'App\Handlers\Events\UserListener',
        ],
        //'auth.logout' => [
          //  'App\Handlers\Events\SomeHandler@onUserLogout',
        //],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
