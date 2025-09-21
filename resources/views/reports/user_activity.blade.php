@extends('layouts.app')
@section('content')
<div class="page-content">
   <div class="container-fluid">
      <div class="card mb-3 shadow-sm sticky-card">
         <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-1">
               <div>
                  <h5 class="fw-bold">
                     User Login Activity Analytics
                  </h5>
                  <small class="text-muted mb-0">Monitor user authentication patterns and security insights for
                  better access management</small>
               </div>
               <div class="d-flex gap-1 align-items-center">
                  <input type="text" class="form-control form-control-sm" data-provider="flatpickr"
                     data-date-format="Y-m-d" data-range-date="true" id="dateRange" style="width: 200px;"
                     placeholder="Select date range" value="">
                  <select id="filterType" class="form-select form-select-sm w-auto">
                     <option value="all">All Logins</option>
                     <option value="token">Token Authentication</option>
                     <option value="normal">Normal Authentication</option>
                     <option value="multiple_ip">Multiple IP Users</option>
                  </select>
                  <select id="sortBy" class="form-select form-select-sm w-auto">
                     <option value="timestamp">Sort by Time</option>
                     <option value="user_id">Sort by User</option>
                     <option value="login_method">Sort by Method</option>
                  </select>
               </div>
            </div>
            <div class="row text-center">
               <div class="col border-end">
                  <i class="bi bi-person-check text-primary fs-2 mb-2"></i>
                  <p class="text-muted mb-0">Total Logins</p>
                  <h5 id="totalLogins" class="text-primary fw-bold mb-0">0</h5>
               </div>
               <div class="col border-end">
                  <i class="bi bi-people text-secondary fs-2 mb-2"></i>
                  <p class="text-muted mb-0">Unique Users</p>
                  <h5 id="uniqueUsers" class="text-secondary fw-bold mb-0">0</h5>
               </div>
               <div class="col border-end">
                  <i class="bi bi-shield-check text-info fs-2 mb-2"></i>
                  <p class="text-muted mb-0">Token Logins</p>
                  <h5 id="tokenLogins" class="text-info fw-bold mb-0">0</h5>
               </div>
               <div class="col border-end">
                  <i class="bi bi-geo-alt text-success fs-2 mb-2"></i>
                  <p class="text-muted mb-0">Unique IPs</p>
                  <h5 id="uniqueIPs" class="text-success fw-bold mb-0">0</h5>
               </div>
               <div class="col border-end">
                  <i class="bi bi-clock text-warning fs-2 mb-2"></i>
                  <p class="text-muted mb-0">Peak Hour</p>
                  <h5 id="peakHour" class="text-warning fw-bold mb-0">--</h5>
               </div>
               <div class="col">
                  <i class="bi bi-exclamation-triangle text-danger fs-2 mb-2"></i>
                  <p class="text-muted mb-0">Multi-IP Users</p>
                  <h5 id="multiIPUsers" class="text-danger fw-bold mb-0">0</h5>
               </div>
            </div>
         </div>
      </div>
      <div class="row">
         <div class="col-lg-6">
            <div class="card mb-3 shadow-sm">
               <div class="card-body" style="height: 400px;">
                  <h5 class="mb-3">Login Activity by Hour</h5>
                  <canvas id="hourlyChart"></canvas>
               </div>
            </div>
         </div>
         <div class="col-lg-6">
            <div class="card mb-3 shadow-sm">
               <div class="card-body" style="height: 400px;">
                  <h5 class="mb-3">Authentication Method Distribution</h5>
                  <div style="max-width:400px; margin:auto;">
                     <canvas id="methodChart"></canvas>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <div class="row">
         <div class="col-lg-6">
            <div class="card mb-3 shadow-sm">
               <div class="card-body" style="height: 400px;">
                  <h5 class="mb-3">User Activity Comparison</h5>
                  <canvas id="userChart"></canvas>
               </div>
            </div>
         </div>
         <div class="col-lg-6">
            <div class="card mb-3 shadow-sm">
               <div class="card-body" style="height: 400px;">
                  <h5 class="mb-3">Daily Login Trends</h5>
                  <canvas id="dailyChart"></canvas>
               </div>
            </div>
         </div>
      </div>
      <div class="card shadow-sm mb-3">
         <div class="card-body">
            <h5 class="mb-3">Detailed Login Analysis</h5>
            <div class="table-responsive">
               <table class="table table-bordered" id="loginTable">
                  <thead class="table-light">
                     <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Employee ID</th>
                        <th>IP Address</th>
                        <th>Login Method</th>
                        <th>Timestamp</th>
                        <th>User Agent</th>
                        <th class="text-center">Status</th>
                     </tr>
                  </thead>
                  <tbody></tbody>
               </table>
            </div>
         </div>
      </div>
   </div>
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script src="{{ asset('custom/js/pages/user_activity.js') }}"></script>
@endpush
@endsection