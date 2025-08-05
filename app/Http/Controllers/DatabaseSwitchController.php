<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Models\FinancialYear;
class DatabaseSwitchController extends Controller
{
    public function getCompanies()
    {
        $companies = config('company_databases.companies');
        $companyList = array_map(function ($id, $company) {
            return ['id' => $id, 'name' => $company['name']];
        }, array_keys($companies), $companies);
        return response()->json($companyList);
    }
    public function getFinancialYears(Request $request)
    {
        $company = $request->query('company');
        if (!$company) {
            return response()->json(['message' => 'Missing company ID'], 400);
        }
        try {
            $this->switchDatabaseConnection($company);
            $years = FinancialYear::select('YearId as id', 'Year as name')->get()->toArray();
            $this->resetDatabaseConnection();
            if (empty($years)) {
                return response()->json(['message' => 'No financial years found for company ID ' . $company], 404);
            }
            return response()->json($years);
        } catch (\Exception $e) {
            $this->resetDatabaseConnection();
            return response()->json(['message' => 'Error fetching financial years: ' . $e->getMessage()], 500);
        }
    }
    protected function switchDatabaseConnection($companyId)
    {
        $companies = config('company_databases.companies');
        if (!isset($companies[$companyId]['hrims'])) {
            throw new \Exception('Invalid company ID or missing hrims configuration');
        }
        $expense = $companies[$companyId]['expense'];
        $connectionName = 'company_' . $companyId;
        config(["database.connections.$connectionName" => ['driver' => 'mysql', 'host' => $expense['host'], 'port' => $expense['port'], 'database' => $expense['database'], 'username' => $expense['username'], 'password' => $expense['password'], 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'strict' => true, 'engine' => null,]]);
        DB::purge($connectionName);
        DB::setDefaultConnection($connectionName);
    }
    protected function resetDatabaseConnection()
    {
        DB::purge(DB::getDefaultConnection());
        DB::setDefaultConnection('mysql');
    }
    public function switchDatabase(Request $request)
    {
        $request->validate(['company_id' => 'required|integer', 'year_id' => 'required|integer',]);
        $companyId = $request->input('company_id');
        $yearId = $request->input('year_id');
        $company_name = config("company_databases.companies.$companyId.name", 'Unknown Company');
        $year_value = FinancialYear::getYearById($yearId);
        try {
            app('dynamic.database')($companyId);
            Session::put('company_id', $companyId);
            Session::put('company_name', $company_name);
            Session::put('year_id', $yearId);
            Session::put('year_value', $year_value);
            return response()->json(['success' => true, 'message' => 'Database switched successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
