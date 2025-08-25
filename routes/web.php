<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DatabaseSwitchController;
use App\Http\Controllers\admin\{
    DashboardController,
    PermissionController,
    PermissionGroupController,
    RolesController,
    UsersController,
    MenuController,
    SettingController,
    ReportController,
    FinancialYearController,
    FilterController,
    ClaimViewController,
    APIManagerController,
    EmployeeController,
    ExpenseDetailsController,
    MediatorController
};

// Authentication Routes
// Handles user authentication and redirection to login page
Route::get('/', fn() => redirect()->route('login'));
Auth::routes();

// Protected Routes (Requires Authentication)
Route::middleware('auth')->group(function () {

    // System Utilities
    // Routes for system-wide utilities like cache clearing and company listing
    Route::get('companies-list', [DatabaseSwitchController::class, 'getCompanies']);
    Route::get('clear-caches', [Controller::class, 'clearAllCaches']);

    // Dashboard Routes
    // Routes for dashboard data and employee trends
    Route::get('home', [HomeController::class, 'index']);
    Route::get('home-data', [HomeController::class, 'getDashboardData']);
    Route::post('get-employee-trend', [HomeController::class, 'getEmployeeTrend']);
    Route::post('export/expense-month-wise', [HomeController::class, 'exportExpenseMonthWise']);
    Route::post('export/expense-department-wise', [HomeController::class, 'exportExpenseDepartmentWise']);
    Route::post('export/expense-claim-type-wise', [HomeController::class, 'exportExpenseClaimTypeWise']);
    Route::get('analytics/{page}', [HomeController::class, 'analytics']);
    Route::get('analytics-dashboard-data', [HomeController::class, 'analyticsDashboardData'])->name('analytics-dashboard-data');

    // Permission Management
    // Routes for managing permission groups, permissions, and role assignments
    Route::resource('permission-groups', PermissionGroupController::class);
    Route::resource('permissions', PermissionController::class);
    Route::post('permissions/assign', [PermissionController::class, 'assignPermissions']);
    Route::get('permissions-list', [PermissionController::class, 'getAllPermissions']);

    // Role Management
    // Routes for managing roles
    Route::resource('roles', RolesController::class);
    Route::get('get-roles-list', [RolesController::class, 'getRoles']);

    // User Management
    // Routes for user management, including permissions and profiles
    Route::resource('users', UsersController::class);
    Route::get('users/{id}/permissions', [PermissionController::class, 'getPermissions']);
    Route::post('users/{id}/permissions/assign', [PermissionController::class, 'assignPermission']);
    Route::post('users/{id}/permissions/revoke', [PermissionController::class, 'revokePermission']);
    Route::get('profile', [UsersController::class, 'profile']);

    // Menu Management
    // Routes for managing menus and their logs
    Route::resource('menu', MenuController::class);
    Route::get('menu-list', [MenuController::class, 'menuList']);
    Route::get('menu/{menu}/logs', [MenuController::class, 'getLogs']);

    // Financial Year Management
    // Routes for managing financial years
    Route::resource('financial', FinancialYearController::class);

    // Settings Management
    // Routes for general, company, and theme settings
    Route::get('settings', [SettingController::class, 'index']);
    Route::get('company', [SettingController::class, 'company']);
    Route::get('company-config/{id}', [SettingController::class, 'getCompanyConfig']);
    Route::post('save-config', [SettingController::class, 'saveCompanyConfig']);
    Route::post('settings/theme', [SettingController::class, 'saveThemeSettings']);
    Route::get('general', [SettingController::class, 'getGeneralSettings']);
    Route::post('general', [SettingController::class, 'saveGeneralSettings']);

    // Report Management
    // Routes for generating and exporting reports, including claims and daily activities
    Route::get('claim-report', [ReportController::class, 'claimReport']);
    Route::get('daily-activity', [ReportController::class, 'dailyActivity']);
    Route::post('daily-activity/data', [ReportController::class, 'getDailyActivityData']);
    Route::post('daily-activity/export', [ReportController::class, 'exportDailyActivity']);
    Route::post('filter-claims', [ReportController::class, 'filterClaims']);
    Route::post('expense-claims/export', [ReportController::class, 'export']);
    Route::get('top-rating-employee', [ReportController::class, 'topRatingEmployee']);
    Route::post('claims/return', [ReportController::class, 'returnClaim']);
    Route::get('same_date', [ReportController::class, 'sameDayCkaimUpload']);

    // Claim Details
    // Routes for viewing claim details and types
    Route::get('claim-detail', [ClaimViewController::class, 'getClaimDetailView']);
    Route::get('claims/detail-view', [ClaimViewController::class, 'getClaimDetailView']);
    Route::get('get-claim-types', [ClaimViewController::class, 'getActiveClaimTypes']);

    // Employee Management
    // Routes for employee-related operations, including search and filtering
    Route::post('employees/search', [EmployeeController::class, 'search']);
    Route::get('employee/{id}', [EmployeeController::class, 'employee']);
    Route::post('employees/by-department', [FilterController::class, 'getEmployeesByDepartment']);
    Route::post('verticals/by-function', [FilterController::class, 'getVerticalsByFunction']);
    Route::post('departments/by-vertical', [FilterController::class, 'getDepartmentsByVertical']);
    Route::post('sub-departments/by-department', [FilterController::class, 'getSubDepartmentsByDepartment']);

    // API Management
    // Routes for managing API field mappings and table/column data
    Route::resource('api-manager', APIManagerController::class);
    Route::get('api/fields-mapping/{claim_id}', [APIManagerController::class, 'showMappingPage']);
    Route::post('api/fields-mapping/{claim_id}', [APIManagerController::class, 'storeFieldMapping']);
    Route::get('api/fields/mapping/{claim_id}', [APIManagerController::class, 'getFieldMappings']);
    Route::get('api/tables', [APIManagerController::class, 'getTables']);
    Route::get('api/columns/{table}', [APIManagerController::class, 'getColumns']);
    Route::post('api/map-fields', [APIManagerController::class, 'mapFields']);

    // Expense Management
    // Route for generating expense PDFs
    Route::get('generate-expense-pdf', [ExpenseDetailsController::class, 'generatePdf']);

    // Data Punch
    // Routes for data punch operations (Note: Duplicate route detected, only one kept)
    Route::get('data-punch', [MediatorController::class, 'dataPunch']);

});