<?php

namespace App\Providers;

use App\Models\Document;
use App\Models\Member;
use App\Models\MembershipApplication;
use App\Models\Complaint;
use App\Policies\ComplaintPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\MemberPolicy;
use App\Policies\MembershipApplicationPolicy;
use App\Support\AccessControl;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

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
        Gate::before(function ($user, string $ability): ?bool {
            return $user->hasRole(AccessControl::ROLE_SUPER_ADMIN) ? true : null;
        });

        Gate::policy(Member::class, MemberPolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(MembershipApplication::class, MembershipApplicationPolicy::class);
        Gate::policy(Complaint::class, ComplaintPolicy::class);
    }
}