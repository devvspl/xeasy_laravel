@extends('layouts.app')
@section('content')
  <div class="page-content">
    <div class="container-fluid">
      <div class="card mb-3 shadow-sm">
        <div class="card-body">
          <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
            <div>
              <h4 class="fw-bold">Department Expense Analytics</h4>
              <p class="text-muted mb-0">Comprehensive expense analysis and decision support system</p>
            </div>
            <div class="d-flex gap-1 align-items-center">
              <input type="text" class="form-control form-control-sm" data-provider="flatpickr" data-date-format="Y-m-d"
                data-range-date="true" id="dateRange" style="width: 150px;" placeholder="Select date range" value="">
              <div class="dropdown form-select-sm w-auto d-inline-block position-relative" style="min-width: 180px;">
                <!-- Button to toggle dropdown and display selected items -->
                <button class="dropdown-toggle form-control" type="button" style="padding: 4px;" id="departmentDropdown"
                  data-bs-toggle="dropdown" aria-expanded="false">
                  <span id="selectedDepartments">All Departments</span>
                </button>

                <!-- Dropdown menu with checkboxes -->
                <ul class="dropdown-menu p-2" style="max-height: 200px; overflow-y: auto;"
                  aria-labelledby="departmentDropdown">
                  <li>
                    <div class="form-check">
                      <input type="checkbox" class="form-check-input" id="selectAllDepartments" value="">
                      <label class="form-check-label" for="selectAllDepartments">
                        All Departments
                      </label>
                    </div>
                  </li>
                  @foreach($departments as $department)
                    <li>
                      <div class="form-check">
                        <input type="checkbox" name="departments[]" class="form-check-input department-checkbox"
                          id="department_{{ $department->id }}" value="{{ $department->id }}">
                        <label class="form-check-label" for="department_{{ $department->id }}">
                          {{ $department->department_name }}
                        </label>
                      </div>
                    </li>
                  @endforeach
                </ul>
              </div>

              <select id="filterType" class="form-select form-select-sm w-auto">
                <option value="all">All Types</option>
                <option value="increased">Increased Expenses</option>
                <option value="decreased">Decreased Expenses</option>
                <option value="critical">Critical Changes (>50%)</option>
              </select>

              <select id="sortBy" class="form-select form-select-sm w-auto">
                <option value="variation">Sort by Variation</option>
                <option value="current">Sort by Current Year</option>
                <option value="previous">Sort by Previous Year</option>
              </select>

              <button class="btn btn-sm btn-primary">
                <i class="ri-download-2-fill"></i>
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
            <div class="card-body" style="height: 365px;">
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
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="align-items-center d-flex mb-2">
            <h5 class="flex-grow-1">Top Expense Submitters Analysis</h5>
          </div>
          <div class="d-flex gap-3">
            <div class="flex-fill">
              <h6 class="text-center">Top Expense Submitters</h6>
              <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped table-hover" id="tblTopEmployees">
                  <thead class="table-light">
                    <tr>
                      <th>#</th>
                      <th class="text-start">Employee</th>
                      <th>Department</th>
                      <th class="text-end">Count</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
            <div class="flex-fill">
              <h6 class="text-center">Same-Day Submissions</h6>
              <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped table-hover" id="tblTopEmployeesSameDay">
                  <thead class="table-light">
                    <tr>
                      <th>#</th>
                      <th class="text-start">Employee</th>
                      <th>Department</th>
                      <th class="text-end">Count</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
            <div class="flex-fill">
              <h6 class="text-center">Delayed Submissions</h6>
              <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped table-hover" id="tblTopEmployeesRevert">
                  <thead class="table-light">
                    <tr>
                      <th>#</th>
                      <th class="text-start">Employee</th>
                      <th>Department</th>
                      <th class="text-end">Count</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="align-items-center d-flex mb-2">
            <h5 class="flex-grow-1">Department-wise Claim Type Totals</h5>
          </div>
          <div id="claimTypeWiseContainer">

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
    document.addEventListener('DOMContentLoaded', function () {
      const selectAllCheckbox = document.getElementById('selectAllDepartments');
      const departmentCheckboxes = document.querySelectorAll('.department-checkbox');
      const selectedDepartmentsSpan = document.getElementById('selectedDepartments');


      function updateSelectedText() {
        const checked = Array.from(departmentCheckboxes)
          .filter(cb => cb.checked)
          .map(cb => {
            const label = cb.nextElementSibling.textContent.trim();
            return label.length > 20 ? label.substring(0, 17) + '...' : label;
          });

        selectedDepartmentsSpan.textContent = checked.length === 0 ?
          'All Departments' :
          checked.length > 2 ? `${checked.length} selected` : checked.join(', ');
      }


      selectAllCheckbox.addEventListener('change', function () {
        departmentCheckboxes.forEach(cb => {
          cb.checked = selectAllCheckbox.checked;
        });
        updateSelectedText();
      });


      departmentCheckboxes.forEach(cb => {
        cb.addEventListener('change', function () {

          selectAllCheckbox.checked = false;


          const allChecked = Array.from(departmentCheckboxes).every(cb => cb.checked);
          if (allChecked && departmentCheckboxes.length > 0) {
            selectAllCheckbox.checked = true;
          }

          updateSelectedText();
        });
      });
    });
    $(function () {
      let barChartInstance, pieChartInstance, lineChartInstance;

      const today = new Date();

      const dateInput = document.getElementById("dateRange");

      const datePicker = flatpickr(dateInput, {
        mode: "range",
        dateFormat: "d M, Y",
        maxDate: new Date(today.setHours(23, 59, 59, 999)),
        defaultDate: [new Date(today.getFullYear(), 3, 1)],
        onClose: function (selectedDates) {
          if (selectedDates.length === 2) {
            const [start, end] = selectedDates;
            const billDateFrom = formatDate(start);
            const billDateTo = formatDate(end);
            fetchDashboardData(billDateFrom, billDateTo);
          }
        },
      });

      const formatDate = (date) => {
        return date.toISOString().split('T')[0];
      };

      const formatCurrency = (v) => new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(v);

      const fetchDashboardData = (billDateFrom, billDateTo, filterType = 'all', sortBy = 'variation', department = '') => {
        $.ajax({
          url: '{{ route("analytics-dashboard-data") }}',
          method: 'get',
          beforeSend: function () {
            startPageLoader();
          },
          data: {
            bill_date_from: billDateFrom,
            bill_date_to: billDateTo,
            filter_type: filterType,
            sort_by: sortBy,
            department: department,
            _token: '{{ csrf_token() }}'
          },
          success: function (response) {
            updateDashboard(response.data.departments, filterType, sortBy);
            renderEmployeeTable(response.data.topEmployees, "tblTopEmployees");
            renderEmployeeTable(response.data.topEmployeesSameDay, "tblTopEmployeesSameDay");
            renderEmployeeTable(response.data.topEmployeesRevert, "tblTopEmployeesRevert");
            renderDepartmentMonthlyChart(response.data.departmentMonthlyTotals);
            console.log(response.data.departmentTotalsClaimTypeWise);
            renderClaimTypeWiseTable(response.data.departmentTotalsClaimTypeWise, "#claimTypeWiseContainer");

          },
          error: function (xhr) {
            alert('Error fetching data: ' + (xhr.responseJSON?.message || 'Unknown error'));
            endPageLoader();
          },
          complete: function () {
            endPageLoader();
          },
        });
      };
      const updateDashboard = (expenseData, filterType, sortBy) => {

        const filterData = (data, filterType) => {
          if (filterType === 'increased') {
            return data.filter(d => d.variation > 0);
          } else if (filterType === 'decreased') {
            return data.filter(d => d.variation < 0);
          } else if (filterType === 'critical') {
            return data.filter(d => Math.abs(d.variation) > 50);
          }
          return data;
        };

        const sortData = (data, sortBy) => {
          if (sortBy === 'variation') {
            return data.sort((a, b) => b.variation - a.variation);
          } else if (sortBy === 'current') {
            return data.sort((a, b) => b.currentYear - a.currentYear);
          } else if (sortBy === 'previous') {
            return data.sort((a, b) => b.previousYear - a.previousYear);
          }
          return data;
        };

        const calculateMetrics = (data) => {
          const totalCurrent = data.reduce((a, b) => a + b.currentYear, 0);
          const totalPrev = data.reduce((a, b) => a + b.previousYear, 0);
          const variation = totalPrev > 0 ? ((totalCurrent - totalPrev) / totalPrev * 100).toFixed(2) : 0;
          const critical = data.filter(d => Math.abs(d.variation) > 50).length;
          const improving = data.filter(d => d.variation > 0).length;

          return { totalCurrent, totalPrev, variation, critical, improving };
        };

        const updateMetricsDisplay = ({ totalCurrent, totalPrev, variation, critical, improving }) => {
          $("#totalCurrent").text(formatCurrency(totalCurrent));
          $("#totalPrevious").text(formatCurrency(totalPrev));
          $("#overallVariation").text((variation > 0 ? "+" : "") + variation + "%");
          $("#decreasedCount").text(critical);
          $("#increasedCount").text(improving);
        };

        const destroyCharts = () => {
          if (barChartInstance) barChartInstance.destroy();
          if (pieChartInstance) pieChartInstance.destroy();
          if (lineChartInstance) lineChartInstance.destroy();
        };

        const renderBarChart = (data) => {
          barChartInstance = new Chart($("#barChart"), {
            type: 'bar',
            data: {
              labels: data.map(d => d.code),
              datasets: [
                { label: 'Previous Year', data: data.map(d => d.previousYear), backgroundColor: '#94a3b8' },
                { label: 'Current Year', data: data.map(d => d.currentYear), backgroundColor: '#3b82f6' }
              ]
            },
            options: { responsive: true, plugins: { tooltip: { callbacks: { label: ctx => formatCurrency(ctx.raw) } } } }
          });
        };

        const renderPieChart = (data) => {
          pieChartInstance = new Chart($("#pieChart"), {
            type: 'pie',
            data: {
              labels: data.map(d => d.department),
              datasets: [{
                data: data.map(d => d.currentYear),
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
        };

        const getStatusAndClass = (variation) => {
          let status = "";
          let rowClass = "";
          let tooltipText = "";

          if (variation > 100) {
            status = `<span class="text-success" data-bs-toggle="tooltip" 
                                                                                                            title="Expense increased drastically compared to last year.">
                                                                                                            <i class="ri-rocket-2-line me-1"></i> Highly Increased
                                                                                                          </span>`;
            rowClass = "table-success";
          } else if (variation > 20) {
            status = `<span class="text-success" data-bs-toggle="tooltip" 
                                                                                                            title="Expenses are higher than last year, monitor the trend.">
                                                                                                            <i class="ri-line-chart-line me-1"></i> Increased
                                                                                                          </span>`;
            rowClass = "table-light";
          } else if (variation < -50) {
            status = `<span class="text-danger" data-bs-toggle="tooltip" 
                                                                                                            title="Expense dropped heavily compared to last year. Needs urgent attention.">
                                                                                                            <i class="ri-error-warning-fill me-1"></i> Critical
                                                                                                          </span>`;
            rowClass = "table-danger";
          } else if (variation < -20) {
            status = `<span class="text-primary" data-bs-toggle="tooltip" 
                                                                                                            title="Expenses are lower than last year. Consider validating the reason.">
                                                                                                            <i class="ri-bar-chart-2-line me-1"></i> Decreased
                                                                                                          </span>`;
            rowClass = "table-info";
          } else if (variation < 0) {
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

          return { status, rowClass };
        };

        const renderDepartmentTable = (data) => {
          let rows = "";
          let overallPrevTotal = 0;
          let overallCurrTotal = 0;

          data.forEach((d, idx) => {
            overallPrevTotal += d.previousYear;
            overallCurrTotal += d.currentYear;

            const { status, rowClass } = getStatusAndClass(d.variation);

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

          const { status: overallStatus, rowClass: overallRowClass } = getStatusAndClass(overallVariationPercent);

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

        let filteredData = filterData(expenseData, filterType);
        filteredData = sortData(filteredData, sortBy);
        const metrics = calculateMetrics(filteredData);
        updateMetricsDisplay(metrics);
        destroyCharts();
        renderBarChart(filteredData);
        renderPieChart(filteredData);
        renderDepartmentTable(filteredData);
      };


      function renderDepartmentMonthlyChart(departmentMonthlyTotals) {

        const months = [...new Set(departmentMonthlyTotals.map(d => d.MonthName))];
        const departmentMap = {};
        departmentMonthlyTotals.forEach(d => {
          if (!departmentMap[d.department_name]) {
            departmentMap[d.department_name] = {};
          }
          departmentMap[d.department_name][d.MonthName] = parseFloat(d.FinancedTotal);
        });

        const datasets = Object.keys(departmentMap).map((dept, i) => {
          const data = months.map(m => departmentMap[dept][m] || 0);
          return {
            label: dept,
            data: data,
            borderColor: [
              '#8884d8', '#82ca9d', '#ffc658', '#ff7c7c', '#8dd1e1', '#d084d0',
              '#87ceeb', '#dda0dd', '#a0522d', '#ffb347', '#3cb371', '#20b2aa',
              '#9370db', '#4682b4', '#ff69b4', '#cd5c5c', '#40e0d0', '#9acd32', '#ff6347'
            ][i % 20],
            backgroundColor: [
              '#8884d8', '#82ca9d', '#ffc658', '#ff7c7c', '#8dd1e1', '#d084d0',
              '#87ceeb', '#dda0dd', '#a0522d', '#ffb347', '#3cb371', '#20b2aa',
              '#9370db', '#4682b4', '#ff69b4', '#cd5c5c', '#40e0d0', '#9acd32', '#ff6347'
            ][i % 20],
            fill: false
          };
        });

        const ctx = document.getElementById('lineChart').getContext('2d');
        if (window.lineChartInstance) {
          window.lineChartInstance.destroy();
        }

        window.lineChartInstance = new Chart(ctx, {
          type: 'line',
          data: { labels: months, datasets: datasets },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { position: 'top' },
              tooltip: { mode: 'index', intersect: false }
            },
            interaction: { mode: 'nearest', intersect: false },
            scales: {
              y: { beginAtZero: true, title: { display: true, text: 'Financed Total' } },
              x: { title: { display: true, text: 'Month' } }
            }
          }
        });
      }

      $("#filterType, #sortBy").on('change', function () {
        const selectedDates = datePicker.selectedDates;
        if (selectedDates.length === 2) {
          const billDateFrom = formatDate(selectedDates[0]);
          const billDateTo = formatDate(selectedDates[1]);
          const filterType = $("#filterType").val();
          const sortBy = $("#sortBy").val();
          const departments = $('input[name="departments[]"]:checked').map(function () {
            return this.value;
          }).get();
          fetchDashboardData(billDateFrom, billDateTo, filterType, sortBy, departments.length > 0 ? departments : null);
        }
      });

      $('input[name="departments[]"]').on('change', function () {
        const selectedDates = datePicker.selectedDates;
        if (selectedDates.length === 2) {
          const billDateFrom = formatDate(selectedDates[0]);
          const billDateTo = formatDate(selectedDates[1]);
          const filterType = $("#filterType").val();
          const sortBy = $("#sortBy").val();
          const departments = $('input[name="departments[]"]:checked').map(function () {
            return this.value;
          }).get();
          fetchDashboardData(billDateFrom, billDateTo, filterType, sortBy, departments.length > 0 ? departments : null);
        }
      });

      $('#selectAllDepartments').on('change', function () {
        const selectedDates = datePicker.selectedDates;
        if (selectedDates.length === 2) {
          const billDateFrom = formatDate(selectedDates[0]);
          const billDateTo = formatDate(selectedDates[1]);
          const filterType = $("#filterType").val();
          const sortBy = $("#sortBy").val();
          const departments = $('input[name="departments[]"]:checked').map(function () {
            return this.value;
          }).get();
          fetchDashboardData(billDateFrom, billDateTo, filterType, sortBy, departments.length > 0 ? departments : null);
        }
      });

      const defaultStart = new Date(today.getFullYear(), 3, 1);
      const defaultEnd = today;

      datePicker.setDate([defaultStart, defaultEnd]);
      fetchDashboardData(formatDate(defaultStart), formatDate(defaultEnd));

      function renderEmployeeTable(data, tableId) {
        let rows = "";
        data.forEach((emp, idx) => {
          rows += `<tr><td>${idx + 1}</td><td class="text-start">${emp.employee_name} - ${emp.EmpCode}</td><td>${emp.department_code ?? "N/A"}</td><td class="text-end">${emp.claim_count}</td></tr>`;
        });
        $(`#${tableId} tbody`).html(rows);
      }
      function renderClaimTypeWiseTable(departmentData, containerId = "") {
        const container = containerId
          ? document.querySelector(
            containerId.startsWith("#") || containerId.startsWith(".")
              ? containerId
              : `#${containerId}`
          )
          : document.querySelector(".card-body");

        if (!container) {
          console.warn(`Container "${containerId || ".card-body"}" not found.`);
          return;
        }

        if (!Array.isArray(departmentData) || departmentData.length === 0) {
          container.innerHTML = '<p class="text-muted">No data available.</p>';
          return;
        }

        function escapeHtml(str) {
          if (str == null) return "";
          const div = document.createElement("div");
          div.textContent = str;
          return div.innerHTML;
        }

        function formatNumber(value) {
          if (value == null || isNaN(value)) return "-";
          return Number(value).toLocaleString("en-IN", {
            style: "currency",
            currency: "INR",
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
          });
        }

        // Collect unique claim types
        const claimMap = new Map();
        departmentData.forEach((dept) => {
          dept.claims.forEach((claim) => {
            if (!claimMap.has(claim.ClaimCode)) {
              claimMap.set(claim.ClaimCode, {
                ClaimName: claim.ClaimName,
                values: {},
              });
            }
            claimMap.get(claim.ClaimCode).values[dept.department_name] = {
              Y7: claim.TotalFinancedTAmt_Y7,
              Y6: claim.TotalFinancedTAmt_Y6,
              VariationPercentage: claim.VariationPercentage,
            };
          });
        });

        const claims = Array.from(claimMap.values());
        const departments = departmentData.map((d) => d.department_name);

        // Color palette
        const colors = [
          "#8884d8",
          "#82ca9d",
          "#ffc658",
          "#ff7c7c",
          "#8dd1e1",
          "#d084d0",
          "#87ceeb",
          "#dda0dd",
          "#a0522d",
          "#ffb347",
          "#3cb371",
          "#20b2aa",
          "#9370db",
          "#4682b4",
          "#ff69b4",
          "#cd5c5c",
          "#40e0d0",
          "#9acd32",
          "#ff6347",
        ];

        // Track totals per department
        const totals = {};
        departments.forEach((d) => (totals[d] = { Y7: 0, Y6: 0 }));

        // Build table
        let html = `<div class="table-responsive">
      <table class="table table-sm table-bordered align-middle">
        <thead>
          <tr>
            <th rowspan="2" style="width:200px;vertical-align: middle;text-align: left;">Claim Name</th>
            ${departments
            .map(
              (d, i) =>
                `<th colspan="3" class="text-center" style="background-color:${colors[i % colors.length]
                }40;">${escapeHtml(d)}</th>`
            )
            .join("")}
            <th colspan="3" class="text-center">Total</th>
          </tr>
          <tr>
            ${departments
            .map(
              (_, i) => `
              <th style="background-color:${colors[i % colors.length]}40;">Prev</th>
              <th style="background-color:${colors[i % colors.length]}40;">Curr</th>
              <th style="background-color:${colors[i % colors.length]}40;">Var</th>
            `
            )
            .join("")}
            <th>Prev</th><th>Curr</th><th>Var</th>
          </tr>
        </thead>
        <tbody>`;

        // Claim rows with row-wise totals
        claims.forEach((claim) => {
          let rowTotalY7 = 0,
            rowTotalY6 = 0;
          html += `<tr>
        <td style="vertical-align: middle;text-align: left;"><b>${escapeHtml(claim.ClaimName)}</b></td>
        ${departments
              .map((d, i) => {
                const val = claim.values[d] || {};
                if (val.Y7) {
                  totals[d].Y7 += val.Y7;
                  rowTotalY7 += val.Y7;
                }
                if (val.Y6) {
                  totals[d].Y6 += val.Y6;
                  rowTotalY6 += val.Y6;
                }

                return `
                <td class="text-end" style="background-color:${colors[i % colors.length]
                  }15;">${formatNumber(val.Y6)}</td>
                <td class="text-end" style="background-color:${colors[i % colors.length]
                  }15;">${formatNumber(val.Y7)}</td>
                <td class="text-end ${val.VariationPercentage > 0
                    ? "text-success"
                    : val.VariationPercentage < 0
                      ? "text-danger"
                      : ""
                  }" style="background-color:${colors[i % colors.length]}15;">
                  ${val.VariationPercentage != null
                    ? Number(val.VariationPercentage).toFixed(2) + "%"
                    : "-"
                  }
                </td>
              `;
              })
              .join("")}

        <td class="text-end"><b>${formatNumber(rowTotalY6)}</b></td>
        <td class="text-end"><b>${formatNumber(rowTotalY7)}</b></td>
        <td class="text-end"><b>${rowTotalY6 !== 0
              ? (((rowTotalY7 - rowTotalY6) / rowTotalY6) * 100).toFixed(2) + "%"
              : rowTotalY7 !== 0
                ? "100%"
                : "-"
            }</b></td>
      </tr>`;
        });

        // Grand total row
        html += `<tr class="fw-bold">
      <td class="text-center">Grand Total</td>
      ${departments
            .map((d, i) => {
              const Y7 = totals[d].Y7;
              const Y6 = totals[d].Y6;
              const variation = Y6 ? ((Y7 - Y6) / Y6) * 100 : Y7 ? 100 : 0;
              return `
              <td class="text-end" style="background-color:${colors[i % colors.length]
                }40;">${formatNumber(Y6)}</td>
              <td class="text-end" style="background-color:${colors[i % colors.length]
                }40;">${formatNumber(Y7)}</td>
              <td class="text-end ${variation > 0 ? "text-success" : variation < 0 ? "text-danger" : ""
                }" style="background-color:${colors[i % colors.length]}40;">
                ${!isNaN(variation) ? variation.toFixed(2) + "%" : "-"}
              </td>
            `;
            })
            .join("")}
      <td class="text-end"><b>${formatNumber(
              claims.reduce(
                (sum, c) =>
                  sum + departments.reduce((s, d) => s + (c.values[d]?.Y6 || 0), 0),
                0
              )
            )}</b></td>
      <td class="text-end"><b>${formatNumber(
              claims.reduce(
                (sum, c) =>
                  sum + departments.reduce((s, d) => s + (c.values[d]?.Y7 || 0), 0),
                0
              )
            )}</b></td>
      <td class="text-end"><b>${(() => {
            const totalY7 = claims.reduce(
              (sum, c) =>
                sum + departments.reduce((s, d) => s + (c.values[d]?.Y7 || 0), 0),
              0
            );
            const totalY6 = claims.reduce(
              (sum, c) =>
                sum + departments.reduce((s, d) => s + (c.values[d]?.Y6 || 0), 0),
              0
            );
            return totalY6 !== 0
              ? (((totalY7 - totalY6) / totalY6) * 100).toFixed(2) + "%"
              : totalY7 !== 0
                ? "100%"
                : "-";
          })()}</b></td>
    </tr>`;

        html += `</tbody></table></div>`;
        container.innerHTML = html;
      }
    });
  </script>
@endpush