<?php

namespace App\Providers;

use App\Models\User;
use App\Models\SupportTicket;
use App\Policies\SupportTicketPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
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
        Schema::defaultStringLength(191);
        Gate::policy(SupportTicket::class, SupportTicketPolicy::class);

        Gate::before(function (User $user): ?bool {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }
}
