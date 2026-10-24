<?php

namespace App\Providers;

use App\Models\Followup;
use App\Support\Crm;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // cheap values every page may use (both are cached per request)
        View::composer('*', function ($view) {
            $view->with([
                'agentList' => Crm::agents(),
                'isAdmin' => Crm::isAdmin(),
            ]);
        });

        View::composer('layouts.app', function ($view) {
            $view->with([
                'crmSettings' => Crm::settings(),
                'overdueCount' => Followup::visible()->pending()->where('due', '<', now())->count(),
            ]);
        });
    }
}
