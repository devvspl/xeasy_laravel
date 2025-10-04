<?php

use App\Http\Controllers\Api\OdometerBackdateController;
use Illuminate\Support\Facades\Route;

Route::get('odo-backdate-submission/{yearId}/{employeeId}/', [OdometerBackdateController::class, 'odoBackdateSubmission']);
Route::get('odo-backdate-submission-show/{yearId}/{employeeId}/', [OdometerBackdateController::class, 'odoBackdateSubmissionBtnShow']);
Route::get('odo-backdate-approval/{yearId}/{employeeId}/{expenseId}/', [OdometerBackdateController::class, 'odoBackdateApprove']);
Route::get('odo-backdate-rejection/{yearId}/{employeeId}/{expenseId}/', [OdometerBackdateController::class, 'odoBackdateReject']);
Route::get('odo-backdate-bulk-approval/{yearId}/{employeeId}/{empiid}', [OdometerBackdateController::class, 'odoBackdateBulkApproval']);
Route::get('odo-backdate-bulk-rejection/{yearId}/{employeeId}/{empiid}', [OdometerBackdateController::class, 'odoBackdateBulkRejection']);
