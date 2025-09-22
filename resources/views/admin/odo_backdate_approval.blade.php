@extends('layouts.app')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header align-items-center d-flex">
                        <h4 class="card-title mb-0 flex-grow-1">Odometer Backdate Approval Settings</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table style="margin-top: 15px" id="odoSettingsTable"
                                class="table nowrap dt-responsive align-middle table-hover table-bordered"
                                style="width:100%">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sr No.</th>
                                        <th style="text-align: left;">Department</th>
                                        <th>Enable Check</th>
                                        <th>Approval Required</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($departments as $department)
                                    @php
                                        $setting = $settings[$department->id] ?? null;
                                    @endphp
                                    <tr data-department-id="{{ $department->id }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td style="text-align: left;">{{ $department->department_name }}</td>
                                        <td>
                                            <div class="form-check form-switch text-center">
                                                <input class="form-check-input is-active-switch" type="checkbox"
                                                    data-department-id="{{ $department->id }}"
                                                    {{ $setting && $setting->is_active ? 'checked' : '' }}>
                                            </div>
                                        </td>
                                        <td>
                                            <select class="form-select approval-select" data-department-id="{{ $department->id }}">
                                                <option value="">Select Approval Type</option>
                                                @if(strtolower($department->department_name) === 'sales')
                                                <option value="bu_level"
                                                    {{ $setting && $setting->approval_type === 'bu_level' ? 'selected' : '' }}>
                                                    BU Level
                                                </option>
                                                @endif
                                                <option value="hod_approval"
                                                    {{ $setting && $setting->approval_type === 'hod_approval' ? 'selected' : '' }}>
                                                    HOD Approval
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            
                                            <button class="btn btn-primary btn-sm save-btn" data-department-id="{{ $department->id }}"><i class="ri-save-3-line"></i></button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmSaveModal" tabindex="-1" aria-labelledby="confirmSaveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmSaveModalLabel">Confirm Save</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to save the changes for this department?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="confirmSaveBtn">Save</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Track original values to revert if needed
    $('.is-active-switch, .approval-select').each(function() {
        $(this).data('original-value', $(this).is(':checkbox') ? $(this).is(':checked') : $(this).val());
    });

    // Save button click handler
    $('.save-btn').on('click', function() {
        var departmentId = $(this).data('department-id');
        var $row = $('tr[data-department-id="' + departmentId + '"]');
        var $checkbox = $row.find('.is-active-switch');
        var $select = $row.find('.approval-select');
        var isActive = $checkbox.is(':checked') ? 1 : 0;
        var approvalType = $select.val();

        // Validation: If checkbox is checked, approval type must be selected
        if (isActive && !approvalType) {
            showAlert('danger', 'ri-error-warning-line', 'Approval type is required when Enable Check is selected.');
            return;
        }

        // Show confirmation modal
        $('#confirmSaveModal').modal('show');
        $('#confirmSaveBtn').off('click').on('click', function() {
            saveSetting(departmentId, isActive, approvalType, $row, $checkbox, $select);
            $('#confirmSaveModal').modal('hide');
        });
    });

    // Cancel button in modal reverts changes
    $('#confirmSaveModal').on('hidden.bs.modal', function() {
        var departmentId = $('#confirmSaveBtn').data('department-id');
        if (departmentId) {
            var $row = $('tr[data-department-id="' + departmentId + '"]');
            var $checkbox = $row.find('.is-active-switch');
            var $select = $row.find('.approval-select');
            $checkbox.prop('checked', $checkbox.data('original-value'));
            $select.val($select.data('original-value'));
        }
    });

    function saveSetting(departmentId, isActive, approvalType, $row, $checkbox, $select) {
        var data = { _token: '{{ csrf_token() }}' };
        if (isActive !== null) {
            data.is_active = isActive;
        }
        if (approvalType !== null) {
            data.approval_type = approvalType;
        }

        $.ajax({
            url: '/odo-backdate-setting/' + departmentId,
            type: 'POST',
            data: data,
            success: function(response) {
                showAlert('success', 'ri-checkbox-circle-line', response.message || 'Setting updated successfully!');
                // Update original values after successful save
                $checkbox.data('original-value', isActive);
                $select.data('original-value', approvalType);
            },
            error: function(xhr) {
                var errorMsg = xhr.responseJSON.message || 'An error occurred';
                showAlert('danger', 'ri-error-warning-line', errorMsg);
                // Revert values on error
                $checkbox.prop('checked', $checkbox.data('original-value'));
                $select.val($select.data('original-value'));
            }
        });
    }
});
</script>
@endpush