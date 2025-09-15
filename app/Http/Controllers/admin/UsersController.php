<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Http\Request;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Models\Roles;
use App\Models\Permission;
use App\Models\HRMEmployees;
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



    /**
     * Shows a form to create a new user.
     *
     * Not used right now.
     */
    public function create()
    {
        //
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


        if ($request->has('role_id') && !empty($request->role_id)) {
            $roleIds = is_array($request->role_id) ? $request->role_id : [$request->role_id];
            $roleIds = array_map('intval', $roleIds);
            $user->assignRole($roleIds);
        }

        return $this->jsonSuccess($user, 'User created successfully.');
    }

    public function importEmployees(Request $request)
    {
        $this->authorize('Create User');
        try {

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
                    'name' => trim($employee->Fname . ' ' . ($employee->Sname ? $employee->Sname . ' ' : '') . $employee->Lname),
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
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), 500);
        }
    }

    /**
     * Shows details of a specific user.
     *
     * Not used right now.
     */
    public function show(string $id)
    {
        //
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


        if ($request->has('role_id') && !empty($request->role_id)) {
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
    $roles = Roles::where('status', 1)->get(); // Fetch active roles
    $user = User::findOrFail($id);

    // User's direct permissions, grouped
    $userPermissions = $user->getDirectPermissions()
        ->load('group') // Assuming a 'group' relationship in Permission model
        ->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'group' => $permission->group ? $permission->group->name : 'Other',
            ];
        })
        ->groupBy('group')
        ->toArray();

    // Permissions assigned to each role, grouped by role and group
    $rolePermissions = [];
    foreach ($roles as $role) {
        $permissions = Permission::whereIn('id', function ($query) use ($role) {
            $query->select('permission_id')
                  ->from('role_has_permissions')
                  ->where('role_id', $role->id);
        })
        ->with('group')
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

        $rolePermissions[$role->name] = $permissions;
    }

    $hasRoles = $user->roles()->pluck('id')->toArray();

    return view('admin.set-permission', compact('roles', 'user', 'userPermissions', 'rolePermissions', 'hasRoles'));
}
}