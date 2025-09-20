@extends('layouts.app')
@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-height-100">
                        <div class="card-header align-items-center d-flex">
                            <h4 class="card-title mb-0 flex-grow-1">Generate Claim Report</h4>
                            <div class="flex-shrink-0 ms-2">
                                <button class="btn btn-soft-primary btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#exportModal">
                                    Get Report<i class="mdi mdi-chevron-down align-middle ms-1"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body pb-3 pt-0">
                            <div
                                class="row align-items-baseline bg-light-subtle border-top-dashed border border-start-0 border-end-0 border-bottom-dashed py-2 mb-3">
                                <div class="col-12 col-md-2 mb-2">
                                    <input type="text" placeholder="Select From Date" class="form-control flatpickr"
                                        id="fromDate">
                                </div>
                                <div class="col-12 col-md-2 mb-2">
                                    <input type="text" placeholder="Select To Date" class="form-control flatpickr"
                                        id="toDate">
                                </div>
                                <div class="col-12 col-md-6 d-flex flex-wrap align-items-baseline mb-2">
                                    <div class="form-check me-3">
                                        <input class="form-check-input" type="radio" name="dateType" id="billDate"
                                            value="billDate" checked>
                                        <label class="form-check-label" for="billDate">Bill Date</label>
                                    </div>
                                    <div class="form-check me-3">
                                        <input class="form-check-input" type="radio" name="dateType" id="uploadDate"
                                            value="uploadDate">
                                        <label class="form-check-label" for="uploadDate">Upload Date</label>
                                    </div>
                                    <div class="form-check me-3">
                                        <input class="form-check-input" type="radio" name="dateType" id="filledDate"
                                            value="filledDate">
                                        <label class="form-check-label" for="filledDate">Filled Date</label>
                                    </div>
                                    <div class="form-check me-3 d-flex align-items-center">
                                        <a class="text-primary position-relative d-flex align-items-center" type="button"
                                            data-bs-toggle="offcanvas" data-bs-target="#additionalFiltersCanvas"
                                            aria-controls="additionalFiltersCanvas">
                                            <i class="ri-equalizer-line fs-16 me-2"></i>
                                            <span class="align-middle">More Filters</span>
                                            <span id="activeFilterCount"
                                                class="badge bg-danger-subtle text-danger ms-2">0</span>
                                        </a>
                                    </div>
                                </div>
                                <div class="col-12 col-md-2 d-flex align-items-baseline">
                                    <button type="button"
                                        class="btn btn-primary btn-label waves-effect waves-light rounded-pill w-100"
                                        id="searchButton">
                                        <i class="ri-check-double-line label-icon align-middle rounded-pill fs-16 me-2">
                                            <span class="loader" style="display: none;"></span>
                                        </i>
                                        Search Data
                                    </button>
                                </div>
                            </div>
                            <div class="offcanvas offcanvas-end" tabindex="-1" id="additionalFiltersCanvas"
                                aria-labelledby="additionalFiltersCanvasLabel">
                                <div class="offcanvas-header header-ligth">
                                    <h5 class="offcanvas-title text-white" id="additionalFiltersCanvasLabel">Additional
                                        Filters</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                                        aria-label="Close"></button>
                                </div>
                                <div class="offcanvas-body">
                                    <div class="row p-1">
                                        @can('function_filter')
                                            <div class="col-md-6 mb-3">
                                                <select class="form-select" id="functionSelect" multiple>
                                                    @foreach ($functions as $function)
                                                        <option value="{{ $function->id }}">{{ $function->function_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endcan
                                        @can('vertical_filter')
                                            <div class="col-md-6 mb-3">
                                                <select class="form-select" id="verticalSelect" multiple>
                                                    @foreach ($verticals as $vertical)
                                                        <option value="{{ $vertical->id }}">{{ $vertical->vertical_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endcan
                                        @can('department_filter')
                                            <div class="col-md-6 mb-3">
                                                <select class="form-select" id="departmentSelect" multiple>
                                                    @foreach ($departments as $department)
                                                        <option value="{{ $department->id }}">{{ $department->department_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endcan
                                        @can('sub_department_filter')
                                            <div class="col-md-6 mb-3">
                                                <select class="form-select" id="subDepartmentSelect" multiple>
                                                    @foreach ($sub_departments as $sub_department)
                                                        <option value="{{ $sub_department->id }}">
                                                            {{ $sub_department->sub_department_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endcan
                                        @can('user_filter')
                                            <div class="col-md-6 mb-3">
                                                <select class="form-select" id="userSelect" multiple>
                                                    @foreach ($employees as $employee)
                                                        <option value="{{ $employee->EmpCode }}"
                                                            data-status="{{ $employee->EmpStatus }}"
                                                            class="{{ $employee->EmpStatus === 'D' ? 'deactivated' : '' }}">
                                                            {{ $employee->EmpCode }} - {{ $employee->Fname }}
                                                            {{ $employee->Sname ?? '' }} {{ $employee->Lname }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endcan
                                        @can('month_filter')
                                            <div class="col-md-6 mb-3">
                                                <select class="form-select" id="monthSelect" multiple>
                                                    <option value="1">January</option>
                                                    <option value="2">February</option>
                                                    <option value="3">March</option>
                                                    <option value="4">April</option>
                                                    <option value="5">May</option>
                                                    <option value="6">June</option>
                                                    <option value="7">July</option>
                                                    <option value="8">August</option>
                                                    <option value="9">September</option>
                                                    <option value="10">October</option>
                                                    <option value="11">November</option>
                                                    <option value="12">December</option>
                                                </select>
                                            </div>
                                        @endcan
                                        @can('claim_type_filter')
                                            @php
                                                $groupedClaimTypes = $claimTypes->groupBy('cgName');
                                               @endphp
                                            <div class="col-md-6 mb-3">
                                                <select class="form-select" id="claimTypeSelect" multiple>
                                                    @foreach ($groupedClaimTypes as $groupName => $claims)
                                                        <optgroup label="{{ $groupName ?? 'Ungrouped' }}">
                                                            @foreach ($claims as $claim)
                                                                <option value="{{ $claim->ClaimId }}">{{ $claim->ClaimName }}</option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endcan
                                        @can('claim_status_filter')
                                            <div class="col-md-6 mb-3">
                                                <select class="form-select" id="claimStatusSelect" multiple>
                                                    <option value="Deactivate">Deactivate</option>
                                                    <option value="Draft">Draft</option>
                                                    <option value="Submitted">Submitted</option>
                                                    <option value="Filled">Filled</option>
                                                    <option value="Verified">Verified</option>
                                                    <option value="Approved">Approved</option>
                                                    <option value="Financed">Financed</option>
                                                </select>
                                            </div>
                                        @endcan
                                        @can('policy_filter')
                                            <div class="col-md-6 mb-3">
                                                <select class="form-select" id="policySelect" multiple>
                                                    @foreach ($eligibility_policy as $policy)
                                                        <option value="{{ $policy->PolicyId }}">{{ $policy->PolicyName }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endcan
                                        @can('wheeler_type_filter')
                                            <div class="col-md-6 mb-3">
                                                <select class="form-select" id="wheelerTypeSelect" multiple>
                                                    <option value="2">2 Wheeler</option>
                                                    <option value="4">4 Wheeler</option>
                                                </select>
                                            </div>
                                        @endcan
                                        @can('vehicle_type_filter')
                                            <div class="col-md-6 mb-3">
                                                <select class="form-select" id="vehicleTypeSelect" multiple>
                                                    <option value="new">New</option>
                                                    <option value="old">Old</option>
                                                </select>
                                            </div>
                                        @endcan
                                        <div class="col-md-12 mb-3">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="claim_filter_type"
                                                    id="deactivateAfterFilling" value="deactivate_after_filling">
                                                <label class="form-check-label" for="deactivateAfterFilling">
                                                    Deactivate After Filling
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="claim_filter_type"
                                                    id="expense_sunday_holiday" value="expense_sunday_holiday">
                                                <label class="form-check-label" for="expense_sunday_holiday">
                                                    Expense (Sunday & Holiday)
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row p-1">
                                        <div class="col-md-12">
                                            <button type="button" class="btn btn-primary w-100" data-bs-dismiss="offcanvas">
                                                Apply Filters
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End Off-Canvas Panel -->
                            <table style="margin-top: 15px" id="claimReportTable"
                                class="table nowrap dt-responsive align-middle table-hover table-bordered"
                                style="width:100%">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sn</th>
                                        <th>Exp Id</th>
                                        <th>Claim Type</th>
                                        <th>Emp Name</th>
                                        <th>Month</th>
                                        <th>Upload Date</th>
                                        <th>Bill Date</th>
                                        <th>Claimed Amt</th>
                                        <th>Claim Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <x-modal.claim_report />
@endsection
@push('styles')
    <link rel="stylesheet" href="assets/libs/@simonwep/pickr/themes/classic.min.css" />
    <link rel="stylesheet" href="assets/libs/@simonwep/pickr/themes/monolith.min.css" />
    <link rel="stylesheet" href="assets/libs/@simonwep/pickr/themes/nano.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('custom/js/pages/claim_report.js') }}"></script>
@endpush