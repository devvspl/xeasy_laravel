<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\HRMEmployees;
use App\Models\Permission;
use App\Models\Roles;
use App\Models\User;
use App\Models\UserLog;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * This controller handles user management in the admin area, like creating, updating, or deleting users.
 */
class UsersController extends Controller
{
    /**
     * Shows a page with a list of all users and their roles.
     *
     * Gets all users and active roles from the database and loads a page to show them.
     */
    public function index()
    {

        $usersQuery = User::query();
        $status = request('status', 'active');
        if ($status === 'active') {
            $usersQuery->where('status', 1);
        } elseif ($status === 'inactive') {
            $usersQuery->where('status', 0);
        }
        $users = $usersQuery->get();
        $roles = Roles::where('status', 1)->get();

        return view('admin.users', compact('users', 'roles', 'status'));
    }

    

    public function userActivity(Request $request)
    {
        return view('reports.user_activity');
    }

    public function userActivityData(Request $request)
    {
        $baseQuery = UserLog::query();

        
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $dateFrom = $request->date_from.' 00:00:00';
            $dateTo = $request->date_to.' 23:59:59';
            $baseQuery->whereBetween('user_logs.timestamp', [$dateFrom, $dateTo]);
        }

        
        if ($request->filled('user_ids')) {
            $userIds = is_array($request->user_ids) ? $request->user_ids : explode(',', $request->user_ids);
            $baseQuery->whereIn('user_logs.user_id', $userIds);
        }

        
        if ($request->filled('login_method') && $request->login_method !== 'all') {
            $baseQuery->where('user_logs.login_method', $request->login_method);
        }

        
        if ($request->filled('filter_type') && $request->filter_type === 'multiple_ip') {
            $multipleIPUsers = UserLog::select('user_id')
                ->groupBy('user_id')
                ->havingRaw('COUNT(DISTINCT ip_address) > 1')
                ->pluck('user_id')
                ->toArray();
            $baseQuery->whereIn('user_logs.user_id', $multipleIPUsers);
        }

        
        $activities = $baseQuery
            ->leftJoin('users', 'user_logs.user_id', '=', 'users.employee_id') 
            ->orderBy('user_logs.timestamp', 'desc')
            ->get([
                'user_logs.id',
                'user_logs.user_id',
                'user_logs.ip_address',
                'user_logs.is_success',
                'user_logs.user_agent',
                'user_logs.timestamp',
                'user_logs.status',
                'user_logs.login_method',
                'user_logs.created_at',
                'user_logs.updated_at',
                'users.name as user_name',
                'users.email as user_email',
                'users.employee_id as employee_id',
            ]);

        
        $totalLogins = (clone $baseQuery)->count();
        $uniqueUsers = (clone $baseQuery)->distinct('user_logs.user_id')->count('user_logs.user_id');
        $tokenLogins = (clone $baseQuery)->where('user_logs.login_method', 'token')->count();
        $uniqueIPs = (clone $baseQuery)->distinct('user_logs.ip_address')->count('user_logs.ip_address');

        $peakHourQuery = (clone $baseQuery)->selectRaw('HOUR(user_logs.timestamp) as hour, COUNT(*) as login_count')
            ->groupBy('hour')
            ->orderByDesc('login_count')
            ->first();
        $peakHour = $peakHourQuery ? $peakHourQuery->hour : 0;

        $multipleIPUsersCount = (clone $baseQuery)->select('user_logs.user_id')
            ->groupBy('user_logs.user_id')
            ->havingRaw('COUNT(DISTINCT user_logs.ip_address) > 1')
            ->count();

        $stats = [
            'total_logins' => $totalLogins,
            'unique_users' => $uniqueUsers,
            'token_logins' => $tokenLogins,
            'unique_ips' => $uniqueIPs,
            'peak_hour' => $peakHour,
            'multiple_ip_users' => $multipleIPUsersCount,
        ];

