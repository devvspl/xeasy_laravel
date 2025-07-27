<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class SetCompanyDatabase
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $companyId = session('company_id');

        if ($companyId) {
            $companyDatabases = config('company_databases.companies');

            if (isset($companyDatabases[$companyId])) {
                // Set hrims connection
                Config::set('database.connections.hrims.database', $companyDatabases[$companyId]['hrims']['database']);
                Config::set('database.connections.hrims.host', $companyDatabases[$companyId]['hrims']['host']);
                Config::set('database.connections.hrims.port', $companyDatabases[$companyId]['hrims']['port']);
                Config::set('database.connections.hrims.username', $companyDatabases[$companyId]['hrims']['username']);
                Config::set('database.connections.hrims.password', $companyDatabases[$companyId]['hrims']['password']);

                // Set expense connection
                Config::set('database.connections.expense.database', $companyDatabases[$companyId]['expense']['database']);
                Config::set('database.connections.expense.host', $companyDatabases[$companyId]['expense']['host']);
                Config::set('database.connections.expense.port', $companyDatabases[$companyId]['expense']['port']);
                Config::set('database.connections.expense.username', $companyDatabases[$companyId]['expense']['username']);
                Config::set('database.connections.expense.password', $companyDatabases[$companyId]['expense']['password']);

                // Reconnect to apply changes
                DB::purge('hrims');
                DB::purge('expense');
            }
        }

        return $next($request);
    }
}
