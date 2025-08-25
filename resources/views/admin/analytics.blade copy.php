@extends('layouts.app')
@section('content')
  <div class="page-content">
    <div class="container-fluid">
      <div class="card mb-3 shadow-sm">
        <div class="card-body">
          <div class="d-flex flex-wrap justify-content-between align-items-center mb-1">
            <div>
              <h3 class="fw-bold">Department Expense Analytics</h3>
              <p class="text-muted mb-0">Comprehensive expense analysis and decision support system</p>
            </div>
            <div class="d-flex gap-2">
              <input type="text" class="form-control" data-provider="flatpickr" data-date-format="Y-m-d"
                data-range-date="true" id="dateRange" style="width: 200px;" placeholder="Select date range" value="">
              <select id="filterType" class="form-select w-auto">
                <option value="all">All Departments</option>
                <option value="increased">Increased Expenses</option>
                <option value="decreased">Decreased Expenses</option>
                <option value="critical">Critical Changes (>50%)</option>
              </select>
              <select id="sortBy" class="form-select w-auto">
                <option value="variation">Sort by Variation</option>
                <option value="current">Sort by Current Year</option>
                <option value="previous">Sort by Previous Year</option>
              </select>
              <button class="btn btn-primary">
                <i class="bi bi-download"></i> Export Report
              </button>
            </div>
          </div>
          <div class="row text-center">
            <div class="col border-end">
              <i class="bi bi-currency-rupee text-primary fs-2 mb-2"></i>
              <p class="text-muted mb-1">Total Previous Year</p>
              <h4 id="totalPrevious" class="text-primary fw-bold mb-0">₹0</h4>
            </div>
            <div class="col border-end">
              <i class="bi bi-currency-rupee text-secondary fs-2 mb-2"></i>
              <p class="text-muted mb-1">Total Current Year</p>
              <h4 id="totalCurrent" class="text-secondary fw-bold mb-0">₹0</h4>
            </div>
            <div class="col border-end">
              <i id="variationIcon" class="bi bi-graph-up text-info fs-2 mb-2"></i>
              <p class="text-muted mb-1">Overall Variation</p>
              <h4 id="overallVariation" class="text-info fw-bold mb-0">0%</h4>
            </div>
            <div class="col border-end">
              <i class="bi bi-arrow-down-circle text-danger fs-2 mb-2"></i>
              <p class="text-muted mb-1">Expense Decreased</p>
              <h4 id="decreasedCount" class="text-danger fw-bold mb-0">0</h4>
            </div>
            <div class="col">
              <i class="bi bi-arrow-up-circle text-success fs-2 mb-2"></i>
              <p class="text-muted mb-1">Expense Increased</p>
              <h4 id="increasedCount" class="text-success fw-bold mb-0">0</h4>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6">
          <div class="card mb-3 shadow-sm">
            <div class="card-body" style="height: 365px;">
              <h5 class="mb-3">Department Expense Comparison</h5>
              <canvas id="barChart"></canvas>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card mb-3 shadow-sm">
            <div class="card-body">
              <h5 class="mb-3">Current Year Expense Distribution</h5>
              <div style="max-width:400px; margin:auto;">
                <canvas id="pieChart"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="card shadow-sm mb-3">
        <div class="card-body">
          <h5 class="mb-3">Detailed Department Analysis</h5>
          <div class="table-responsive">
            <table class="table table-bordered" id="deptTable">
              <thead class="table-light">
                <tr>
                  <th class="text-center">Sn</th>
                  <th class="text-start">Department</th>
                  <th class="text-end">Previous Year</th>
                  <th class="text-end">Current Year</th>
                  <th class="text-center">Variation</th>
                  <th class="text-center">Status</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="card shadow-sm mb-3">
        <div class="card-body">
          <h5 class="mb-3">Monthly Trend Analysis</h5>
          <div style="height:300px;">
            <canvas id="lineChart"></canvas>
          </div>
        </div>
      </div>
      <div class="row">
        <!-- Overall Top Employees -->
        <div class="col-md-4">
          <div class="card shadow-sm mb-3">
            <div class="card-body">
              <h5 class="mb-3">Top Employees</h5>
              <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped" id="tblTopEmployees">
                  <thead class="table-light">
                    <tr>
                      <th>#</th>
                      <th>Employee</th>
                      <th>Dept</th>
                      <th class="text-end">Filled</th>
                      <th class="text-end">Payment</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Same Day Employees -->
        <div class="col-md-4">
          <div class="card shadow-sm mb-3">
            <div class="card-body">
              <h5 class="mb-3">Same Day Uploads</h5>
              <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped" id="tblTopEmployeesSameDay">
                  <thead class="table-light">
                    <tr>
                      <th>#</th>
                      <th>Employee</th>
                      <th>Dept</th>
                      <th class="text-end">Filled</th>
                      <th class="text-end">Payment</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Revert Employees -->
        <div class="col-md-4">
          <div class="card shadow-sm mb-3">
            <div class="card-body">
              <h5 class="mb-3">Revert Uploads</h5>
              <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped" id="tblTopEmployeesRevert">
                  <thead class="table-light">
                    <tr>
                      <th>#</th>
                      <th>Employee</th>
                      <th>Dept</th>
                      <th class="text-end">Filled</th>
                      <th class="text-end">Payment</th>
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
  </div>
