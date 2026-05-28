<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

        // Dynamically add current request host to Sanctum's stateful domains list
        if (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
            $stateful = config('sanctum.stateful', []);
            if (is_string($stateful)) {
                $stateful = explode(',', $stateful);
            }
            if (!in_array($host, $stateful)) {
                $stateful[] = $host;
                config(['sanctum.stateful' => $stateful]);
            }
        }

        // Dynamic Subdomain Database Switcher for Multi-Tenancy (Option A)
        if (!app()->runningInConsole() && isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
            $suffix = 'bills.gridbase.com.do';
            
            $subdomain = null;
            
            // Format 1: tenant.bills.gridbase.com.do
            if (str_ends_with($host, '.' . $suffix)) {
                $subdomain = substr($host, 0, -strlen('.' . $suffix));
            }
            // Format 2: bills.tenant.domain (e.g. bills.ejesalud.com.do)
            elseif (str_starts_with($host, 'bills.')) {
                $parts = explode('.', $host);
                if (count($parts) >= 3 && $parts[1] !== 'gridbase') {
                    $subdomain = $parts[1];
                }
            }
            
            if (!empty($subdomain) && preg_match('/^[a-z0-9\-]+$/i', $subdomain)) {
                $dbName = 'grupaqgl_' . strtolower($subdomain);
                
                // Switch the active database name in the configuration
                config(['database.connections.mysql.database' => $dbName]);
                
                // Purge and reconnect to apply changes instantly
                DB::purge('mysql');
                DB::reconnect('mysql');
            }
        }
    }
}
