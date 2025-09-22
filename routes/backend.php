<?php

use App\Http\Controllers\admin\APIManagerController;
use App\Http\Controllers\admin\ClaimViewController;
use App\Http\Controllers\admin\CoreAPIController;
use App\Http\Controllers\admin\EmailTemplateController;
use App\Http\Controllers\admin\EmployeeController;
use App\Http\Controllers\admin\ExpenseDetailsController;
use App\Http\Controllers\admin\FilterController;
use App\Http\Controllers\admin\FinancialYearController;
use App\Http\Controllers\admin\MediatorController;
use App\Http\Controllers\admin\MenuController;
use App\Http\Controllers\admin\NotificationController;
use App\Http\Controllers\admin\PermissionController;
use App\Http\Controllers\admin\PermissionGroupController;
use App\Http\Controllers\admin\ReportController;
use App\Http\Controllers\admin\RolesController;
use App\Http\Controllers\admin\SettingController;
use App\Http\Controllers\admin\UsersController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DatabaseSwitchController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

// Protected Routes (Requires Authentication)
Route::middleware('auth')->group(function () {

    // System Utilities
    // Routes for system-wide utilities like cache clearing and company listing
    Route::get('companies-list', [DatabaseSwitchController::class, 'getCompanies']);
    Route::get('clear-caches', [Controller::class, 'clearAllCaches']);

    // Financial Year Management
    // Routes for managing financial years
    Route::resource('financial', FinancialYearController::class);

    // Dashboard Routes
    // Routes for dashboard data and employee trends
    Route::middleware('permission:home_page')->group(function () {
        Route::get('home', [HomeController::class, 'index']);
        Route::get('home-data', [HomeController::class, 'getDashboardData']);
        Route::post('get-employee-trend', [HomeController::class, 'getEmployeeTrend']);
        Route::post('export/expense-month-wise', [HomeController::class, 'exportExpenseMonthWise']);
        Route::post('export/expense-department-wise', [HomeController::class, 'exportExpenseDepartmentWise']);
        Route::post('export/expense-claim-type-wise', [HomeController::class, 'exportExpenseClaimTypeWise']);
        Route::get('analytics/{page}', [HomeController::class, 'analytics']);
        Route::get('analytics-dashboard-data', [HomeController::class, 'analyticsDashboardData']);
        Route::post('get-sub-departments', [HomeController::class, 'getSubDepartments']);
        Route::post('export-reports', [HomeController::class, 'exportReports']);
    });

    // Permission Management
    // Routes for managing permission groups, permissions, and role assignments
    Route::middleware('permission:permission_management')->group(function () {
        Route::resource('permission-groups', PermissionGroupController::class);
        Route::resource('permissions', PermissionController::class);
        Route::get('permissions-old', [PermissionController::class, 'permissionOld']);
        Route::post('permissions/assign', [PermissionController::class, 'assignPermissions']);
        Route::get('permissions-list', [PermissionController::class, 'getAllPermissions']);
    });

    // Role Management
    // Routes for managing roles
    Route::middleware('permission:role_management')->group(function () {
        Route::resource('roles', RolesController::class);
        Route::get('get-roles-list', [RolesController::class, 'getRoles']);
    });

    // User Management
    // Routes for user management, including permissions and profiles
    Route::middleware('permission:user_management')->group(function () {
        Route::resource('users', UsersController::class);
        Route::get('users/data', [UsersController::class, 'getUsersData']);
        Route::get('user/{id}/permission', [UsersController::class, 'getPermissionView']);
        Route::get('users/{id}/permissions', [PermissionController::class, 'getPermissions']);
        Route::post('users/{id}/permissions/assign', [PermissionController::class, 'assignPermission']);
        Route::post('users/{id}/permissions/revoke', [PermissionController::class, 'revokePermission']);
        Route::post('users/import', [UsersController::class, 'importEmployees'])->name('users.import');
        Route::get('profile', [UsersController::class, 'profile']);
        Route::get('user-activity', [UsersController::class, 'userActivity']);
        Route::get('user-activity/data', [UsersController::class, 'userActivityData']);
    });

    // Menu Management
    // Routes for managing menus and their logs
    Route::middleware('permission:menu_management')->group(function () {
        Route::resource('menu', MenuController::class);
        Route::get('menu-list', [MenuController::class, 'menuList']);
        Route::get('menu/log/{id}', [MenuController::class, 'getLogs']);
    });

    // Settings Management
    // Routes for general, company, and theme settings
    Route::middleware('permission:general_settings')->group(function () {
        Route::get('settings', [SettingController::class, 'index']);
        Route::get('company', [SettingController::class, 'company']);
        Route::get('company-config/{id}', [SettingController::class, 'getCompanyConfig']);
        Route::post('save-config', [SettingController::class, 'saveCompanyConfig']);
        Route::post('settings/theme', [SettingController::class, 'saveThemeSettings']);
        Route::get('general', [SettingController::class, 'getGeneralSettings']);
        Route::post('general', [SettingController::class, 'saveGeneralSettings']);
        Route::get('odo-backdate-approval', [SettingController::class, 'odoBackdateApproval']);
        Route::post('odo-backdate-setting/{department_id}', [SettingController::class, 'updateOdoBackdateSetting'])->name('odo-backdate-setting.update');
    });

    // Report Management
    // Routes for generating and exporting reports, including claims and daily activities
    Route::middleware('permission:report_management')->group(function () {
        Route::get('claim-report', [ReportController::class, 'claimReport']);
        Route::get('daily-activity', [ReportController::class, 'dailyActivity']);
        Route::post('daily-activity/data', [ReportController::class, 'getDailyActivityData']);
        Route::post('daily-activity/export', [ReportController::class, 'exportDailyActivity']);
        Route::post('filter-claims', [ReportController::class, 'filterClaims']);
        Route::post('expense-claims/export', [ReportController::class, 'export']);
        Route::get('top-rating-employee', [ReportController::class, 'topRatingEmployee']);
        Route::post('claims/return', [ReportController::class, 'returnClaim']);
        Route::get('same_date', [ReportController::class, 'sameDayClaimUpload']);
    });

    // Email Template Management
    // Routes for managing email templates and their logs
    Route::middleware('permission:email_template_management')->group(function () {
        Route::get('email-templates', [EmailTemplateController::class, 'index'])->name('email_templates.index');
        Route::post('email-template', [EmailTemplateController::class, 'store'])->name('email_template.store');
        Route::get('email-template/{id}/edit', [EmailTemplateController::class, 'edit'])->name('email_template.edit');
        Route::put('email-template/{template}', [EmailTemplateController::class, 'update'])->name('email_template.update');
        Route::delete('email-template/{id}', [EmailTemplateController::class, 'destroy'])->name('email_template.destroy');
        Route::get('email-template/log/{template}', [EmailTemplateController::class, 'getLogs'])->name('email_template.logs');
        Route::get('email-template-list', [EmailTemplateController::class, 'templateList'])->name('email_template.list');
        Route::get('email-template-variables', [EmailTemplateController::class, 'getVariables']);
    });

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
    Route::get('data-punch/{status}', [MediatorController::class, 'dataPunch']);

    // Core API
    Route::get('core', [CoreAPIController::class, 'index'])->name('core');
    Route::get('core_api_sync', [CoreAPIController::class, 'sync'])->name('core_api_sync');
    Route::post('importAPISData', [CoreAPIController::class, 'importAPISData'])->name('importAPISData');

    // Notification Management
    // Routes for sending notification emails
    Route::get('submission-reminder', [NotificationController::class, 'submissionReminder']);
    Route::get('get-submission-reminder', [NotificationController::class, 'getSubmissionReminderEmails']);
    Route::post('admin/notifications/non-submitted/dynamic', [NotificationController::class, 'sendDynamicNonSubmittedEmails']);

});
