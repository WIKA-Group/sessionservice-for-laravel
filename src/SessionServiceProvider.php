<?php

namespace WikaGroup\SessionService;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SessionServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('sessionservice')
            ->hasConfigFile('sessionservice')
            ->hasRoute('web')
            ->hasMigration('add_oauth_id_to_users_table');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(SessionService::class);
    }
}
