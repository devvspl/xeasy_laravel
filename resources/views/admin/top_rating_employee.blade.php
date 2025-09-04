@extends('layouts.app')
@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-height-100">
                        <div class="card-header align-items-center d-flex gap-2">
                            <h4 class="card-title mb-0 flex-grow-1">Top Rating Employees (Same Day Uploads)</h4>
                            <input type="text" class="form-control" data-provider="flatpickr" data-date-format="Y-m-d"
                                data-range-date="true" id="dateRange" style="width: 200px;" placeholder="Select date range"
                                value="{{ old('fromDate', $fromDate ?? date('Y-m-d')) }} to {{ old('toDate', $toDate ?? date('Y-m-d')) }}">
                        </div>
                        <div class="card-body pb-3 pt-0">
                            <form method="GET" class="row g-1 mb-1 mt-1" action="{{ url()->current() }}">
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
            flatpickr("#dateRange", {
                dateFormat: "Y-m-d",
                mode: "range",
                onChange: function (selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        var dates = selectedDates.map(date =>
                            instance.formatDate(date, "Y-m-d")
                        );
                        $('#dateRange').val(dates.join(' to '));
                        var form = $('form');
                        form.find('input[name="fromDate"], input[name="toDate"]').remove();
                        form.append($('<input>', {
                            type: 'hidden',
                            name: 'fromDate',
                            value: dates[0]
                        }));
                        form.append($('<input>', {
                            type: 'hidden',
                            name: 'toDate',
                            value: dates[1]
                        }));
                        form.submit();
                    }
                }
            });
            let table;
            if ($.fn.DataTable.isDataTable("#claimReportTable")) {
                table = $("#claimReportTable").DataTable();
                table.destroy();
                $("#claimReportTable").empty();
            }
            table = $("#claimReportTable").DataTable({
                responsive: true,
                ordering: true,
                searching: true,
                paging: true
            });
        });
    </script>
@endpush