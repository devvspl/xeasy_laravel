<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Dcblogdev\LaravelSentEmails\Models\Sentemail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Part\TextPart;

class NotificationController extends Controller
{
    public function submissionReminder()
    {
        return view('admin.submission_reminder');
    }

    public function getSubmissionReminderEmails()
    {
        try {
            $notifications = DB::table('eml_notification')
                ->where('notification_type', 'expense_submission')
                ->where('is_active', 1)
                ->get();

            if ($notifications->isEmpty()) {
                return $this->jsonSuccess([], 'No active notifications found.');
            }

            $allEmployees = collect();
            $isMailActive = filter_var(env('MAIL_ACTIVE', false), FILTER_VALIDATE_BOOLEAN);

            $today = Carbon::now('Asia/Kolkata');
            $todayDate = (int) $today->day;
            $currentMonth = (int) $today->month;
            $previousMonth = $today->copy()->subMonth()->month;

            foreach ($notifications as $notification) {
                $dateCondition = (int) $todayDate === (int) $notification->send_day;

                if (! $dateCondition) {
                    continue;
                }

                $template = DB::table('eml_email_templates')
                    ->where('id', $notification->email_template_id)
                    ->first();

                $employees = DB::table('y7_monthexpensefinal as m')
                    ->join('users as u', 'u.employee_id', '=', 'm.EmployeeId')
                    ->select('u.id', 'u.name', 'u.email', 'm.EmployeeId', 'm.Month', 'm.Status')
                    ->where('m.Status', 'Open')
                    // ->where('m.EmployeeId', '1729')
                    ->where('m.Month', $previousMonth)
                    ->where(function ($q) {
                        $q->whereNull('m.DateOfSubmit')->orWhere('m.DateOfSubmit', '0000-00-00');
                    })
                    ->groupBy('u.id', 'u.name', 'u.email', 'm.EmployeeId', 'm.Month', 'm.Status')
                    ->get();

                foreach ($employees as $emp) {
                    $month_name = Carbon::createFromFormat('m', $emp->Month)->format('F');

                    $previousMonthRecord = DB::table('y7_monthexpensefinal')
                        ->where('EmployeeId', $emp->EmployeeId)
                        ->where('Month', $previousMonth)
                        ->where('Status', 'Open')
                        ->where(function ($q) {
                            $q->whereNull('DateOfSubmit')->orWhere('DateOfSubmit', '0000-00-00');
                        })
                        ->first();

                    if (! $previousMonthRecord) {
                        continue;
                    }

                    $alreadySent = DB::table('eml_stage_email_logs')
                        ->where('employee_id', $emp->EmployeeId)
                        ->where('notification_type', 'expense_submission')
                        ->where('stage', $notification->stage)
                        ->where('sent_for_month', now()->format('Y-m'))
                        ->exists();

                    $debugger = [
                        'today_date' => $todayDate,
                        'send_day' => $notification->send_day,
                        'employee_id' => $emp->EmployeeId,
                        'month_in_record' => $emp->Month,
                        'previous_month' => $previousMonth,
                        'date_condition' => $dateCondition,
                        'previous_month_record_found' => $previousMonthRecord,
                    ];

                    $conditions = [
                        'today_date' => $todayDate,
                        'send_day' => $notification->send_day,
                        'mail_active' => $isMailActive ? 'true' : 'false',
                        'date_condition' => $dateCondition ? 'true' : 'false',
                        'previous_month_not_submitted' => $previousMonthRecord ? 'true' : 'false',
                        'already_sent' => $alreadySent ? '<span class="badge bg-teal-subtle text-teal badge-border">Yes</span>' : '<span class="badge bg-dark-subtle text-dark badge-border">No</span>',
                    ];

                    $allEmployees->push([
                        'employee_id' => $emp->EmployeeId,
                        'employee_name' => $emp->name,
                        'email' => $emp->email,
                        'month' => $month_name,
                        'status' => $emp->Status,
                        'stage' => $notification->stage,
                        'email_template_subject' => $template->subject ?? '(No template)',
                        'conditions' => $conditions,
                        'debugger' => $debugger,
                    ]);
                }
            }

            if ($allEmployees->isEmpty()) {
                return $this->jsonSuccess([], 'No employees found for reminder.');
            }

            return $this->jsonSuccess($allEmployees, 'Employees with conditions fetched successfully.');
        } catch (\Exception $e) {
            return $this->jsonError('Error fetching employees: '.$e->getMessage(), 500);
        }
    }

