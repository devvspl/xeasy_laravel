<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\admin\{DashboardController, PermissionController, PermissionGroupController, RolesController, UsersController, MenuController, SettingController, ReportController, FinancialYearController, FilterController, ClaimViewController, APIManagerController};


Route::get('/', fn() => redirect()->route('login'));


Auth::routes();


Route::middleware('auth')->group(function () {


    Route::get('clear-caches', [Controller::class, 'clearAllCaches'])->name('system.clear_caches');


    Route::get('home', [HomeController::class, 'index'])->name('home');

    Route::resource('permission-groups', PermissionGroupController::class)->names('permission_groups');
    Route::resource('permissions', PermissionController::class)->names('permissions');
    Route::post('permissions/assign', [PermissionController::class, 'assignPermissions'])->name('permissions.assign');
    Route::get('permissions-list', [PermissionController::class, 'getAllPermissions'])->name('permissions.list');
    Route::resource('roles', RolesController::class)->names('roles');
    Route::get('roles/list', [RolesController::class, 'getRoles'])->name('roles.list');

    Route::resource('users', UsersController::class)->names('users');
    Route::get('users/{id}/permissions', [PermissionController::class, 'getPermissions'])->name('users.permissions');
    Route::post('users/{id}/permissions/assign', [PermissionController::class, 'assignPermission'])->name('users.permissions.assign');
    Route::post('users/{id}/permissions/revoke', [PermissionController::class, 'revokePermission'])->name('users.permissions.revoke');
    Route::get('profile', [UsersController::class, 'profile'])->name('users.profile');


    Route::resource('menu', MenuController::class)->names('menu');
    Route::get('menu/list', [MenuController::class, 'menuList'])->name('menu.list');
    Route::get('menu/{menu}/logs', [MenuController::class, 'getLogs'])->name('menu.logs');


    Route::resource('financial', FinancialYearController::class)->names('financial');



    Route::get('settings', [SettingController::class, 'index'])->name('settings');
    Route::get('company', [SettingController::class, 'company'])->name('company');
    Route::get('company-config/{id}', [SettingController::class, 'getCompanyConfig'])->name('company_config.get');
    Route::post('save-config', [SettingController::class, 'saveCompanyConfig'])->name('company_config.save');
    Route::post('theme', [SettingController::class, 'saveThemeSettings'])->name('theme.save');
    Route::get('general', [SettingController::class, 'getGeneralSettings'])->name('general.get');
    Route::post('general', [SettingController::class, 'saveGeneralSettings'])->name('general.save');




    Route::get('claim-report', [ReportController::class, 'claimReport'])->name('claim-report');
    Route::get('claim-detail', [ClaimViewController::class, 'getClaimDetailView']);
    Route::get('daily-activity', [ReportController::class, 'dailyActivity'])->name('daily_activity');
    Route::post('daily-activity/data', [ReportController::class, 'getDailyActivityData'])->name('daily_activity.data');
    Route::post('daily-activity/export', [ReportController::class, 'exportDailyActivity'])->name('daily_activity.export');
    Route::post('filter-claims', [ReportController::class, 'filterClaims'])->name('filter_claims');
    Route::post('expense-claims/export', [ReportController::class, 'export'])->name('expense_claims.export');



    Route::prefix('filters')->name('filters.')->group(function () {
        Route::post('employees/by-department', [FilterController::class, 'getEmployeesByDepartment'])->name('employees.by_department');
        Route::post('verticals/by-function', [FilterController::class, 'getVerticalsByFunction'])->name('verticals.by_function');
        Route::post('departments/by-vertical', [FilterController::class, 'getDepartmentsByVertical'])->name('departments.by_vertical');
        Route::post('sub-departments/by-department', [FilterController::class, 'getSubDepartmentsByDepartment'])->name('sub_departments.by_department');
    });


    Route::get('claims/detail-view', [ClaimViewController::class, 'getClaimDetailView'])->name('claims.detail_view');



    Route::resource('api-manager', APIManagerController::class)->names('api_manager');
    Route::get('api/fields-mapping/{claim_id}', [APIManagerController::class, 'showMappingPage'])->name('api.fields_mapping.page');
    Route::post('api/fields-mapping/{claim_id}', [APIManagerController::class, 'storeFieldMapping'])->name('api.fields_mapping.store');
    Route::get('api/fields/mapping/{claim_id}', [APIManagerController::class, 'getFieldMappings'])->name('api.fields_mapping.get');
    Route::get('api/tables', [APIManagerController::class, 'getTables'])->name('api.tables');
    Route::get('api/columns/{table}', [APIManagerController::class, 'getColumns'])->name('api.columns');
    Route::post('api/map-fields', [APIManagerController::class, 'mapFields'])->name('api.map_fields');
});
