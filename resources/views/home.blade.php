@extends('layouts.app') @section('content')
<div class="page-content">
   <div class="container-fluid">
      <div class="row">
         @php $defaultCardData = [ 'Total Expense' => 0, 'Deactivate' => 0, 'Draft' => 0, 'Submitted' => 0, 'Filled' => 0, 'Verified' => 0, 'Approved' => 0, 'Financed' => 0, ]; $cardData = array_merge($defaultCardData, $cardData ?? []);
         $cards = [ ['label' => 'Total Expense', 'icon' => 'ri-wallet-3-fill', 'color' => 'text-primary', 'value' => $cardData['Total Expense']], ['label' => 'Deactivate', 'icon' => 'ri-archive-line', 'color' => 'text-secondary', 'value'
         => $cardData['Deactivate']], ['label' => 'Draft', 'icon' => 'ri-time-line', 'color' => 'text-warning', 'value' => $cardData['Draft']], ['label' => 'Submitted', 'icon' => 'ri-send-plane-fill', 'color' => 'text-dark', 'value' =>
         $cardData['Submitted']], ['label' => 'Filled', 'icon' => 'ri-file-list-3-line', 'color' => 'text-info', 'value' => $cardData['Filled']], ['label' => 'Verified', 'icon' => 'ri-eye-fill', 'color' => 'text-success', 'value' =>
         $cardData['Verified']], ['label' => 'Approved', 'icon' => 'ri-check-double-fill', 'color' => 'text-success', 'value' => $cardData['Approved']], ['label' => 'Financed', 'icon' => 'ri-hand-coin-fill', 'color' => 'text-primary',
         'value' => $cardData['Financed']], ]; @endphp @foreach ($cards as $card)
         <div class="col-lg-3 col-md-6">
            <div class="card mb-3">
               <div class="card-body">
                  <div class="d-flex align-items-center">
                     <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-light {{ $card['color'] }} rounded-circle fs-3 material-shadow">
                        <i class="{{ $card['icon'] }} align-middle"></i>
                        </span>
                     </div>
                     <div class="flex-grow-1 ms-3">
                        <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">{{ $card['label'] }}</p>
                        <h4 class="mb-0"><span class="counter-value" data-target="{{ $card['value'] }}">{{ $card['value'] }}</span></h4>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         @endforeach
      </div>
      <div class="row">
         <div class="col-xl-6">
            <div class="card">
               <div class="card-header border-0 align-items-center d-flex">
                  <h4 class="card-title mb-0 flex-grow-1">Expense Overview Month Wise</h4>
                  <div class="flex-shrink-0 ms-2">
                     <button class="btn btn-soft-primary btn-sm" data-bs-toggle="modal" data-bs-target="#exportModal">
                     <i class="mdi mdi-table align-middle me-1"></i> Tabular View
                     </button>
                  </div>
               </div>
               <div class="card-header p-0 border-0 bg-light-subtle">
                  <div class="row g-0 text-center">
                     <div class="col-6 col-sm-3">
                        <div class="p-2 border border-dashed border-start-0">
                           <h5 class="mb-1"><span class="counter-value" data-target="{{ $totalAllMonths['filled'] }}">{{ $totalAllMonths['filled'] }}</span></h5>
                           <p class="text-muted mb-0">Filled Amount</p>
                        </div>
                     </div>
                     <div class="col-6 col-sm-3">
                        <div class="p-2 border border-dashed border-start-0">
                           <h5 class="mb-1"><span class="counter-value" data-target="{{ $totalAllMonths['verified'] }}">{{ $totalAllMonths['verified'] }}</span></h5>
                           <p class="text-muted mb-0">Verified Amount</p>
                        </div>
                     </div>
                     <div class="col-6 col-sm-3">
                        <div class="p-2 border border-dashed border-start-0">
                           <h5 class="mb-1"><span class="counter-value" data-target="{{ $totalAllMonths['approved'] }}">{{ $totalAllMonths['approved'] }}</span></h5>
                           <p class="text-muted mb-0">Approved Amount</p>
                        </div>
                     </div>
                     <div class="col-6 col-sm-3">
                        <div class="p-2 border border-dashed border-start-0 border-end-0">
                           <h5 class="mb-1"><span class="counter-value" data-target="{{ $totalAllMonths['financed'] }}">{{ $totalAllMonths['financed'] }}</span></h5>
                           <p class="text-muted mb-0">Financed Amount</p>
                        </div>
                     </div>
                  </div>
               </div>
               <div class="card-body p-0 pb-2">
                  <div>
                     <div
                        id="expense-monthly-chart"
                        data-colors='["--vz-primary", "--vz-warning", "--vz-success"]'
                        data-colors-minimal='["--vz-primary", "--vz-primary-rgb, 0.1", "--vz-primary-rgb, 0.50"]'
                        data-colors-interactive='["--vz-primary", "--vz-info", "--vz-warning"]'
                        data-colors-creative='["--vz-secondary", "--vz-warning", "--vz-success"]'
                        data-colors-corporate='["--vz-primary", "--vz-secondary", "--vz-danger"]'
                        data-colors-galaxy='["--vz-primary", "--vz-primary-rgb, 0.1", "--vz-primary-rgb, 0.50"]'
                        data-colors-classic='["--vz-primary", "--vz-secondary", "--vz-warning"]'
                        dir="ltr"
                        class="apex-charts"
                        ></div>
                  </div>
               </div>
            </div>
         </div>
                  <div class="col-xl-6">
            <div class="card">
               <div class="card-header">
                  <h4 class="card-title mb-0">Department-wise Expense Comparison</h4>
               </div>
               <div class="card-body">
                  <div
                     id="multi_chart"
                     data-colors='["--vz-primary", "--vz-info", "--vz-success"]'
                     dir="ltr"
                     class="apex-charts"
                     ></div>
               </div>
            </div>
         </div>
      </div>
      <div class="row">

      </div>
   </div>
