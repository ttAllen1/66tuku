<?php

namespace App\Providers;

use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //Route 路由自定义scene(场景方法)
        Route::macro('scene',function ($scene=null){
            $action = Route::getAction();
            $action['_scene'] = $scene;
            Route::setAction($action);
        });
    }
}
