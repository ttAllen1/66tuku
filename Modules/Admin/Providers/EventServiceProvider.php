<?php

namespace Modules\Admin\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Admin\Events\TestEvent1;
use Modules\Admin\Listeners\TestEventSubscriber;
use Modules\Admin\Listeners\TestListener;


class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        TestEvent1::class => [
            TestListener::class
        ]
    ];

    protected $subscribe = [
        TestEventSubscriber::class,
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
