<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\Session;
class SetDatabaseConnection {
    public function handle($request, Closure $next) {
        if (Session::has('company_id')) {
            try {
                app('dynamic.database') (Session::get('company_id'));
            }
            catch(\Exception $e) {
                Session::forget(['company_id', 'year_id']);
            }
        }
        return $next($request);
    }
}
