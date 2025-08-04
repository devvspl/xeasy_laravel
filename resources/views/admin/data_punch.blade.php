@extends('layouts.app')
@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-height-100">
                        <div class="card-header align-items-center d-flex" id="dataPunchSection">
                            <h4 class="card-title mb-0 flex-grow-1">Punched Data</h4>
                            <div class="status-buttons">
                                <a href="{{ route('data-punch', ['status' => 'hold']) }}"
                                    class="status-btn hold {{ $status == 'hold' ? 'active' : '' }}">
                                    <span>Hold</span><span>({{ $holdCount }})</span>
                                </a>
                                <a href="{{ route('data-punch', ['status' => 'draft']) }}"
                                    class="status-btn draft {{ $status == 'draft' ? 'active' : '' }}">
                                    <span>Draft</span><span>({{ $draftCount }})</span>
                                </a>
                                <a href="{{ route('data-punch', ['status' => 'filled']) }}"
                                    class="status-btn filled {{ $status == 'filled' ? 'active' : '' }}">
                                    <span>Filled</span><span>({{ $filledCount }})</span>
                                </a>
                                <a href="{{ route('data-punch', ['status' => 'uploaded']) }}"
                                    class="status-btn uploaded {{ $status == 'uploaded' ? 'active' : '' }}">
                                    <span>Uploaded</span><span>({{ $uploadedCount }})</span>
                                </a>
                                <a href="{{ route('data-punch', ['status' => 'denied']) }}"
                                    class="status-btn denied {{ $status == 'denied' ? 'active' : '' }}">
                                    <span>Denied</span><span>({{ $deniedCount }})</span>
                                </a>
                            </div>

                            <div class="dropdowns">
                                <select class="select2" id="selDate" name="date" style="width: 130px">
                                    <option value="">Select Date</option>
                                    @foreach ($availableDates as $date)
                                        <option value="{{ $date }}" {{ $selectedDate == $date ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::parse($date)->format('d-m-Y') }}
                                        </option>
                                    @endforeach
                                </select>

                                <select class="select2" id="selEmployee" name="emp" style="width: 250px">
                                    <option value="">Select Employee</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->EmployeeID }}" {{ $selectedEmp == $employee->EmployeeID ? 'selected' : '' }}>
                                            {{ $employee->EmpCode }} - {{ $employee->EmpName }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="card-body pb-3 pt-2">
                            <table style="margin-top: 15px" id="claimReportTable"
                                class="table nowrap dt-responsive align-middle table-hover table-bordered"
                                style="width:100%">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="width:5%; text-align:center;">Sn</th>
                                        <th scope="col" style="width:5%; text-align:center;">Exp Id</th>
                                        <th scope="col" style="width:18%; text-align:center;">Emp Name</th>
                                        <th scope="col" style="width:7%; text-align:center;">Date</th>
                                        <th scope="col" style="width:30%; text-align:center;">Date Entry Remark</th>
                                        <th scope="col" style="width:10%; text-align:center;">Action</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $i = 1;
                                    @endphp
                                    @foreach ($punchData as $exp)
                                        <tr>
                                            <th scope="row" style="text-align:center;">{{ $i }}</th>
                                            <td scope="row" style="text-align:center;">{{ $exp->ExpId }}</td>
                                            <td>
                                                @php
                                                    $emp = $employees->firstWhere('EmployeeID', $exp->CrBy);
                                                    $empName = $emp ? $emp->EmpName : 'Unknown';
                                                @endphp
                                                {{ $empName }}
                                            </td>
                                            <td style="text-align:center;">{{ $exp->CrDateFormatted }}</td>
                                            <td><textarea rows="1" name="" readonly class="form-control form-control-sm"
                                                    id="">{{ $exp->DateEntryRemark }}</textarea></td>
                                            <td>
                                                <button type="button" class="btn btn-primary btn-sm"><i
                                                        class="ri-edit-2-fill"></i></button>
                                                <button type="button" class="btn btn-info btn-sm"><i
                                                        class="ri-link"></i></button>

                                            </td>
                                        </tr>
                                        @php
                                            $i++;
                                        @endphp
                                    @endforeach
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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $('.select2').select2();
        $("#claimReportTable").DataTable({
            ordering: false,
            searching: true,
            paging: true,
            info: true,
            lengthChange: true,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
        });
        $('#selDate, #selEmployee').on('change', function () {
            const date = $('#selDate').val();
            const emp = $('#selEmployee').val();
            const status = '{{ $status }}';
            let query = `?status=${status}`;
            if (date) query += `&date=${date}`;
            if (emp) query += `&emp=${emp}`;
            window.location.href = `{{ route('data-punch') }}` + query;
        });
    </script>
@endpush