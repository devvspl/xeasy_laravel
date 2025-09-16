@extends('layouts.app')
@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header align-items-center d-flex">
                            <h4 class="card-title mb-0 flex-grow-1">Email Template List</h4>
                            <div class="flex-shrink-0">
                                @can('Create Email Template')
                                    <button type="button"
                                        class="btn btn-primary btn-label waves-effect waves-light rounded-pill"
                                        data-bs-toggle="modal" data-bs-target="#templateModal" id="templateBtn">
                                        <i class="ri-add-circle-fill label-icon align-middle rounded-pill fs-16 me-2"></i> Add New
                                    </button>
                                @endcan
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="templateMasterTable"
                                class="table nowrap dt-responsive align-middle table-hover table-bordered"
                                style="width: 100%;">
                                <thead class="table-light">
                                    <tr>
                                        <th>S No.</th>
                                        <th>Name</th>
                                        <th>Subject</th>
                                        <th>Category</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @can('Email Template List')
                                        @foreach ($templates as $key => $template)
                                            <tr>
                                                <td>{{ $key + 1 }}</td>
                                                <td>{{ $template->name }}</td>
                                                <td>{{ $template->subject }}</td>
                                                <td>{{ $template->category ?? 'N/A' }}</td>
                                                <td>
                                                    @can('Edit Email Template')
                                                        <button type="button" data-bs-toggle="modal" data-bs-target="#templateModal"
                                                            class="btn btn-primary btn-sm edit-template"
                                                            data-id="{{ $template->id }}"><i class="ri-edit-2-fill"></i></button>
                                                    @endcan
                                                    @can('Delete Email Template')
                                                        <button type="button" class="btn btn-danger btn-sm delete-template"
                                                            data-id="{{ $template->id }}"><i class="ri-delete-bin-5-fill"></i></button>
                                                    @endcan
                                                    @can('View Email Template Logs')
                                                        <button type="button" class="btn btn-info btn-sm view-logs"
                                                            data-id="{{ $template->id }}" data-bs-toggle="modal"
                                                            data-bs-target="#logModal"><i class="ri-file-list-3-fill"></i></button>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="5" class="text-center">
                                                <span class="text-danger">You do not have permission to view the email template list.</span>
                                            </td>
                                        </tr>
                                    @endcan
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Template Modal -->
    <div class="modal fade" id="templateModal" tabindex="-1" aria-labelledby="templateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="progress-container" style="display: none">
                    <div class="progres" style="height: 5px;">
                        <div class="indeterminate" style="background-color: var(--vz-primary);"></div>
                    </div>
                </div>
                <div class="modal-header">
                    <h5 class="modal-title" id="templateModalLabel">Add New Email Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="templateForm">
                        <div class="mb-3">
                            <label for="name" class="form-label">Template Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-control" id="category" name="category">
                                <option value="">Select a category</option>
                                <option value="Support">Support / Queries</option>
                                <option value="Notifications">Notifications / Alerts</option>
                                <option value="Policy">Policy / Updates</option>
                            </select>
                        </div>
                       <div class="mb-3">
                            <label for="body_html" class="form-label">Body</label>
                            <textarea class="form-control" id="body_html" name="body_html" rows="6" required></textarea>
                        </div>
                        <div class="mb-3">
                           <div class="form-check form-switch">
                                <input class="form-check-input" name="is_active" type="checkbox" checked
                                    permission="switch" id="is_active" onchange="toggleSwitchText()" />
                                <label class="form-check-label" for="is_active" id="is_active_label">Active</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Template Variables</label>
                            <div id="variableContainer">
                                <div class="variable-row mb-2">
                                    <div class="row">
                                        <div class="col-md-6">
                                            {{-- <input type="text" class="form-control" name="variables[0][variable_name]" placeholder="Variable Name (e.g., {{user_name}})" required> --}}
                                        </div>
                                        <div class="col-md-5">
                                            {{-- <input type="text" class="form-control" name="variables[0][description]" placeholder="Description" required> --}}
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-danger btn-sm remove-variable"><i class="ri-delete-bin-5-fill"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" id="addVariableBtn">Add Variable</button>
                        </div>
                        <button type="submit" class="btn btn-primary" id="saveTemplateBtn">Save Template</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Log Modal -->
    <div class="modal fade" id="logModal" tabindex="-1" aria-labelledby="logModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="progress-container" style="display: none">
                    <div class="progres" style="height: 5px;">
                        <div class="indeterminate" style="background-color: var(--vz-primary);"></div>
                    </div>
                </div>
                <div class="modal-header">
                    <h5 class="modal-title" id="logModalLabel">Activity Logs for Email Template - <span id="logTemplateTitle"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered table-striped" id="logTable">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Description</th>
                                <th>Changes</th>
                                <th>User</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
<script src="{{ asset('custom/js/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ asset('custom/js/pages/email_templates.js') }}"></script>
@endpush