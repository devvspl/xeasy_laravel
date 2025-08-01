@extends('layouts.app') @section('content')
    <div class="page-content">
        <div class="container-fluid">
            <div class="row mb-1 pb-1">
                <div class="col-12">
                    <div class="d-flex align-items-lg-center flex-lg-row flex-column">
                        <div class="flex-grow-1">
                            <h4 class="fs-16 mb-1">Yearly Expense Comparison</h4>
                            <p class="text-muted mb-0"><strong>CY Expense:</strong> <span id="cyExpense"
                                    class="text-primary">0</span> <strong>PY Expense:</strong> <span id="pyExpense"
                                    class="text-warning">0</span> <strong>Variation :</strong> <span id="variancePercent"
                                    class="text-success">0%</span></p>
                        </div>
                        <div class="mt-3 mt-lg-0">
                            <div class="row g-1 mb-0 align-items-center" id="quarter-buttons">
                                <div class="col-sm-auto" id="date-picker-wrapper">
                                    <div class="input-group">
                                        <input type="text" id="dateRange"
                                            class="form-control border-0 minimal-border dash-filter-picker shadow"
                                            placeholder="Select date range" data-provider="flatpickr" data-range-date="true"
                                            data-date-format="d M, Y" />
                                        <div class="input-group-text bg-primary border-primary text-white">
                                            <i class="ri-calendar-2-line"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row" id="card-container"></div>
            <div class="row">
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header align-items-center d-flex">
                            <h4 class="card-title mb-0 flex-grow-1">Expense Overview Month Wise</h4>
                            <div class="flex-shrink-0">
                                <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#expenseModal"
                                    class="link-primary">Source Data<i class="ri-arrow-right-line"></i></a>
                            </div>
                        </div>
                        <div class="card-body p-0 pb-2">
                            <div>
                                <div style="height: 400px;" id="expense-monthly-chart"
                                    data-colors='["--vz-primary", "--vz-warning", "--vz-success"]'
                                    data-colors-minimal='["--vz-primary", "--vz-primary-rgb, 0.1", "--vz-primary-rgb, 0.50"]'
                                    data-colors-interactive='["--vz-primary", "--vz-info", "--vz-warning"]'
                                    data-colors-creative='["--vz-secondary", "--vz-warning", "--vz-success"]'
                                    data-colors-corporate='["--vz-primary", "--vz-secondary", "--vz-danger"]'
                                    data-colors-galaxy='["--vz-primary", "--vz-primary-rgb, 0.1", "--vz-primary-rgb, 0.50"]'
                                    data-colors-classic='["--vz-primary", "--vz-secondary", "--vz-warning"]' dir="ltr"
                                    class="apex-charts"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header align-items-center d-flex">
                            <h4 class="card-title mb-0 flex-grow-1">Department Wise Expense Comparison</h4>
                            <div class="flex-shrink-0">
                                <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#departmentModal"
                                    class="link-primary">Source Data<i class="ri-arrow-right-line"></i></a>
                            </div>
                        </div>
                        <div class="card-body p-0 pb-2">
                            <div style="height: 400px;" id="multi_chart"
                                data-colors='["--vz-primary", "--vz-info", "--vz-success"]' dir="ltr" class="apex-charts">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header align-items-center d-flex">
                            <h4 class="card-title mb-0 flex-grow-1">Claim Type Financials</h4>
                            <div class="flex-shrink-0">
                                <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#claimTypeModal"
                                    class="link-primary">Source Data<i class="ri-arrow-right-line"></i></a>
                            </div>
                        </div>
                        <div class="card-body p-0 pb-2">
                            <div id="claim-type-chart" data-colors='["--vz-success", "--vz-danger"]'
                                data-colors-minimal='["--vz-primary", "--vz-info"]'
                                data-colors-interactive='["--vz-info", "--vz-primary"]'
                                data-colors-galaxy='["--vz-primary", "--vz-secondary"]'
                                data-colors-classic='["--vz-primary", "--vz-secondary"]' class="apex-charts" dir="ltr">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="expenseModal" tabindex="-1" aria-labelledby="expenseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="progress-container" style="display: none;">
                    <div class="progress" style="height: 5px;">
                        <div class="indeterminate" style="background-color: var(--vz-primary);"></div>
                    </div>
                </div>
                <div class="modal-header">
                    <h5 class="modal-title" id="expenseModalLabel">Expense Month Wise Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Month</th>
                                <th>Filled Total</th>
                                <th>Verified Total</th>
                                <th>Approved Total</th>
                                <th>Financed Total</th>
                            </tr>
                        </thead>
                        <tbody id="modal-table-body"></tbody>
                        <tfoot class="table-light" id="modal-table-footer"></tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="departmentModal" tabindex="-1" aria-labelledby="departmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="departmentModalLabel">Expense Department Wise Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th style="text-align: left;">Department</th>
                                <th>Current Year Total (₹)</th>
                                <th>Previous Year Total (₹)</th>
                                <th>Variation (%)</th>
                            </tr>
                        </thead>
                        <tbody id="department-table-body"></tbody>
                        <tfoot class="table-light" id="department-table-footer"></tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="claimTypeModal" tabindex="-1" aria-labelledby="claimTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="claimTypeModalLabel">Expense Claim Type Wise Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th style="text-align: left;">Claim Type</th>
                                <th>Filled</th>
                                <th>Verified</th>
                                <th>Approved</th>
                                <th>Financed</th>
                            </tr>
                        </thead>
                        <tbody id="claim-type-table-body"></tbody>
                        <tfoot id="claim-type-table-footer"></tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection @push('scripts')
    <script src="assets/libs/apexcharts/apexcharts.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <script src="{{ asset('custom/js/pages/home.js') }}"></script>
@endpush