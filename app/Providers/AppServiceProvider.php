<?php

namespace App\Providers;

use App\Http\Controllers\NotificationCenterController;
use App\Models\User;
use App\Models\SupportTicket;
use App\Policies\SupportTicketPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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

        View::composer('layouts.topbar', function ($view): void {
            $view->with(NotificationCenterController::topbarData(request()));
        });
    }
}
