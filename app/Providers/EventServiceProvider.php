<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Admin\Events\OnceAccount;
use Modules\Admin\Events\ReopenAccount;
use Modules\Admin\Listeners\OnceAccountListener;
use Modules\Admin\Listeners\ReopenAccountListener;
use Modules\Api\Events\ChatEvent;
use Modules\Api\Events\CreateVoteByPic;
use Modules\Api\Events\PicDetailCreatedEvent;
use Modules\Api\Listeners\PicDetailCreatedListener;
use Modules\Api\Listeners\PicVoteListener;
use Modules\Api\Listeners\UserChatListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        PicDetailCreatedEvent::class => [
            PicDetailCreatedListener::class
        ],
        ChatEvent::class => [
            UserChatListener::class
        ],
        OnceAccount::class => [
            OnceAccountListener::class
        ],
        ReopenAccount::class => [
            ReopenAccountListener::class
        ],
        CreateVoteByPic::class => [
            PicVoteListener::class
        ]
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