@endsection
@push('styles')
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush
@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
  <script>
    $(function () {
      let barChartInstance, pieChartInstance, lineChartInstance;


      const today = new Date();
      const dateInput = document.getElementById("dateRange");
      const datePicker = flatpickr(dateInput, {
        mode: "range",
        dateFormat: "d M, Y",
        maxDate: new Date(today.setHours(23, 59, 59, 999)),
        onClose: function (selectedDates) {
          if (selectedDates.length === 2) {
            const [start, end] = selectedDates;
            const billDateFrom = formatDateForAPI(start);
            const billDateTo = formatDateForAPI(end);
            fetchDashboardData(billDateFrom, billDateTo);
          }
        },
      });


      const formatDateForAPI = (date) => {
        return date.toISOString().split('T')[0];
      };


      const formatCurrency = (v) => new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(v);


      const fetchDashboardData = (billDateFrom, billDateTo, filterType = 'all', sortBy = 'variation') => {
        $.ajax({
          url: '{{ route('analytics-dashboard-data') }}',
          method: 'get',
          beforeSend: function () {
            startPageLoader();
          },
          data: {
            bill_date_from: billDateFrom,
            bill_date_to: billDateTo,
            filter_type: filterType,
            sort_by: sortBy,
            _token: '{{ csrf_token() }}'
          },
          success: function (response) {
            updateDashboard(response.data.departments, filterType, sortBy);
          },
          error: function (xhr) {
            alert('Error fetching data: ' + (xhr.responseJSON?.message || 'Unknown error'));
          },
          complete: function () {
            endPageLoader();
          },
        });
      };


      const updateDashboard = (expenseData, filterType, sortBy) => {

        let filteredData = expenseData;
        if (filterType === 'increased') {
          filteredData = expenseData.filter(d => d.variation > 0);
        } else if (filterType === 'decreased') {
          filteredData = expenseData.filter(d => d.variation < 0);
        } else if (filterType === 'critical') {
          filteredData = expenseData.filter(d => Math.abs(d.variation) > 50);
        }


        if (sortBy === 'variation') {
          filteredData.sort((a, b) => b.variation - a.variation);
        } else if (sortBy === 'current') {
          filteredData.sort((a, b) => b.currentYear - a.currentYear);
        } else if (sortBy === 'previous') {
          filteredData.sort((a, b) => b.previousYear - a.previousYear);
        }


        const totalCurrent = filteredData.reduce((a, b) => a + b.currentYear, 0);
        const totalPrev = filteredData.reduce((a, b) => a + b.previousYear, 0);
        const variation = totalPrev > 0 ? ((totalCurrent - totalPrev) / totalPrev * 100).toFixed(1) : 0;
        const critical = filteredData.filter(d => Math.abs(d.variation) > 50).length;
        const improving = filteredData.filter(d => d.variation > 0).length;

        $("#totalCurrent").text(formatCurrency(totalCurrent));
        $("#totalPrevious").text(formatCurrency(totalPrev));
        $("#overallVariation").text((variation > 0 ? "+" : "") + variation + "%");
        $("#decreasedCount").text(critical);
        $("#increasedCount").text(improving);


        if (barChartInstance) barChartInstance.destroy();
        if (pieChartInstance) pieChartInstance.destroy();
        if (lineChartInstance) lineChartInstance.destroy();


        barChartInstance = new Chart($("#barChart"), {
          type: 'bar',
          data: {
            labels: filteredData.map(d => d.code),
            datasets: [
              { label: 'Previous Year', data: filteredData.map(d => d.previousYear), backgroundColor: '#94a3b8' },
              { label: 'Current Year', data: filteredData.map(d => d.currentYear), backgroundColor: '#3b82f6' }
            ]
          },
          options: { responsive: true, plugins: { tooltip: { callbacks: { label: ctx => formatCurrency(ctx.raw) } } } }
        });


        pieChartInstance = new Chart($("#pieChart"), {
          type: 'pie',
          data: {
            labels: filteredData.map(d => d.department),
            datasets: [{
              data: filteredData.map(d => d.currentYear),
              backgroundColor: [
                '#8884d8', '#82ca9d', '#ffc658', '#ff7c7c', '#8dd1e1', '#d084d0',
                '#87ceeb', '#dda0dd', '#a0522d', '#ffb347', '#3cb371', '#20b2aa',
                '#9370db', '#4682b4', '#ff69b4', '#cd5c5c', '#40e0d0', '#9acd32', '#ff6347'
              ]
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'right',
                labels: {
                  generateLabels: (chart) => {
                    const data = chart.data;
                    return data.labels.map((label, i) => {
                      const value = data.datasets[0].data[i];
                      return {
                        text: `${label} – ${value.toLocaleString("en-IN")}`,
                        fillStyle: data.datasets[0].backgroundColor[i],
                        strokeStyle: data.datasets[0].backgroundColor[i],
                        fontColor: "#666666",
                        hidden: isNaN(value) || value === null,
                        index: i
                      };
                    });
                  },
                  color: "#666666"
                }
              }
            }
          }
        });


        const months = ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'];
        const trendData = months.map((m, i) => {
          return filteredData.map(d => Math.round((Math.sin(i * 0.5) * 0.2 + 1) * (d.currentYear / 12)));
        });

        lineChartInstance = new Chart($("#lineChart"), {
          type: 'line',
          data: {
            labels: months,
            datasets: filteredData.slice(0, 20).map((d, i) => ({
              label: d.code,
              data: trendData.map(t => t[i]),
              borderColor: ['#8884d8', '#82ca9d', '#ffc658', '#ff7c7c', '#8dd1e1', '#d084d0',
                '#87ceeb', '#dda0dd', '#a0522d', '#ffb347', '#3cb371', '#20b2aa',
                '#9370db', '#4682b4', '#ff69b4', '#cd5c5c', '#40e0d0', '#9acd32', '#ff6347'][i],
              backgroundColor: [
                '#8884d8', '#82ca9d', '#ffc658', '#ff7c7c', '#8dd1e1', '#d084d0',
                '#87ceeb', '#dda0dd', '#a0522d', '#ffb347', '#3cb371', '#20b2aa',
                '#9370db', '#4682b4', '#ff69b4', '#cd5c5c', '#40e0d0', '#9acd32', '#ff6347'
              ][i],
              fill: false
            }))
          },
          options: {
            responsive: true,
            maintainAspectRatio: false
          }
        });


        let rows = "";
        let overallPrevTotal = 0;
        let overallCurrTotal = 0;

        filteredData.forEach((d, idx) => {
          overallPrevTotal += d.previousYear;
          overallCurrTotal += d.currentYear;

          let status = "";
          let rowClass = "";
          let tooltipText = "";

          if (d.variation > 100) {
            status = `<span class="text-success" data-bs-toggle="tooltip" 
                              title="Expense increased drastically compared to last year.">
                              <i class="ri-rocket-2-line me-1"></i> Highly Increased
                            </span>`;
            rowClass = "table-success";

          } else if (d.variation > 20) {
            status = `<span class="text-success" data-bs-toggle="tooltip" 
                              title="Expenses are higher than last year, monitor the trend.">
                              <i class="ri-line-chart-line me-1"></i> Increased
                            </span>`;
            rowClass = "table-light";

          } else if (d.variation < -50) {
            status = `<span class="text-danger" data-bs-toggle="tooltip" 
                              title="Expense dropped heavily compared to last year. Needs urgent attention.">
                              <i class="ri-error-warning-fill me-1"></i> Critical
                            </span>`;
            rowClass = "table-danger";

          } else if (d.variation < -20) {
            status = `<span class="text-primary" data-bs-toggle="tooltip" 
                              title="Expenses are lower than last year. Consider validating the reason.">
                              <i class="ri-bar-chart-2-line me-1"></i> Decreased
                            </span>`;
            rowClass = "table-info";

          } else if (d.variation < 0) {
            status = `<span class="text-warning" data-bs-toggle="tooltip" 
                              title="Small decrease compared to last year. Not a major concern.">
                              <i class="ri-arrow-down-line me-1"></i> Slightly Decreased
                            </span>`;
            rowClass = "table-warning";

          } else {
            status = `<span class="text-muted" data-bs-toggle="tooltip" 
                              title="No significant change compared to last year.">
                              <i class="ri-subtract-line me-1"></i> Stable
                            </span>`;
            rowClass = "";
          }


          rows += `<tr class="">
                                        <td class="text-center">${idx + 1}</td>
                                        <td class="text-start">${d.department ?? "N/A"}</td>
                                        <td class="text-end">${formatCurrency(d.previousYear)}</td>
                                        <td class="text-end">${formatCurrency(d.currentYear)}</td>
                                        <td class="text-center ${d.variation > 0 ? 'text-success' : (d.variation < 0 ? 'text-danger' : 'text-muted')}">
                                          ${d.variation > 0 ? "+" : ""}${d.variation}%
                                        </td>
                                        <td class="text-center">${status}</td>
                                      </tr>`;
        });


        const overallVariationPercent = overallPrevTotal === 0
          ? 0
          : (((overallCurrTotal - overallPrevTotal) / overallPrevTotal) * 100).toFixed(2);

        let overallStatus = "";
        let overallRowClass = "";

        if (overallVariationPercent > 100) {
          overallStatus = `<span class="text-success"><i class="bi bi-rocket-takeoff-fill me-1"></i> Highly Increased</span>`;
          overallRowClass = "table-success";
        } else if (overallVariationPercent > 20) {
          overallStatus = `<span class="text-success"><i class="bi bi-graph-up-arrow me-1"></i> Increased</span>`;
          overallRowClass = "table-light";
        } else if (overallVariationPercent < -50) {
          overallStatus = `<span class="text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> Critical</span>`;
          overallRowClass = "table-danger";
        } else if (overallVariationPercent < -20) {
          overallStatus = `<span class="text-primary"><i class="bi bi-graph-down-arrow me-1"></i> Decreased</span>`;
          overallRowClass = "table-info";
        } else if (overallVariationPercent < 0) {
          overallStatus = `<span class="text-warning"><i class="bi bi-arrow-down me-1"></i> Slightly Decreased</span>`;
          overallRowClass = "table-warning";
        } else {
          overallStatus = `<span class="text-muted"><i class="bi bi-dash-circle me-1"></i> Stable</span>`;
          overallRowClass = "";
        }

        rows += `<tr class="fw-bold">
                                      <td class="text-center">#</td>
                                      <td class="text-start">Overall Total</td>
                                      <td class="text-end">${formatCurrency(overallPrevTotal)}</td>
                                      <td class="text-end">${formatCurrency(overallCurrTotal)}</td>
                                      <td class="text-center ${overallVariationPercent > 0 ? 'text-success' : (overallVariationPercent < 0 ? 'text-danger' : 'text-muted')}">
                                        ${overallVariationPercent > 0 ? "+" : ""}${overallVariationPercent}%
                                      </td>
                                      <td class="text-center">${overallStatus}</td>
                                    </tr>`;

        $("#deptTable tbody").html(rows);
      };


      $("#filterType, #sortBy").on('change', function () {
        const selectedDates = datePicker.selectedDates;
        if (selectedDates.length === 2) {
          const billDateFrom = formatDateForAPI(selectedDates[0]);
          const billDateTo = formatDateForAPI(selectedDates[1]);
          const filterType = $("#filterType").val();
          const sortBy = $("#sortBy").val();
          fetchDashboardData(billDateFrom, billDateTo, filterType, sortBy);
        }
      });


      const defaultStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
      const defaultEnd = new Date(today.getFullYear(), today.getMonth(), 0);
      datePicker.setDate([defaultStart, defaultEnd]);
      fetchDashboardData(formatDateForAPI(defaultStart), formatDateForAPI(defaultEnd));
      function renderEmployeeTable(data, tableId) {
        let rows = "";
        data.forEach((emp, idx) => {
          rows += `<tr>
        <td>${idx + 1}</td>
        <td>${emp.employee_name} <br><small class="text-muted">(${emp.EmpCode})</small></td>
        <td>${emp.department_name ?? "N/A"}</td>
        <td class="text-end">${formatCurrency(emp.filled_total_amount)}</td>
        <td class="text-end">${formatCurrency(emp.payment_total_amount)}</td>
      </tr>`;
        });

        $(`#${tableId} tbody`).html(rows);
      }

    });
  </script>
@endpush