</div>
<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="progress-container" style="display: none;">
            <div class="progress" style="height: 5px;">
               <div class="indeterminate" style="background-color: var(--vz-primary);"></div>
            </div>
         </div>
         <div class="modal-header">
            <h5 class="modal-title" id="exportModalLabel">Expense Month Wise Report</h5>
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
               <tbody>
                  @forelse ($monthlyStatusTotals as $item)
                  <tr>
                     <td>{{ $item->MonthName ?? 'N/A' }}</td>
                     <td>{{ number_format($item->FilledTotal ?? 0, 0) }}</td>
                     <td>{{ number_format($item->VerifiedTotal ?? 0, 0) }}</td>
                     <td>{{ number_format($item->ApprovedTotal ?? 0, 0) }}</td>
                     <td>{{ number_format($item->FinancedTotal ?? 0, 0) }}</td>
                  </tr>
                  @empty
                  <tr>
                     <td colspan="5" class="text-center">No data available</td>
                  </tr>
                  @endforelse
               </tbody>
               <tfoot class="table-light">
                  <tr>
                     <td><strong>Total</strong></td>
                     <td><strong>₹ {{ number_format($monthlyStatusTotals->sum('FilledTotal'), 0) }} </strong></td>
                     <td><strong>₹ {{ number_format($monthlyStatusTotals->sum('VerifiedTotal'), 0) }} </strong></td>
                     <td><strong>₹ {{ number_format($monthlyStatusTotals->sum('ApprovedTotal'), 0) }} </strong></td>
                     <td><strong>₹ {{ number_format($monthlyStatusTotals->sum('FinancedTotal'), 0) }} </strong></td>
                  </tr>
               </tfoot>
            </table>
         </div>
      </div>
   </div>
</div>
</div>
@endsection @push('scripts')
<script src="assets/libs/apexcharts/apexcharts.min.js"></script>

