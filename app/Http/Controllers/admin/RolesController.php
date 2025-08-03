<?php
namespace App\Http\Controllers\admin;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Roles;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
class RolesController extends Controller
{
    public function index()
    {
        $roles = Roles::all();
        return view('admin.roles', compact('roles'));
    }
    public function create()
    {
    }
    public function store(StoreRoleRequest $request)
    {
        $roles = Roles::create(['name' => $request->role_name, 'guard_name' => 'web', 'status' => $request->is_active, 'created_by' => Auth::id(), 'created_at' => now(),]);
        return $this->jsonSuccess($roles, 'Role created successfully.');
    }
    public function show(string $id)
    {
    }
    public function edit(string $id)
    {
        $role = Roles::findOrFail($id);
        return $this->jsonSuccess($role, 'Role retrieved successfully.');
    }
    public function update(UpdateRoleRequest $request, string $id)
    {
        $role = Roles::findOrFail($id);
        $role->update(['name' => $request->role_name, 'guard_name' => 'web', 'status' => $request->is_active, 'updated_by' => Auth::id(), 'updated_at' => now(),]);
        return $this->jsonSuccess($role, 'Role updated successfully.');
    }
    public function destroy(string $id)
    {
        $role = Roles::findOrFail($id);
        $role->delete();
        return $this->jsonSuccess($role, 'Role deleted successfully.');
    }
    public function getRoles()
    {
        $roles = Roles::where('status', 1)->get();
        return $this->jsonSuccess($roles, 'Roles retrieved successfully.');
    }
}