        return $this->jsonSuccess([
            'activities' => $activities,
            'stats' => $stats,
        ], 'User activity data and statistics fetched successfully.');
    }

    /**
     * Shows a form to create a new user.
     *
     * Not used right now.
     */
    public function create()
    {
        
    }

    /**
     * Saves a new user to the database.
     *
     * Checks the input, creates a user with name, email, and a secure password,
     * sets their status, and assigns roles if provided.
     */
    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => $request->is_active,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        if ($request->has('role_id') && ! empty($request->role_id)) {
            $roleIds = is_array($request->role_id) ? $request->role_id : [$request->role_id];
            $roleIds = array_map('intval', $roleIds);
            $user->assignRole($roleIds);
        }

        return $this->jsonSuccess($user, 'User created successfully.');
    }

    public function importEmployees(Request $request)
    {
        $employees = HRMEmployees::on('hrims')
            ->leftJoin('hrm_employee_general', 'hrm_employee.EmployeeID', '=', 'hrm_employee_general.EmployeeID')
            ->select(
                'hrm_employee.EmployeeID',
                'hrm_employee.Fname',
                'hrm_employee.Sname',
                'hrm_employee.Lname',
                'hrm_employee.EmpStatus',
                'hrm_employee.EmpPass',
                'hrm_employee_general.EmailId_Vnr as email'
            )
            ->get();

        $importedCount = 0;
        $defaultRoleId = 7;
        $authUserId = auth()->id();
        foreach ($employees as $employee) {
            if (empty($employee->email) || User::where('email', $employee->email)->exists()) {
                continue;
            }
            $user = User::create([
                'name' => trim($employee->Fname.' '.($employee->Sname ? $employee->Sname.' ' : '').$employee->Lname),
                'email' => $employee->email,
                'password' => $employee->EmpPass,
                'employee_id' => $employee->EmployeeID,
                'role_id' => $defaultRoleId,
                'status' => $employee->EmpStatus === 'A' ? 1 : 0,
                'created_by' => $authUserId,
                'updated_by' => $authUserId,
            ]);
            if ($defaultRoleId) {
                $user->assignRole($defaultRoleId);
            }
            $importedCount++;
        }

        return $this->jsonSuccess(['imported_count' => $importedCount], "$importedCount employees imported successfully.");
    }

    /**
     * Shows details of a specific user.
     *
     * Not used right now.
     */
    public function show(string $id)
    {
        
    }

    /**
     * Gets a user to edit their details.
     *
     * Finds a user by their ID, gets their current roles, and sends the data back
     * for an edit form.
     */
    public function edit(string $id)
    {
        $user = User::findOrFail($id);
        $hasRoles = $user->roles()->pluck('id');
        $userData = $user->toArray();
        $userData['role_ids'] = $hasRoles;

        return $this->jsonSuccess($userData, 'User fetched successfully.');
    }

    /**
     * Updates a user in the database.
     *
     * Finds a user by ID, updates their name and status, and syncs their roles
     * (adds or removes roles based on the input).
     */
    public function update(UpdateUserRequest $request, string $id)
    {
        $user = User::findOrFail($id);
        $user->update([
            'name' => $request->name,
            'status' => $request->is_active,
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ]);

        if ($request->has('role_id') && ! empty($request->role_id)) {
            $roleIds = is_array($request->role_id) ? $request->role_id : [$request->role_id];
            $roleIds = array_map('intval', $roleIds);
            $user->syncRoles($roleIds);
        } else {
            $user->syncRoles([]);
        }

        return $this->jsonSuccess($user, 'User updated successfully.');
    }

    /**
     * Deletes a user from the database.
     *
     * Finds a user by their ID and removes them.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return $this->jsonSuccess(null, 'User deleted successfully.');
    }

    /**
     * Shows the profile page for the logged-in user.
     *
     * Gets the details of the current user and loads their profile page.
     */
    public function profile()
    {
        $id = Auth::id();
        $user_detail = User::find($id);

        return view('admin.profile', compact('user_detail'));
    }

    public function getPermissionView($id)
    {
        $roles = Roles::where('status', 1)->get();
        $user = User::findOrFail($id);

        
        $userPermissions = $user->getDirectPermissions()
            ->load('group')
            ->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'group' => $permission->group ? $permission->group->name : 'Other',
                ];
            })
            ->groupBy('group')
            ->toArray();

        
        $allPermissions = Permission::with('group')
            ->get()
            ->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'group' => $permission->group ? $permission->group->name : 'Other',
                ];
            })
            ->groupBy('group')
            ->toArray();

        $hasRoles = $user->roles()->pluck('id')->toArray();

        return view('admin.set_permission', compact('roles', 'user', 'userPermissions', 'allPermissions', 'hasRoles'));
    }
}