<script>
   var monthlyTotals = @json($monthlyStatusTotals);
    var departmentTotals = @json($departmentTotals);
        var yearId = @json($yearId ?? session('year_id'));
        var previousYearId = yearId - 1;
   
   function getChartColorsArray(e) {
       if (null !== document.getElementById(e)) {
           var t = "data-colors" + ("-" + document.documentElement.getAttribute("data-theme") ?? ""),
               t = document.getElementById(e).getAttribute(t) ?? document.getElementById(e).getAttribute("data-colors");
           if (t) {
               return JSON.parse(t).map(function (e) {
                   var t = e.replace(" ", "");
                   return t.indexOf(",") === -1 ? getComputedStyle(document.documentElement).getPropertyValue(t) || t : "rgba(" + getComputedStyle(document.documentElement).getPropertyValue(e.split(",")[0]) + "," + e.split(",")[1] + ")";
               });
           }
           console.warn("data-colors attributes not found on", e);
           return ["#5156be", "#ffbf53", "#2ab57d", "#fd625e"];
       }
   }
   
   var expenseChartColors = getChartColorsArray("expense-monthly-chart");
   var options = {
       series: [
           {
               name: "Filled Total",
               type: "bar",
               data: monthlyTotals.map((item) => item.FilledTotal || 0),
           },
           {
               name: "Verified Total",
               type: "bar",
               data: monthlyTotals.map((item) => item.VerifiedTotal || 0),
           },
           {
               name: "Approved Total",
               type: "bar",
               data: monthlyTotals.map((item) => item.ApprovedTotal || 0),
           },
           {
               name: "Financed Total",
               type: "area",
               data: monthlyTotals.map((item) => item.FinancedTotal || 0),
           },
       ],
       chart: {
           height: 374,
           type: "line",
           toolbar: { show: false },
       },
       stroke: {
           curve: "smooth",
           dashArray: [0, 0, 0, 3],
           width: [0, 0, 0, 1],
       },
       fill: {
           opacity: [1, 1, 1, 0.1],
       },
       markers: {
           size: [0, 0, 0, 4],
           strokeWidth: 2,
           hover: { size: 4 },
       },
       xaxis: {
           categories: monthlyTotals.map((item) => item.MonthName || ""),
           axisTicks: { show: false },
           axisBorder: { show: false },
       },
       grid: {
           show: true,
           xaxis: { lines: { show: true } },
           yaxis: { lines: { show: false } },
           padding: { top: 0, right: -2, bottom: 15, left: 10 },
       },
       legend: {
           show: true,
           horizontalAlign: "center",
           offsetX: 0,
           offsetY: -5,
           markers: { width: 9, height: 9, radius: 6 },
           itemMargin: { horizontal: 10, vertical: 0 },
       },
       plotOptions: {
           bar: { columnWidth: "30%", barHeight: "70%" },
       },
       colors: expenseChartColors,
        tooltip: {
            shared: true,
            y: [
                {
                    formatter: function (val) {
                        return val !== undefined ? val.toLocaleString('en-IN') : val;
                    },
                },
                {
                    formatter: function (val) {
                        return val !== undefined ? val.toLocaleString('en-IN') : val;
                    },
                },
                {
                    formatter: function (val) {
                        return val !== undefined ? val.toLocaleString('en-IN') : val;
                    },
                },
                {
                    formatter: function (val) {
                        return val !== undefined ? val.toLocaleString('en-IN') : val;
                    },
                },
            ],
        },
   };
   var chart = new ApexCharts(document.querySelector("#expense-monthly-chart"), options);
   chart.render();
   
   
        // Department-wise Comparison Chart
        var chartMultiColors = getChartColorsArray("multi_chart");
        var multiOptions = {
            series: [
                {
                    name: "Current Year",
                    type: "column",
                    data: departmentTotals.map(item => item["TotalFinancedTAmt_Y" + yearId] || 0)
                },
                {
                    name: "Previous Year",
                    type: "column",
                    data: departmentTotals.map(item => item["TotalFinancedTAmt_Y" + previousYearId] || 0)
                },
                {
                    name: "Variation (%)",
                    type: "line",
                    data: departmentTotals.map(item => {
                        var current = item["TotalFinancedTAmt_Y" + yearId] || 0;
                        var previous = item["TotalFinancedTAmt_Y" + previousYearId] || 0;
                        return previous ? ((current - previous) / previous * 100).toFixed(2) : 0;
                    })
                }
            ],
            chart: {
                height: 350,
                type: "line",
                stacked: false,
                toolbar: { show: false }
            },
            dataLabels: { enabled: false },
            stroke: { width: [1, 1, 4] },
            xaxis: {
                categories: departmentTotals.map(item => item.department_name || 'N/A'),
                labels: {
                    rotate: -45,
                    rotateAlways: true,
                    trim: true
                }
            },
            yaxis: [
                {
                    axisTicks: { show: true },
                    axisBorder: { show: true, color: chartMultiColors[0] },
                    labels: { style: { colors: chartMultiColors[0] } },
                    title: {
                        text: "Current Year Expenses (₹)",
                        style: { color: chartMultiColors[0], fontWeight: 600 }
                    },
                    tooltip: { enabled: true }
                },
                {
                    seriesName: "Previous Year",
                    opposite: true,
                    axisTicks: { show: true },
                    axisBorder: { show: true, color: chartMultiColors[1] },
                    labels: { style: { colors: chartMultiColors[1] } },
                    title: {
                        text: "Previous Year Expenses (₹)",
                        style: { color: chartMultiColors[1], fontWeight: 600 }
                    }
                },
                {
                    seriesName: "Variation",
                    opposite: true,
                    axisTicks: { show: true },
                    axisBorder: { show: true, color: chartMultiColors[2] },
                    labels: { style: { colors: chartMultiColors[2] } },
                    title: {
                        text: "Variation (%)",
                        style: { color: chartMultiColors[2], fontWeight: 600 }
                    },
                    formatter: function (val) {
                        return val.toFixed(2) + "%";
                    }
                }
            ],
            tooltip: {
                fixed: {
                    enabled: true,
                    position: "topLeft",
                    offsetY: 30,
                    offsetX: 60
                },
                y: [
                    {
                        formatter: function (val) {
                            return val !== undefined ? "₹" + val.toFixed(0) : val;
                        }
                    },
                    {
                        formatter: function (val) {
                            return val !== undefined ? "₹" + val.toFixed(0) : val;
                        }
                    },
                    {
                        formatter: function (val) {
                            return val !== undefined ? val.toFixed(2) + "%" : val;
                        }
                    }
                ]
            },
            legend: { horizontalAlign: "left", offsetX: 10 },
            colors: chartMultiColors
        };
   
         var multiChart = new ApexCharts(document.querySelector("#multi_chart"), multiOptions);
        multiChart.render();
</script>
@endpush