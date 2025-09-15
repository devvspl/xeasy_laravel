@extends('layouts.app')
@section('content')
<div class="page-content">
   <div class="container-fluid">
      <div class="row">
         <div class="col-lg-12">
            <div class="card">
               <div class="card-header align-items-center d-flex">
                  <h4 class="card-title mb-0 flex-grow-1">Set Permissions for {{ $user->name }}</h4>
                  <div class="flex-shrink-0">
                     <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#rolesModal">Manage Roles</button>
                  </div>
               </div>
               <div class="card-body">
                  <div class="mb-4">
                     <h5 class="mb-3">Assign Roles</h5>
                     <div class="row">
                        @foreach ($roles as $role)
                        <div class="col-md-4">
                           <div class="form-check mb-2">
                              <input class="form-check-input role-checkbox" type="checkbox" 
                              id="role-{{ $role->id }}" 
                              value="{{ $role->id }}"
                              {{ in_array($role->id, $hasRoles) ? 'checked' : '' }}
                              data-user-id="{{ $user->id }}">
                              <label class="form-check-label" for="role-{{ $role->id }}">{{ $role->name }}</label>
                           </div>
                        </div>
                        @endforeach
                     </div>
                  </div>
                  <h5 class="mb-3">Permissions by Role</h5>
                  @foreach ($rolePermissions as $roleName => $groups)
                  <div class="mb-4">
                     <h6 class="mb-2 text-primary">{{ $roleName }}</h6>
                     @if (empty($groups))
                     <p class="text-muted">No permissions assigned to this role.</p>
                     @else
                     @foreach ($groups as $groupName => $permissions)
                     <div class="ms-3 mb-3">
                        <h6 class="mb-2">{{ $groupName }}</h6>
                        <div class="row ms-3">
                           @foreach ($permissions as $permission)
                           <div class="col-md-4">
                              <div class="form-check mb-2 d-flex align-items-center">
                                 <input class="form-check-input permission-checkbox me-2" 
                                 type="checkbox" 
                                 id="permission-{{ $permission['id'] }}-{{ $roleName }}" 
                                 value="{{ $permission['id'] }}"
                                 {{ isset($userPermissions[$groupName]) && in_array($permission['id'], array_column($userPermissions[$groupName], 'id')) ? 'checked' : '' }}
                                 data-user-id="{{ $user->id }}">
                                 <label class="form-check-label" for="permission-{{ $permission['id'] }}-{{ $roleName }}">{{ $permission['name'] }}</label>
                              </div>
                           </div>
                           @endforeach
                        </div>
                     </div>
                     @endforeach
                     @endif
                  </div>
                  @endforeach
               </div>
            </div>
         </div>
      </div>
   </div>
</div>
<x-modal.roles />
@endsection
@push('scripts')
<script></script>
@endpush