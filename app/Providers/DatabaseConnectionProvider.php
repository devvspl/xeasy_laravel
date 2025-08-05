<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
class DatabaseConnectionProvider extends ServiceProvider
{
    public function boot()
    {
    }
    public function register()
    {
        $this->app->singleton('dynamic.database', function ($app) {
            return function ($companyId) {
                $companies = config('company_databases.companies');
                if (!isset($companies[$companyId])) {
                    throw new \Exception('Invalid company ID');
                }
                $company = $companies[$companyId];
                Config::set('database.connections.hrims', ['driver' => 'mysql', 'host' => $company['hrims']['host'], 'port' => $company['hrims']['port'], 'database' => $company['hrims']['database'], 'username' => $company['hrims']['username'], 'password' => $company['hrims']['password'], 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'strict' => true, 'engine' => null,]);
                Config::set('database.connections.expense', ['driver' => 'mysql', 'host' => $company['expense']['host'], 'port' => $company['expense']['port'], 'database' => $company['expense']['database'], 'username' => $company['expense']['username'], 'password' => $company['expense']['password'], 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'strict' => true, 'engine' => null,]);
                DB::purge('hrims');
                DB::purge('expense');
                DB::reconnect('hrims');
                DB::reconnect('expense');
            };
        });
    }
}
