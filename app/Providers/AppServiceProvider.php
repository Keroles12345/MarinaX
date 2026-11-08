<?php

namespace App\Providers;

use App\Services\CurrencyService;
use App\Support\DashboardTemplateTranslator;
use App\Support\TenantContext;
use App\Support\TenantDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use App\Support\SystemPermissions;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
     $this->app->singleton(CurrencyService::class,function(){
return new CurrencyService();
     });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::precompiler([DashboardTemplateTranslator::class, 'compile']);

        Blade::if('systemPermission', function (string $module, string $action = 'view'): bool {
            return SystemPermissions::allows(auth()->user(), $module, $action);
        });

        Validator::extend('tenant_exists', function (string $attribute, mixed $value, array $parameters): bool {
            if (! TenantContext::isInitialized() || TenantContext::id() === null || empty($parameters[0])) {
                return false;
            }

            $table = $parameters[0];
            $column = $parameters[1] ?? 'id';

            return TenantDatabase::table($table)->where($column, $value)->exists();
        });
    }
}
