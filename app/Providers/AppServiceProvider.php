<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Gate;
use App\Policies\ClientPolicy;
use App\Policies\RecurringInvoicePolicy;
use App\Models\Client;
use App\Models\RecurringInvoice;

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
        Vite::prefetch(concurrency: 3);
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
        
        $this->registerPolicies();
    }
    
    protected function registerPolicies()
    {
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(RecurringInvoice::class, RecurringInvoicePolicy::class);
    }
}
