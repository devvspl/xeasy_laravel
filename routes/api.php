<?php

use App\Http\Controllers\Api\OdometerBackdateController;
use Illuminate\Support\Facades\Route;

Route::get('odo-backdate-submission/{yearId}/{employeeId}/', [OdometerBackdateController::class, 'odoBackdateSubmission']);
Route::post('odo-backdate-approval/{yearId}/{expenseId}/{employeeId}', [OdometerBackdateController::class, 'odoBackdateApproval']);
Route::post('odo-backdate-rejection/{yearId}/{expenseId}/{employeeId}', [OdometerBackdateController::class, 'odoBackdateRejection']);
Route::post('odo-backdate-bulk-approval/{yearId}/{employeeId}', [OdometerBackdateController::class, 'odoBackdateBulkApproval']);
Route::post('odo-backdate-bulk-rejection/{yearId}/{employeeId}', [OdometerBackdateController::class, 'odoBackdateBulkRejection']);