    public function sendDynamicNonSubmittedEmails()
    {
        try {
            $notifications = DB::table('eml_notification')
                ->where('notification_type', 'expense_submission')
                ->where('is_active', 1)
                ->get();

            if ($notifications->isEmpty()) {
                return $this->jsonSuccess([], 'No active notifications found.');
            }

            $isMailActive = filter_var(env('MAIL_ACTIVE', false), FILTER_VALIDATE_BOOLEAN);
            $today = Carbon::now('Asia/Kolkata');
            $todayDate = (int) $today->day;
            $previousMonth = $today->copy()->subMonth()->month;

            foreach ($notifications as $notification) {
                if ((int) $todayDate !== (int) $notification->send_day) {
                    continue;
                }

                $template = DB::table('eml_email_templates')
                    ->where('id', $notification->email_template_id)
                    ->first();
                if (! $template) {
                    continue;
                }

                $employees = DB::table('y7_monthexpensefinal as m')
                    ->join('users as u', 'u.employee_id', '=', 'm.EmployeeId')
                    ->select('u.id', 'u.name', 'u.email', 'm.EmployeeId', 'm.Month', 'm.Status', 'm.DateOfSubmit')
                    ->where('m.Status', 'Open')
                    // ->where('m.EmployeeId', '1729')
                    ->where('m.Month', $previousMonth)
                    ->where(function ($q) {
                        $q->whereNull('m.DateOfSubmit')->orWhere('m.DateOfSubmit', '0000-00-00');
                    })
                    ->distinct()
                    ->get();

                foreach ($employees as $employee) {
                    $previousMonthRecord = DB::table('y7_monthexpensefinal')
                        ->where('EmployeeId', $employee->EmployeeId)
                        ->where('Month', $previousMonth)
                        ->where('Status', 'Open')
                        ->where(function ($q) {
                            $q->whereNull('DateOfSubmit')->orWhere('DateOfSubmit', '0000-00-00');
                        })
                        ->first();
                    if (! $previousMonthRecord) {
                        continue;
                    }

                    $alreadySent = DB::table('eml_stage_email_logs')
                        ->where('employee_id', $employee->EmployeeId)
                        ->where('notification_type', 'expense_submission')
                        ->where('stage', $notification->stage)
                        ->where('sent_for_month', now()->format('Y-m'))
                        ->exists();

                    if ($alreadySent) {
                        continue;
                    }

                    if (empty($employee->email)) {
                        continue;
                    }

                    $month_name = Carbon::createFromFormat('m', $employee->Month)->format('F');
                    $body = str_replace('{{month_name}}', $month_name, $template->body_html);

                    if ($isMailActive) {
                        Mail::send([], [], function ($message) use ($employee, $template, $body) {
                            $message
                                ->to($employee->email, $employee->name)
                                ->subject($template->subject)
                                ->setBody(new TextPart($body, 'utf-8', 'html'));
                        });
                    }

                    DB::table('eml_stage_email_logs')->insert([
                        'employee_id' => $employee->EmployeeId,
                        'notification_type' => 'expense_submission',
                        'stage' => $notification->stage,
                        'sent_for_month' => now()->format('Y-m'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Sentemail::create([
                        'to' => $employee->email,
                        'subject' => $template->subject,
                        'body' => $body,
                    ]);
                }
            }

            return $this->jsonSuccess([], 'Emails processed successfully.');
        } catch (\Exception $e) {
            return $this->jsonError('Error sending emails: '.$e->getMessage(), 500);
        }
    }
}
