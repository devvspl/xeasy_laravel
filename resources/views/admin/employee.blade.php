@extends('layouts.app')

@section('content')
<div class="page-content">
   <div class="container-fluid">
      <div class="row">
         <div class="col-lg-12">
            <div class="card">
               <div class="card-header align-items-center d-flex">
                  <h4 class="card-title mb-0 flex-grow-1">Employee Status</h4>
                  <div class="flex-shrink-0">
                     <button class="btn btn-primary btn-sm">Export Report</button>
                  </div>
               </div>
               <div class="card-body">
                  <div class="table-responsive">
                     <table style="margin-top: 15px" id="claimReportTable"
                        class="table nowrap dt-responsive align-middle table-hover table-bordered"
                        style="width:100%">
                        <thead class="table-light">
                           <tr>
                              <th style="vertical-align: middle;text-align: center;font-size: 12px;" rowspan="2">Claim Month</th>
                              <th style="vertical-align: middle;text-align: center;font-size: 12px;" rowspan="2">Total Claims</th>
                              <th style="vertical-align: middle;text-align: center;font-size: 12px;" rowspan="2">Filling Status</th>
                              <th style="vertical-align: middle;text-align: center;font-size: 12px;" colspan="4">Pending For</th>
                              <th style="vertical-align: middle;text-align: center;font-size: 12px;" rowspan="2">View Home</th>
                              <th style="vertical-align: middle;text-align: center;font-size: 12px;" rowspan="2">Return Claim</th>
                              <th style="vertical-align: middle;text-align: center;font-size: 12px;" rowspan="2">View Details</th>
                              <th style="vertical-align: middle;text-align: center;font-size: 12px;" rowspan="2">Allow Submit</th>
                              <th style="vertical-align: middle;text-align: center;font-size: 12px;" rowspan="2">Month Status</th>
                           </tr>
                           <tr>
                              <th style="vertical-align: middle;text-align: center;font-size: 12px;">Claimant Approval</th>
                              <th style="vertical-align: middle;text-align: center;font-size: 12px;">Verify Approval</th>
                              <th style="vertical-align: middle;text-align: center;font-size: 12px;">Reporting Approval</th>
                              <th style="vertical-align: middle;text-align: center;font-size: 12px;">Finance Approval</th>
                           </tr>
                        </thead>
                        <tbody>
                           @php
                           $claims = [
                               [
                                   'month' => 'Draft',
                                   'total' => 0,
                                   'filling' => '',
                                   'claimant' => 0,
                                   'verify' => 0,
                                   'reporting' => 0,
                                   'finance' => 0,
                                   'view' => false,
                                   'return' => false,
                                   'submit' => false,
                                   'status' => 'Open'
                               ],
                               [
                                   'month' => 'January',
                                   'total' => 12,
                                   'filling' => '',
                                   'claimant' => 3,
                                   'verify' => 2,
                                   'reporting' => 1,
                                   'finance' => 0,
                                   'view' => true,
                                   'return' => true,
                                   'submit' => false,
                                   'status' => 'Open'
                               ],
                               [
                                   'month' => 'February',
                                   'total' => 15,
                                   'filling' => '',
                                   'claimant' => 0,
                                   'verify' => 4,
                                   'reporting' => 2,
                                   'finance' => 1,
                                   'view' => true,
                                   'return' => false,
                                   'submit' => true,
                                   'status' => 'Closed'
                               ],
                               [
                                   'month' => 'March',
                                   'total' => 18,
                                   'filling' => '',
                                   'claimant' => 2,
                                   'verify' => 0,
                                   'reporting' => 0,
                                   'finance' => 0,
                                   'view' => true,
                                   'return' => true,
                                   'submit' => false,
                                   'status' => 'Open'
                               ],
                               [
                                   'month' => 'April',
                                   'total' => 22,
                                   'filling' => '',
                                   'claimant' => 1,
                                   'verify' => 0,
                                   'reporting' => 0,
                                   'finance' => 3,
                                   'view' => true,
                                   'return' => true,
                                   'submit' => true,
                                   'status' => 'Closed'
                               ],
                               [
                                   'month' => 'May',
                                   'total' => 20,
                                   'filling' => '',
                                   'claimant' => 0,
                                   'verify' => 0,
                                   'reporting' => 2,
                                   'finance' => 0,
                                   'view' => true,
                                   'return' => false,
                                   'submit' => false,
                                   'status' => 'Open'
                               ],
                               [
                                   'month' => 'June',
                                   'total' => 16,
                                   'filling' => '',
                                   'claimant' => 0,
                                   'verify' => 0,
                                   'reporting' => 0,
                                   'finance' => 0,
                                   'view' => true,
                                   'return' => true,
                                   'submit' => false,
                                   'status' => 'Open'
                               ],
                               [
                                   'month' => 'July',
                                   'total' => 14,
                                   'filling' => '',
                                   'claimant' => 2,
                                   'verify' => 3,
                                   'reporting' => 0,
                                   'finance' => 0,
                                   'view' => true,
                                   'return' => true,
                                   'submit' => false,
                                   'status' => 'Open'
                               ],
                               [
                                   'month' => 'August',
                                   'total' => 19,
                                   'filling' => '',
                                   'claimant' => 0,
                                   'verify' => 2,
                                   'reporting' => 1,
                                   'finance' => 2,
                                   'view' => true,
                                   'return' => false,
                                   'submit' => true,
                                   'status' => 'Closed'
                               ],
                               [
                                   'month' => 'September',
                                   'total' => 25,
                                   'filling' => '',
                                   'claimant' => 4,
                                   'verify' => 3,
                                   'reporting' => 0,
                                   'finance' => 0,
                                   'view' => true,
                                   'return' => true,
                                   'submit' => false,
                                   'status' => 'Open'
                               ],
                               [
                                   'month' => 'October',
                                   'total' => 13,
                                   'filling' => '',
                                   'claimant' => 0,
                                   'verify' => 0,
                                   'reporting' => 2,
                                   'finance' => 0,
                                   'view' => true,
                                   'return' => false,
                                   'submit' => true,
                                   'status' => 'Closed'
                               ],
                               [
                                   'month' => 'November',
                                   'total' => 10,
                                   'filling' => '',
                                   'claimant' => 1,
                                   'verify' => 1,
                                   'reporting' => 0,
                                   'finance' => 0,
                                   'view' => true,
                                   'return' => true,
                                   'submit' => false,
                                   'status' => 'Open'
                               ],
                               [
                                   'month' => 'December',
                                   'total' => 8,
                                   'filling' => '',
                                   'claimant' => 0,
                                   'verify' => 0,
                                   'reporting' => 0,
                                   'finance' => 0,
                                   'view' => true,
                                   'return' => true,
                                   'submit' => false,
                                   'status' => 'Open'
                               ],
                           ];
                           $totalClaims = array_sum(array_column($claims, 'total'));
                           $totalClaimant = array_sum(array_column($claims, 'claimant'));
                           $totalVerify = array_sum(array_column($claims, 'verify'));
                           $totalReporting = array_sum(array_column($claims, 'reporting'));
                           $totalFinance = array_sum(array_column($claims, 'finance'));
                           @endphp
                           @foreach ($claims as $claim)
                           <tr>
                              <td>{{ $claim['month'] }}</td>
                              <td>{{ $claim['total'] }}</td>
                              <td>{{ $claim['filling'] }}</td>
                              <td>
                                 {!! $claim['claimant'] ? "<a href='#'><b>{$claim['claimant']}</b></a>" : '-' !!}
                              </td>
                              <td>
                                 {!! $claim['verify'] ? "<a href='#'><b>{$claim['verify']}</b></a>" : '-' !!}
                              </td>
                              <td>
                                 {!! $claim['reporting'] ? "<a href='#'><b>{$claim['reporting']}</b></a>" : '-' !!}
                              </td>
                              <td>
                                 {!! $claim['finance'] ? "<a href='#'><b>{$claim['finance']}</b></a>" : '-' !!}
                              </td>
                              <td>
                                 @if($claim['view'])
                                 <a href="javascript:void(0)" class="badge bg-primary-subtle text-primary">
                                    <i class="ri-home-4-line me-1"></i> E-Home
                                 </a>
                                 @else
                                 -
                                 @endif
                              </td>
                              <td>
                                 @if($claim['return'])
                                 <a href="javascript:void(0)" class="badge bg-danger-subtle text-danger">
                                    <i class="ri-arrow-go-back-line me-1"></i> Return
                                 </a>
                                 @else
                                 -
                                 @endif
                              </td>
                              <td>
                                 <a href="javascript:void(0)" class="badge bg-info-subtle text-info">
                                    <i class="ri-eye-line me-1"></i> View
                                 </a>
                              </td>
                              <td class="text-center">
                                 <div class="form-check text-center">
                                    <input class="form-check-input" type="checkbox" 
                                           {{ $claim['submit'] ? 'checked' : '' }} disabled>
                                 </div>
                              </td>
                              <td>
                                 @if($claim['status'] === 'Open')
                                 <span style="width: 50px" class="badge bg-secondary">Open</span>
                                 @elseif($claim['status'] === 'Closed')
                                 <span style="width: 50px" class="badge bg-success">Closed</span>
                                 @else
                                 <span style="width: 50px" class="badge bg-dark">{{ $claim['status'] }}</span>
                                 @endif
                              </td>
                           </tr>
                           @endforeach
                        </tbody>
                        <tfoot>
                           <tr>
                              <th>Total</th>
                              <th>{{ $totalClaims }}</th>
                              <th>-</th>
                              <th>{{ $totalClaimant }}</th>
                              <th>{{ $totalVerify }}</th>
                              <th>{{ $totalReporting }}</th>
                              <th>{{ $totalFinance }}</th>
                              <th>-</th>
                              <th>-</th>
                              <th>-</th>
                              <th>-</th>
                              <th>-</th>
                           </tr>
                        </tfoot>
                     </table>
                  </div>
                 <div class="mt-1">
                     <h5 class="fw-bold">Team Members:</h5>
                     <div class="d-flex flex-wrap align-items-center gap-3">
                        @php
                        $team = [
                        ['id' => '1008', 'name' => 'Shankar Honyal', 'role' => 'Claims Analyst', 'department' => 'Claims Processing', 'avatar' => 'https://vnrseeds.co.in/file-view/Employee_Image/1/1008.jpg'],
                        ['id' => '1507', 'name' => 'Sunil Kumar Bhatt', 'role' => 'Senior Verifier', 'department' => 'Verification', 'avatar' => 'https://vnrseeds.co.in/file-view/Employee_Image/1/1507.jpg'],
                        ['id' => '163', 'name' => 'Ashok Kumar Patel', 'role' => 'Finance Manager', 'department' => 'Finance', 'avatar' => 'https://vnrseeds.co.in/file-view/Employee_Image/1/163.jpg'],
                        ['id' => '241', 'name' => 'Akhilesh Kumar Singh', 'role' => 'Reporting Lead', 'department' => 'Reporting', 'avatar' => 'https://vnrseeds.co.in/file-view/Employee_Image/1/241.jpg'],
                        ['id' => '376', 'name' => 'Akhil Anand', 'role' => 'Claims Coordinator', 'department' => 'Claims Processing', 'avatar' => 'https://vnrseeds.co.in/file-view/Employee_Image/1/376.jpg'],
                        ['id' => '431', 'name' => 'Murali Eruku', 'role' => 'Data Analyst', 'department' => 'Analytics', 'avatar' => 'https://vnrseeds.co.in/file-view/Employee_Image/1/431.jpg'],
                        ['id' => '464', 'name' => 'Rajendra Singh Rana', 'role' => 'Compliance Officer', 'department' => 'Compliance', 'avatar' => 'https://vnrseeds.co.in/file-view/Employee_Image/1/464.jpg'],
                        ['id' => '676', 'name' => 'Omkar Mahilang', 'role' => 'Finance Analyst', 'department' => 'Finance', 'avatar' => 'https://vnrseeds.co.in/file-view/Employee_Image/1/676.jpg'],
                        ['id' => '853', 'name' => 'G Swapnil', 'role' => 'Claims Supervisor', 'department' => 'Claims Processing', 'avatar' => 'https://vnrseeds.co.in/file-view/Employee_Image/1/853.jpg']
                        ];
                        @endphp
                        @foreach ($team as $index => $member)
                        <div class="d-flex align-items-center"">
                           <img style="margin-right: 5px" src="{{ $member['avatar'] }}" alt="{{ $member['name'] }}"
                              class="avatar-xs rounded-circle">
                           <div>
                              <a href="#"><b>{{ $member['id'] }}-{{ $member['name'] }}</b></a>
                           </div>
                           @if($index < count($team) - 1)<span>,</span>@endif
                        </div>
                        @endforeach
                     </div>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>
@endsection

@push('scripts')

@endpush