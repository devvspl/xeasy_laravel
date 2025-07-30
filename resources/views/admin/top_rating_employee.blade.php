@extends('layouts.app')

@section('content')
<div class="page-content">
    <div class="container-fluid">

        @section('titleMaybe', 'Top Rating Employees with Same Day Uploads')
                    <x-theme.breadcrumb title="Top Rating Employees" :breadcrumbs="[
                    ['label' => 'Reports', 'url' => '#'],
                    ['label' => 'Top Rating Employees']
                ]" />

                    <div class="row">
                        <div class="col-xl-12">
                            <div class="card card-height-100">
                                <div class="card-header align-items-center d-flex">
                                    <h4 class="card-title mb-0 flex-grow-1">Top Rating Employees (Same Day Uploads)</h4>
                                </div>
                                <div class="card-body pb-3 pt-0">
                                    <form method="GET" class="row g-3 mb-3 mt-2" action="{{ url()->current() }}">
                                        <div class="col-md-3">
                                            <label for="fromDate" class="form-label">From</label>
                                            <input type="date" class="form-control" id="fromDate" name="fromDate"
                                                value="{{ old('fromDate', $fromDate ?? date('Y-m-d')) }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="toDate" class="form-label">To</label>
                                            <input type="date" class="form-control" id="toDate" name="toDate"
                                                value="{{ old('toDate', $toDate ?? date('Y-m-d')) }}">
                                        </div>
                                        <div class="col-md-3 align-self-end">
                                            <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                                            <a href="{{ url()->current() }}" class="btn btn-sm btn-secondary ms-2">Reset</a>
                                        </div>
                                    </form>

                                    <table id="claimReportTable"
                                        class="table nowrap dt-responsive align-middle table-hover table-bordered"
                                        style="width:100%">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Sn</th>
                                                <th>Emp Code</th>
                                                <th>Employee Name</th>
                                                <th>Grade</th>
                                                <th>Vertical</th>
                                                <th>Department</th>
                                                <th>Total Claims Uploaded</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($data as $index => $item)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $item->EmpCode }}</td>
                                                    <td>{{ $item->EmployeeName }}</td>
                                                    <td>{{ $item->grade_name }}</td>
                                                    <td>{{ $item->vertical_name }}</td>
                                                    <td>{{ $item->department_name }}</td>
                                                    <td>{{ $item->TotalClaimsUploaded }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center">No records found for selected date range.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>

                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
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
    <script>
        $(document).ready(function () {
            flatpickr("#fromDate", {
                dateFormat: "Y-m-d",
                disable: [],
                enableYearSelection: true,
                onReady: function () {
                    this.isDisabled = false;
                },
            });

            flatpickr("#toDate", {
                dateFormat: "Y-m-d",
                disable: [],
                enableYearSelection: true,
                onReady: function () {
                    this.isDisabled = false;
                },
            });
            if ($.fn.DataTable.isDataTable("#claimReportTable")) {
                table.destroy();
                $("#claimReportTable").empty();
            }

            table = $("#claimReportTable").DataTable();
        })
    </script>
@endpush