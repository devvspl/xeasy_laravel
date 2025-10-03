<?php

namespace App\Http\Controllers;

use App\Models\FinancialYear;
use App\Models\HRMEmployees;
use App\Models\User;
use App\Models\UserLog;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected $strcode;

    public function __construct()
    {
        $this->strcode = ['', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'A', 'b', 'B', 'c', 'C', 'd', 'D', 'e', 'E', 'f', 'F', 'g', 'G', 'h', 'H', 'i', 'I', 'j', 'J', 'k', 'K', 'l', 'L', 'm', 'M', 'n', 'N', 'o', 'O', 'p', 'P', 'q', 'Q', 'r', 'R', 's', 'S', 't', 'T', 'u', 'U', 'v', 'V', 'w', 'W', 'x', 'X', 'y', 'Y', 'z', 'Z', '#', '@', '$', '%', '^', '&', '*', '_', '!', '?', ' '];
    }

    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $token = $request->input('token');
        $secretKey = 'v7n90l9uvy';
        $login_method = $token ? 'token' : 'normal';
        $status_messages = ['Login attempt initiated'];
        $log_data = [
            'user_id' => 0,
            'ip_address' => $request->ip(),
            'is_success' => 0,
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toDateTimeString(),
            'status' => [], // Will be updated with JSON-encoded status_messages
            'login_method' => $login_method,
        ];

        // If GET request and no token, show login view
        if ($request->isMethod('get') && ! $token) {
            $financial_years = FinancialYear::orderBy('YearId', 'desc')->first();
            $status_messages[] = 'Login page accessed';
            UserLog::create(array_merge($log_data, ['status' => json_encode($status_messages)]));

            return view('login', compact('financial_years'));
        }

        // Validate input (company and financial_year not required for token-based login)
        try {
            $credentials = $request->validate([
                'employeeid' => $token ? 'nullable|string' : 'required|string',
                'password' => $token ? 'nullable|string' : 'required|string',
                'company' => $token ? 'nullable|in:1' : 'required|in:1',
                'financial_year' => $token ? 'nullable' : 'required',
            ]);
            $status_messages[] = 'Input validation completed';
        } catch (ValidationException $e) {
            // Format validation errors for logging
            $errorMessages = collect($e->errors())->flatten()->toArray();
            $errorCount = count($errorMessages);
            $reason = 'Input validation failed: '.$errorMessages[0].($errorCount > 1 ? ' (and '.($errorCount - 1).' more errors)' : '');
            $status_messages[] = $reason;
            UserLog::create(array_merge($log_data, ['status' => json_encode($status_messages)]));

            return back()->withErrors($e->errors())->withInput($request->except('password'));
        }

        // Check if financial year is valid
        $fy_check = FinancialYear::orderBy('YearId', 'desc')->first();
        if (! $fy_check) {
            $status_messages[] = 'Invalid or inactive financial year';
            UserLog::create(array_merge($log_data, ['status' => json_encode($status_messages)]));

            return back()->with('error', 'Invalid or inactive financial year.')
                ->withInput($request->except('password'));
        }
        $status_messages[] = 'Financial year validated';

        $user = null;
        $token_valid = false;
        $decoded = null;

        // Handle token-based authentication
        if ($token) {
            try {
                $status_messages[] = 'Attempting token-based authentication';
                $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
                if (! isset($decoded->sub) || empty($decoded->sub)) {
                    $status_messages[] = 'Token username does not match provided identity';
                    UserLog::create(array_merge($log_data, ['status' => json_encode($status_messages)]));
                    throw new Exception('Token username does not match provided identity.');
                }
                if ($request->employeeid && $decoded->sub !== $request->employeeid) {
                    $status_messages[] = 'Token username mismatch with provided employee ID';
                    UserLog::create(array_merge($log_data, ['status' => json_encode($status_messages)]));
                    throw new Exception('Token username does not match provided identity.');
                }

                $user = User::where('employee_id', $decoded->sub)
                    ->where('status', 1)
                    ->first();

                if (! $user) {
                    $status_messages[] = 'User not found or inactive';
                    UserLog::create(array_merge($log_data, ['status' => json_encode($status_messages)]));
                    throw new Exception('User not found or inactive.');
                }

                // Check for existing session with different user
                if (Session::has('authenticated') && Session::get('employee_id') != $user->employee_id) {
                    $status_messages[] = 'Session terminated for new user login';
                    $log_data['user_id'] = Session::get('employee_id') ?: 0;
                    UserLog::create(array_merge($log_data, ['status' => json_encode($status_messages)]));
                    Session::flush();

                    return back()->with('error', 'Previous user session terminated. Logging in as new user.')
                        ->withInput($request->except('password'));
                }

                $token_valid = true;
                $status_messages[] = 'Token-based authentication successful, using static company=1, financial_year=7';
            } catch (Exception $e) {
                $status_messages[] = 'Token authentication failed: '.$e->getMessage();
                UserLog::create(array_merge($log_data, ['status' => json_encode($status_messages)]));

                return back()->with('error', 'Invalid or expired token: '.$e->getMessage())
                    ->withInput($request->except('password'));
            }
        }

        // Handle normal authentication or token with password
        if (! $token_valid || ($token_valid && $request->password)) {
            if (! $request->employeeid) {
                $status_messages[] = 'Employee ID required for normal authentication';
                UserLog::create(array_merge($log_data, ['status' => json_encode($status_messages)]));

                return back()->withErrors([
                    'employeeid' => 'The employeeid field is required for normal authentication.',
                ])->withInput($request->except('password'));
            }

            $status_messages[] = 'Attempting normal authentication';
            $employee = HRMEmployees::where('EmpCode_New', $request->employeeid)->first();

            if (! $employee || $this->decrypt($employee->EmpPass) !== $request->password) {
                $status_messages[] = 'Invalid credentials';
                UserLog::create(array_merge($log_data, ['status' => json_encode($status_messages)]));

                return back()->withErrors([
                    'employeeid' => 'The provided credentials do not match our records.',
                ])->withInput($request->except('password'));
            }

            $user = User::where([['employee_id', $employee->EmployeeID], ['status', 1]])->first();
            $status_messages[] = $user ? 'Normal authentication successful' : 'User not found after employee check';
        }

        // If no user found, authentication failed
        if (! $user) {
            $status_messages[] = 'Authentication failed';
            UserLog::create(array_merge($log_data, ['status' => json_encode($status_messages)]));

            return back()->with('error', 'Authentication failed.')
                ->withInput($request->except('password'));
        }

        // Set session data
        $log_data['user_id'] = $user->employee_id;
        Session::put('back_office', false);
        Session::put('back_office_active', false);
        Session::put('role_id', $user->role_id ?? 7);
        Session::put('employee_id', $user->employee_id);
        Session::put('company_id', $token_valid ? 1 : $request->company);
        Session::put('year_id', $token_valid ? 7 : $request->financial_year);
        Session::put('emp_code', $request->employeeid ?: ($decoded ? $decoded->sub : ''));
        Session::put('authenticated', true);
        $status_messages[] = 'Session data set'.($token_valid ? ' with static company=1, financial_year=7' : '');

        // Login user
        Auth::login($user, $request->has('remember'));
        $status_messages[] = 'User logged in via Laravel Auth';

        // Log successful login
        $log_data['is_success'] = 1;
        $status_messages[] = 'Login successful';
        UserLog::create(array_merge($log_data, ['status' => json_encode($status_messages)]));

        return redirect()->route('dashboard')->with('success', 'Logged in successfully');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'Logged out successfully');
    }

    public function decrypt($encryptedText)
    {
        $chunks = str_split($encryptedText, 3);
        $output = '';
        foreach ($chunks as $chunk) {
            $output .= $this->derandomized($chunk, $this->strcode);
        }

        return $output;
    }

    public function strsplt($text, $size = 1)
    {
        $chunks = [];
        $length = strlen($text);
        for ($i = 0; $i < $length; $i += $size) {
            $chunks[] = substr($text, $i, $size);
        }

        return $chunks;
    }

    public function derandomized($chunk, $strcode)
    {
        $arr = $this->strsplt($chunk, strlen($chunk) - 1);
        $output = '';
        for ($x = 0; $x < strlen($chunk) - 1; $x++) {
            $s = $this->key_locator(substr($arr[0], $x, 1), $strcode);
            $t = $this->key_locator($arr[1], $strcode);
            $newcode = $s - $t;
            if ($newcode < 0) {
                $newcode += count($strcode) - 1;
            }
            if ($newcode == 0 && $s != 0) {
                $newcode = count($strcode) - 1;
            }
            $output .= $strcode[$newcode];
        }

        return $output;
    }

    public function convert_keyto_value($thetext)
    {
        $output = '';
        $a = $this->add_random_key($thetext);
        while (in_array(count($this->strcode) - 1, $a)) {
            $a = $this->add_random_key($thetext);
        }
        for ($i = 0; $i < strlen($thetext) + 1; $i++) {
            $output .= $this->strcode[$a[$i]];
        }

        return $output;
    }

    public function add_random_key($thetext)
    {
        $newcode = [];
        $rnd = rand(1, count($this->strcode) - 2);
        for ($i = 0; $i < strlen($thetext); $i++) {
            $x = $this->key_locator(substr($thetext, $i, 1), $this->strcode);
            $temp = $x + $rnd;
            if ($temp > count($this->strcode) - 1) {
                $temp -= count($this->strcode) - 1;
            }
            $newcode[] = $temp;
        }
        $newcode[] = $rnd;

        return $newcode;
    }

    public function encrypt($thetext)
    {
        $output = '';
        $nstr = $this->strsplt($thetext, 2);
        for ($i = 0; $i < count($nstr); $i++) {
            $output .= $this->convert_keyto_value($nstr[$i]);
        }

        return $output;
    }

    public function key_locator($code, $strcode)
    {
        return array_search($code, $strcode) ?: 0;
    }
}
