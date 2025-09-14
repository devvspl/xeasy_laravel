<?php

namespace App\Http\Controllers;

use App\Models\HRMEmployees;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;

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
        $credentials = $request->validate([
            'employeeid' => 'required|string',
            'password' => 'required|string',
            'company' => 'required|in:1',
            'financial_year' => 'required|in:7',
        ]);

        $employee = HRMEmployees::where('EmpCode_New', $request->employeeid)->first();

        if ($employee && $this->decrypt($employee->EmpPass) === $request->password) {
            $user = User::where('employee_id', $employee->EmployeeID)->first();

            if ($user) {
                Auth::login($user, $request->has('remember'));
                Session::put('company', $request->company);
                Session::put('financial_year', $request->financial_year);
                Session::put('employee_id', $user->employee_id);
                Session::put('role_id', $user->role_id);

                return redirect()->route('dashboard')->with('success', 'Logged in successfully');
            }
        }

        return back()->withErrors([
            'employeeid' => 'The provided credentials do not match our records.',
        ])->withInput($request->except('password'));
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