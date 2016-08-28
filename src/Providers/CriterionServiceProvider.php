<?php

namespace Ideasvn\Repositories\Providers;

use Illuminate\Support\ServiceProvider;

class CriterionServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            'Ideasvn\Repositories\Contracts\CriteriaInterface',
            'Ideasvn\Repositories\Criteria\Criteria'
        );
    }
}
